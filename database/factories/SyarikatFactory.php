<?php

namespace Database\Factories;

use App\Models\Syarikat;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Syarikat>
 */
class SyarikatFactory extends Factory
{
    public function definition(): array
    {
        $nama = strtoupper(fake()->unique()->company());

        return [
            'nama' => $nama,
            'subdomain' => Str::slug($nama).'-'.Str::lower(Str::random(4)),
            'kod_awalan' => 'PRJ-',
            'status' => 'aktif',
        ];
    }
}
