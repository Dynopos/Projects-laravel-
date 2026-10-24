# Deploy ke Laravel Forge

## 1. Server

- **PHP 8.3 atau lebih baharu** (Laravel 13 tidak berjalan pada PHP 8.2).
- Sambungan PHP: `pgsql`, `gd`, `zip`, `intl`, `mbstring`, `xml` (lalai Forge sudah ada).
- Pangkalan data: **PostgreSQL 16+**.

## 2. Pangkalan data — pengguna BUKAN superuser

RLS (lapisan kedua pengasingan data syarikat) **diabaikan oleh superuser**. Dalam Forge:
**Server → Database → Add user**, cipta pengguna baharu (cth `dyno`) yang memiliki
pangkalan data aplikasi, dan guna pengguna itu dalam `.env` — bukan `forge`/`postgres`.

## 3. Site

- Repository: `Dynopos/Projects-laravel-`, branch `main`.
- Web directory: `/public`.

## 4. Environment (.env)

```dotenv
APP_NAME="Dyno Project OS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dynoprojectos.my
APP_LOCALE=ms

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=dyno
DB_USERNAME=dyno          # bukan superuser — lihat langkah 2
DB_PASSWORD=...

DYNO_DOMAIN=dynoprojectos.my   # syarikat = <subdomain>.dynoprojectos.my
DYNO_SYARIKAT_LALAI=           # KOSONG di produksi
DYNO_CAKERA=local              # logo; tukar ke s3 bila storan objek disediakan

MAIL_MAILER=smtp               # Postmark / SES / Mailgun — perlu untuk e-mel jemputan
MAIL_FROM_ADDRESS="noreply@dynoprojectos.my"
```

`APP_KEY` dijana oleh Forge. **Jangan** commit `.env`.

## 5. Skrip deploy

Repo ada `package.json` kosong supaya skrip lalai Forge (`npm ci && npm run build`) tidak
gagal, tetapi langkah npm tidak diperlukan. Skrip minimum:

```bash
cd $FORGE_SITE_PATH
git pull origin $FORGE_SITE_BRANCH
$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader
$FORGE_PHP artisan migrate --force
$FORGE_PHP artisan optimize
( flock -w 10 9 || exit 1; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock
```

(Jika site guna *zero-downtime deployment*, kekalkan makro `$CREATE_RELEASE()` /
`$ACTIVATE_RELEASE()` yang Forge sediakan dan ganti baris di antaranya sahaja.)

## 6. Domain & SSL

Setiap syarikat ialah subdomain, jadi perlukan:

- DNS: rekod `A` untuk `dynoprojectos.my` **dan** `*.dynoprojectos.my` → IP server.
- Forge: tambah `*.dynoprojectos.my` sebagai alias site.
- SSL wildcard: Let's Encrypt wildcard perlukan pengesahan DNS (Forge sokong melalui API
  penyedia DNS, cth Cloudflare).

Pelanggan dengan domain sendiri (cth `management.syarikat.my`): tambah sebagai alias +
sijil SSL berasingan, dan cipta syarikat dengan `--domain=`.

## 7. Syarikat pertama

Data demo (`db:seed`) **tidak** dijalankan di produksi. Cipta syarikat sebenar melalui
**Site → Commands** (atau SSH):

```bash
php artisan dyno:syarikat-baru "FINE CABINETRY" finecabinetry pemilik@contoh.my --sufiks=".CO" --awalan=FC-
```

Pemilik menerima e-mel jemputan; arahan itu juga memaparkan pautan jemputan (sah 7 hari)
jika e-mel belum disediakan. Selepas pemilik masuk, dia jemput staf dari **Ahli & Jemputan**.

## Bila deploy gagal

Buka **Deployments → (deploy yang gagal) → View output** dan cari baris pertama yang merah:

| Mesej | Punca |
|---|---|
| `Your requirements could not be resolved` … `php >=8.3` | PHP server lebih lama — tukar ke 8.3+ |
| `npm ERR!` / `Missing script: "build"` | Skrip deploy lama; tarik commit terkini atau guna skrip di atas |
| `could not find driver` | Sambungan `pgsql` tiada untuk versi PHP itu |
| `SQLSTATE[08006]` / `password authentication failed` | `DB_*` dalam `.env` salah |
| `Permission denied (publickey)` / `Repository not found` | Forge belum diberi akses ke repo private |
