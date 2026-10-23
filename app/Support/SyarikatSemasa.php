<?php

namespace App\Support;

use App\Models\Syarikat;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Syarikat yang sedang dilayan oleh permintaan ini.
 *
 * Satu-satunya tempat app.syarikat_id ditetapkan dalam PostgreSQL, jadi scope
 * Eloquent (lapisan 1) dan RLS (lapisan 2) sentiasa menunjuk ke syarikat yang sama.
 */
class SyarikatSemasa
{
    private ?Syarikat $syarikat = null;

    private int $pintas = 0;

    public function tetapkan(?Syarikat $syarikat): void
    {
        $this->syarikat = $syarikat;
        $this->tetapkanPg('app.syarikat_id', $syarikat ? (string) $syarikat->getKey() : '');
    }

    public function ada(): bool
    {
        return $this->syarikat !== null;
    }

    public function get(): ?Syarikat
    {
        return $this->syarikat;
    }

    public function id(): ?int
    {
        return $this->syarikat?->getKey();
    }

    public function wajib(): Syarikat
    {
        return $this->syarikat ?? throw new RuntimeException('Tiada syarikat semasa — panggil SyarikatSemasa::tetapkan() dahulu.');
    }

    public function sedangPintas(): bool
    {
        return $this->pintas > 0;
    }

    /**
     * Jalankan $fn merentas semua syarikat (panel pentadbir, seeder, arahan konsol).
     * Jangan sesekali panggil dari laluan yang dilayan kepada pengguna syarikat.
     */
    public function tanpaSkop(callable $fn): mixed
    {
        if ($this->pintas++ === 0) {
            $this->tetapkanPg('app.pintas_rls', 'on');
        }

        try {
            return $fn();
        } finally {
            if (--$this->pintas === 0) {
                $this->tetapkanPg('app.pintas_rls', '');
            }
        }
    }

    /** Jalankan $fn sebagai syarikat lain, kemudian pulihkan syarikat asal. */
    public function sebagai(Syarikat $syarikat, callable $fn): mixed
    {
        $asal = $this->syarikat;
        $this->tetapkan($syarikat);

        try {
            return $fn();
        } finally {
            $this->tetapkan($asal);
        }
    }

    private function tetapkanPg(string $kunci, string $nilai): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::select('select set_config(?, ?, false)', [$kunci, $nilai]);
        }
    }
}
