# Dyno Project OS — versi Laravel

Sistem projek & pembayaran untuk kontraktor dan pemaju pembinaan. Setiap syarikat ada
ruang sendiri; data tidak pernah bercampur.

**Status:** Fasa 1 (asas). Belum sedia untuk pelanggan — sistem HTML sedia ada terus
digunakan sehingga versi ini lulus ujian penerimaan.

## Apa yang sudah ada

- Dashboard: kad **Baki Terkini** besar + 8 kotak (Total/Ended/Running/Pending Project ·
  Total/Deposit/Pending/Received Payment)
- Projek aktif, Projek Siap (arkib automatik), butiran projek
- Rekod bayaran & perbelanjaan; staf rekod bayaran sebagai *Menunggu*, pengurus sahkan
- Empat peranan: Pemilik, Pengurus, Staf, Akauntan
- Log aktiviti yang tidak boleh diubah atau dipadam
- Tema gelap/cerah, menu laci untuk telefon

## Keperluan

PHP 8.3+, Composer, PostgreSQL 16+ (pengguna pangkalan data **bukan** superuser).

## Mula

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Buka http://localhost:8000 dan log masuk dengan `pemilik@demo.test` / `password`
(akaun demo tempatan sahaja; data demo ialah rekaan).

Ujian: `php artisan test`. Lihat `CLAUDE.md` untuk seni bina dan peraturan.
