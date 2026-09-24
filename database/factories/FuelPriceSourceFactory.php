<?php

namespace Database\Factories;

use App\Models\FuelPriceSource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FuelPriceSourceFactory extends Factory
{
    protected $model = FuelPriceSource::class;

    public function definition(): array
    {
        $displayName = ucfirst($this->faker->unique()->words(2, true));

        return [
            'key' => Str::snake(Str::lower($displayName)),
            'display_name' => $displayName,
        ];
    }

    public function texaco(): static
    {
        return $this->state([
            'key' => 'manual_texaco_entry',
            'display_name' => 'Texaco (manual entry)',
        ]);
    }
}
