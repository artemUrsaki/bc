<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MeasurementController extends Controller
{
    public function latest(Request $request, Device $device): JsonResponse
    {
        $validated = $request->validate([
            'protocol' => ['nullable', 'string', Rule::in(['http', 'mqtt'])],
        ]);

        $measurement = $device->measurements()
            ->when(
                isset($validated['protocol']),
                fn ($query) => $query->where('protocol', $validated['protocol'])
            )
            ->latest('recorded_at')
            ->first();

        return response()->json([
            'data' => $measurement,
        ]);
    }

    public function index(Request $request, Device $device): JsonResponse
    {
        $validated = $request->validate([
            'protocol' => ['nullable', 'string', Rule::in(['http', 'mqtt'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $limit = $validated['limit'] ?? 50;

        $measurements = $device->measurements()
            ->when(
                isset($validated['protocol']),
                fn ($query) => $query->where('protocol', $validated['protocol'])
            )
            ->latest('recorded_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $measurements,
        ]);
    }
}
