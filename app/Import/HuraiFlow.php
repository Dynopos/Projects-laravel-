<?php

namespace App\Import;

/**
 * Penghurai format "PROJECT FLOW" (fail pelanggan pertama, Fine Cabinetry).
 * Dipindah baris demi baris dari huraiFlow() / wangFC() / tkhFC() versi HTML.
 * JANGAN ubah tanpa ujian regresi (lihat tests/Feature/ImportTest.php):
 *   10 projek · 16 bayaran · RM184,193.00 nilai · RM83,861.00 diterima.
 *
 * Format bukan satu-baris-satu-rekod. Setiap projek 2–3 baris:
 *   A: No | Nama | Alamat | Tarikh Inv | Inv No | DONE… | Total Project(RM)
 *   B:    |      | Telefon|            | RM2,000| RM1,500 …amaun bertaburan…
 *   C:    |      |        |            |        | 10/08/26  ← tarikh bayaran
 * Lajur "Total Payment By Client(RM)" ialah rujukan muktamad jumlah diterima.
 */
class HuraiFlow
{
    /** Amaun wang; 0 jika bukan wang (termasuk sel tarikh dalam lajur wang). */
    public static function wang(?string $v): float
    {
        $t = trim((string) $v);
        if (! preg_match('/rm|^\d/i', $t)) {
            return 0;
        }
        if (preg_match('/^\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4}/', $t) || preg_match('/^\d{4}-\d{2}-\d{2}/', $t)) {
            return 0;   // tarikh, bukan wang
        }
        $n = preg_replace('/[^\d.]/', '', $t);

        return (float) $n;   // seperti parseFloat: "12.5.3" → 12.5, "" → 0
    }

    /** Tarikh Y-m-d. Teks dalam fail ialah M/D/YY (locale US); sel tarikh sebenar sudah Y-m-d. */
    public static function tarikh(?string $v): string
    {
        $t = trim((string) $v);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $t, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }
        if (! preg_match('/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{2,4})/', $t, $m)) {
            return '';
        }
        [, $a, $b, $c] = $m;
        if (strlen($c) === 2) {
            $c = '20'.$c;
        }
        [$bln, $hari] = [(int) $a, (int) $b];
        if ($bln > 12) {
            [$bln, $hari] = [$hari, $bln];
        }
        if ($bln > 12 || $hari > 31) {
            return '';
        }

        return sprintf('%s-%02d-%02d', $c, $bln, $hari);
    }

    /**
     * @param  list<list<string>>  $A  baris helaian
     */
    public static function hurai(array $A, string $awalan, string $helaian = ''): ?HasilImport
    {
        $sel = fn (array $r, int $i): string => $i >= 0 ? trim((string) ($r[$i] ?? '')) : '';

        $hRow = -1;
        foreach ($A as $i => $r) {
            if (strtolower($sel($r, 0)) === 'no.' && collect($r)->contains(fn ($c) => preg_match('/total project/i', (string) $c))) {
                $hRow = $i;
                break;
            }
        }
        if ($hRow < 0) {
            return null;
        }

        $H = array_map(fn ($x) => trim((string) $x), $A[$hRow]);
        $idx = function (string $n) use ($H): int {
            foreach ($H as $i => $h) {
                if (str_starts_with(strtolower($h), strtolower($n))) {
                    return $i;
                }
            }

            return -1;
        };
        $C = ['no' => 0, 'nama' => 1, 'alamat' => 2, 'tinv' => $idx('Date Invoice'), 'inv' => $idx('Inv No'),
            'dep' => $idx('Deposit'), 'lukis' => $idx('Drawing'), 'pasang' => $idx('Date Installation'),
            'baki' => $idx('Balance Payment'), 'terima' => $idx('Total Payment By Client'),
            'nilai' => $idx('Total Project'), 'nota' => $idx('Remarks')];
        if ($C['nilai'] < 0 || $C['terima'] < 0) {
            return null;
        }

        $baris = fn (int $i) => $A[$i] ?? [];
        $tamat = fn (array $r) => preg_match('/^total$/i', $sel($r, $C['terima'] - 1));
        $projek = [];
        $bayaran = [];
        $n = 0;

        for ($i = $hRow + 1; $i < count($A); $i++) {
            $r = $baris($i);
            $noStr = $sel($r, $C['no']);
            $nama = $sel($r, $C['nama']);
            if ($tamat($r) || preg_match('/sales\s+(january|jan)\b/i', $nama)) {
                break;
            }
            if (! preg_match('/^\d+$/', $noStr) || $nama === '') {
                continue;
            }
            $n++;
            $nilai = self::wang($sel($r, $C['nilai']));
            $alamat = $sel($r, $C['alamat']);
            $invNo = preg_replace('/^no:\s*/i', '', $sel($r, $C['inv']));
            $kod = $awalan.str_pad((string) $n, 2, '0', STR_PAD_LEFT);

            $butir = [];
            for ($j = $i + 1; $j < count($A); $j++) {
                $rr = $baris($j);
                if ((preg_match('/^\d+$/', $sel($rr, $C['no'])) && $sel($rr, $C['nama']) !== '') || $tamat($rr)) {
                    break;
                }
                $butir[] = $rr;
            }

            $telefon = collect($butir)->map(fn ($b) => $sel($b, $C['alamat']))->first(fn ($x) => preg_match('/\d{3}/', $x)) ?? '';
            $terimaJum = collect($butir)->map(fn ($b) => self::wang($sel($b, $C['terima'])))->first(fn ($x) => $x > 0) ?? 0;

            $lajur = [[$C['inv'], 'Bayaran'], [$C['dep'], 'Deposit'], [$C['lukis'], 'Bayaran Lukisan'],
                [$C['pasang'], 'Bayaran Pemasangan'], [$C['baki'], 'Baki Akhir']];
            $calon = [];
            foreach ($butir as $b) {
                foreach ($lajur as [$c, $jenis]) {
                    $a = self::wang($sel($b, $c));
                    if ($a > 0) {
                        $calon[] = ['jenis' => $jenis, 'amaun' => $a];
                    }
                }
            }
            // Amaun pendua (nilai sama dalam dua lajur) — nyahduplikasi ikut RM terdekat.
            $nampak = [];
            $calon = array_values(array_filter($calon, function ($x) use (&$nampak) {
                $k = (int) round($x['amaun']);
                if (isset($nampak[$k])) {
                    return false;
                }

                return $nampak[$k] = true;
            }));

            $tDep = collect($butir)->map(fn ($b) => self::tarikh($sel($b, $C['dep'])))->first(fn ($x) => $x !== '') ?: self::tarikh($sel($r, $C['tinv']));
            $tBaki = self::tarikh($sel($r, $C['baki'])) ?: (self::tarikh($sel($r, $C['pasang'])) ?: $tDep);

            // Rekonsiliasi dengan lajur Total Payment By Client.
            $jum = array_sum(array_column($calon, 'amaun'));
            if ($terimaJum > 0 && abs($jum - $terimaJum) > 1) {
                if ($jum > $terimaJum) {
                    $calon = [['jenis' => 'Bayaran Diterima', 'amaun' => $terimaJum]];
                } else {
                    $calon[] = ['jenis' => 'Bayaran Lain', 'amaun' => round($terimaJum - $jum, 2)];
                }
            }
            if (! $calon && $terimaJum > 0) {
                $calon = [['jenis' => 'Bayaran Diterima', 'amaun' => $terimaJum]];
            }
            if (count($calon) === 1 && $terimaJum > 0 && abs($calon[0]['amaun'] - $terimaJum) <= max(1, $terimaJum * 0.01)) {
                $calon[0]['amaun'] = $terimaJum;
            }

            foreach ($calon as $x) {
                $bayaran[] = ['kod' => $kod, 'jenis' => $x['jenis'], 'amaun' => $x['amaun'],
                    'tarikh' => preg_match('/baki|pemasangan/i', $x['jenis']) ? ($tBaki ?: $tDep) : $tDep,
                    'ruj' => $invNo];
            }

            $diterima = array_sum(array_column($calon, 'amaun'));
            $done = fn (int $c) => (bool) preg_match('/done/i', $sel($r, $c));
            $projek[] = [
                'kod' => $kod, 'nama' => $nama, 'klien' => $nama, 'lokasi' => $alamat, 'telefon' => $telefon,
                'status' => ($nilai > 0 && $diterima >= $nilai - 1) ? 'Siap' : ($diterima > 0 ? 'Dalam Progres' : 'Baru'),
                'nilai' => $nilai, 'mula' => self::tarikh($sel($r, $C['tinv'])), 'inv' => $invNo,
                'nota' => $sel($r, $C['nota']),
                'aliran' => [
                    'deposit' => $done($C['dep']), 'lukisan' => $done($C['lukis']), 'gambarSblm' => $done($C['dep'] + 2),
                    'pasang' => self::tarikh($sel($r, $C['pasang'])) !== '', 'baki' => self::tarikh($sel($r, $C['baki'])) !== '',
                    'gambarSlps' => $done($C['baki'] + 1), 'maklumBalas' => $done($C['baki'] + 2), 'socmed' => $done($C['nilai'] - 1),
                ],
            ];
            $i += count($butir);
        }

        return $projek ? new HasilImport('flow', $helaian, $projek, $bayaran) : null;
    }
}
