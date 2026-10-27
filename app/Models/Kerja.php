<?php

namespace App\Models;

use App\Models\Concerns\DirekodOleh;
use App\Models\Concerns\MilikSyarikat;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kerja kecil di bawah projek payung. Setiap kerja ada nilai sendiri; bayaran &
 * perbelanjaan boleh diikat padanya (kerja_id) supaya untung setiap kerja kelihatan.
 * Nilai kontrak projek = Σ nilai kerja, dikemas kini setiap kali kerja berubah.
 */
#[Table('kerja')]
#[Fillable(['nama', 'nilai', 'tarikh', 'siap', 'nota'])]
class Kerja extends Model
{
    use DirekodOleh, MilikSyarikat;

    protected static function booted(): void
    {
        static::saved(fn (self $k) => $k->projek->kemasKiniNilai());
        static::deleting(function (self $k) {
            // SQLite tiada FK pada kerja_id — lepaskan rekod secara manual (PostgreSQL: on delete set null).
            Bayaran::where('kerja_id', $k->id)->update(['kerja_id' => null]);
            Belanja::where('kerja_id', $k->id)->update(['kerja_id' => null]);
        });
        static::deleted(fn (self $k) => $k->projek->kemasKiniNilai());
    }

    protected function casts(): array
    {
        return ['nilai' => 'decimal:2', 'tarikh' => 'date', 'siap' => 'boolean'];
    }

    public function projek(): BelongsTo
    {
        return $this->belongsTo(Projek::class);
    }

    public function bayaran(): HasMany
    {
        return $this->hasMany(Bayaran::class);
    }

    public function belanja(): HasMany
    {
        return $this->hasMany(Belanja::class);
    }
}
