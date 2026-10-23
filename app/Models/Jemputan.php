<?php

namespace App\Models;

use App\Enums\Peranan;
use App\Models\Concerns\MilikSyarikat;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Jemputan menyertai syarikat. Tiada daftar sendiri — satu-satunya cara akaun
 * baharu wujud ialah melalui pautan dalam e-mel ini.
 *
 * Token dalam pangkalan data ialah hash SHA-256; token asal hanya ada dalam
 * e-mel. Kebocoran jadual ini tidak membocorkan pautan yang boleh digunakan.
 */
#[Table('jemputan')]
#[Fillable(['email', 'peranan'])]
class Jemputan extends Model
{
    use MilikSyarikat;

    public const TEMPOH_HARI = 7;

    protected function casts(): array
    {
        return ['peranan' => Peranan::class, 'tamat_pada' => 'datetime', 'diterima_pada' => 'datetime'];
    }

    /**
     * Cipta jemputan (atau ganti jemputan lama untuk e-mel yang sama) dan
     * pulangkan token asal untuk dihantar dalam e-mel.
     *
     * @return array{0: self, 1: string}
     */
    public static function terbitkan(string $email, Peranan $peranan, User $oleh): array
    {
        $token = Str::random(48);
        $email = Str::lower(trim($email));

        static::query()->belumDiterima()->where('email', $email)->delete();

        $j = new static(['email' => $email, 'peranan' => $peranan]);
        $j->forceFill([
            'token' => hash('sha256', $token),
            'dijemput_oleh' => $oleh->id,
            'tamat_pada' => now()->addDays(self::TEMPOH_HARI),
        ])->save();

        return [$j, $token];
    }

    public static function cariToken(string $token): ?self
    {
        return static::query()->where('token', hash('sha256', $token))->first();
    }

    public function scopeBelumDiterima(Builder $q): void
    {
        $q->whereNull('diterima_pada');
    }

    public function sah(): bool
    {
        return $this->diterima_pada === null && $this->tamat_pada->isFuture();
    }

    public function penjemput(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dijemput_oleh');
    }
}
