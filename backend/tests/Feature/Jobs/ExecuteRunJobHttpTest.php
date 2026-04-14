<?php

use App\Jobs\ExecuteRunJob;
use App\Models\Experiment;
use App\Models\Run;
use App\Services\RunMetricsService;
use App\Services\Runners\ProtocolRunnerManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('executes an http run and stores samples with aggregates', function (): void {
    Http::fake([
        '*' => Http::response([
            'ok' => true,
        ], 200),
    ]);

    $experiment = Experiment::query()->create([
        'name' => 'HTTP execution benchmark',
        'default_protocol' => 'http',
    ]);

    $run = Run::query()->create([
        'experiment_id' => $experiment->id,
        'protocol' => 'http',
        'status' => Run::STATUS_QUEUED,
        'config' => [
            'url' => 'http://benchmark.test/api/v1/probe/http-echo',
            'message_count' => 3,
            'payload_bytes' => 64,
            'timeout_ms' => 5000,
        ],
    ]);

    $job = new ExecuteRunJob($run->id);
    $job->handle(app(RunMetricsService::class), app(ProtocolRunnerManager::class));

    $run->refresh()->load('samples', 'aggregate');

    expect($run->status)->toBe(Run::STATUS_COMPLETED);
    expect($run->samples)->toHaveCount(3);
    expect($run->samples->every(fn ($sample) => $sample->success))->toBeTrue();
    expect($run->aggregate)->not->toBeNull();
    expect($run->aggregate->total_count)->toBe(3);
    expect($run->aggregate->failure_count)->toBe(0);
});
