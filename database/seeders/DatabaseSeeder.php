<?php

namespace Database\Seeders;

use App\Enums\Peranan;
use App\Models\Projek;
use App\Models\Syarikat;
use App\Models\User;
use App\Support\SyarikatSemasa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Syarikat demo dengan data pembinaan REKAAN (database/data/demo.json, sama dengan
 * data.json repo ProjectOs). Bukan data pelanggan sebenar — jangan sesekali letak
 * data pelanggan dalam repo.
 *
 * Akaun demo (kata laluan: password) — untuk pembangunan tempatan SAHAJA.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $semasa = app(SyarikatSemasa::class);

        $semasa->tanpaSkop(function () use ($semasa) {
            $s = Syarikat::firstOrCreate(['subdomain' => 'demo'], [
                'nama' => 'BINAAN CONTOH', 'sufiks' => ' SDN BHD', 'jabatan' => 'Jabatan Projek',
                'kod_awalan' => 'PRJ-', 'status' => 'aktif',
            ]);
            $s->sediakanTetapanLalai();

            $akaun = [
                ['Pemilik Demo', 'pemilik@demo.test', Peranan::Pemilik],
                ['Pengurus Demo', 'pengurus@demo.test', Peranan::Pengurus],
                ['Staf Demo', 'staf@demo.test', Peranan::Staf],
                ['Akauntan Demo', 'akauntan@demo.test', Peranan::Akauntan],
            ];
            foreach ($akaun as [$nama, $email, $peranan]) {
                $u = User::firstOrCreate(['email' => $email], ['name' => $nama, 'password' => 'password']);
                $s->tambahAhli($u, $peranan);
            }

            if (Projek::where('syarikat_id', $s->id)->exists()) {
                return;
            }

            $semasa->sebagai($s, function () {
                $data = json_decode(file_get_contents(database_path('data/demo.json')), true);
                $staf = User::where('email', 'staf@demo.test')->first();

                DB::transaction(function () use ($data, $staf) {
                    $id = [];
                    foreach ($data['projek'] as $p) {
                        $id[$p['kod']] = Projek::create($p);
                    }
                    foreach ($data['bayaran'] as $b) {
                        $id[$b['kod']]->bayaran()->create(collect($b)->except('kod')->all());
                    }
                    foreach ($data['belanja'] as $x) {
                        $id[$x['kod']]->belanja()->create(collect($x)->except('kod')->map(fn ($v) => $v ?? '')->all());
                    }
                    // Staf demo ditugaskan pada dua projek aktif sahaja.
                    collect($id)->filter(fn ($p) => $p->jenisStatus() !== 'siap')->take(2)->each->tugaskan($staf);
                });
            });
        });
    }
}
