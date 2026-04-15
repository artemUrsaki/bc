<?php

use App\Models\Device;
use App\Models\Measurement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists devices and can filter active ones', function (): void {
    Device::factory()->create([
        'name' => 'Active device',
        'slug' => 'active-device',
        'type' => 'temperature',
        'is_active' => true,
    ]);

    Device::factory()->create([
        'name' => 'Inactive device',
        'slug' => 'inactive-device',
        'type' => 'humidity',
        'is_active' => false,
    ]);

    $this->getJson('/api/v1/devices?active=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'active-device');
});

it('returns the latest measurement for a device', function (): void {
    $device = Device::factory()->create([
        'slug' => 'server-room',
    ]);

    Measurement::factory()->create([
        'device_id' => $device->id,
        'protocol' => 'http',
        'value' => 23.1,
        'recorded_at' => now()->subMinute(),
    ]);

    Measurement::factory()->create([
        'device_id' => $device->id,
        'protocol' => 'mqtt',
        'value' => 24.6,
        'recorded_at' => now(),
    ]);

    $this->getJson("/api/v1/devices/{$device->id}/latest")
        ->assertOk()
        ->assertJsonPath('data.protocol', 'mqtt')
        ->assertJsonPath('data.value', 24.6);
});

it('returns measurement history and can filter by protocol', function (): void {
    $device = Device::factory()->create();

    Measurement::factory()->create([
        'device_id' => $device->id,
        'protocol' => 'http',
        'value' => 20.0,
        'recorded_at' => now()->subMinutes(2),
    ]);

    Measurement::factory()->create([
        'device_id' => $device->id,
        'protocol' => 'mqtt',
        'value' => 21.5,
        'recorded_at' => now()->subMinute(),
    ]);

    $this->getJson("/api/v1/devices/{$device->id}/measurements?protocol=mqtt&limit=10")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.protocol', 'mqtt');
});
