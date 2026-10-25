<?php

use App\Enums\Peranan;
use App\Livewire\SenaraiBayaran;
use App\Models\Bayaran;
use App\Models\Projek;
use App\Models\Syarikat;
use App\Models\User;
use App\Support\Kiraan;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->s = syarikatBaru();
    semasa()->tetapkan($this->s);
    $this->a = Projek::factory()->create(['kod' => 'PRJ-01', 'nama' => 'Rumah A']);
    $this->b = Projek::factory()->create(['kod' => 'PRJ-02', 'nama' => 'Rumah B']);
    $this->a->bayaran()->create(['jenis' => 'Deposit', 'amaun' => 3000, 'status' => 'Diterima', 'ruj' => 'RUJ-A1']);
    $this->a->bayaran()->create(['jenis' => 'Bayaran Progres', 'amaun' => 2000, 'status' => 'Menunggu', 'ruj' => 'RUJ-A2']);
    $this->b->bayaran()->create(['jenis' => 'Bayaran Akhir', 'amaun' => 1500, 'status' => 'Diterima', 'ruj' => 'RUJ-B1']);
    $this->pemilik = ahli($this->s, Peranan::Pemilik);
});

it('memapar semua bayaran dengan jumlah diterima, deposit dan menunggu', function () {
    Livewire::actingAs($this->pemilik)->test(SenaraiBayaran::class)
        ->assertSee('RUJ-A1')->assertSee('RUJ-A2')->assertSee('RUJ-B1')
        ->assertSee('RM4,500.00')    // diterima
        ->assertSee('RM3,000.00')    // deposit
        ->assertSee('RM2,000.00');   // menunggu
});

it('tapisan status, deposit dan projek', function () {
    $t = Livewire::actingAs($this->pemilik)->test(SenaraiBayaran::class);
    $t->set('status', 'Menunggu')->assertSee('RUJ-A2')->assertDontSee('RUJ-A1')->assertDontSee('RUJ-B1');
    $t->set('status', 'Diterima')->set('jenis', 'deposit')->assertSee('RUJ-A1')->assertDontSee('RUJ-B1')->assertDontSee('RUJ-A2');
    $t->call('kosongkan')->set('projek', (string) $this->b->id)->assertSee('RUJ-B1')->assertDontSee('RUJ-A1');
});

it('jumlah sama dengan kotak Dashboard (data demo)', function () {
    $this->seed(DatabaseSeeder::class);
    $demo = Syarikat::where('subdomain', 'demo')->first();
    semasa()->tetapkan($demo);
    $u = User::where('email', 'pemilik@demo.test')->first();
    $r = Kiraan::ringkasan();

    Livewire::actingAs($u)->test(SenaraiBayaran::class)
        ->assertViewHas('diterimaSen', $r['diterima'])
        ->assertViewHas('depositSen', $r['deposit'])
        ->set('status', 'Diterima')->assertViewHas('jumlahSenarai', $r['diterima'])
        ->set('jenis', 'deposit')->assertViewHas('jumlahSenarai', $r['deposit']);
});

it('staf rekod bayaran: dipaksa Menunggu, hanya projek ditugaskan', function () {
    $staf = ahli($this->s, Peranan::Staf);
    $this->a->tugaskan($staf);

    $t = Livewire::actingAs($staf)->test(SenaraiBayaran::class)->assertSee('RUJ-A1')->assertDontSee('RUJ-B1');
    $t->call('bukaBorang')->set('b.projek_id', $this->a->id)->set('b.amaun', '999')->set('b.status', 'Diterima')
        ->set('b.ruj', 'STAF-1')->call('simpan')->assertHasNoErrors();
    expect(Bayaran::where('ruj', 'STAF-1')->sole()->status)->toBe('Menunggu');

    $t->call('bukaBorang')->set('b.projek_id', $this->b->id)->set('b.amaun', '1')->call('simpan')->assertForbidden();
});

it('pengurus sahkan bayaran dari tab ini; staf tidak boleh', function () {
    $menunggu = Bayaran::where('status', 'Menunggu')->sole();
    $staf = ahli($this->s, Peranan::Staf);
    $this->a->tugaskan($staf);

    Livewire::actingAs($staf)->test(SenaraiBayaran::class)->call('sahkan', $menunggu->id)->assertForbidden();
    Livewire::actingAs(ahli($this->s, Peranan::Pengurus))->test(SenaraiBayaran::class)->call('sahkan', $menunggu->id);
    expect($menunggu->fresh()->status)->toBe('Diterima');
});

it('tidak boleh merekod atau menyentuh bayaran syarikat lain', function () {
    $lain = syarikatBaru();
    [$pLain, $bLain] = semasa()->sebagai($lain, function () {
        $p = Projek::factory()->create();

        return [$p, $p->bayaran()->create(['jenis' => 'Deposit', 'amaun' => 1, 'status' => 'Menunggu'])];
    });

    Livewire::actingAs($this->pemilik)->test(SenaraiBayaran::class)
        ->call('bukaBorang')->set('b.projek_id', $pLain->id)->set('b.amaun', '1')->call('simpan')->assertNotFound();
    Livewire::actingAs($this->pemilik)->test(SenaraiBayaran::class)->call('padam', $bLain->id)->assertNotFound();
    Livewire::actingAs($this->pemilik)->test(SenaraiBayaran::class)->call('sahkan', $bLain->id)->assertNotFound();
    expect(semasa()->sebagai($lain, fn () => $bLain->fresh()->status))->toBe('Menunggu');
});

it('akauntan boleh lihat tetapi tidak boleh rekod, sahkan atau padam', function () {
    $akaun = ahli($this->s, Peranan::Akauntan);
    $b = Bayaran::where('status', 'Menunggu')->sole();

    Livewire::actingAs($akaun)->test(SenaraiBayaran::class)->assertSee('RUJ-A1')->assertDontSee('+ Bayaran')
        ->call('sahkan', $b->id)->assertForbidden();
    Livewire::actingAs($akaun)->test(SenaraiBayaran::class)->call('padam', $b->id)->assertForbidden();
});

it('kotak Deposit & Received Payment di Dashboard memaut ke Pembayaran dengan tapisan', function () {
    $this->actingAs($this->pemilik)->get(urlSyarikat($this->s))
        ->assertSee(urlSyarikat($this->s, '/pembayaran?jenis=deposit&amp;status=Diterima'), false)
        ->assertSee(urlSyarikat($this->s, '/pembayaran?status=Diterima'), false);
});

it('butang Sahkan dan padam dipapar kepada pemilik, tidak kepada akauntan', function () {
    Livewire::actingAs($this->pemilik)->test(SenaraiBayaran::class)
        ->assertSeeHtml('wire:click="sahkan(')->assertSeeHtml('wire:click="padam(');
    Livewire::actingAs(ahli($this->s, Peranan::Akauntan))->test(SenaraiBayaran::class)
        ->assertDontSeeHtml('wire:click="sahkan(')->assertDontSeeHtml('wire:click="padam(');
});

it('bayaran tanpa tarikh disusun di bawah (sama di SQLite dan PostgreSQL)', function () {
    $this->a->bayaran()->update(['tarikh' => '2026-01-01']);
    Livewire::actingAs($this->pemilik)->test(SenaraiBayaran::class)->assertSeeInOrder(['RUJ-A1', 'RUJ-B1']);
});

it('kotak Pembayaran guna kelas (bukan gaya sebaris) supaya satu lajur di telefon', function () {
    Livewire::actingAs($this->pemilik)->test(SenaraiBayaran::class)
        ->assertSeeHtml('class="tiles tiga"')->assertDontSeeHtml('grid-template-columns:repeat(3');
});
