<?php

use App\Enums\Peranan;
use App\Livewire\LaporanUntungRugi;
use App\Livewire\SenaraiBelanja;
use App\Models\Projek;
use App\Support\UntungRugi;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo('2026-10-15');
    $this->s = syarikatBaru();
    semasa()->tetapkan($this->s);

    // A: kontrak 10,000 · diterima 6,000 (Mac + Jun) · belanja 7,000 (Mac 2,000 + Jun 5,000)
    $this->a = Projek::factory()->create(['kod' => 'PRJ-01', 'nama' => 'Rumah A', 'nilai' => 10000, 'status' => 'Dalam progres']);
    $this->a->bayaran()->create(['jenis' => 'Deposit', 'amaun' => 3000, 'status' => 'Diterima', 'tarikh' => '2026-03-02']);
    $this->a->bayaran()->create(['jenis' => 'Progres', 'amaun' => 3000, 'status' => 'Diterima', 'tarikh' => '2026-06-10']);
    $this->a->bayaran()->create(['jenis' => 'Progres', 'amaun' => 4000, 'status' => 'Menunggu', 'tarikh' => '2026-07-01']);
    $this->a->belanja()->create(['kategori' => 'Material', 'amaun' => 2000, 'tarikh' => '2026-03-20']);
    $this->a->belanja()->create(['kategori' => 'Upah pekerja', 'amaun' => 5000, 'tarikh' => '2026-06-30']);

    // B (siap): kontrak 5,000 · diterima 5,000 (2025) · belanja 6,500 → rugi dijangka; satu belanja tanpa tarikh
    $this->b = Projek::factory()->create(['kod' => 'PRJ-02', 'nama' => 'Rumah B', 'nilai' => 5000, 'status' => 'Siap']);
    $this->b->bayaran()->create(['jenis' => 'Akhir', 'amaun' => 5000, 'status' => 'Diterima', 'tarikh' => '2025-12-01']);
    $this->b->belanja()->create(['kategori' => 'Material', 'amaun' => 6000, 'tarikh' => '2026-01-05']);
    $this->b->belanja()->create(['kategori' => 'Lain-lain', 'amaun' => 500]);

    $this->pemilik = ahli($this->s, Peranan::Pemilik);
});

it('bulanan: menunggu tidak dikira, untung & kumulatif setiap bulan, tanpa tarikh diasingkan', function () {
    $b = UntungRugi::bulanan(Projek::query(), 2026);

    expect($b['masuk'])->toBe(600000)->and($b['keluar'])->toBe(1300000)->and($b['untung'])->toBe(-700000)
        ->and($b['tanpa_tarikh'])->toBe(1)
        ->and($b['bulan'][0])->toMatchArray(['masuk' => 0, 'keluar' => 600000, 'untung' => -600000, 'kumulatif' => -600000])
        ->and($b['bulan'][2])->toMatchArray(['masuk' => 300000, 'keluar' => 200000, 'untung' => 100000, 'kumulatif' => -500000])
        ->and($b['bulan'][5])->toMatchArray(['untung' => -200000, 'kumulatif' => -700000])
        ->and($b['bulan'][6]['masuk'])->toBe(0);   // Julai: hanya bayaran Menunggu

    expect(UntungRugi::bulanan(Projek::query(), 2025)['untung'])->toBe(500000)
        ->and(UntungRugi::senaraiTahun(Projek::query()))->toBe([2026, 2025]);
});

it('per projek: untung tunai, untung dijangka dan margin', function () {
    $p = UntungRugi::perProjek(Projek::query(), 'untung');

    expect($p->pluck('kod')->all())->toBe(['PRJ-02', 'PRJ-01'])     // rugi dahulu
        ->and($p[0])->untung_tunai->toBe(-150000)->untung_jangka->toBe(-150000)->margin->toBe(-300)
        ->and($p[1])->untung_tunai->toBe(-100000)->untung_jangka->toBe(300000)->margin->toBe(300)
        ->and(UntungRugi::teksMargin(-300))->toBe('−30.0%')
        ->and(UntungRugi::teksMargin(null))->toBe('—');
});

it('halaman untung rugi: tapis tahun & projek siap', function () {
    Livewire::actingAs($this->pemilik)->test(LaporanUntungRugi::class)
        ->assertSee('Untung tunai · 2026')->assertSee('−RM7,000.00')->assertSee('1 rekod bayaran/perbelanjaan tanpa tarikh')
        ->assertSee('Rumah A')->assertSee('Rumah B')
        ->set('tahun', '2025')->assertSee('Untung tunai · 2025')->assertSee('RM5,000.00')
        ->set('kat', 'aktif')->assertSee('Rumah A')->assertDontSee('Rumah B');

    $this->actingAs($this->pemilik)->get(urlSyarikat($this->s, '/untung-rugi'))->assertOk()->assertSee('Untung Rugi');
});

it('staf tidak boleh buka untung rugi; akauntan dan pengurus boleh', function () {
    Livewire::actingAs(ahli($this->s, Peranan::Staf))->test(LaporanUntungRugi::class)->assertForbidden();
    Livewire::actingAs(ahli($this->s, Peranan::Akauntan))->test(LaporanUntungRugi::class)->assertOk();
    Livewire::actingAs(ahli($this->s, Peranan::Pengurus))->test(LaporanUntungRugi::class)->assertOk();
});

it('eksport CSV untung rugi projek dan bulanan', function () {
    $r = $this->actingAs($this->pemilik)->get(urlSyarikat($this->s, '/eksport/pnl.csv?susun=untung'))->assertOk();
    $b = csvBaris($r->streamedContent());
    expect($b[0])->toBe(['Kod', 'Projek', 'Status', 'Nilai Kontrak', 'Diterima', 'Perbelanjaan', 'Untung Tunai', 'Untung Dijangka', 'Margin'])
        ->and($b[1])->toBe(['PRJ-02', 'Rumah B', 'Siap', '5000.00', '5000.00', '6500.00', '-1500.00', '-1500.00', '−30.0%'])
        ->and(end($b))->toBe(['JUMLAH · 2 projek', '', '', '15000.00', '11000.00', '13500.00', '-2500.00', '1500.00', '10.0%']);

    $r = $this->actingAs($this->pemilik)->get(urlSyarikat($this->s, '/eksport/pnl-bulan.csv?tahun=2026'))->assertOk();
    $b = csvBaris($r->streamedContent());
    expect($b[3])->toBe(['Mac', '3000.00', '2000.00', '1000.00', '-5000.00'])
        ->and(end($b))->toBe(['JUMLAH 2026', '6000.00', '13000.00', '-7000.00', '']);

    $this->actingAs($this->pemilik)->get(urlSyarikat($this->s, '/eksport/pnl.pdf'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $this->actingAs(ahli($this->s, Peranan::Staf))->get(urlSyarikat($this->s, '/eksport/pnl.csv'))->assertForbidden();
});

it('butiran projek papar untung rugi projek dan perbelanjaan ikut kategori (bukan untuk staf)', function () {
    $this->actingAs($this->pemilik)->get(urlSyarikat($this->s, '/projek/'.$this->b->id))->assertOk()
        ->assertSee('Untung rugi projek')->assertSee('Projek ini rugi')->assertSee('−30.0%')
        ->assertSee('Perbelanjaan ikut kategori')->assertSee('130% daripada nilai kontrak');

    semasa()->tetapkan($this->s);     // middleware mengosongkan syarikat selepas permintaan; RLS perlukannya
    $staf = ahli($this->s, Peranan::Staf);
    $this->b->staf()->attach($staf->id, ['syarikat_id' => $this->s->id]);
    $this->actingAs($staf)->get(urlSyarikat($this->s, '/projek/'.$this->b->id))->assertOk()->assertDontSee('Untung rugi projek');
});

it('perbelanjaan: paparan ikut projek, tapis kategori, klik projek ke senarai', function () {
    Livewire::actingAs($this->pemilik)->test(SenaraiBelanja::class)
        ->set('projek', (string) $this->a->id)
        ->set('paparan', 'projek')->assertSet('projek', '')
        ->assertSeeInOrder(['PRJ-01', 'RM7,000.00', 'PRJ-02', 'RM6,500.00'])->assertSee('Untung dijangka')
        ->set('kategori', 'Material')->assertSee('RM6,000.00')->assertSee('RM2,000.00')
        ->call('pilihProjek', $this->b->id)->assertSet('paparan', 'senarai')->assertSet('projek', (string) $this->b->id);

    $staf = ahli($this->s, Peranan::Staf);
    $this->a->staf()->attach($staf->id, ['syarikat_id' => $this->s->id]);
    Livewire::actingAs($staf)->test(SenaraiBelanja::class)->set('paparan', 'projek')
        ->assertSee('PRJ-01')->assertDontSee('PRJ-02')->assertDontSee('Untung dijangka');
});
