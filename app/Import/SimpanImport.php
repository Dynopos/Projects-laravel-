<?php

namespace App\Import;

use App\Models\Bayaran;
use App\Models\LogAktiviti;
use App\Models\Projek;
use App\Observers\CatatAktiviti;
use Illuminate\Support\Facades\DB;

/**
 * Masukkan HasilImport ke dalam syarikat semasa — satu transaksi, semua atau tiada.
 * (Versi Supabase boleh gagal separuh jalan; di sini ralat membatalkan semuanya.)
 *
 * Projek dipadan ikut kod: yang sudah wujud DIGANTI (butiran + semua bayarannya),
 * melainkan $hanyaBaharu. Belanja tidak disentuh.
 */
class SimpanImport
{
    /** @return array{baharu: list<string>, wujud: list<string>, bayaranDipadam: int, manualDipadam: int} */
    public static function semak(HasilImport $h, bool $hanyaBaharu = false): array
    {
        $kod = array_column($h->projek, 'kod');
        $wujud = Projek::whereIn('kod', $kod)->pluck('id', 'kod');
        $ganti = $hanyaBaharu ? collect() : $wujud;
        $q = fn () => Bayaran::whereIn('projek_id', $ganti->values());

        return [
            'baharu' => array_values(array_diff($kod, $wujud->keys()->all())),
            'wujud' => $wujud->keys()->values()->all(),
            'bayaranDipadam' => $q()->count(),
            'manualDipadam' => $q()->where('diimport', false)->count(),
        ];
    }

    /** @return array{projek: int, bayaran: int} */
    public static function simpan(HasilImport $h, bool $hanyaBaharu = false): array
    {
        $semak = self::semak($h, $hanyaBaharu);

        return DB::transaction(fn () => CatatAktiviti::senyap(function () use ($h, $hanyaBaharu, $semak) {
            $projekDibuat = 0;
            $bayaranDibuat = 0;
            $wujud = array_flip($semak['wujud']);

            foreach ($h->projek as $p) {
                if ($hanyaBaharu && isset($wujud[$p['kod']])) {
                    continue;
                }
                $medan = collect($p)->only(['nama', 'klien', 'lokasi', 'telefon', 'inv', 'status', 'nilai', 'nota', 'aliran'])
                    ->map(fn ($v, $k) => $k === 'aliran' ? $v : (is_string($v) ? mb_substr($v, 0, $k === 'nota' ? 5000 : 255) : $v))
                    ->put('mula', $p['mula'] ?: null)->all();

                $projek = Projek::firstOrNew(['kod' => $p['kod']]);
                $projek->fill($medan)->save();
                $projek->bayaran()->delete();
                $projekDibuat++;

                foreach (array_filter($h->bayaran, fn ($b) => $b['kod'] === $p['kod']) as $b) {
                    $byr = $projek->bayaran()->make([
                        'jenis' => $b['jenis'], 'amaun' => $b['amaun'], 'tarikh' => $b['tarikh'] ?: null,
                        'kaedah' => '', 'ruj' => mb_substr((string) $b['ruj'], 0, 80), 'status' => Bayaran::DITERIMA,
                    ]);
                    $byr->diimport = true;
                    $byr->save();
                    $bayaranDibuat++;
                }
            }

            LogAktiviti::catat('Import Excel', "{$projekDibuat} projek, {$bayaranDibuat} bayaran (helaian {$h->helaian})"
                .($semak['bayaranDipadam'] ? " — {$semak['bayaranDipadam']} bayaran lama diganti" : ''));

            return ['projek' => $projekDibuat, 'bayaran' => $bayaranDibuat];
        }));
    }
}
