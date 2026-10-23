<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Peranan;
use App\Support\SyarikatSemasa;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @var array<int, Keahlian|null> cache peranan setiap syarikat untuk permintaan ini */
    private array $keahlianCache = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function keahlian(): HasMany
    {
        return $this->hasMany(Keahlian::class);
    }

    public function syarikat(): BelongsToMany
    {
        return $this->belongsToMany(Syarikat::class, 'keahlian')->withPivot('peranan', 'aktif')->wherePivot('aktif', true);
    }

    public function keahlianDi(?Syarikat $syarikat): ?Keahlian
    {
        if (! $syarikat) {
            return null;
        }

        return $this->keahlianCache[$syarikat->id] ??= $this->keahlian()
            ->where('syarikat_id', $syarikat->id)->where('aktif', true)->first();
    }

    /** Peranan dalam syarikat semasa; null = bukan ahli (atau tiada syarikat). */
    public function peranan(): ?Peranan
    {
        return $this->keahlianDi(app(SyarikatSemasa::class)->get())?->peranan;
    }

    public function ahliSyarikatSemasa(): bool
    {
        return $this->peranan() !== null;
    }
}
