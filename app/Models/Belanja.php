<?php

namespace App\Models;

use App\Models\Concerns\DirekodOleh;
use App\Models\Concerns\MilikSyarikat;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('belanja')]
#[Fillable(['projek_id', 'kategori', 'amaun', 'tarikh', 'penerima', 'nota', 'resit_path'])]
class Belanja extends Model
{
    use DirekodOleh, MilikSyarikat;

    protected function casts(): array
    {
        return ['amaun' => 'decimal:2', 'tarikh' => 'date'];
    }

    public function projek(): BelongsTo
    {
        return $this->belongsTo(Projek::class);
    }

    public function perekod(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicipta_oleh');
    }
}
