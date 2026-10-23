<?php

use App\Enums\Peranan;
use App\Livewire\TetapanSyarikat;
use App\Models\Belanja;
use App\Models\KategoriBelanja;
use App\Models\PeringkatAliran;
use App\Models\Projek;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    $this->s = syarikatBaru(['nama' => 'BINAAN UJIAN']);
    semasa()->tetapkan($this->s);
    $this->pemilik = ahli($this->s, Peranan::Pemilik);
});

it('hanya pemilik boleh buka tetapan', function (Peranan $p) {
    Livewire::actingAs(ahli($this->s, $p))->test(TetapanSyarikat::class)->assertForbidden();
})->with([Peranan::Pengurus, Peranan::Staf, Peranan::Akauntan]);

it('pemilik ubah maklumat dan awalan kod digunakan untuk projek baharu', function () {
    Livewire::actingAs($this->pemilik)->test(TetapanSyarikat::class)
        ->set('maklumat.nama', 'FINE CABINETRY')->set('maklumat.sufiks', '.CO')->set('maklumat.kod_awalan', 'FC-')
        ->call('simpanMaklumat')->assertHasNoErrors();

    expect($this->s->fresh())->nama->toBe('FINE CABINETRY')->kod_awalan->toBe('FC-');
    semasa()->tetapkan($this->s->fresh());
    expect(Projek::kodSeterusnya())->toBe('FC-01');
});

it('menolak awalan kod yang tidak sah', function (string $awalan) {
    Livewire::actingAs($this->pemilik)->test(TetapanSyarikat::class)
        ->set('maklumat.kod_awalan', $awalan)->call('simpanMaklumat')->assertHasErrors('maklumat.kod_awalan');
})->with(['fc-', 'FC 01', 'FC--', '<b>']);

it('logo dilukis semula ke PNG 256 dan dihidang pada /logo', function () {
    Livewire::actingAs($this->pemilik)->test(TetapanSyarikat::class)
        ->set('logo', UploadedFile::fake()->image('logo.jpg', 800, 400))
        ->call('simpanLogo')->assertHasNoErrors();

    $laluan = $this->s->fresh()->logo_path;
    expect($laluan)->toStartWith("syarikat/{$this->s->id}/")->toEndWith('.png');
    [$w, $h, $jenis] = getimagesizefromstring(Storage::disk('local')->get($laluan));
    expect([$w, $h, $jenis])->toBe([256, 128, IMAGETYPE_PNG]);

    $this->get(urlSyarikat($this->s, '/logo'))->assertOk()->assertHeader('Content-Type', 'image/png');
});

it('menolak fail bukan imej dan SVG sebagai logo', function (string $nama, string $mime) {
    Livewire::actingAs($this->pemilik)->test(TetapanSyarikat::class)
        ->set('logo', UploadedFile::fake()->create($nama, 10, $mime))
        ->call('simpanLogo')->assertHasErrors('logo');
    expect($this->s->fresh()->logo_path)->toBeNull();
})->with([['logo.svg', 'image/svg+xml'], ['skrip.php', 'text/x-php']]);

it('logo lama dipadam bila ditukar atau dibuang', function () {
    $t = Livewire::actingAs($this->pemilik)->test(TetapanSyarikat::class);
    $t->set('logo', UploadedFile::fake()->image('a.png', 100, 100))->call('simpanLogo');
    $lama = $this->s->fresh()->logo_path;
    $t->set('logo', UploadedFile::fake()->image('b.png', 100, 100))->call('simpanLogo');
    Storage::disk('local')->assertMissing($lama);

    $t->call('buangLogo');
    expect($this->s->fresh()->logo_path)->toBeNull();
    $this->get(urlSyarikat($this->s, '/logo'))->assertNotFound();
});

it('tambah peringkat menjana kunci unik dan tukar label mengekalkan kunci', function () {
    $t = Livewire::actingAs($this->pemilik)->test(TetapanSyarikat::class)
        ->set('peringkatBaru', 'Pemasangan kabinet')->call('tambahPeringkat')
        ->set('peringkatBaru', 'Pemasangan kabinet')->call('tambahPeringkat');

    expect(PeringkatAliran::where('kunci', 'like', 'pemasanganKabinet%')->pluck('kunci')->sort()->values()->all())
        ->toBe(['pemasanganKabinet', 'pemasanganKabinet2']);

    $p = PeringkatAliran::where('kunci', 'pemasanganKabinet')->sole();
    $t->set("labelPeringkat.{$p->id}", 'Pasang')->call('simpanPeringkat', $p->id);
    expect($p->fresh())->label->toBe('Pasang')->kunci->toBe('pemasanganKabinet');
});

it('susun semula peringkat', function () {
    $senarai = PeringkatAliran::orderBy('susunan')->pluck('kunci')->all();
    $kedua = PeringkatAliran::where('kunci', $senarai[1])->sole();

    Livewire::actingAs($this->pemilik)->test(TetapanSyarikat::class)->call('alihPeringkat', $kedua->id, -1);

    expect(PeringkatAliran::orderBy('susunan')->pluck('kunci')->take(2)->all())->toBe([$senarai[1], $senarai[0]]);
});

it('tukar nama kategori turut mengemas kini rekod perbelanjaan', function () {
    $p = Projek::factory()->create();
    $p->belanja()->create(['kategori' => 'Material', 'amaun' => 100]);
    $k = KategoriBelanja::where('nama', 'Material')->sole();

    Livewire::actingAs($this->pemilik)->test(TetapanSyarikat::class)
        ->set("namaKategori.{$k->id}", 'Bahan binaan')->call('simpanKategori', $k->id)->assertHasNoErrors();

    expect(Belanja::sole()->kategori)->toBe('Bahan binaan')->and($k->fresh()->nama)->toBe('Bahan binaan');
});

it('tukar nama kategori tidak menyentuh perbelanjaan syarikat lain', function () {
    $lain = syarikatBaru();
    $bLain = semasa()->sebagai($lain, fn () => Projek::factory()->create()->belanja()->create(['kategori' => 'Material', 'amaun' => 1]));
    $k = KategoriBelanja::where('nama', 'Material')->sole();

    Livewire::actingAs($this->pemilik)->test(TetapanSyarikat::class)
        ->set("namaKategori.{$k->id}", 'Bahan')->call('simpanKategori', $k->id);

    expect(semasa()->sebagai($lain, fn () => $bLain->fresh()->kategori))->toBe('Material');
});

it('kategori yang digunakan tidak boleh dipadam', function () {
    Projek::factory()->create()->belanja()->create(['kategori' => 'Material', 'amaun' => 1]);
    $k = KategoriBelanja::where('nama', 'Material')->sole();

    Livewire::actingAs($this->pemilik)->test(TetapanSyarikat::class)->call('padamKategori', $k->id)->assertHasErrors("namaKategori.{$k->id}");
    expect($k->fresh())->not->toBeNull();
});

it('menolak kategori pendua', function () {
    Livewire::actingAs($this->pemilik)->test(TetapanSyarikat::class)
        ->set('kategoriBaru', 'Material')->call('tambahKategori')->assertHasErrors('kategoriBaru');
});

it('syarikat baca sahaja tidak boleh ubah tetapan', function () {
    $this->s->update(['status' => 'baca_sahaja']);
    Livewire::actingAs($this->pemilik)->test(TetapanSyarikat::class)->assertForbidden();
});
