<?php

namespace App\Support;

use App\Models\Bayaran;
use App\Models\Projek;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Peraturan kiraan — dipindah TEPAT dari versi HTML + Supabase. Jangan ubah tanpa sebab kukuh.
 *
 *   diterima(projek) = Σ bayaran WHERE status != 'Menunggu'
 *   baki             = nilai − diterima
 *   Total Payment    = Σ nilai
 *   Total Received   = Σ diterima
 *   Pending Payment  = Total Payment − Total Received
 *   Deposit Payment  = Σ bayaran WHERE jenis ~ /deposit|pendahuluan|advance/i AND status != 'Menunggu'
 *   Baki terkini     = Σ diterima − Σ belanja
 *
 * Invarian: Total Payment = Total Received + Pending Payment.
 *
 * Semua amaun dikira dalam SEN (integer) supaya invarian tepat tanpa ralat titik apung.
 */
class Kiraan
{
    public const REGEX_DEPOSIT = '/deposit|pendahuluan|advance/i';

    /** Kategori status — faham Bahasa Melayu & Inggeris. */
    public static function jenisStatus(?string $s): string
    {
        $t = mb_strtolower((string) $s);

        if (preg_match('/siap|selesai|complete|completed|ended|done|closed|delivered|handover/', $t)) {
            return 'siap';
        }
        if (preg_match('/progres|progress|ongoing|running|berjalan|jalan|aktif|active|wip|dalam/', $t)) {
            return 'jalan';
        }

        return 'pending';
    }

    public static function sen(int|float|string|null $rm): int
    {
        return (int) round(((float) $rm) * 100);
    }

    /** "RM12,345.00"; negatif guna tanda tolak tipografi seperti versi HTML. */
    public static function rm(int $sen): string
    {
        return ($sen < 0 ? '−' : '').'RM'.number_format(abs($sen) / 100, 2);
    }

    /**
     * Projek beserta diterima_sen, belanja_sen, baki_sen untuk setiap baris.
     *
     * @param  Builder<Projek>|null  $projek  pertanyaan projek (sudah ditapis peranan); lalai semua
     * @return Collection<int, Projek>
     */
    public static function projekDenganJumlah(?Builder $projek = null): Collection
    {
        $senarai = ($projek ?? Projek::query())
            ->withSum(['bayaran as diterima_rm' => fn ($q) => $q->where('status', '!=', Bayaran::MENUNGGU)], 'amaun')
            ->withSum('belanja as belanja_rm', 'amaun')
            ->get();

        foreach ($senarai as $p) {
            $p->nilai_sen = self::sen($p->nilai);
            $p->diterima_sen = self::sen($p->diterima_rm);
            $p->belanja_sen = self::sen($p->belanja_rm);
            $p->baki_sen = $p->nilai_sen - $p->diterima_sen;
            $p->peratus = $p->nilai_sen > 0 ? min(100, (int) round($p->diterima_sen / $p->nilai_sen * 100)) : 0;
        }

        return $senarai;
    }

    /**
     * Angka Dashboard: 8 kotak + kad Baki Terkini.
     *
     * @param  Builder<Projek>|null  $projek
     * @return array<string, int>
     */
    public static function ringkasan(?Builder $projek = null): array
    {
        $senarai = self::projekDenganJumlah($projek);
        $ids = $senarai->modelKeys();

        $deposit = Bayaran::query()->whereIn('projek_id', $ids)->where('status', '!=', Bayaran::MENUNGGU)
            ->selectRaw('jenis, sum(amaun) as jumlah')->groupBy('jenis')->get()
            ->filter(fn ($r) => preg_match(self::REGEX_DEPOSIT, (string) $r->jenis))
            ->sum(fn ($r) => self::sen($r->jumlah));

        $menunggu = Bayaran::query()->whereIn('projek_id', $ids)->where('status', Bayaran::MENUNGGU)->count();

        $kat = $senarai->countBy(fn ($p) => self::jenisStatus($p->status));
        $jumlahNilai = $senarai->sum('nilai_sen');
        $diterima = $senarai->sum('diterima_sen');
        $belanja = $senarai->sum('belanja_sen');

        return [
            'projek' => $senarai->count(),
            'siap' => $kat['siap'] ?? 0,
            'jalan' => $kat['jalan'] ?? 0,
            'pending' => $kat['pending'] ?? 0,
            'jumlah_nilai' => $jumlahNilai,
            'diterima' => $diterima,
            'tertunggak' => $jumlahNilai - $diterima,
            'deposit' => $deposit,
            'belanja' => $belanja,
            'baki_terkini' => $diterima - $belanja,
            'bayaran_menunggu' => $menunggu,
        ];
    }
}
