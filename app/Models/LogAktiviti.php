<?php

namespace App\Models;

use App\Models\Concerns\MilikSyarikat;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/** Tambah & baca sahaja — tiada sesiapa, termasuk Pemilik, boleh ubah atau padam. */
#[Table('log_aktiviti')]
#[Fillable(['tindakan', 'butiran'])]
class LogAktiviti extends Model
{
    use MilikSyarikat;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::creating(function (self $log) {
            $log->user_id ??= auth()->id();
            $log->ip ??= request()?->ip();
        });
        static::updating(fn () => throw new LogicException('log_aktiviti tidak boleh diubah'));
        static::deleting(fn () => throw new LogicException('log_aktiviti tidak boleh dipadam'));
    }

    public static function catat(string $tindakan, string $butiran = ''): self
    {
        return static::create(['tindakan' => $tindakan, 'butiran' => $butiran]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
