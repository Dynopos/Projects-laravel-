<?php

use App\Enums\Peranan;
use App\Models\Syarikat;
use App\Models\User;
use App\Support\SyarikatSemasa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * Lalai: SQLite dalam memori (pantas). Untuk menguji lapisan RLS juga:
 *   DB_CONNECTION=pgsql DB_DATABASE=dyno_ujian DB_USERNAME=dyno DB_PASSWORD=dyno php artisan test
 * Ujian bertanda ->group('pgsql') dilangkau di SQLite.
 */
pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');

function semasa(): SyarikatSemasa
{
    return app(SyarikatSemasa::class);
}

function syarikatBaru(array $atribut = []): Syarikat
{
    return semasa()->tanpaSkop(function () use ($atribut) {
        $s = Syarikat::factory()->create($atribut);
        $s->sediakanTetapanLalai();

        return $s;
    });
}

function ahli(Syarikat $s, Peranan $peranan, array $atribut = []): User
{
    $u = User::factory()->create($atribut);
    $s->tambahAhli($u, $peranan);

    return $u;
}

/** URL penuh untuk syarikat, cth http://demo.dynoprojectos.test/projek */
function urlSyarikat(Syarikat $s, string $laluan = '/'): string
{
    return 'http://'.$s->subdomain.'.'.config('dyno.domain_utama').$laluan;
}

function pgsql(): bool
{
    return DB::getDriverName() === 'pgsql';
}
