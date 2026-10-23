<?php

use App\Enums\Peranan;
use App\Livewire\ButiranProjek;
use App\Livewire\Dashboard;
use App\Livewire\SenaraiProjek;
use App\Models\Bayaran;
use App\Models\Belanja;
use App\Models\Projek;
use Livewire\Livewire;

beforeEach(function () {
    $this->s = syarikatBaru();
    semasa()->tetapkan($this->s);
    $this->projek = Projek::factory()->create(['kod' => 'PRJ-01', 'nilai' => 10000]);
    $this->lain = Projek::factory()->create(['kod' => 'PRJ-02']);
});

it('staf merekod bayaran sebagai Menunggu walaupun borang hantar Diterima', function () {
    $staf = ahli($this->s, Peranan::Staf);
    $this->projek->tugaskan($staf);

    Livewire::actingAs($staf)->test(ButiranProjek::class, ['projek' => $this->projek])
        ->call('bukaRekod', 'bayaran')
        ->set('b.amaun', '2500')->set('b.status', 'Diterima')
        ->call('simpanBayaran')->assertHasNoErrors();

    $b = Bayaran::sole();
    expect($b->status)->toBe('Menunggu')->and($b->dicipta_oleh)->toBe($staf->id);
});

it('pengurus boleh sahkan bayaran, staf tidak', function () {
    $staf = ahli($this->s, Peranan::Staf);
    $this->projek->tugaskan($staf);
    $b = $this->projek->bayaran()->create(['jenis' => 'Deposit', 'amaun' => 1000, 'status' => 'Menunggu']);

    Livewire::actingAs($staf)->test(ButiranProjek::class, ['projek' => $this->projek])
        ->call('sahkan', $b->id)->assertForbidden();
    expect($b->fresh()->status)->toBe('Menunggu');

    $pengurus = ahli($this->s, Peranan::Pengurus);
    Livewire::actingAs($pengurus)->test(Dashboard::class)->call('sahkan', $b->id);
    expect($b->fresh())->status->toBe('Diterima')->disahkan_oleh->toBe($pengurus->id);
});

it('staf hanya nampak projek yang ditugaskan', function () {
    $staf = ahli($this->s, Peranan::Staf);
    $this->projek->tugaskan($staf);

    Livewire::actingAs($staf)->test(SenaraiProjek::class)->assertSee('PRJ-01')->assertDontSee('PRJ-02');
    Livewire::actingAs($staf)->test(ButiranProjek::class, ['projek' => $this->lain])->assertForbidden();
});

it('staf dan akauntan tidak boleh cipta, ubah atau padam projek', function (Peranan $peranan) {
    $u = ahli($this->s, $peranan);
    $this->projek->tugaskan($u);

    Livewire::actingAs($u)->test(SenaraiProjek::class)->call('bukaBorang')->assertForbidden();
    Livewire::actingAs($u)->test(ButiranProjek::class, ['projek' => $this->projek])->call('padamProjek')->assertForbidden();
    expect(Projek::count())->toBe(2);
})->with([Peranan::Staf, Peranan::Akauntan]);

it('akauntan boleh lihat tetapi tidak boleh merekod', function () {
    $u = ahli($this->s, Peranan::Akauntan);
    Livewire::actingAs($u)->test(ButiranProjek::class, ['projek' => $this->projek])
        ->assertOk()->call('bukaRekod', 'belanja')->assertForbidden();
});

it('pengurus cipta projek dengan deposit awal sebagai bayaran Diterima', function () {
    $u = ahli($this->s, Peranan::Pengurus);

    Livewire::actingAs($u)->test(SenaraiProjek::class)
        ->call('bukaBorang')->assertSet('form.kod', 'PRJ-03')
        ->set('form.nama', 'Rumah teres')->set('form.nilai', '50000')->set('form.deposit', '15000')
        ->call('simpan')->assertHasNoErrors();

    $p = Projek::where('kod', 'PRJ-03')->sole();
    expect($p->syarikat_id)->toBe($this->s->id)
        ->and($p->bayaran()->sole())->jenis->toBe('Deposit')->status->toBe('Diterima')
        ->and((float) $p->bayaran()->sole()->amaun)->toBe(15000.0);
});

it('projek siap berpindah dari senarai utama ke arkib', function () {
    $u = ahli($this->s, Peranan::Pemilik);
    $this->projek->update(['status' => 'Siap']);

    Livewire::actingAs($u)->test(SenaraiProjek::class)->assertDontSee('PRJ-01')->assertSee('PRJ-02');
    Livewire::actingAs($u)->test(SenaraiProjek::class, ['arkib' => true])->assertSee('PRJ-01')->assertDontSee('PRJ-02');
});

it('padam projek turut memadam bayaran dan perbelanjaan', function () {
    $u = ahli($this->s, Peranan::Pemilik);
    $this->projek->bayaran()->create(['jenis' => 'Deposit', 'amaun' => 1, 'status' => 'Diterima']);
    $this->projek->belanja()->create(['kategori' => 'Material', 'amaun' => 1]);

    Livewire::actingAs($u)->test(ButiranProjek::class, ['projek' => $this->projek])->call('padamProjek');

    expect(Projek::count())->toBe(1)->and(Bayaran::count())->toBe(0)->and(Belanja::count())->toBe(0);
});

it('syarikat baca sahaja tidak boleh menulis', function () {
    $this->s->update(['status' => 'baca_sahaja']);
    $u = ahli($this->s, Peranan::Pemilik);

    Livewire::actingAs($u)->test(SenaraiProjek::class)->call('bukaBorang')->assertForbidden();
});
