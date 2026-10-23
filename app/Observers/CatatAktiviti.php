<?php

namespace App\Observers;

use App\Models\Bayaran;
use App\Models\Belanja;
use App\Models\LogAktiviti;
use App\Models\Projek;
use App\Support\Kiraan;
use Illuminate\Database\Eloquent\Model;

/** Setiap perubahan pada projek, bayaran dan belanja masuk log aktiviti secara automatik. */
class CatatAktiviti
{
    public function created(Model $m): void
    {
        LogAktiviti::catat('Tambah '.$this->jenis($m), $this->ringkas($m));
    }

    public function updated(Model $m): void
    {
        $ubah = collect($m->getChanges())->except(['updated_at', 'diubah_oleh'])->keys()->implode(', ');
        if ($ubah !== '') {
            LogAktiviti::catat('Ubah '.$this->jenis($m), $this->ringkas($m)." ({$ubah})");
        }
    }

    public function deleted(Model $m): void
    {
        LogAktiviti::catat('Padam '.$this->jenis($m), $this->ringkas($m));
    }

    private function jenis(Model $m): string
    {
        return match (true) {
            $m instanceof Projek => 'projek',
            $m instanceof Bayaran => 'bayaran',
            $m instanceof Belanja => 'perbelanjaan',
            default => class_basename($m),
        };
    }

    private function ringkas(Model $m): string
    {
        return match (true) {
            $m instanceof Projek => "{$m->kod} — {$m->nama}",
            $m instanceof Bayaran => ($m->projek?->kod ?? '?')." · {$m->jenis} · ".Kiraan::rm(Kiraan::sen($m->amaun))." · {$m->status}",
            $m instanceof Belanja => ($m->projek?->kod ?? '?')." · {$m->kategori} · ".Kiraan::rm(Kiraan::sen($m->amaun)),
            default => (string) $m->getKey(),
        };
    }
}
