<?php

use App\Enums\Peranan;
use App\Http\Controllers\EksportController;
use App\Models\LogAktiviti;
use App\Models\Projek;

beforeEach(function () {
    $this->s = syarikatBaru(['nama' => 'BINAAN UJIAN']);
    semasa()->tetapkan($this->s);
    $this->a = Projek::factory()->create(['kod' => 'PRJ-01', 'nama' => 'Rumah A', 'klien' => '=HYPERLINK("http://jahat")', 'nilai' => 10000, 'status' => 'Dalam progres']);
    $this->b = Projek::factory()->create(['kod' => 'PRJ-02', 'nama' => 'Rumah B', 'nilai' => 5000, 'status' => 'Siap']);
    $this->a->bayaran()->create(['jenis' => 'Deposit', 'amaun' => 3000.5, 'status' => 'Diterima', 'tarikh' => '2026-03-01', 'ruj' => 'RUJ-A1']);
    $this->a->bayaran()->create(['jenis' => 'Bayaran Progres', 'amaun' => 2000, 'status' => 'Menunggu', 'ruj' => 'RUJ-A2']);
    $this->b->bayaran()->create(['jenis' => 'Bayaran Akhir', 'amaun' => 5000, 'status' => 'Diterima', 'ruj' => 'RUJ-B1']);
    $this->a->belanja()->create(['kategori' => 'Material', 'amaun' => 1200, 'penerima' => 'KEDAI A']);
    $this->pemilik = ahli($this->s, Peranan::Pemilik);
});

function csvBaris(string $isi): array
{
    $isi = preg_replace('/^\xEF\xBB\xBF/', '', $isi);

    return array_map(fn ($l) => str_getcsv($l, escape: ''), array_values(array_filter(explode("\n", trim($isi)))));
}

it('CSV bayaran: BOM, tajuk, amaun nombor biasa, tapisan status, baris jumlah', function () {
    $r = $this->actingAs($this->pemilik)->get(urlSyarikat($this->s, '/eksport/bayaran.csv?status=Diterima'));
    $r->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    expect($r->headers->get('Content-Disposition'))->toContain($this->s->subdomain.'-bayaran-');

    $isi = $r->streamedContent();
    expect($isi)->toStartWith("\xEF\xBB\xBF");
    $b = csvBaris($isi);
    expect($b[0])->toBe(['Tarikh', 'Kod Projek', 'Projek', 'Daripada', 'Jenis', 'Kaedah', 'Rujukan', 'Status', 'Amaun'])
        ->and(collect($b)->slice(1)->pluck(6)->filter()->values()->all())->toBe(['RUJ-A1', 'RUJ-B1'])   // Menunggu ditapis keluar
        ->and(collect($b)->firstWhere(6, 'RUJ-A1')[8])->toBe('3000.50')
        ->and(end($b))->toBe(['JUMLAH · 2 bayaran', '', '', '', '', '', '', '', '8000.50']);
});

it('CSV projek aktif mengecualikan projek siap; arkib sebaliknya', function () {
    $aktif = csvBaris($this->actingAs($this->pemilik)->get(urlSyarikat($this->s, '/eksport/projek.csv'))->streamedContent());
    $arkib = csvBaris($this->actingAs($this->pemilik)->get(urlSyarikat($this->s, '/eksport/arkib.csv'))->streamedContent());

    expect(array_column($aktif, 0))->toContain('PRJ-01')->not->toContain('PRJ-02')
        ->and(array_column($arkib, 0))->toContain('PRJ-02')->not->toContain('PRJ-01');
    $a = collect($aktif)->firstWhere(0, 'PRJ-01');
    expect([$a[7], $a[8], $a[9], $a[10], $a[11]])->toBe(['10000.00', '3000.50', '6999.50', '30', '1200.00']);
});

it('CSV melindungi daripada suntikan formula Excel', function () {
    $b = csvBaris($this->actingAs($this->pemilik)->get(urlSyarikat($this->s, '/eksport/projek.csv'))->streamedContent());
    expect(collect($b)->firstWhere(0, 'PRJ-01')[3])->toBe("'=HYPERLINK(\"http://jahat\")");
});

it('CSV perbelanjaan dengan tapisan kategori', function () {
    $b = csvBaris($this->actingAs($this->pemilik)->get(urlSyarikat($this->s, '/eksport/belanja.csv?kategori=Material'))->streamedContent());
    expect($b[1][4])->toBe('KEDAI A')->and($b[1][7])->toBe('1200.00');

    $kosong = csvBaris($this->actingAs($this->pemilik)->get(urlSyarikat($this->s, '/eksport/belanja.csv?kategori=Tiada'))->streamedContent());
    expect($kosong)->toHaveCount(1);   // tajuk sahaja
});

it('PDF dijana untuk setiap senarai', function (string $jenis) {
    $r = $this->actingAs($this->pemilik)->get(urlSyarikat($this->s, "/eksport/{$jenis}.pdf"));
    $r->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($r->getContent())->toStartWith('%PDF');
})->with(['projek', 'arkib', 'bayaran', 'belanja']);

it('setiap eksport dilog', function () {
    $this->actingAs($this->pemilik)->get(urlSyarikat($this->s, '/eksport/bayaran.pdf'));
    semasa()->tetapkan($this->s);
    expect(LogAktiviti::where('tindakan', 'Eksport PDF')->sole()->butiran)->toContain('Pembayaran');
});

it('akauntan boleh eksport; staf tidak', function () {
    $this->actingAs(ahli($this->s, Peranan::Akauntan))->get(urlSyarikat($this->s, '/eksport/bayaran.csv'))->assertOk();
    $this->actingAs(ahli($this->s, Peranan::Staf))->get(urlSyarikat($this->s, '/eksport/bayaran.csv'))->assertForbidden();
});

it('eksport tidak mengandungi data syarikat lain', function () {
    $lain = syarikatBaru();
    semasa()->sebagai($lain, fn () => Projek::factory()->create(['kod' => 'RAHSIA-99'])
        ->bayaran()->create(['jenis' => 'Deposit', 'amaun' => 1, 'status' => 'Diterima', 'ruj' => 'RUJ-RAHSIA']));

    foreach (['projek', 'bayaran'] as $j) {
        $isi = $this->actingAs($this->pemilik)->get(urlSyarikat($this->s, "/eksport/{$j}.csv"))->streamedContent();
        expect($isi)->not->toContain('RAHSIA');
    }
});

it('jenis atau format tidak sah = 404; tetamu dihantar log masuk', function () {
    $this->actingAs($this->pemilik)->get(urlSyarikat($this->s, '/eksport/users.csv'))->assertNotFound();
    $this->actingAs($this->pemilik)->get(urlSyarikat($this->s, '/eksport/bayaran.xls'))->assertNotFound();
    auth()->logout();
    $this->get(urlSyarikat($this->s, '/eksport/bayaran.csv'))->assertRedirect(urlSyarikat($this->s, '/masuk'));
});

it('butang eksport dipapar kepada pemilik, tidak kepada staf', function () {
    $this->actingAs($this->pemilik)->get(urlSyarikat($this->s, '/pembayaran'))->assertSee('⬇ CSV')->assertSee('⬇ PDF');
    semasa()->tetapkan($this->s);   // middleware mengosongkan syarikat selepas permintaan; RLS menolak tulisan tanpanya
    $staf = ahli($this->s, Peranan::Staf);
    $this->a->tugaskan($staf);
    $this->actingAs($staf)->get(urlSyarikat($this->s, '/pembayaran'))->assertDontSee('⬇ CSV');
});

it('format sel PDF: tarikh sebenar sahaja diformat, teks JUMLAH kekal', function () {
    $f = fn ($v, $j) => EksportController::selPdf($v, $j);
    expect($f('2026-03-01', 'tarikh'))->toBe('01/03/2026')
        ->and($f('JUMLAH · 2 bayaran', 'tarikh'))->toBe('JUMLAH · 2 bayaran')
        ->and($f(null, 'tarikh'))->toBe('')
        ->and($f(-150, 'wang'))->toBe('−RM1.50')
        ->and($f(30, 'peratus'))->toBe('30%');
});
