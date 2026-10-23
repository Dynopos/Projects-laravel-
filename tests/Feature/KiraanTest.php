<?php

use App\Enums\Peranan;
use App\Models\Bayaran;
use App\Models\Projek;
use App\Models\Syarikat;
use App\Support\Kiraan;
use Database\Seeders\DatabaseSeeder;

it('mengkategorikan status dalam BM dan Inggeris', function (string $s, string $jangka) {
    expect(Kiraan::jenisStatus($s))->toBe($jangka);
})->with([
    ['Siap', 'siap'], ['SELESAI', 'siap'], ['Completed', 'siap'], ['Handover', 'siap'],
    ['Dalam progres', 'jalan'], ['Ongoing', 'jalan'], ['Aktif', 'jalan'], ['WIP', 'jalan'],
    ['Baru', 'pending'], ['Tertunda', 'pending'], ['', 'pending'],
]);

it('memformat ringgit dengan tanda tolak tipografi', function () {
    expect(Kiraan::rm(22740000))->toBe('RM227,400.00')
        ->and(Kiraan::rm(-150))->toBe('−RM1.50')
        ->and(Kiraan::sen('0.1') + Kiraan::sen('0.2'))->toBe(30);
});

it('memadan angka data demo versi HTML tepat', function () {
    $this->seed(DatabaseSeeder::class);
    semasa()->tetapkan(Syarikat::where('subdomain', 'demo')->first());

    $r = Kiraan::ringkasan();

    expect($r['projek'])->toBe(8)
        ->and($r['diterima'])->toBe(126910000)       // RM1,269,100
        ->and($r['belanja'])->toBe(104170000)        // RM1,041,700
        ->and($r['baki_terkini'])->toBe(22740000)    // RM227,400
        ->and($r['jumlah_nilai'])->toBe($r['diterima'] + $r['tertunggak']);
});

it('tidak mengira bayaran Menunggu sehingga disahkan', function () {
    $s = syarikatBaru();
    semasa()->tetapkan($s);
    $p = Projek::factory()->create(['nilai' => 10000]);
    $p->bayaran()->create(['jenis' => 'Deposit', 'amaun' => 3000, 'status' => Bayaran::DITERIMA]);
    $b = $p->bayaran()->create(['jenis' => 'Bayaran Progres', 'amaun' => 2000, 'status' => Bayaran::MENUNGGU]);
    $p->belanja()->create(['kategori' => 'Material', 'amaun' => 500]);

    $r = Kiraan::ringkasan();
    expect($r['diterima'])->toBe(300000)
        ->and($r['tertunggak'])->toBe(700000)
        ->and($r['baki_terkini'])->toBe(250000)
        ->and($r['bayaran_menunggu'])->toBe(1);

    $b->sahkan(ahli($s, Peranan::Pengurus));
    expect(Kiraan::ringkasan()['diterima'])->toBe(500000);
});

it('mengira deposit ikut corak jenis dan kecualikan Menunggu', function () {
    semasa()->tetapkan(syarikatBaru());
    $p = Projek::factory()->create();
    $p->bayaran()->createMany([
        ['jenis' => 'Deposit 30%', 'amaun' => 100, 'status' => 'Diterima'],
        ['jenis' => 'Wang pendahuluan', 'amaun' => 50, 'status' => 'Diterima'],
        ['jenis' => 'ADVANCE', 'amaun' => 25, 'status' => 'Diterima'],
        ['jenis' => 'Deposit', 'amaun' => 999, 'status' => 'Menunggu'],
        ['jenis' => 'Bayaran Akhir', 'amaun' => 400, 'status' => 'Diterima'],
    ]);

    expect(Kiraan::ringkasan()['deposit'])->toBe(17500);
});

it('menjana kod projek seterusnya ikut awalan syarikat', function () {
    semasa()->tetapkan(syarikatBaru(['kod_awalan' => 'FC-']));
    expect(Projek::kodSeterusnya())->toBe('FC-01');
    Projek::factory()->create(['kod' => 'FC-09']);
    expect(Projek::kodSeterusnya())->toBe('FC-10');
});
