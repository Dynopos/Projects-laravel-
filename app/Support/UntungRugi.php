<?php

namespace App\Support;

use App\Models\Bayaran;
use App\Models\Belanja;
use App\Models\BelanjaUmum;
use App\Models\Projek;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Untung rugi (PNL).
 *
 *   Untung setakat ini (untung_tunai)  = Σ bayaran diterima − Σ perbelanjaan projek  (= Baki Terkini)
 *   Untung bersih                      = untung projek − Σ perbelanjaan umum (overhead) (bulanan, semua projek sahaja)
 *   Untung bila siap (untung_jangka)   = nilai kontrak − perbelanjaan setakat ini (per projek)
 *   Margin          = untung ÷ asasnya (kutipan / nilai kontrak)
 *
 * Bayaran Menunggu tidak dikira, sama seperti Kiraan. Semua amaun dalam SEN.
 */
class UntungRugi
{
    /**
     * 12 bulan setahun: masuk, keluar (projek), untung, umum (overhead), bersih, kumulatif (bersih).
     * Overhead hanya dikira bila $umum — ia milik syarikat, bukan projek yang ditapis.
     * Rekod tanpa tarikh dikira berasingan.
     *
     * @param  Builder<Projek>  $projek
     * @return array{bulan: list<array{bulan: int, masuk: int, keluar: int, untung: int, umum: int, bersih: int, kumulatif: int}>, masuk: int, keluar: int, untung: int, umum: int, bersih: int, tanpa_tarikh: int}
     */
    public static function bulanan(Builder $projek, int $tahun, bool $umum = false): array
    {
        $ids = $projek->clone()->select('id');
        $masuk = self::ikutBulan(Bayaran::query()->whereIn('projek_id', $ids)->where('status', '!=', Bayaran::MENUNGGU), $tahun);
        $keluar = self::ikutBulan(Belanja::query()->whereIn('projek_id', $ids), $tahun);
        $overhead = $umum ? self::ikutBulan(BelanjaUmum::query(), $tahun) : ['bulan' => [], 'tanpa_tarikh' => 0];

        $kumulatif = 0;
        $bulan = [];
        for ($b = 1; $b <= 12; $b++) {
            $untung = ($masuk['bulan'][$b] ?? 0) - ($keluar['bulan'][$b] ?? 0);
            $bersih = $untung - ($overhead['bulan'][$b] ?? 0);
            $kumulatif += $bersih;
            $bulan[] = ['bulan' => $b, 'masuk' => $masuk['bulan'][$b] ?? 0, 'keluar' => $keluar['bulan'][$b] ?? 0,
                'untung' => $untung, 'umum' => $overhead['bulan'][$b] ?? 0, 'bersih' => $bersih, 'kumulatif' => $kumulatif];
        }
        $j = fn ($k) => array_sum(array_column($bulan, $k));

        return ['bulan' => $bulan, 'masuk' => $j('masuk'), 'keluar' => $j('keluar'), 'untung' => $j('untung'),
            'umum' => $j('umum'), 'bersih' => $j('bersih'),
            'tanpa_tarikh' => $masuk['tanpa_tarikh'] + $keluar['tanpa_tarikh'] + $overhead['tanpa_tarikh']];
    }

    /**
     * Projek dengan untung tunai, untung dijangka dan margin.
     *
     * @param  Builder<Projek>  $projek
     * @return Collection<int, Projek>
     */
    public static function perProjek(Builder $projek, string $susun = 'kod'): Collection
    {
        return Kiraan::projekDenganJumlah($projek)->each(function (Projek $p) {
            $p->untung_tunai = $p->diterima_sen - $p->belanja_sen;
            $p->untung_jangka = $p->nilai_sen - $p->belanja_sen;
            $p->margin = self::margin($p->untung_jangka, $p->nilai_sen);
        })->sort(fn ($a, $b) => match ($susun) {
            'untung' => $a->untung_jangka <=> $b->untung_jangka,      // terendah dahulu: projek rugi di atas
            'margin' => ($a->margin ?? PHP_INT_MAX) <=> ($b->margin ?? PHP_INT_MAX),
            'belanja' => $b->belanja_sen <=> $a->belanja_sen,
            default => strnatcmp($a->kod, $b->kod),
        })->values();
    }

    /** Peratus satu titik perpuluhan (×10, cth 254 = 25.4%); null bila asas sifar. */
    public static function margin(int $untung, int $asas): ?int
    {
        return $asas > 0 ? (int) round($untung / $asas * 1000) : null;
    }

    public static function teksMargin(?int $m): string
    {
        return $m === null ? '—' : ($m < 0 ? '−' : '').number_format(abs($m) / 10, 1).'%';
    }

    /** Tahun yang ada rekod (menurun), sentiasa termasuk tahun semasa. */
    public static function senaraiTahun(Builder $projek): array
    {
        $ids = $projek->clone()->select('id');
        $tahun = collect([Bayaran::query()->whereIn('projek_id', $ids), Belanja::query()->whereIn('projek_id', $ids), BelanjaUmum::query()])
            ->flatMap(fn ($q) => $q->whereNotNull('tarikh')->pluck('tarikh'))
            ->map(fn ($t) => (int) $t->format('Y'))->push((int) now()->format('Y'));

        return $tahun->unique()->sortDesc()->values()->all();
    }

    /** Dijumlah dalam PHP — sama di SQLite dan PostgreSQL, dan jadual sebesar ini kecil. */
    private static function ikutBulan(Builder $q, int $tahun): array
    {
        $bulan = [];
        $tanpa = 0;
        foreach ($q->get(['tarikh', 'amaun']) as $r) {
            if (! $r->tarikh) {
                $tanpa++;

                continue;
            }
            if ((int) $r->tarikh->format('Y') === $tahun) {
                $m = (int) $r->tarikh->format('n');
                $bulan[$m] = ($bulan[$m] ?? 0) + Kiraan::sen($r->amaun);
            }
        }

        return ['bulan' => $bulan, 'tanpa_tarikh' => $tanpa];
    }
}
