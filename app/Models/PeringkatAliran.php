<?php

namespace App\Models;

use App\Models\Concerns\MilikSyarikat;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('peringkat_aliran')]
#[Fillable(['kunci', 'label', 'susunan'])]
class PeringkatAliran extends Model
{
    use MilikSyarikat;

    public $timestamps = false;
}
