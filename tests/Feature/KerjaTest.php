<?php

use App\Enums\Peranan;
use App\Livewire\ButiranProjek;
use App\Models\Bayaran;
use App\Models\Kerja;
use App\Models\LogAktiviti;
use App\Models\Projek;
use Livewire\Livewire;

beforeEach(function () {
    $this->s = syarikatBaru();
    semasa()->tetapkan($this->s);
    $this->pemilik = ahli($this->s, Peranan::Pemilik);
    // Projek payung: kerja pertama RM400, sudah siap dan dibayar
    $this->p = Projek::factory()->create(['kod' => 'PRJ-01', 'nama' => 'Alumglass En. Ali', 'nilai' => 400, 'status' => 'Siap']);
    $this->p->bayaran()->create(['jenis' => 'Bayaran Akhir', 'amaun' => 400, 'status' => 'Diterima', 'tarikh' => '2026-10-01']);
    $this->p->belanja()->create(['kategori' => 'Material', 'amaun' => 180, 'tarikh' => '2026-09-28']);
});

it('kerja pertama simpan nilai & rekod asal, kerja baharu tambah nilai dan buka semula projek siap', function () {
    Livewire::actingAs($this->pemilik)->test(ButiranProjek::class, ['projek' => $this->p])
        ->assertSee('+ Kerja')
        ->call('bukaRekod', 'kerja')->assertSee('Kerja pertama')
        ->set('k.nama', 'Pasang alumglass bilik air')->set('k.nilai', '250')
        ->call('simpanKerja')->assertHasNoErrors()
        ->assertSee('Kerja dalam projek ini')->assertSee('Alumglass En. Ali')->assertSee('Pasang alumglass bilik air');

    $p = $this->p->fresh();
    $asal = $p->kerja()->where('nama', 'Alumglass En. Ali')->sole();
    expect((float) $p->nilai)->toBe(650.0)
        ->and($p->status)->toBe('Dalam Progres')                       // keluar dari arkib
        ->and($asal->siap)->toBeTrue()->and((float) $asal->nilai)->toBe(400.0)
        ->and($p->bayaran()->sole()->kerja_id)->toBe($asal->id)        // rekod lama ikut kerja asal
        ->and($p->belanja()->sole()->kerja_id)->toBe($asal->id)
        ->and(LogAktiviti::where('tindakan', 'Tambah kerja')->count())->toBe(2);
});

it('bayaran & perbelanjaan masuk ke kerja; untung setiap kerja dipapar', function () {
    $baru = $this->p->tambahKerja(['nama' => 'Pasang alumglass dapur', 'nilai' => 250]);

    $lw = Livewire::actingAs($this->pemilik)->test(ButiranProjek::class, ['projek' => $this->p->fresh()])
        ->call('bukaRekod', 'belanja')->assertSet('bl.kerja_id', $baru->id)   // lalai: kerja terbaru belum siap
        ->set('bl.amaun', '120')->call('simpanBelanja')
        ->call('bukaRekod', 'bayaran')->set('b.amaun', '100')->call('simpanBayaran');

    expect($baru->belanja()->sum('amaun'))->toEqual(120)->and($baru->bayaran()->sum('amaun'))->toEqual(100);
    $this->actingAs($this->pemilik)->get(urlSyarikat($this->s, '/projek/'.$this->p->id))
        ->assertSeeInOrder(['Pasang alumglass dapur', 'RM250.00', 'RM100.00', 'RM120.00', '−RM20.00', 'RM130.00']);
});

it('ubah nilai kerja dan padam kerja: nilai projek dikira semula, rekod tidak hilang', function () {
    $baru = $this->p->tambahKerja(['nama' => 'Kerja B', 'nilai' => 250]);
    $this->p->belanja()->create(['kategori' => 'Material', 'amaun' => 50, 'kerja_id' => $baru->id]);

    Livewire::actingAs($this->pemilik)->test(ButiranProjek::class, ['projek' => $this->p->fresh()])
        ->call('ubahKerja', $baru->id)->set('k.nilai', '300')->call('simpanKerja');
    expect((float) $this->p->fresh()->nilai)->toBe(700.0);

    Livewire::actingAs($this->pemilik)->test(ButiranProjek::class, ['projek' => $this->p->fresh()])
        ->call('padamKerja', $baru->id)->assertSee('Tidak ditetapkan');
    expect((float) $this->p->fresh()->nilai)->toBe(400.0)
        ->and($this->p->belanja()->count())->toBe(2)
        ->and($this->p->belanja()->whereNull('kerja_id')->count())->toBe(1);
});

it('nilai kontrak dikunci dalam borang projek bila ada kerja', function () {
    $this->p->tambahKerja(['nama' => 'Kerja B', 'nilai' => 250]);
    Livewire::actingAs($this->pemilik)->test(ButiranProjek::class, ['projek' => $this->p->fresh()])
        ->call('bukaBorang')->assertSee('Jumlah semua kerja')
        ->set('form.nilai', '99999')->call('simpan');
    expect((float) $this->p->fresh()->nilai)->toBe(650.0);
});

it('staf rekod ke kerja tetapi tidak boleh tambah/ubah kerja; kerja_id projek lain ditolak', function () {
    $staf = ahli($this->s, Peranan::Staf);
    $this->p->tugaskan($staf);
    $k = $this->p->tambahKerja(['nama' => 'Kerja B', 'nilai' => 250]);
    $lain = Projek::factory()->create(['kod' => 'PRJ-02']);
    $kerjaLain = $lain->tambahKerja(['nama' => 'Bukan milik', 'nilai' => 10]);

    Livewire::actingAs($staf)->test(ButiranProjek::class, ['projek' => $this->p->fresh()])
        ->assertDontSee('+ Kerja')
        ->call('bukaRekod', 'kerja')->assertForbidden();
    Livewire::actingAs($staf)->test(ButiranProjek::class, ['projek' => $this->p->fresh()])
        ->call('togolSiap', $k->id)->assertForbidden();

    Livewire::actingAs($staf)->test(ButiranProjek::class, ['projek' => $this->p->fresh()])
        ->call('bukaRekod', 'bayaran')->set('b.amaun', '50')->set('b.kerja_id', $kerjaLain->id)->call('simpanBayaran');
    expect(Bayaran::latest('id')->first())->kerja_id->toBeNull()->status->toBe('Menunggu');
});

it('kerja diasingkan antara syarikat', function () {
    $this->p->tambahKerja(['nama' => 'Kerja B', 'nilai' => 250]);
    semasa()->tetapkan(syarikatBaru());
    expect(Kerja::count())->toBe(0);
});
