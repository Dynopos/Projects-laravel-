<?php

use App\Enums\Peranan;
use App\Livewire\SenaraiBelanja;
use App\Models\Belanja;
use App\Models\Projek;
use Livewire\Livewire;

beforeEach(function () {
    $this->s = syarikatBaru();
    semasa()->tetapkan($this->s);
    $this->a = Projek::factory()->create(['kod' => 'PRJ-01', 'nama' => 'Rumah A']);
    $this->b = Projek::factory()->create(['kod' => 'PRJ-02', 'nama' => 'Rumah B']);
    $this->a->bayaran()->create(['jenis' => 'Deposit', 'amaun' => 10000, 'status' => 'Diterima']);
    $this->a->belanja()->create(['kategori' => 'Material', 'amaun' => 3000, 'penerima' => 'KEDAI A']);
    $this->b->belanja()->create(['kategori' => 'Upah pekerja', 'amaun' => 1000, 'penerima' => 'PEKERJA B']);
    $this->pemilik = ahli($this->s, Peranan::Pemilik);
});

it('memapar semua perbelanjaan, ringkasan kategori dan Baki Terkini', function () {
    Livewire::actingAs($this->pemilik)->test(SenaraiBelanja::class)
        ->assertSee('KEDAI A')->assertSee('PEKERJA B')
        ->assertSee('RM4,000.00')        // jumlah belanja
        ->assertSee('RM6,000.00');       // baki terkini = 10,000 − 4,000
});

it('tapisan projek dan kategori', function () {
    Livewire::actingAs($this->pemilik)->test(SenaraiBelanja::class)
        ->set('projek', (string) $this->b->id)->assertSee('PEKERJA B')->assertDontSee('KEDAI A')
        ->set('projek', '')->set('kategori', 'Material')->assertSee('KEDAI A')->assertDontSee('PEKERJA B');
});

it('staf hanya nampak perbelanjaan projek yang ditugaskan', function () {
    $staf = ahli($this->s, Peranan::Staf);
    $this->b->tugaskan($staf);

    Livewire::actingAs($staf)->test(SenaraiBelanja::class)->assertSee('PEKERJA B')->assertDontSee('KEDAI A');
});

it('rekod perbelanjaan; nama perekod diisi oleh pelayan', function () {
    $staf = ahli($this->s, Peranan::Staf);
    $this->a->tugaskan($staf);

    Livewire::actingAs($staf)->test(SenaraiBelanja::class)->call('bukaBorang')
        ->set('bl.projek_id', $this->a->id)->set('bl.kategori', 'Peralatan')->set('bl.amaun', '250')
        ->call('simpan')->assertHasNoErrors();

    $x = Belanja::where('kategori', 'Peralatan')->sole();
    expect($x->projek_id)->toBe($this->a->id)->and($x->dicipta_oleh)->toBe($staf->id);
});

it('staf tidak boleh merekod ke projek yang tidak ditugaskan', function () {
    $staf = ahli($this->s, Peranan::Staf);

    Livewire::actingAs($staf)->test(SenaraiBelanja::class)->call('bukaBorang')
        ->set('bl.projek_id', $this->a->id)->set('bl.amaun', '1')->call('simpan')->assertForbidden();
});

it('tidak boleh merekod ke projek syarikat lain', function () {
    $lain = syarikatBaru();
    $pLain = semasa()->sebagai($lain, fn () => Projek::factory()->create());

    Livewire::actingAs($this->pemilik)->test(SenaraiBelanja::class)->call('bukaBorang')
        ->set('bl.projek_id', $pLain->id)->set('bl.amaun', '1')->call('simpan')->assertNotFound();
    expect(semasa()->sebagai($lain, fn () => Belanja::count()))->toBe(0);
});

it('akauntan boleh lihat tetapi tidak boleh rekod atau padam', function () {
    $akaun = ahli($this->s, Peranan::Akauntan);
    $x = $this->a->belanja()->first();

    Livewire::actingAs($akaun)->test(SenaraiBelanja::class)->assertSee('KEDAI A')->assertDontSee('+ Perbelanjaan')
        ->call('padam', $x->id)->assertForbidden();
    expect($x->fresh())->not->toBeNull();
});

it('pemilik boleh padam', function () {
    $x = $this->a->belanja()->first();
    Livewire::actingAs($this->pemilik)->test(SenaraiBelanja::class)->call('padam', $x->id);
    expect($x->fresh())->toBeNull();
});

it('menu Perbelanjaan ada di sidebar dan kad Baki Terkini dashboard memautnya', function () {
    $this->actingAs($this->pemilik)->get(urlSyarikat($this->s))
        ->assertSee('Perbelanjaan')->assertSee(urlSyarikat($this->s, '/perbelanjaan'), false);
});
