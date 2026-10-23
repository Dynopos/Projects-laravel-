<?php

namespace App\Models;

use App\Models\Concerns\MilikSyarikat;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('kategori_belanja')]
#[Fillable(['nama', 'susunan'])]
class KategoriBelanja extends Model
{
    use MilikSyarikat;

    public $timestamps = false;
}
