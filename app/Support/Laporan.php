<?php

namespace App\Support;

use App\Models\Bayaran;
use App\Models\Belanja;
use App\Models\Projek;
use App\Models\User;

/**
 * Data untuk eksport CSV / PDF. Tapisan sama dengan halaman yang dieksport
 * (SenaraiProjek, SenaraiBayaran, SenaraiBelanja), supaya fail = apa yang dilihat.
 * Amaun dalam SEN; diformat oleh penulis CSV / PDF.
 *
 * @phpstan-type Laporan array{tajuk: string, tapisan: string, lajur: list<array{label: string, jenis: string}>, baris: list<list<mixed>>, jumlah: list<mixed>|null}
 */
class Laporan
{
    public const JENIS = ['projek', 'arkib', 'bayaran', 'belanja'];

    /** @param array<string, string> $t tapisan dari query string */
    public static function bina(string $jenis, User $user, array $t): array
    {
        return match ($jenis) {
            'projek' => self::projek($user, $t, false),
            'arkib' => self::projek($user, $t, true),
            'bayaran' => self::bayaran($user, $t),
            'belanja' => self::belanja($user, $t),
        };
    }

    private static function projek(User $user, array $t, bool $arkib): array
    {
        $q = mb_strtolower(trim($t['q'] ?? ''));
        $kat = $t['kat'] ?? '';
        $rows = Kiraan::projekDenganJumlah(Projek::query()->bolehDilihat($user))
            ->filter(fn ($p) => $arkib ? $p->jenisStatus() === 'siap' : $p->jenisStatus() !== 'siap')
            ->filter(fn ($p) => $kat === '' || $p->jenisStatus() === $kat)
            ->filter(fn ($p) => $q === '' || collect([$p->kod, $p->nama, $p->klien, $p->lokasi, $p->telefon, $p->inv])
                ->contains(fn ($x) => str_contains(mb_strtolower((string) $x), $q)))
            ->sort(fn ($a, $b) => match ($t['susun'] ?? 'kod') {
                'baki' => $b->baki_sen <=> $a->baki_sen,
                'nilai' => $b->nilai_sen <=> $a->nilai_sen,
                'pct' => $b->peratus <=> $a->peratus,
                default => strnatcmp($a->kod, $b->kod),
            })->values();

        return [
            'tajuk' => $arkib ? 'Projek Siap' : 'Projek Aktif',
            'tapisan' => self::teksTapisan(['Carian' => $t['q'] ?? '', 'Kategori' => ['jalan' => 'Dalam progres', 'pending' => 'Baru / tertunda', 'siap' => 'Siap'][$kat] ?? '']),
            'lajur' => [
                ['label' => 'Kod', 'jenis' => 'teks'], ['label' => 'No. Invois', 'jenis' => 'teks'],
                ['label' => 'Projek', 'jenis' => 'teks'], ['label' => 'Klien', 'jenis' => 'teks'],
                ['label' => 'Lokasi', 'jenis' => 'teks'], ['label' => 'Telefon', 'jenis' => 'teks'],
                ['label' => 'Status', 'jenis' => 'teks'], ['label' => 'Nilai Kontrak', 'jenis' => 'wang'],
                ['label' => 'Diterima', 'jenis' => 'wang'], ['label' => 'Baki', 'jenis' => 'wang'],
                ['label' => '% Kutipan', 'jenis' => 'peratus'], ['label' => 'Perbelanjaan', 'jenis' => 'wang'],
            ],
            'baris' => $rows->map(fn ($p) => [$p->kod, $p->inv, $p->nama, $p->klien, $p->lokasi, $p->telefon, $p->status,
                $p->nilai_sen, $p->diterima_sen, $p->baki_sen, $p->peratus, $p->belanja_sen])->all(),
            'jumlah' => $rows->isEmpty() ? null : ['JUMLAH · '.$rows->count().' projek', '', '', '', '', '', '',
                $rows->sum('nilai_sen'), $rows->sum('diterima_sen'), $rows->sum('baki_sen'), '', $rows->sum('belanja_sen')],
        ];
    }

    private static function bayaran(User $user, array $t): array
    {
        $projek = $t['projek'] ?? '';
        $status = in_array($t['status'] ?? '', [Bayaran::DITERIMA, Bayaran::MENUNGGU], true) ? $t['status'] : '';
        $deposit = ($t['jenis'] ?? '') === 'deposit';

        $rows = Bayaran::query()->whereIn('projek_id', Projek::query()->bolehDilihat($user)->select('id'))
            ->when($projek !== '', fn ($q) => $q->where('projek_id', (int) $projek))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->with('projek:id,kod,nama')->orderByRaw('tarikh is null, tarikh desc')->orderByDesc('id')->get()
            ->when($deposit, fn ($c) => $c->filter(fn ($b) => preg_match(Kiraan::REGEX_DEPOSIT, (string) $b->jenis)))
            ->values();

        return [
            'tajuk' => 'Pembayaran',
            'tapisan' => self::teksTapisan(['Projek' => self::namaProjek($projek), 'Status' => $status, 'Jenis' => $deposit ? 'Deposit sahaja' : '']),
            'lajur' => [
                ['label' => 'Tarikh', 'jenis' => 'tarikh'], ['label' => 'Kod Projek', 'jenis' => 'teks'],
                ['label' => 'Projek', 'jenis' => 'teks'], ['label' => 'Jenis', 'jenis' => 'teks'],
                ['label' => 'Kaedah', 'jenis' => 'teks'], ['label' => 'Rujukan', 'jenis' => 'teks'],
                ['label' => 'Status', 'jenis' => 'teks'], ['label' => 'Amaun', 'jenis' => 'wang'],
            ],
            'baris' => $rows->map(fn ($b) => [$b->tarikh?->toDateString(), $b->projek?->kod, $b->projek?->nama, $b->jenis,
                $b->kaedah, $b->ruj, $b->status, Kiraan::sen($b->amaun)])->all(),
            'jumlah' => $rows->isEmpty() ? null : ['JUMLAH · '.$rows->count().' bayaran', '', '', '', '', '', '',
                $rows->sum(fn ($b) => Kiraan::sen($b->amaun))],
        ];
    }

    private static function belanja(User $user, array $t): array
    {
        $projek = $t['projek'] ?? '';
        $kategori = $t['kategori'] ?? '';

        $rows = Belanja::query()->whereIn('projek_id', Projek::query()->bolehDilihat($user)->select('id'))
            ->when($projek !== '', fn ($q) => $q->where('projek_id', (int) $projek))
            ->when($kategori !== '', fn ($q) => $q->where('kategori', $kategori))
            ->with(['projek:id,kod,nama', 'perekod:id,name'])->orderByRaw('tarikh is null, tarikh desc')->orderByDesc('id')->get();

        return [
            'tajuk' => 'Perbelanjaan',
            'tapisan' => self::teksTapisan(['Projek' => self::namaProjek($projek), 'Kategori' => $kategori]),
            'lajur' => [
                ['label' => 'Tarikh', 'jenis' => 'tarikh'], ['label' => 'Kod Projek', 'jenis' => 'teks'],
                ['label' => 'Projek', 'jenis' => 'teks'], ['label' => 'Kategori', 'jenis' => 'teks'],
                ['label' => 'Penerima', 'jenis' => 'teks'], ['label' => 'Nota', 'jenis' => 'teks'],
                ['label' => 'Direkod oleh', 'jenis' => 'teks'], ['label' => 'Amaun', 'jenis' => 'wang'],
            ],
            'baris' => $rows->map(fn ($x) => [$x->tarikh?->toDateString(), $x->projek?->kod, $x->projek?->nama, $x->kategori,
                $x->penerima, $x->nota, $x->perekod?->name, Kiraan::sen($x->amaun)])->all(),
            'jumlah' => $rows->isEmpty() ? null : ['JUMLAH · '.$rows->count().' rekod', '', '', '', '', '', '',
                $rows->sum(fn ($x) => Kiraan::sen($x->amaun))],
        ];
    }

    private static function namaProjek(string $id): string
    {
        if ($id === '') {
            return '';
        }
        $p = Projek::find((int) $id);

        return $p ? "{$p->kod} — {$p->nama}" : '';
    }

    /** @param array<string, string> $t */
    private static function teksTapisan(array $t): string
    {
        $ada = array_filter($t, fn ($v) => $v !== '');

        return $ada ? implode(' · ', array_map(fn ($k, $v) => "{$k}: {$v}", array_keys($ada), $ada)) : 'Semua rekod';
    }
}
