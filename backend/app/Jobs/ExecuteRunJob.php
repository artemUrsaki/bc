<?php

namespace App\Jobs;

use App\Models\Run;
use App\Models\RunAggregate;
use App\Services\RunEventService;
use App\Services\RunMetricsService;
use App\Services\Runners\ProtocolRunnerManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ExecuteRunJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $runId)
    {
    }

    public function handle(
        RunMetricsService $metricsService,
        ProtocolRunnerManager $runnerManager,
        ?RunEventService $eventService = null
    ): void {
        $eventService ??= app(RunEventService::class);
        $run = Run::query()->with('samples')->find($this->runId);

        if ($run === null || $run->status !== Run::STATUS_QUEUED) {
            return;
        }

        $run->update([
            'status' => Run::STATUS_RUNNING,
            'started_at' => now(),
            'error_message' => null,
        ]);

        $eventService->record(
            $run,
            'run.started',
            'Run execution started.',
            context: [
                'protocol' => $run->protocol,
                'scenario' => $run->config['scenario'] ?? null,
            ]
        );

        try {
            $runnerManager->for($run->protocol)->run($run);
            $metrics = $metricsService->computeForRun($run->fresh('samples'));

            RunAggregate::query()->updateOrCreate(
                ['run_id' => $run->id],
                $metrics
            );

            $run->update([
                'status' => Run::STATUS_COMPLETED,
                'finished_at' => now(),
            ]);

            $eventService->record(
                $run,
                'run.completed',
                'Run execution completed.',
                context: [
                    'total_count' => $metrics['total_count'],
                    'success_count' => $metrics['success_count'],
                    'failure_count' => $metrics['failure_count'],
                ]
            );
        } catch (Throwable $exception) {
            $run->update([
                'status' => Run::STATUS_FAILED,
                'finished_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);

            $eventService->record(
                $run,
                'run.failed',
                'Run execution failed.',
                'error',
                [
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]
            );

            throw $exception;
        }
    }
}
