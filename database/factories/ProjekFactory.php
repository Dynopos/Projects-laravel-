<?php

namespace Database\Factories;

use App\Models\Projek;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * syarikat_id diisi oleh MilikSyarikat dari SyarikatSemasa.
 *
 * @extends Factory<Projek>
 */
class ProjekFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kod' => 'PRJ-'.fake()->unique()->numberBetween(100, 99999),
            'nama' => 'Projek '.fake()->words(3, true),
            'klien' => strtoupper(fake()->name()),
            'status' => 'Dalam progres',
            'nilai' => fake()->numberBetween(10, 500) * 1000,
            'mula' => fake()->date(),
        ];
    }
}
