<?php

use App\Jobs\ExecuteRunJob;
use App\Models\Experiment;
use App\Models\Run;
use App\Models\RunAggregate;
use App\Models\RunEvent;
use App\Models\Sample;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('creates a run and dispatches execution job', function (): void {
    Queue::fake();

    $experiment = Experiment::query()->create([
        'name' => 'HTTP throughput experiment',
        'default_protocol' => 'http',
        'default_config' => [
            'message_count' => 25,
        ],
    ]);

    $response = $this->postJson('/api/v1/runs', [
        'experiment_id' => $experiment->id,
        'config' => [
            'message_count' => 50,
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.experiment_id', $experiment->id)
        ->assertJsonPath('data.protocol', 'http')
        ->assertJsonPath('data.status', 'queued')
        ->assertJsonPath('data.config.message_count', 50);

    $runId = $response->json('data.id');

    Queue::assertPushed(ExecuteRunJob::class, function (ExecuteRunJob $job) use ($runId): bool {
        return $job->runId === $runId;
    });
});

it('returns run details including aggregate when present', function (): void {
    $experiment = Experiment::query()->create([
        'name' => 'MQTT reliability experiment',
        'default_protocol' => 'mqtt',
    ]);

    $run = Run::query()->create([
        'experiment_id' => $experiment->id,
        'protocol' => 'mqtt',
        'status' => Run::STATUS_COMPLETED,
        'config' => ['message_count' => 100],
    ]);

    RunAggregate::query()->create([
        'run_id' => $run->id,
        'total_count' => 100,
        'success_count' => 99,
        'failure_count' => 1,
        'avg_latency_ms' => 10.123,
        'p95_latency_ms' => 20.500,
        'throughput_per_sec' => 500.000,
        'success_rate' => 99.00,
    ]);

    $this->getJson("/api/v1/runs/{$run->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $run->id)
        ->assertJsonPath('data.aggregate.success_rate', 99);
});

it('applies a scenario template and stores an environment snapshot', function (): void {
    Queue::fake();

    $experiment = Experiment::query()->create([
        'name' => 'HTTP scenario experiment',
        'default_protocol' => 'http',
    ]);

    $response = $this->postJson('/api/v1/runs', [
        'experiment_id' => $experiment->id,
        'scenario' => 'large_payload',
        'config' => [
            'message_count' => 5,
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.config.scenario', 'large_payload')
        ->assertJsonPath('data.config.payload_bytes', 4096)
        ->assertJsonPath('data.config.message_count', 5)
        ->assertJsonPath('data.environment_snapshot.protocol', 'http')
        ->assertJsonPath('data.environment_snapshot.scenario', 'large_payload');
});

it('rejects invalid config keys for the selected protocol', function (): void {
    Queue::fake();

    $experiment = Experiment::query()->create([
        'name' => 'Strict config experiment',
        'default_protocol' => 'http',
    ]);

    $this->postJson('/api/v1/runs', [
        'experiment_id' => $experiment->id,
        'config' => [
            'host' => '127.0.0.1',
        ],
    ])->assertUnprocessable()
        ->assertJsonPath('errors.config.0', 'Unsupported config keys for protocol [http]: host.');
});

it('rejects an unavailable scenario for the selected protocol', function (): void {
    Queue::fake();

    $experiment = Experiment::query()->create([
        'name' => 'Scenario validation experiment',
        'default_protocol' => 'mqtt',
    ]);

    $this->postJson('/api/v1/runs', [
        'experiment_id' => $experiment->id,
        'scenario' => 'slow_polling',
    ])->assertUnprocessable()
        ->assertJsonPath('errors.scenario.0', 'Scenario [slow_polling] is not available for protocol [mqtt].');
});

it('returns 404 when aggregates are not available for a run', function (): void {
    $experiment = Experiment::query()->create([
        'name' => 'No aggregate run',
        'default_protocol' => 'http',
    ]);

    $run = Run::query()->create([
        'experiment_id' => $experiment->id,
        'protocol' => 'http',
        'status' => Run::STATUS_RUNNING,
    ]);

    $this->getJson("/api/v1/runs/{$run->id}/aggregates")
        ->assertNotFound()
        ->assertJsonPath('message', 'Aggregates are not available for this run yet.');
});

it('lists runs and can filter them', function (): void {
    $httpExperiment = Experiment::query()->create([
        'name' => 'HTTP run experiment',
        'default_protocol' => 'http',
    ]);

    $mqttExperiment = Experiment::query()->create([
        'name' => 'MQTT run experiment',
        'default_protocol' => 'mqtt',
    ]);

    Run::query()->create([
        'experiment_id' => $httpExperiment->id,
        'protocol' => 'http',
        'status' => Run::STATUS_COMPLETED,
    ]);

    Run::query()->create([
        'experiment_id' => $mqttExperiment->id,
        'protocol' => 'mqtt',
        'status' => Run::STATUS_FAILED,
    ]);

    $this->getJson('/api/v1/runs?protocol=http&status=completed')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.protocol', 'http')
        ->assertJsonPath('data.0.status', 'completed');
});

it('returns ordered run samples and can filter by success', function (): void {
    $experiment = Experiment::query()->create([
        'name' => 'Sample listing experiment',
        'default_protocol' => 'http',
    ]);

    $run = Run::query()->create([
        'experiment_id' => $experiment->id,
        'protocol' => 'http',
        'status' => Run::STATUS_COMPLETED,
    ]);

    Sample::query()->create([
        'run_id' => $run->id,
        'sequence_no' => 2,
        'latency_ms' => 30.5,
        'success' => false,
        'error_code' => 'timeout',
    ]);

    Sample::query()->create([
        'run_id' => $run->id,
        'sequence_no' => 1,
        'latency_ms' => 10.1,
        'success' => true,
        'status_code' => 200,
    ]);

    $this->getJson("/api/v1/runs/{$run->id}/samples")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.sequence_no', 1)
        ->assertJsonPath('data.1.sequence_no', 2);

    $this->getJson("/api/v1/runs/{$run->id}/samples?success=1")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.success', true);
});

it('returns stored run events ordered by time', function (): void {
    $experiment = Experiment::query()->create([
        'name' => 'Events experiment',
        'default_protocol' => 'mqtt',
    ]);

    $run = Run::query()->create([
        'experiment_id' => $experiment->id,
        'protocol' => 'mqtt',
        'status' => Run::STATUS_COMPLETED,
    ]);

    RunEvent::query()->create([
        'run_id' => $run->id,
        'type' => 'run.started',
        'level' => 'info',
        'message' => 'Run execution started.',
        'occurred_at' => now()->subSecond(),
    ]);

    RunEvent::query()->create([
        'run_id' => $run->id,
        'type' => 'run.completed',
        'level' => 'info',
        'message' => 'Run execution completed.',
        'occurred_at' => now(),
    ]);

    $this->getJson("/api/v1/runs/{$run->id}/events")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.type', 'run.started')
        ->assertJsonPath('data.1.type', 'run.completed')
        ->assertJsonPath('meta.implemented', true)
        ->assertJsonPath('meta.count', 2);
});

it('exports run data as json', function (): void {
    $experiment = Experiment::query()->create([
        'name' => 'JSON export experiment',
        'default_protocol' => 'http',
    ]);

    $run = Run::query()->create([
        'experiment_id' => $experiment->id,
        'protocol' => 'http',
        'status' => Run::STATUS_COMPLETED,
    ]);

    RunAggregate::query()->create([
        'run_id' => $run->id,
        'total_count' => 1,
        'success_count' => 1,
        'failure_count' => 0,
        'avg_latency_ms' => 12.3,
        'p95_latency_ms' => 12.3,
        'throughput_per_sec' => 80,
        'success_rate' => 100,
    ]);

    Sample::query()->create([
        'run_id' => $run->id,
        'sequence_no' => 1,
        'latency_ms' => 12.3,
        'success' => true,
        'status_code' => 200,
    ]);

    RunEvent::query()->create([
        'run_id' => $run->id,
        'type' => 'run.completed',
        'level' => 'info',
        'message' => 'Run execution completed.',
        'occurred_at' => now(),
    ]);

    $this->getJson("/api/v1/runs/{$run->id}/export?format=json")
        ->assertOk()
        ->assertJsonPath('data.run.id', $run->id)
        ->assertJsonPath('data.aggregate.total_count', 1)
        ->assertJsonCount(1, 'data.events')
        ->assertJsonCount(1, 'data.samples');
});

it('exports run samples as csv', function (): void {
    $experiment = Experiment::query()->create([
        'name' => 'CSV export experiment',
        'default_protocol' => 'http',
    ]);

    $run = Run::query()->create([
        'experiment_id' => $experiment->id,
        'protocol' => 'http',
        'status' => Run::STATUS_COMPLETED,
    ]);

    Sample::query()->create([
        'run_id' => $run->id,
        'sequence_no' => 1,
        'latency_ms' => 15.2,
        'success' => true,
        'status_code' => 200,
        'metadata' => ['url' => 'http://example.test'],
    ]);

    $response = $this->get("/api/v1/runs/{$run->id}/export?format=csv");

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($response->streamedContent())->toContain('sample_id,run_id,sequence_no');
    expect($response->streamedContent())->toContain((string) $run->id);
});
