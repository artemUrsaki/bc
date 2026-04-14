<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ExecuteRunJob;
use App\Models\Experiment;
use App\Models\Run;
use App\Models\Sample;
use App\Services\RunConfigurationService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RunController extends Controller
{
    public function __construct(private readonly RunConfigurationService $runConfigurationService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'experiment_id' => ['nullable', 'integer', 'exists:experiments,id'],
            'protocol' => ['nullable', 'string', Rule::in(['http', 'mqtt'])],
            'status' => ['nullable', 'string', Rule::in([
                Run::STATUS_QUEUED,
                Run::STATUS_RUNNING,
                Run::STATUS_COMPLETED,
                Run::STATUS_FAILED,
                Run::STATUS_CANCELLED,
            ])],
        ]);

        $runs = Run::query()
            ->with('aggregate')
            ->when(
                isset($validated['experiment_id']),
                fn ($query) => $query->where('experiment_id', $validated['experiment_id'])
            )
            ->when(
                isset($validated['protocol']),
                fn ($query) => $query->where('protocol', $validated['protocol'])
            )
            ->when(
                isset($validated['status']),
                fn ($query) => $query->where('status', $validated['status'])
            )
            ->latest()
            ->get();

        return response()->json([
            'data' => $runs,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'experiment_id' => ['required', 'integer', 'exists:experiments,id'],
            'protocol' => ['nullable', 'string', Rule::in(['http', 'mqtt'])],
            'scenario' => ['nullable', 'string'],
            'config' => ['nullable', 'array'],
        ]);

        $experiment = Experiment::query()->findOrFail($validated['experiment_id']);
        $protocol = $validated['protocol'] ?? $experiment->default_protocol;
        $resolved = $this->runConfigurationService->resolve(
            $protocol,
            $validated['scenario'] ?? null,
            $experiment->default_config ?? [],
            $validated['config'] ?? [],
            $request
        );

        $run = Run::query()->create([
            'experiment_id' => $experiment->id,
            'protocol' => $protocol,
            'status' => Run::STATUS_QUEUED,
            'config' => $resolved['config'],
            'environment_snapshot' => $resolved['environment_snapshot'],
        ]);

        ExecuteRunJob::dispatch($run->id);

        return response()->json([
            'data' => $run->fresh(),
        ], 201);
    }

    public function show(Run $run): JsonResponse
    {
        $run->load('aggregate', 'events');

        return response()->json([
            'data' => $run,
        ]);
    }

    public function aggregates(Run $run): JsonResponse
    {
        $aggregate = $run->aggregate;

        if ($aggregate === null) {
            return response()->json([
                'message' => 'Aggregates are not available for this run yet.',
            ], 404);
        }

        return response()->json([
            'data' => $aggregate,
        ]);
    }

    public function samples(Request $request, Run $run): JsonResponse
    {
        $validated = $request->validate([
            'success' => ['nullable', 'boolean'],
        ]);

        $samples = $run->samples()
            ->when(
                array_key_exists('success', $validated),
                fn ($query) => $query->where('success', $validated['success'])
            )
            ->orderBy('sequence_no')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $samples,
        ]);
    }

    public function events(Run $run): JsonResponse
    {
        $events = $run->events()
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $events,
            'meta' => [
                'implemented' => true,
                'count' => $events->count(),
            ],
        ]);
    }

    public function export(Request $request, Run $run): JsonResponse|StreamedResponse
    {
        $validated = $request->validate([
            'format' => ['nullable', 'string', Rule::in(['json', 'csv'])],
        ]);

        $format = $validated['format'] ?? 'json';
        $run->load('aggregate');
        $samples = $run->samples()
            ->orderBy('sequence_no')
            ->orderBy('id')
            ->get();
        $events = $run->events()
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        if ($format === 'csv') {
            return $this->streamCsvExport($run, $samples);
        }

        return response()->json([
            'data' => [
                'run' => $run,
                'aggregate' => $run->aggregate,
                'events' => $events,
                'samples' => $samples,
            ],
        ]);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection<int, Sample> $samples
     */
    private function streamCsvExport(Run $run, $samples): StreamedResponse
    {
        $filename = "run-{$run->id}-samples.csv";

        return response()->streamDownload(function () use ($samples): void {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, [
                'sample_id',
                'run_id',
                'sequence_no',
                'sent_at',
                'received_at',
                'latency_ms',
                'success',
                'status_code',
                'error_code',
                'error_message',
                'metadata',
            ]);

            foreach ($samples as $sample) {
                fputcsv($handle, [
                    $sample->id,
                    $sample->run_id,
                    $sample->sequence_no,
                    optional($sample->sent_at)?->toIso8601String(),
                    optional($sample->received_at)?->toIso8601String(),
                    $sample->latency_ms,
                    $sample->success ? '1' : '0',
                    $sample->status_code,
                    $sample->error_code,
                    $sample->error_message,
                    $sample->metadata !== null ? json_encode($sample->metadata, JSON_UNESCAPED_SLASHES) : null,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
