<?php

namespace App\Http\Controllers;

use App\Models\LogAktiviti;
use App\Support\Kiraan;
use App\Support\Laporan;
use App\Support\SyarikatSemasa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Eksport CSV / PDF — pemilik, pengurus, akauntan (bukan staf). Setiap eksport dilog (PDPA). */
class EksportController extends Controller
{
    public function __invoke(Request $request, SyarikatSemasa $semasa, string $jenis, string $format)
    {
        abort_unless(in_array($jenis, Laporan::JENIS, true) && in_array($format, ['csv', 'pdf'], true), 404);
        abort_unless($request->user()->peranan()?->bolehEksport(), 403);

        $tapisan = collect($request->only(['q', 'kat', 'susun', 'projek', 'status', 'jenis', 'kategori', 'tahun']))
            ->map(fn ($v) => is_string($v) ? mb_substr($v, 0, 100) : '')->all();
        $l = Laporan::bina($jenis, $request->user(), $tapisan);
        $s = $semasa->wajib();
        $nama = "{$s->subdomain}-{$jenis}-".now($s->zon_waktu)->format('Ymd-Hi');

        LogAktiviti::catat('Eksport '.strtoupper($format), "{$l['tajuk']} · ".count($l['baris'])." baris · {$l['tapisan']}");

        if ($format === 'csv') {
            return $this->csv($l, "{$nama}.csv");
        }

        // Subset fon: hanya aksara yang digunakan dibenam — PDF ~50 KB, bukan ~1 MB.
        return Pdf::setOption(['isFontSubsettingEnabled' => true])->loadView('eksport.laporan', ['l' => $l, 'syarikat' => $s, 'oleh' => $request->user(),
            'masa' => now($s->zon_waktu)])->setPaper('a4', 'landscape')->download("{$nama}.pdf");
    }

    private function csv(array $l, string $fail): StreamedResponse
    {
        return response()->streamDownload(function () use ($l) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");   // BOM — Excel baca UTF-8 dengan betul
            fputcsv($out, array_column($l['lajur'], 'label'), escape: '');
            foreach ([...$l['baris'], ...($l['jumlah'] ? [$l['jumlah']] : [])] as $baris) {
                fputcsv($out, array_map(fn ($v, $i) => $this->selCsv($v, $l['lajur'][$i]['jenis']), $baris, array_keys($baris)), escape: '');
            }
            fclose($out);
        }, $fail, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function selCsv(mixed $v, string $jenis): string
    {
        if ($jenis === 'wang' && is_int($v)) {
            return number_format($v / 100, 2, '.', '');   // nombor biasa supaya Excel boleh jumlahkan
        }
        $t = (string) ($v ?? '');

        // Suntikan formula CSV: nama klien "=HYPERLINK(...)" tidak boleh jadi formula dalam Excel.
        return preg_match('/^[=+\-@\t\r]/', $t) ? "'".$t : $t;
    }

    /** Dipanggil dari templat PDF. */
    public static function selPdf(mixed $v, string $jenis): string
    {
        return match (true) {
            $jenis === 'wang' && is_int($v) => Kiraan::rm($v),
            $jenis === 'peratus' && $v !== '' => $v.'%',
            $jenis === 'tarikh' && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string) $v, $m) => "{$m[3]}/{$m[2]}/{$m[1]}",
            default => (string) ($v ?? ''),
        };
    }
}
