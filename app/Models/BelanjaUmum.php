<?php

namespace App\Models;

use App\Models\Concerns\DirekodOleh;
use App\Models\Concerns\MilikSyarikat;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Perbelanjaan syarikat yang bukan milik mana-mana projek (overhead).
 * Rekod/padam: pemilik & pengurus. Lihat: + akauntan. Staf: tiada akses.
 */
#[Table('belanja_umum')]
#[Fillable(['kategori', 'amaun', 'tarikh', 'penerima', 'nota'])]
class BelanjaUmum extends Model
{
    use DirekodOleh, MilikSyarikat;

    protected function casts(): array
    {
        return ['amaun' => 'decimal:2', 'tarikh' => 'date'];
    }

    public function perekod(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicipta_oleh');
    }
}
