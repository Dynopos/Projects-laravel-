<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bayaran pelanggan kepada Dyno melalui CHIP — bukan bayaran projek (lihat Bayaran).
 * Tiada MilikSyarikat: bayaran daftar wujud sebelum syarikatnya dicipta.
 */
#[Table('pembayaran_langganan')]
#[Fillable(['syarikat_id', 'jenis', 'purchase_id', 'amaun_sen', 'status', 'butiran', 'dibayar_pada'])]
class PembayaranLangganan extends Model
{
    protected function casts(): array
    {
        return ['butiran' => 'array', 'dibayar_pada' => 'datetime'];
    }

    public function syarikat(): BelongsTo
    {
        return $this->belongsTo(Syarikat::class);
    }

    public function dibayar(): bool
    {
        return $this->status === 'dibayar';
    }

    /** Rujukan dalam CHIP, cth DAFTAR-12 / BAHARU-40. */
    public function rujukan(): string
    {
        return ($this->jenis === 'daftar' ? 'DAFTAR-' : 'BAHARU-').$this->id;
    }

    /**
     * Tanda untuk pautan /daftar/selesai — HMAC id dengan APP_KEY, jadi tiada token
     * perlu disimpan dan pautan yang sama boleh dihantar semula dalam e-mel.
     */
    public function tanda(): string
    {
        return hash_hmac('sha256', 'daftar:'.$this->id, (string) config('app.key'));
    }
}
