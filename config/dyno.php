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

    'kategori_lalai' => ['Material', 'Upah pekerja', 'Subkontraktor', 'Pengangkutan', 'Peralatan', 'Lain-lain'],
];
