<?php

use App\Enums\Peranan;
use App\Models\Bayaran;
use App\Models\LogAktiviti;
use App\Models\Projek;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/*
 * Risiko #1 dalam rancangan: data satu syarikat bocor ke syarikat lain.
 * Setiap lapisan diuji secara berasingan.
 */
beforeEach(function () {
    $this->a = syarikatBaru();
    $this->b = syarikatBaru();
    $this->projekB = semasa()->sebagai($this->b, fn () => tap(Projek::factory()->create(['kod' => 'B-01']),
        fn ($p) => $p->bayaran()->create(['jenis' => 'Deposit', 'amaun' => 5000, 'status' => 'Diterima'])));
    semasa()->sebagai($this->a, fn () => Projek::factory()->create(['kod' => 'A-01']));
});

it('lapisan 1: scope Eloquent hanya memulangkan syarikat semasa', function () {
    semasa()->tetapkan($this->a);
    expect(Projek::pluck('kod')->all())->toBe(['A-01'])
        ->and(Projek::find($this->projekB->id))->toBeNull()
        ->and(Bayaran::count())->toBe(0);
});

it('gagal tertutup bila tiada syarikat ditetapkan', function () {
    semasa()->tetapkan(null);
    expect(Projek::count())->toBe(0);
});

it('menolak rekod untuk syarikat lain dan pertukaran syarikat_id', function () {
    semasa()->tetapkan($this->a);
    expect(fn () => Projek::factory()->create(['syarikat_id' => $this->b->id]))->toThrow(RuntimeException::class);

    $p = Projek::first();
    $p->syarikat_id = $this->b->id;
    expect(fn () => $p->save())->toThrow(RuntimeException::class);
});

it('FK komposit menolak bayaran yang menunjuk ke projek syarikat lain', function () {
    semasa()->tetapkan($this->a);
    expect(fn () => Bayaran::create(['projek_id' => $this->projekB->id, 'jenis' => 'Deposit', 'amaun' => 1, 'status' => 'Diterima']))
        ->toThrow(QueryException::class);
});

it('lapisan 2: RLS menapis pertanyaan yang memintas Eloquent', function () {
    semasa()->tetapkan($this->a);
    expect(DB::table('projek')->pluck('kod')->all())->toBe(['A-01'])
        ->and(DB::table('bayaran')->count())->toBe(0);

    semasa()->tetapkan(null);
    expect(DB::table('projek')->count())->toBe(0);

    semasa()->tetapkan($this->a);
    expect(fn () => DB::table('projek')->insert(['syarikat_id' => $this->b->id, 'kod' => 'X', 'nama' => 'X']))
        ->toThrow(QueryException::class);
})->skip(fn () => ! pgsql(), 'RLS hanya dalam PostgreSQL');

it('URL projek syarikat lain memulangkan 404, projek sendiri dibuka', function () {
    $u = ahli($this->a, Peranan::Pemilik);
    $sendiri = semasa()->sebagai($this->a, fn () => Projek::first());
    $this->actingAs($u)->get(urlSyarikat($this->a, '/projek/'.$sendiri->id))->assertOk()->assertSee('A-01');
    $this->actingAs($u)->get(urlSyarikat($this->a, '/projek/'.$this->projekB->id))->assertNotFound();
});

it('ahli syarikat A tidak boleh guna subdomain syarikat B', function () {
    $u = ahli($this->a, Peranan::Pemilik);
    $this->actingAs($u)->get(urlSyarikat($this->b))->assertRedirect(urlSyarikat($this->b, '/masuk'));
    $this->assertGuest();
});

it('hos yang tidak dikenali memulangkan 404', function () {
    config(['dyno.syarikat_lalai' => null]);
    $this->get('http://tiada.'.config('dyno.domain_utama').'/masuk')->assertNotFound();
});

it('log aktiviti kekal dalam syarikat masing-masing', function () {
    semasa()->sebagai($this->a, fn () => LogAktiviti::catat('Ujian', 'A'));
    semasa()->tetapkan($this->b);
    expect(LogAktiviti::where('butiran', 'A')->count())->toBe(0);
});
