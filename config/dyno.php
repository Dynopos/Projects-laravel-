<?php

return [
    // Domain utama produk. Syarikat dipilih dari subdomain: binaanmaju.<domain_utama>.
    // Pelanggan juga boleh guna domain sendiri (medan `domain` dalam jadual syarikat).
    'domain_utama' => env('DYNO_DOMAIN', 'dynoprojectos.test'),

    // Untuk pembangunan tempatan sahaja (localhost tiada subdomain).
    // Kosongkan dalam produksi.
    'syarikat_lalai' => env('DYNO_SYARIKAT_LALAI'),

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
