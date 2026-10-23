<?php

use App\Enums\Peranan;
use App\Import\BacaFail;
use App\Import\HuraiFlow;
use App\Import\HuraiGenerik;
use App\Livewire\ImportExcel;
use App\Models\Bayaran;
use App\Models\LogAktiviti;
use App\Models\Projek;
use App\Support\Kiraan;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\Support\FailContoh;

beforeEach(function () {
    $this->s = syarikatBaru(['kod_awalan' => 'FC-']);
    semasa()->tetapkan($this->s);
    $this->pengurus = ahli($this->s, Peranan::Pengurus);
});

function muatNaik(string $laluan, string $nama = 'fail.xlsx'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($nama, file_get_contents($laluan));
}

it('wang() dan tarikh() mengikut peraturan versi HTML', function () {
    expect(HuraiFlow::wang('RM2,000.00'))->toBe(2000.0)
        ->and(HuraiFlow::wang('1500'))->toBe(1500.0)
        ->and(HuraiFlow::wang('DONE'))->toBe(0.0)
        ->and(HuraiFlow::wang('1/16/26'))->toBe(0.0)        // tarikh dalam lajur wang
        ->and(HuraiFlow::wang('2026-01-16'))->toBe(0.0)
        ->and(HuraiFlow::tarikh('10/08/26'))->toBe('2026-10-08')   // M/D/YY
        ->and(HuraiFlow::tarikh('10/08/26(CC)'))->toBe('2026-10-08')
        ->and(HuraiFlow::tarikh('25/12/26'))->toBe('2026-12-25')   // bulan > 12 → tukar
        ->and(HuraiFlow::tarikh('2026-02-10'))->toBe('2026-02-10')
        ->and(HuraiFlow::tarikh('DONE'))->toBe('');
});

it('menghurai fail PROJECT FLOW contoh dengan tepat', function () {
    $buku = BacaFail::helaian(FailContoh::flow());
    $h = HuraiFlow::hurai($buku['PROJECT FLOW 2026'], 'FC-', 'PROJECT FLOW 2026');

    expect($h->format)->toBe('flow')
        ->and(count($h->projek))->toBe(4)
        ->and(count($h->bayaran))->toBe(5)
        ->and($h->jumlahNilai())->toBe(29000.0)
        ->and($h->jumlahDiterima())->toBe(10000.0)
        ->and(array_column($h->projek, 'kod'))->toBe(['FC-01', 'FC-02', 'FC-03', 'FC-04'])
        ->and(array_column($h->projek, 'status'))->toBe(['Siap', 'Dalam Progres', 'Baru', 'Dalam Progres'])
        ->and($h->invoisBerulang())->toBe(['INV002/2026']);

    [$p1, $p2] = $h->projek;
    expect($p1)->telefon->toBe('012-555 0101')->inv->toBe('INV001/2026')->mula->toBe('2026-01-15')->nota->toBe('Kabinet dapur')
        ->and($p1['aliran'])->toMatchArray(['deposit' => true, 'lukisan' => true, 'gambarSblm' => true, 'pasang' => true,
            'baki' => true, 'gambarSlps' => true, 'maklumBalas' => true, 'socmed' => true])
        ->and($p2['mula'])->toBe('2026-02-10');   // sel tarikh sebenar

    $b = collect($h->bayaran)->groupBy('kod');
    expect($b['FC-01']->pluck('amaun', 'jenis')->all())->toBe(['Bayaran' => 2000.0, 'Baki Akhir' => 3000.0])
        ->and($b['FC-01']->firstWhere('jenis', 'Bayaran')['tarikh'])->toBe('2026-01-16')
        ->and($b['FC-01']->firstWhere('jenis', 'Baki Akhir')['tarikh'])->toBe('2026-03-20')
        ->and($b['FC-02']->pluck('amaun', 'jenis')->all())->toBe(['Deposit' => 3000.0, 'Bayaran Lain' => 1000.0])
        ->and($b['FC-04']->pluck('amaun', 'jenis')->all())->toBe(['Bayaran Diterima' => 1000.0])
        ->and($h->bayaranMasaDepan('2026-10-23'))->toHaveCount(1);
});

it('penghurai generik memadan lajur dan tidak menghurai fail flow sebagai generik', function () {
    $buku = BacaFail::helaian(FailContoh::generik());
    $h = HuraiGenerik::hurai($buku['Senarai'], fn ($n) => 'FC-'.str_pad($n + 5, 2, '0', STR_PAD_LEFT), 'Senarai');

    expect($h->format)->toBe('generik')
        ->and(array_column($h->projek, 'kod'))->toBe(['ABC-1', 'FC-06'])
        ->and(array_column($h->projek, 'nilai'))->toBe([120000.0, 80000.0])
        ->and(array_column($h->projek, 'mula'))->toBe(['2026-03-15', '2026-04-01'])   // D/M/Y Malaysia
        ->and($h->bayaran)->toHaveCount(1)
        ->and($h->peta)->toMatchArray(['kod' => 'Kod', 'nama' => 'Nama Projek', 'nilai' => 'Nilai Kontrak (RM)', 'deposit' => 'Deposit']);

    expect(HuraiFlow::hurai($buku['Senarai'], 'FC-'))->toBeNull();
});

it('import melalui skrin: pratonton, masuk pangkalan data, angka Dashboard sama', function () {
    Livewire::actingAs($this->pengurus)->test(ImportExcel::class)
        ->set('fail', muatNaik(FailContoh::flow()))
        ->assertSee('Format PROJECT FLOW dikenali')->assertSee('RM29,000.00')->assertSee('RM10,000.00')
        ->assertSee('INV002/2026')->assertSee('tarikh bayaran jatuh selepas hari ini')
        ->call('importSemua')->assertSet('selesai', ['projek' => 4, 'bayaran' => 5]);

    $r = Kiraan::ringkasan();
    expect($r['jumlah_nilai'])->toBe(2900000)->and($r['diterima'])->toBe(1000000)
        ->and(Bayaran::where('diimport', true)->count())->toBe(5)
        ->and(LogAktiviti::pluck('tindakan')->all())->toBe(['Import Excel']);   // satu log, bukan 9
});

it('import semula menggantikan, dan amaran menyebut bayaran manual', function () {
    $t = Livewire::actingAs($this->pengurus)->test(ImportExcel::class);
    $t->set('fail', muatNaik(FailContoh::flow()))->call('importSemua');

    Projek::where('kod', 'FC-02')->sole()->bayaran()->create(['jenis' => 'Bayaran Progres', 'amaun' => 500, 'status' => 'Diterima']);
    Projek::where('kod', 'FC-01')->sole()->belanja()->create(['kategori' => 'Material', 'amaun' => 100]);

    $t->set('selesai', null)->set('fail', muatNaik(FailContoh::flow()))
        ->assertSee('4 kod projek sudah wujud')->assertSee('1 bayaran dimasukkan sendiri oleh staf akan hilang')
        ->call('importSemua');

    expect(Bayaran::count())->toBe(5)
        ->and(Projek::count())->toBe(4)
        ->and(Projek::where('kod', 'FC-01')->sole()->belanja()->count())->toBe(1);   // belanja tidak disentuh
});

it('hanya projek baharu mengekalkan projek sedia ada dan bayaran manual', function () {
    $sedia = Projek::factory()->create(['kod' => 'FC-01', 'nama' => 'Projek manual']);
    $sedia->bayaran()->create(['jenis' => 'Deposit', 'amaun' => 777, 'status' => 'Diterima']);

    Livewire::actingAs($this->pengurus)->test(ImportExcel::class)
        ->set('fail', muatNaik(FailContoh::flow()))->call('importBaharu')
        ->assertSet('selesai', ['projek' => 3, 'bayaran' => 3]);

    expect($sedia->fresh()->nama)->toBe('Projek manual')
        ->and((float) $sedia->bayaran()->sole()->amaun)->toBe(777.0);
});

it('import generik menyambung kod dari projek sedia ada, bukan menimpa', function () {
    Projek::factory()->create(['kod' => 'FC-07']);

    Livewire::actingAs($this->pengurus)->test(ImportExcel::class)
        ->set('fail', muatNaik(FailContoh::generik()))->call('importSemua');

    expect(Projek::orderBy('kod')->pluck('kod')->all())->toBe(['ABC-1', 'FC-07', 'FC-08']);
});

it('import tidak menyentuh syarikat lain yang ada kod sama', function () {
    $lain = syarikatBaru(['kod_awalan' => 'FC-']);
    $p = semasa()->sebagai($lain, fn () => Projek::factory()->create(['kod' => 'FC-01', 'nama' => 'Milik syarikat lain']));

    Livewire::actingAs($this->pengurus)->test(ImportExcel::class)->set('fail', muatNaik(FailContoh::flow()))->call('importSemua');

    expect(semasa()->sebagai($lain, fn () => [$p->fresh()->nama, Projek::count()]))->toBe(['Milik syarikat lain', 1]);
});

it('staf dan akauntan tidak boleh import', function (Peranan $peranan) {
    Livewire::actingAs(ahli($this->s, $peranan))->test(ImportExcel::class)->assertForbidden();
})->with([Peranan::Staf, Peranan::Akauntan]);

it('menolak fail bukan Excel', function () {
    Livewire::actingAs($this->pengurus)->test(ImportExcel::class)
        ->set('fail', UploadedFile::fake()->create('virus.exe', 10))->assertHasErrors('fail');
});

/*
 * Ujian regresi WAJIB dengan fail pelanggan sebenar. Fail itu data pelanggan — TIDAK
 * dalam repo. Jalankan dengan:
 *   DYNO_FAIL_PROJECT_FLOW="/laluan/PROJECT LISTING 2026 FCI.xls" php artisan test --filter=sebenar
 */
it('fail sebenar Fine Cabinetry: 10 projek, 16 bayaran, RM184,193 / RM83,861', function () {
    $buku = BacaFail::helaian(getenv('DYNO_FAIL_PROJECT_FLOW'));
    $h = collect($buku)->map(fn ($b, $n) => HuraiFlow::hurai($b, 'FC-', $n))->filter()->first();

    expect(count($h->projek))->toBe(10)
        ->and(count($h->bayaran))->toBe(16)
        ->and($h->jumlahNilai())->toBe(184193.0)
        ->and($h->jumlahDiterima())->toBe(83861.0);
})->skip(fn () => ! is_file((string) getenv('DYNO_FAIL_PROJECT_FLOW')), 'Fail pelanggan tiada (DYNO_FAIL_PROJECT_FLOW)');
