<?php

namespace App\Models;

use App\Enums\Peranan;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Tiada MilikSyarikat: keahlian dibaca sebelum syarikat diketahui (log masuk).
#[Table('keahlian')]
#[Fillable(['syarikat_id', 'user_id', 'peranan', 'aktif'])]
class Keahlian extends Model
{
    protected function casts(): array
    {
        return ['peranan' => Peranan::class, 'aktif' => 'boolean'];
    }

    public function syarikat(): BelongsTo
    {
        return $this->belongsTo(Syarikat::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
