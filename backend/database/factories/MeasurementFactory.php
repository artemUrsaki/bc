<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\Measurement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Measurement>
 */
class MeasurementFactory extends Factory
{
    protected $model = Measurement::class;

    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'protocol' => fake()->randomElement(['http', 'mqtt']),
            'value' => fake()->randomFloat(2, 18, 32),
            'unit' => fake()->randomElement(['C', '%', 'hPa']),
            'recorded_at' => now()->subSeconds(fake()->numberBetween(0, 3600)),
            'metadata' => [
                'source' => 'seed',
            ],
        ];
    }
}
