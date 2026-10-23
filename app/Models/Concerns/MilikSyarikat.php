<?php

namespace App\Models\Concerns;

use App\Models\Syarikat;
use App\Support\SyarikatSemasa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Lapisan 1 pengasingan: setiap model yang ada syarikat_id guna trait ini.
 * Pertanyaan ditapis ke syarikat semasa, dan rekod baharu diisi syarikat_id secara automatik.
 */
trait MilikSyarikat
{
    public static function bootMilikSyarikat(): void
    {
        static::addGlobalScope(new SkopSyarikat);

        static::creating(function ($model) {
            $semasa = app(SyarikatSemasa::class);

            if ($model->syarikat_id === null) {
                $model->syarikat_id = $semasa->id()
                    ?? throw new RuntimeException('Tiada syarikat semasa untuk '.static::class);
            } elseif ($semasa->ada() && ! $semasa->sedangPintas() && (int) $model->syarikat_id !== $semasa->id()) {
                throw new RuntimeException('Cubaan mencipta '.static::class.' untuk syarikat lain.');
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('syarikat_id')) {
                throw new RuntimeException('syarikat_id tidak boleh ditukar.');
            }
        });
    }

    public function syarikat(): BelongsTo
    {
        return $this->belongsTo(Syarikat::class);
    }
}
