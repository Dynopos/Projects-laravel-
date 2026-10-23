<?php

namespace App\Models;

use App\Models\Concerns\DirekodOleh;
use App\Models\Concerns\MilikSyarikat;
use App\Support\Kiraan;
use App\Support\SyarikatSemasa;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('projek')]
#[Fillable(['kod', 'nama', 'klien', 'lokasi', 'telefon', 'inv', 'status', 'nilai', 'mula', 'nota', 'aliran'])]
class Projek extends Model
{
    use DirekodOleh, HasFactory, MilikSyarikat;

    protected static function booted(): void
    {
        static::updating(function (self $p) {
            $p->diubah_oleh = auth()->id() ?? $p->diubah_oleh;
        });
    }

    protected function casts(): array
    {
        return ['nilai' => 'decimal:2', 'mula' => 'date', 'aliran' => 'array'];
    }

    public function bayaran(): HasMany
    {
        return $this->hasMany(Bayaran::class);
    }

    public function belanja(): HasMany
    {
        return $this->hasMany(Belanja::class);
    }

    public function staf(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'penugasan');
    }

    public function tugaskan(User $user): void
    {
        $this->staf()->syncWithoutDetaching([$user->id => ['syarikat_id' => $this->syarikat_id]]);
    }

    /** "siap" | "jalan" | "pending" — lihat Kiraan::jenisStatus(). */
    public function jenisStatus(): string
    {
        return Kiraan::jenisStatus($this->status);
    }

    /** Projek yang pengguna ini boleh nampak dalam syarikat semasa. */
    public function scopeBolehDilihat(Builder $q, User $user): void
    {
        if (! $user->peranan()?->nampakSemua()) {
            $q->whereHas('staf', fn ($s) => $s->whereKey($user->id));
        }
    }

    /** Kod seterusnya: awalan syarikat + nombor terbesar sedia ada + 1, cth PRJ-09. */
    public static function kodSeterusnya(): string
    {
        $awalan = app(SyarikatSemasa::class)->wajib()->kod_awalan;
        $max = static::query()->where('kod', 'like', $awalan.'%')->pluck('kod')
            ->map(fn ($k) => (int) preg_replace('/\D/', '', substr($k, strlen($awalan))))
            ->max() ?? 0;

        return $awalan.str_pad((string) ($max + 1), 2, '0', STR_PAD_LEFT);
    }
}
