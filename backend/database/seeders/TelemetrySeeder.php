<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Measurement;
use Illuminate\Database\Seeder;

class TelemetrySeeder extends Seeder
{
    public function run(): void
    {
        $devices = collect([
            [
                'name' => 'Server Room Sensor',
                'slug' => 'server-room-sensor',
                'type' => 'temperature',
                'location' => 'Server room',
                'unit' => 'C',
                'range' => [20, 28],
            ],
            [
                'name' => 'Office Humidity Sensor',
                'slug' => 'office-humidity-sensor',
                'type' => 'humidity',
                'location' => 'Open office',
                'unit' => '%',
                'range' => [35, 60],
            ],
            [
                'name' => 'Weather Pressure Sensor',
                'slug' => 'weather-pressure-sensor',
                'type' => 'pressure',
                'location' => 'Roof station',
                'unit' => 'hPa',
                'range' => [990, 1030],
            ],
        ])->map(function (array $deviceData): Device {
            return Device::query()->updateOrCreate(
                ['slug' => $deviceData['slug']],
                [
                    'name' => $deviceData['name'],
                    'type' => $deviceData['type'],
                    'location' => $deviceData['location'],
                    'is_active' => true,
                ]
            );
        });

        foreach ($devices as $index => $device) {
            $range = match ($device->type) {
                'temperature' => [20, 28],
                'humidity' => [35, 60],
                default => [990, 1030],
            };
            $unit = match ($device->type) {
                'temperature' => 'C',
                'humidity' => '%',
                default => 'hPa',
            };

            for ($i = 0; $i < 20; $i++) {
                Measurement::query()->create([
                    'device_id' => $device->id,
                    'protocol' => $i % 2 === 0 ? 'http' : 'mqtt',
                    'value' => fake()->randomFloat(2, $range[0], $range[1]),
                    'unit' => $unit,
                    'recorded_at' => now()->subMinutes((20 - $i) + $index),
                    'metadata' => [
                        'source' => 'telemetry_seeder',
                        'sequence_no' => $i + 1,
                    ],
                ]);
            }
        }
    }
}
