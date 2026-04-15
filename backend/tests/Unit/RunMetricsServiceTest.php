<?php

use App\Models\Experiment;
use App\Models\Run;
use App\Models\RunEvent;
use App\Models\Sample;
use App\Services\RunMetricsService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('computes aggregate metrics from stored samples', function (): void {
    $experiment = Experiment::query()->create([
        'name' => 'Metrics benchmark',
        'default_protocol' => 'http',
    ]);

    $run = Run::query()->create([
        'experiment_id' => $experiment->id,
        'protocol' => 'http',
        'status' => Run::STATUS_COMPLETED,
    ]);

    $start = CarbonImmutable::parse('2026-04-04 12:00:00.000000');

    Sample::query()->create([
        'run_id' => $run->id,
        'sequence_no' => 1,
        'sent_at' => $start,
        'received_at' => $start->addSecond(),
        'latency_ms' => 50,
        'success' => true,
        'status_code' => 200,
        'metadata' => ['dup' => false],
    ]);

    Sample::query()->create([
        'run_id' => $run->id,
        'sequence_no' => 2,
        'sent_at' => $start->addSeconds(2),
        'received_at' => $start->addSeconds(4),
        'latency_ms' => 120,
        'success' => true,
        'status_code' => 200,
        'metadata' => ['dup' => true],
    ]);

    Sample::query()->create([
        'run_id' => $run->id,
        'sequence_no' => 3,
        'sent_at' => $start->addSeconds(5),
        'received_at' => $start->addSeconds(9),
        'latency_ms' => null,
        'success' => false,
        'error_code' => 'timeout',
    ]);

    RunEvent::query()->create([
        'run_id' => $run->id,
        'type' => 'http.request.retrying',
        'level' => 'warning',
        'message' => 'Retrying HTTP request.',
        'occurred_at' => $start->addSeconds(5),
    ]);

    RunEvent::query()->create([
        'run_id' => $run->id,
        'type' => 'mqtt.connection.reconnect_attempted',
        'level' => 'warning',
        'message' => 'Attempting reconnect.',
        'occurred_at' => $start->addSeconds(6),
    ]);

    $metrics = app(RunMetricsService::class)->computeForRun($run);

    expect($metrics['total_count'])->toBe(3);
    expect($metrics['success_count'])->toBe(2);
    expect($metrics['failure_count'])->toBe(1);
    expect($metrics['timeout_count'])->toBe(1);
    expect($metrics['connection_failure_count'])->toBe(0);
    expect($metrics['duplicate_count'])->toBe(1);
    expect($metrics['retry_count'])->toBe(1);
    expect($metrics['reconnect_count'])->toBe(1);
    expect($metrics['avg_latency_ms'])->toBe(85.0);
    expect($metrics['median_latency_ms'])->toBe(85.0);
    expect($metrics['min_latency_ms'])->toBe(50.0);
    expect($metrics['max_latency_ms'])->toBe(120.0);
    expect($metrics['p95_latency_ms'])->toBe(116.5);
    expect($metrics['p99_latency_ms'])->toBe(119.3);
    expect($metrics['success_rate'])->toBe(66.67);
    expect($metrics['throughput_per_sec'])->toBe(0.333);
});

it('returns null latency metrics for a run without successful timings', function (): void {
    $experiment = Experiment::query()->create([
        'name' => 'Empty metrics benchmark',
        'default_protocol' => 'mqtt',
    ]);

    $run = Run::query()->create([
        'experiment_id' => $experiment->id,
        'protocol' => 'mqtt',
        'status' => Run::STATUS_FAILED,
    ]);

    $metrics = app(RunMetricsService::class)->computeForRun($run);

    expect($metrics['total_count'])->toBe(0);
    expect($metrics['timeout_count'])->toBe(0);
    expect($metrics['connection_failure_count'])->toBe(0);
    expect($metrics['duplicate_count'])->toBe(0);
    expect($metrics['retry_count'])->toBe(0);
    expect($metrics['reconnect_count'])->toBe(0);
    expect($metrics['avg_latency_ms'])->toBeNull();
    expect($metrics['median_latency_ms'])->toBeNull();
    expect($metrics['min_latency_ms'])->toBeNull();
    expect($metrics['max_latency_ms'])->toBeNull();
    expect($metrics['p95_latency_ms'])->toBeNull();
    expect($metrics['p99_latency_ms'])->toBeNull();
    expect($metrics['throughput_per_sec'])->toBeNull();
    expect($metrics['success_rate'])->toBe(0.0);
});
