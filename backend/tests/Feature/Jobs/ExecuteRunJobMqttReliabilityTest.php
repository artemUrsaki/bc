<?php

use App\Jobs\ExecuteRunJob;
use App\Models\Experiment;
use App\Models\Run;
use App\Services\RunMetricsService;
use App\Services\Runners\ProtocolRunnerManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('fails a run when mqtt connection fault is injected before connect', function (): void {
    $experiment = Experiment::query()->create([
        'name' => 'MQTT connection fault benchmark',
        'default_protocol' => 'mqtt',
    ]);

    $run = Run::query()->create([
        'experiment_id' => $experiment->id,
        'protocol' => 'mqtt',
        'status' => Run::STATUS_QUEUED,
        'config' => [
            'message_count' => 1,
            'payload_bytes' => 64,
            'timeout_ms' => 5000,
            'simulate_connection_failure_on_sequences' => [1],
        ],
    ]);

    $job = new ExecuteRunJob($run->id);

    expect(fn () => $job->handle(app(RunMetricsService::class), app(ProtocolRunnerManager::class)))
        ->toThrow(RuntimeException::class, 'Injected MQTT connection failure.');

    $run->refresh()->load('events');

    expect($run->status)->toBe(Run::STATUS_FAILED);
    expect($run->events->pluck('type')->all())->toContain('run.failed');
});
