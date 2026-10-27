<?php

return [
    // Domain utama produk. Syarikat dipilih dari subdomain: binaanmaju.<domain_utama>.
    // Pelanggan juga boleh guna domain sendiri (medan `domain` dalam jadual syarikat).
    'domain_utama' => env('DYNO_DOMAIN', 'dynoprojectos.test'),

    // Untuk pembangunan tempatan sahaja (localhost tiada subdomain).
    // Kosongkan dalam produksi.
    'syarikat_lalai' => env('DYNO_SYARIKAT_LALAI'),

    // Hubungi kami — butang "Minta demo" di landing page. Kosong = butang menatal ke "Cara mula".
    'hubungi' => [
        'whatsapp' => env('DYNO_WHATSAPP'),   // cth 60123456789 (tanpa + atau sengkang)
        'emel' => env('DYNO_EMEL'),
    ],

    // Harga langganan dalam sen (CHIP). Daftar = persediaan + tahun pertama; pembaharuan = setiap tahun.
    'harga' => [
        'daftar' => (int) env('DYNO_HARGA_DAFTAR', 9900),
        'pembaharuan' => (int) env('DYNO_HARGA_PEMBAHARUAN', 3000),
    ],

    // Hari selepas aktif_hingga sebelum syarikat jadi baca sahaja.
    'tempoh_tangguh' => 14,

    // Pengendali perkhidmatan — dipapar dalam Terma & Notis Privasi.
    'pengendali' => [
        'nama' => env('DYNO_PENGENDALI_NAMA', 'Dyno Project OS'),
        'ssm' => env('DYNO_PENGENDALI_SSM'),
        'alamat' => env('DYNO_PENGENDALI_ALAMAT'),
        'emel' => env('DYNO_EMEL_PRIVASI', env('DYNO_EMEL')),
        'hosting' => env('DYNO_HOSTING', 'penyedia awan kami'),
        'lokasi_server' => env('DYNO_LOKASI_SERVER', 'Singapura'),
    ],

    // Cakera untuk fail syarikat (logo; kelak resit). Produksi: s3 (R2/Spaces), peribadi.
    'cakera' => env('DYNO_CAKERA', 'local'),

    // Peringkat aliran & kategori perbelanjaan untuk syarikat baharu.
    // Selepas dicipta, setiap syarikat ubah sendiri (jadual peringkat_aliran / kategori_belanja).
    'peringkat_lalai' => [
        'sebutHarga' => 'Sebut harga',
        'deposit' => 'Deposit',
        'lukisan' => 'Lukisan / kelulusan',
        'bahan' => 'Bahan dipesan',
        'tapak' => 'Kerja tapak',
        'pemeriksaan' => 'Pemeriksaan',
        'serahan' => 'Serahan',
        'baki' => 'Bayaran akhir',
    ],

    // Kategori perbelanjaan umum (overhead) — senarai cadangan; pengguna boleh taip kategori lain.
    'kategori_umum' => ['Sewa', 'Gaji pejabat', 'Utiliti', 'Kenderaan & minyak', 'Pemasaran', 'Peralatan pejabat', 'Yuran & lesen', 'Lain-lain'],

    'kategori_lalai' => ['Material', 'Upah pekerja', 'Subkontraktor', 'Pengangkutan', 'Peralatan', 'Lain-lain'],
];
