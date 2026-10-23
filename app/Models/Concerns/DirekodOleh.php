<?php

namespace App\Models\Concerns;

/**
 * dicipta_oleh diisi oleh pelayan dari pengguna yang log masuk — tidak boleh
 * dihantar dari borang (bukan fillable), jadi tak boleh dipalsukan.
 */
trait DirekodOleh
{
    public static function bootDirekodOleh(): void
    {
        static::creating(function ($model) {
            $model->dicipta_oleh ??= auth()->id();
        });
    }
}
