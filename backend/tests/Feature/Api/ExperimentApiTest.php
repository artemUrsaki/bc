<?php

use App\Models\Experiment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates an experiment', function (): void {
    $response = $this->postJson('/api/v1/experiments', [
        'name' => 'Baseline latency benchmark',
        'description' => 'Compares HTTP and MQTT latency under normal network conditions.',
        'hypothesis' => 'MQTT will have lower p95 latency for small payloads.',
        'default_protocol' => 'http',
        'default_config' => [
            'message_count' => 100,
            'payload_bytes' => 256,
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.name', 'Baseline latency benchmark')
        ->assertJsonPath('data.default_protocol', 'http');

    expect(Experiment::query()->count())->toBe(1);
});

it('returns an experiment by id', function (): void {
    $experiment = Experiment::query()->create([
        'name' => 'Reliability experiment',
        'default_protocol' => 'mqtt',
    ]);

    $this->getJson("/api/v1/experiments/{$experiment->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $experiment->id)
        ->assertJsonPath('data.default_protocol', 'mqtt');
});

it('lists experiments', function (): void {
    Experiment::query()->create([
        'name' => 'Experiment A',
        'default_protocol' => 'http',
    ]);

    Experiment::query()->create([
        'name' => 'Experiment B',
        'default_protocol' => 'mqtt',
    ]);

    $this->getJson('/api/v1/experiments')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('rejects invalid default config for the selected protocol', function (): void {
    $this->postJson('/api/v1/experiments', [
        'name' => 'Broken experiment',
        'default_protocol' => 'mqtt',
        'default_config' => [
            'method' => 'POST',
        ],
    ])->assertUnprocessable()
        ->assertJsonPath('errors.config.0', 'Unsupported config keys for protocol [mqtt]: method.');
});
