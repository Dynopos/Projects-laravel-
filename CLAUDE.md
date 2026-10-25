# CLAUDE.md — Konteks Projek

Baca dahulu sebelum ubah apa-apa.

## Apa ini

**Dyno Project OS** versi Laravel — sistem projek & pembayaran untuk kontraktor dan
pemaju, dijual sebagai SaaS multi-syarikat. Setiap pelanggan log masuk ke ruang
syarikat sendiri (`<subdomain>.dynoprojectos.my` atau domain sendiri).

Versi HTML + Supabase (repo `Dynopos/ProjectOs`, dan `FCmanagement` untuk Fine
Cabinetry) kekal sebagai **rujukan reka bentuk dan peraturan kiraan**. Rancangan penuh:
dokumen "Dyno Project OS — Rancangan Laravel".

**Stack:** Laravel 13 · PHP 8.3+ · Livewire 4 + Blade · PostgreSQL 16 · Pest.
**Tiada build step / npm.** CSS dalam `public/css/dyno.css` (dipindah dari versi HTML).
`package.json` hanya ada skrip `build` kosong supaya skrip deploy lalai Forge tidak gagal —
jangan tambah kebergantungan. Deploy: `DEPLOY.md`. Syarikat produksi: `php artisan dyno:syarikat-baru`.

**Bahasa: Bahasa Melayu.** Teks UI, nama jadual, medan, kelas dan kaedah domain dalam
BM (`projek`, `bayaran`, `belanja`, `Kiraan::ringkasan()`). Kekalkan konvensyen ini.
Nama kotak Dashboard (Total Payment, Ended Project…) kekal Inggeris seperti versi asal.

## Pengasingan data — PALING PENTING

Risiko #1 produk ini ialah data satu syarikat bocor ke syarikat lain. Tiga lapisan:

1. **Scope Eloquent** — setiap model dengan `syarikat_id` guna trait
   `App\Models\Concerns\MilikSyarikat`. Ia menapis ke `SyarikatSemasa`, mengisi
   `syarikat_id` semasa cipta, dan menolak cipta/tukar ke syarikat lain.
2. **PostgreSQL RLS** — migrasi `rls_postgres`. Polisi membaca `app.syarikat_id`
   yang ditetapkan oleh `SyarikatSemasa::tetapkan()` (satu-satunya tempat).
3. **Ujian** — `tests/Feature/PengasinganTest.php`.

Peraturan:

- **Gagal tertutup.** Tiada syarikat semasa = tiada baris (scope `1 = 0`, RLS sama).
- Merentas syarikat (panel pentadbir, seeder, arahan konsol) **hanya** melalui
  `app(SyarikatSemasa::class)->tanpaSkop(fn () => …)`. Jangan sesekali dari laluan pengguna.
- Model baharu yang ada `syarikat_id` **mesti** guna `MilikSyarikat` **dan** ditambah ke
  senarai jadual dalam migrasi RLS **dan** diuji dalam `PengasinganTest`.
- `keahlian` & `jemputan` sengaja tiada scope/RLS (dibaca sebelum syarikat diketahui).
- FK komposit `(syarikat_id, projek_id) → projek(syarikat_id, id)` pada `bayaran`,
  `belanja`, `penugasan` — rekod anak tak boleh menunjuk ke projek syarikat lain.
- Pengguna DB aplikasi **tidak boleh superuser** (superuser memintas RLS). Jadual guna
  `FORCE ROW LEVEL SECURITY` kerana pengguna aplikasi juga pemilik jadual.
- `TentukanSyarikat` mesti berjalan **sebelum** `SubstituteBindings` (lihat
  `bootstrap/app.php`). Jika tidak, `{projek}` dicari tanpa syarikat → setiap halaman 404.
- Permintaan Livewire melalui laluan kemas kini tersuai dengan `TentukanSyarikat`
  (`AppServiceProvider`) — jangan buang.

## Model data

`syarikat` (menggantikan config.js) · `users` · `keahlian` (syarikat_id, user_id, peranan) ·
`jemputan` · `peringkat_aliran` (menggantikan TETAPAN.PERINGKAT) · `kategori_belanja` ·
`projek` · `penugasan` (staf ↔ projek) · `bayaran` · `belanja` · `log_aktiviti`

Beza utama dari versi Supabase: **bayaran & belanja diikat dengan `projek_id`**, bukan
teks `kod`. Kod projek hanya label (unik dalam syarikat).

Amaun `decimal(14,2)`. Dalam PHP, kira dalam **sen (integer)** melalui `Kiraan::sen()`.

## Peraturan kiraan — `App\Support\Kiraan` (JANGAN ubah tanpa sebab kukuh)

```
diterima(projek) = Σ bayaran WHERE status != 'Menunggu'
baki             = nilai − diterima
Total Payment    = Σ nilai
Total Received   = Σ diterima
Pending Payment  = Total Payment − Total Received
Deposit Payment  = Σ bayaran WHERE jenis ~ /deposit|pendahuluan|advance/i AND status != 'Menunggu'
Baki terkini     = Σ diterima − Σ belanja
```

Invarian: **Total Payment = Total Received + Pending Payment.**
`jenisStatus()` — regex BM & Inggeris yang sama tepat dengan versi HTML
(`siap` / `jalan` / `pending`). Projek `siap` keluar dari senarai utama ke Projek Siap.

Ujian regresi: data demo (`database/data/demo.json`, sama dengan `data.json` ProjectOs)
mesti beri Baki Terkini **RM227,400**, duit masuk RM1,269,100, belanja RM1,041,700.

## Peranan — `App\Enums\Peranan`, dikuatkuasa oleh Policy di pelayan

| | Pemilik | Pengurus | Staf | Akauntan |
|---|---|---|---|---|
| Lihat projek | semua | semua | ditugaskan sahaja | semua |
| Cipta / ubah / padam projek | ✓ | ✓ | ✗ | ✗ |
| Rekod bayaran | ✓ | ✓ | ✓ (dipaksa **Menunggu**) | ✗ |
| Sahkan bayaran | ✓ | ✓ | ✗ | ✗ |
| Rekod perbelanjaan | ✓ | ✓ | ✓ | ✗ |
| Log aktiviti | ✓ | ✓ | ✗ | ✓ |

Jangan bergantung pada menyembunyikan butang — setiap tindakan Livewire panggil
`$this->authorize()`. Syarikat berstatus `baca_sahaja`/`digantung` tak boleh menulis.

## Jemputan

Satu-satunya cara akaun baharu wujud. `UrusAhli` (Livewire) hantar `JemputanMail`;
`JemputanController` terima di `/jemputan/{token}`.

- Token asal hanya dalam e-mel; jadual simpan **hash SHA-256**. Sah 7 hari, sekali guna,
  hantar semula membatalkan pautan lama.
- `Jemputan` guna `MilikSyarikat`: token syarikat A tak berfungsi pada subdomain B.
- E-mel baharu → tetapkan nama + kata laluan. Akaun sedia ada (ahli syarikat lain) →
  sahkan dengan kata laluan sedia ada, bukan tetapkan semula.
- `Peranan::bolehUrus()`: pemilik urus semua; pengurus jemput & urus staf sahaja.
  Tiada sesiapa boleh ubah keahlian sendiri (elak pemilik terkunci di luar).
- Produksi perlukan `MAIL_*` sebenar (Postmark/SES/Mailgun). Tempatan: `MAIL_MAILER=log`,
  pautan ada dalam `storage/logs/laravel.log`.

## Tetapan syarikat

`TetapanSyarikat` (Livewire) — **pemilik sahaja** (`Peranan::bolehTetapan()`).

- Logo: dilukis semula oleh GD (`App\Support\Logo`) ke PNG ≤256×256; fail asal tidak
  disimpan, SVG ditolak. Disimpan pada cakera `config('dyno.cakera')` dan dihidang oleh
  `/logo` (tanpa log masuk — skrin log masuk perlukannya). URL ada `?v=` untuk cache.
- Peringkat: `kunci` dijana sekali dari label dan **tidak pernah berubah** (disimpan dalam
  `projek.aliran`). Padam peringkat tidak memadam tanda dalam projek.
- Kategori: tukar nama turut `update belanja set kategori` (dalam transaksi, ikut scope
  syarikat). Kategori yang masih digunakan tidak boleh dipadam.

## Import Excel — `app/Import/`

`BacaFail` (PhpSpreadsheet) → `HuraiFlow::hurai()` dicuba dahulu → `HuraiGenerik::hurai()`
untuk helaian dipilih → `SimpanImport::simpan()`. Halaman: `ImportExcel` (pemilik & pengurus).

- `HuraiFlow` = port baris-demi-baris `huraiFlow()`/`wangFC()`/`tkhFC()` versi HTML.
  Peraturan sama: lajur "Total Payment By Client" muktamad, nyahduplikasi ikut RM terdekat,
  teks tarikh M/D/YY, sel tarikh dalam lajur wang ditolak, kod `{awalan}01…NN`.
- Beza dari HTML: sel **tarikh sebenar** dibaca terus sebagai Y-m-d (bukan teks yang diteka),
  dan `wang()` juga menolak Y-m-d supaya tarikh tak jadi amaun.
- `SimpanImport` dalam **satu transaksi**. Projek dipadan ikut kod: wujud = diganti berserta
  semua bayarannya (pratonton mengira bayaran manual, `diimport = false`, yang akan hilang);
  "Hanya projek baharu" melangkau yang wujud. Belanja tidak disentuh. Satu log ringkasan
  (`CatatAktiviti::senyap()`), bukan satu log setiap rekod.
- Generik: baris tanpa kod disambung dari `Projek::kodSeterusnya()` — tak menimpa projek sedia ada.

### Ujian regresi — WAJIB bila `HuraiFlow` diubah

`tests/Support/FailContoh.php` membina fail PROJECT FLOW **rekaan** (4 projek · 5 bayaran ·
RM29,000 · RM10,000). Fail sebenar pelanggan **tidak boleh** masuk repo; jalankan:

```bash
DYNO_FAIL_PROJECT_FLOW="/laluan/PROJECT LISTING 2026 FCI.xls" php artisan test --filter=sebenar
# jangkaan: 10 projek · 16 bayaran · RM184,193.00 · RM83,861.00
```

## Senarai merentas projek (Pembayaran, Perbelanjaan)

- Tapis dengan `Projek::bolehDilihat($user)` — staf hanya projek yang ditugaskan.
- Rekod bayaran **sentiasa** melalui `Projek::rekodBayaran()` (staf dipaksa Menunggu).
- Bila eager-load projek dengan lajur terhad, **sertakan `syarikat_id`** — `ProjekPolicy`
  menyemaknya; tanpanya semua `@can` jadi false dan butang Sahkan/Padam hilang senyap.

## Eksport — `App\Support\Laporan` + `EksportController`

`/eksport/{projek|arkib|bayaran|belanja}.{csv|pdf}` dengan tapisan query string yang sama
dengan halaman. Pemilik, pengurus, akauntan (`Peranan::bolehEksport()`); setiap eksport dilog.

- CSV: BOM UTF-8, amaun nombor biasa `1234.50` (Excel boleh jumlah), sel bermula
  `= + - @` diberi `'` (suntikan formula).
- PDF: DomPDF, A4 landskap, cetakan putih, `isFontSubsettingEnabled` (~30 KB).
- Susunan tarikh: `orderByRaw('tarikh is null, tarikh desc')` — PostgreSQL & SQLite
  meletakkan NULL berbeza untuk DESC.

## Log aktiviti

Tambah & baca sahaja. `CatatAktiviti` (observer) merekod setiap perubahan projek,
bayaran, belanja. Model menolak update/delete; dalam PostgreSQL RLS tiada polisi update
dan trigger `kunci_log` menolak update (walaupun superuser) dan delete (kecuali mod pintas).

## Menjalankan

```bash
composer install
cp .env.example .env && php artisan key:generate
# PostgreSQL: cipta pengguna BUKAN superuser + pangkalan data dyno
php artisan migrate --seed
php artisan serve          # http://localhost:8000 → syarikat DYNO_SYARIKAT_LALAI (demo)
```

Akaun demo (tempatan sahaja, kata laluan `password`): `pemilik@demo.test`,
`pengurus@demo.test`, `staf@demo.test`, `akauntan@demo.test`.

## Ujian

```bash
php artisan test                                                     # SQLite, pantas
DB_CONNECTION=pgsql DB_DATABASE=dyno_ujian DB_USERNAME=dyno DB_PASSWORD=dyno php artisan test   # + RLS
./vendor/bin/pint                                                    # gaya kod
```

Kedua-dua mesti lulus sebelum push. Ujian RLS/trigger dilangkau dalam SQLite.

## Perkara mudah tersalah

- **Jangan sesekali** commit `.env`, kunci API, atau **data pelanggan sebenar**
  (fail Excel Fine Cabinetry dsb.). Ujian import dengan fail sebenar membaca laluan dari
  pemboleh ubah persekitaran, bukan dari repo.
- `DYNO_SYARIKAT_LALAI` hanya berfungsi dalam `local`/`testing`. Produksi: hos tak dikenali = 404.
- Dalam produksi guna php-fpm biasa. Jika Octane digunakan kelak, `SyarikatSemasa` ialah
  `scoped` dan middleware menetapkan semula `app.syarikat_id` — sahkan semula dengan ujian.

## Fasa

- [x] **Fasa 1 — asas:** multi-tenant 3 lapisan, peranan, kiraan, Dashboard 8 kotak +
  Baki Terkini, projek/arkib, butiran projek dengan bayaran & perbelanjaan, log aktiviti, 40 ujian
- [x] Jemputan staf melalui e-mel, urus ahli (halaman Ahli & Jemputan)
- [x] Tetapan syarikat: maklumat, awalan kod, zon waktu, logo, peringkat kerja, kategori perbelanjaan
- [x] Eksport CSV & PDF — projek, projek siap, pembayaran, perbelanjaan (ikut tapisan skrin)
- [ ] Gambar resit (ditangguhkan atas permintaan pemilik)
- [x] Tab Pembayaran — semua bayaran, kotak Diterima/Deposit/Menunggu (sama dengan Dashboard), sahkan & padam
- [x] Tab Perbelanjaan — semua projek, tapis projek/kategori, ringkasan kategori, rekod & padam
- [x] Import Excel — PROJECT FLOW (`HuraiFlow`) + generik (`HuraiGenerik`), pratonton & amaran
- [ ] Panel pentadbir (Filament), langganan + payment gateway
- [ ] PWA, masa nyata (Reverb)
- [ ] Migrasi Fine Cabinetry
- [ ] Invois PDF, notifikasi WhatsApp, pembantu bil AI
