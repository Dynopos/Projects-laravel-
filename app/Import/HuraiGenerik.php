<?php

namespace App\Import;

/**
 * Penghurai generik: baris pertama ialah tajuk lajur, satu baris satu projek.
 * Lajur dipadan ikut KAMUS (BM & Inggeris). Dipindah dari padan()/importSekarang().
 */
class HuraiGenerik
{
    public const KAMUS = [
        'kod' => ['kod', 'code', 'no.', 'no ', 'bil', 'id', 'rujukan projek'],
        'nama' => ['projek', 'project', 'nama', 'kerja', 'perkara', 'description', 'tajuk'],
        'klien' => ['klien', 'client', 'pelanggan', 'customer', 'syarikat', 'company'],
        'pic' => ['pic', 'pegawai', 'incharge', 'in charge', 'petugas', 'owner', 'sales'],
        'status' => ['status', 'keadaan', 'peringkat'],
        'nilai' => ['nilai', 'kontrak', 'contract', 'harga', 'price', 'jumlah kontrak', 'total', 'amount', 'sebut harga', 'quotation', 'value'],
        'deposit' => ['deposit', 'pendahuluan', 'advance', 'downpayment', 'down payment', 'bayaran pertama'],
        'bayar' => ['bayaran', 'diterima', 'dibayar', 'paid', 'received', 'kutipan', 'terima'],
        'tarikh' => ['tarikh', 'date', 'mula', 'start'],
    ];

    /**
     * @param  list<string>  $tajuk
     * @return array<string, int> medan => indeks lajur
     */
    public static function padan(array $tajuk): array
    {
        $peta = [];
        foreach (self::KAMUS as $medan => $kata) {
            foreach ($tajuk as $i => $k) {
                $t = mb_strtolower(trim((string) $k));
                if ($t !== '' && ! in_array($i, $peta, true) && collect($kata)->contains(fn ($x) => str_contains($t, $x))) {
                    $peta[$medan] = $i;
                    break;
                }
            }
        }

        return $peta;
    }

    public static function angka(?string $v): float
    {
        $n = preg_replace('/[^\d.\-]/', '', (string) $v);

        return is_numeric($n) ? (float) $n : 0.0;
    }

    /** Y-m-d dari Y-m-d, D/M/YYYY (Malaysia) atau nombor siri Excel. */
    public static function tarikh(?string $v): string
    {
        $s = trim((string) $v);
        if ($s === '') {
            return '';
        }
        if (is_numeric($s) && $s > 20000 && $s < 60000) {
            return gmdate('Y-m-d', (int) round(((float) $s - 25569) * 86400));
        }
        if (preg_match('/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})/', $s, $m)) {
            return sprintf('%s-%02d-%02d', $m[1], $m[2], $m[3]);
        }
        if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})/', $s, $m)) {
            return checkdate((int) $m[2], (int) $m[1], (int) $m[3]) ? sprintf('%s-%02d-%02d', $m[3], $m[2], $m[1]) : '';
        }
        $t = strtotime($s);

        return $t ? date('Y-m-d', $t) : '';
    }

    /**
     * @param  list<list<string>>  $A
     * @param  callable(int): string  $kodBaharu  kod untuk baris ke-n yang TIADA kod (n = 1, 2, …)
     */
    public static function hurai(array $A, callable $kodBaharu, string $helaian = ''): ?HasilImport
    {
        // Tajuk = baris pertama yang tidak kosong.
        while ($A && trim(implode('', $A[0])) === '') {
            array_shift($A);
        }
        if (count($A) < 2) {
            return null;
        }
        $tajuk = array_shift($A);
        $peta = self::padan($tajuk);
        if (! isset($peta['nama']) && ! isset($peta['kod'])) {
            return null;
        }

        $sel = fn (array $r, string $m): string => isset($peta[$m]) ? trim((string) ($r[$peta[$m]] ?? '')) : '';
        $projek = [];
        $bayaran = [];
        $tanpaKod = 0;
        foreach ($A as $r) {
            $nama = $sel($r, 'nama');
            $kodRaw = $sel($r, 'kod');
            if ($nama === '' && $kodRaw === '') {
                continue;
            }
            $kod = $kodRaw !== '' ? mb_substr($kodRaw, 0, 30) : $kodBaharu(++$tanpaKod);
            $tar = self::tarikh($sel($r, 'tarikh'));
            $projek[] = ['kod' => $kod, 'nama' => $nama ?: $kod, 'klien' => $sel($r, 'klien'), 'lokasi' => '', 'telefon' => '',
                'status' => $sel($r, 'status') ?: 'Dalam Progres', 'nilai' => self::angka($sel($r, 'nilai')),
                'mula' => $tar, 'inv' => '', 'nota' => $sel($r, 'pic') ? 'PIC: '.$sel($r, 'pic') : '', 'aliran' => []];

            $dep = self::angka($sel($r, 'deposit'));
            if ($dep > 0) {
                $bayaran[] = ['kod' => $kod, 'jenis' => 'Deposit', 'amaun' => $dep, 'tarikh' => $tar, 'ruj' => ''];
            }
            $byr = self::angka($sel($r, 'bayar'));
            if ($byr > 0 && $byr != $dep) {
                $bayaran[] = ['kod' => $kod, 'jenis' => 'Bayaran', 'amaun' => $byr, 'tarikh' => $tar, 'ruj' => ''];
            }
        }

        return $projek ? new HasilImport('generik', $helaian, $projek, $bayaran,
            array_map(fn ($i) => (string) $tajuk[$i], $peta)) : null;
    }
}
