<?php

namespace App\Models;

use App\Models\Concerns\DirekodOleh;
use App\Models\Concerns\MilikSyarikat;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('bayaran')]
#[Fillable(['projek_id', 'kerja_id', 'jenis', 'amaun', 'tarikh', 'kaedah', 'ruj', 'status'])]
class Bayaran extends Model
{
    use DirekodOleh, MilikSyarikat;

    public const DITERIMA = 'Diterima';

    public const MENUNGGU = 'Menunggu';

    protected function casts(): array
    {
        return ['amaun' => 'decimal:2', 'tarikh' => 'date', 'disahkan_pada' => 'datetime'];
    }

    public function projek(): BelongsTo
    {
        return $this->belongsTo(Projek::class);
    }

    public function kerja(): BelongsTo
    {
        return $this->belongsTo(Kerja::class);
    }

    public function menunggu(): bool
    {
        return $this->status === self::MENUNGGU;
    }

    public function sahkan(User $oleh): void
    {
        $this->forceFill(['status' => self::DITERIMA, 'disahkan_oleh' => $oleh->id, 'disahkan_pada' => now()])->save();
    }
}
