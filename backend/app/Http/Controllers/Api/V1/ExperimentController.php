<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Experiment;
use App\Services\RunConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExperimentController extends Controller
{
    public function __construct(private readonly RunConfigurationService $runConfigurationService)
    {
    }

    public function index(): JsonResponse
    {
        $experiments = Experiment::query()
            ->latest()
            ->get();

        return response()->json([
            'data' => $experiments,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'hypothesis' => ['nullable', 'string'],
            'default_protocol' => ['required', 'string', Rule::in(['http', 'mqtt'])],
            'default_config' => ['nullable', 'array'],
        ]);

        $this->runConfigurationService->validateExperimentDefaults(
            $validated['default_protocol'],
            $validated['default_config'] ?? []
        );

        $experiment = Experiment::query()->create($validated);

        return response()->json([
            'data' => $experiment,
        ], 201);
    }

    public function show(Experiment $experiment): JsonResponse
    {
        return response()->json([
            'data' => $experiment,
        ]);
    }
}
