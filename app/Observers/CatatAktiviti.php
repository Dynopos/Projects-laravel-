<?php

namespace App\Observers;

use App\Models\Bayaran;
use App\Models\Belanja;
use App\Models\BelanjaUmum;
use App\Models\Kerja;
use App\Models\LogAktiviti;
use App\Models\Projek;
use App\Support\Kiraan;
use Illuminate\Database\Eloquent\Model;

/** Setiap perubahan pada projek, bayaran, belanja dan belanja umum masuk log aktiviti secara automatik. */
class CatatAktiviti
{
    private static int $senyap = 0;

    /** Jalankan $fn tanpa log setiap rekod (cth import pukal — satu log ringkasan sahaja). */
    public static function senyap(callable $fn): mixed
    {
        self::$senyap++;
        try {
            return $fn();
        } finally {
            self::$senyap--;
        }
    }

    public function created(Model $m): void
    {
        if (self::$senyap) {
            return;
        }
        LogAktiviti::catat('Tambah '.$this->jenis($m), $this->ringkas($m));
    }

    public function updated(Model $m): void
    {
        if (self::$senyap) {
            return;
        }
        $ubah = collect($m->getChanges())->except(['updated_at', 'diubah_oleh'])->keys()->implode(', ');
        if ($ubah !== '') {
            LogAktiviti::catat('Ubah '.$this->jenis($m), $this->ringkas($m)." ({$ubah})");
        }
    }

    public function deleted(Model $m): void
    {
        if (self::$senyap) {
            return;
        }
        LogAktiviti::catat('Padam '.$this->jenis($m), $this->ringkas($m));
    }

    private function jenis(Model $m): string
    {
        return match (true) {
            $m instanceof Projek => 'projek',
            $m instanceof Bayaran => 'bayaran',
            $m instanceof Belanja => 'perbelanjaan',
            $m instanceof BelanjaUmum => 'perbelanjaan umum',
            $m instanceof Kerja => 'kerja',
            default => class_basename($m),
        };
    }

    private function ringkas(Model $m): string
    {
        return match (true) {
            $m instanceof Projek => "{$m->kod} — {$m->nama}",
            $m instanceof Bayaran => ($m->projek?->kod ?? '?')." · {$m->jenis} · ".Kiraan::rm(Kiraan::sen($m->amaun))." · {$m->status}",
            $m instanceof Belanja => ($m->projek?->kod ?? '?')." · {$m->kategori} · ".Kiraan::rm(Kiraan::sen($m->amaun)),
            $m instanceof BelanjaUmum => "Umum · {$m->kategori} · ".Kiraan::rm(Kiraan::sen($m->amaun)),
            $m instanceof Kerja => ($m->projek?->kod ?? '?')." · {$m->nama} · ".Kiraan::rm(Kiraan::sen($m->nilai)),
            default => (string) $m->getKey(),
        };
    }
}
