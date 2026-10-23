<?php

namespace App\Models\Concerns;

use App\Support\SyarikatSemasa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SkopSyarikat implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $semasa = app(SyarikatSemasa::class);

        if ($semasa->sedangPintas()) {
            return;
        }

        // Gagal tertutup, sama seperti RLS: tiada syarikat = tiada baris.
        $semasa->ada()
            ? $builder->where($model->qualifyColumn('syarikat_id'), $semasa->id())
            : $builder->whereRaw('1 = 0');
    }
}
