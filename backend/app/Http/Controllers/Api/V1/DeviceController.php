<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'active' => ['nullable', 'boolean'],
            'type' => ['nullable', 'string', 'max:64'],
        ]);

        $devices = Device::query()
            ->when(
                array_key_exists('active', $validated),
                fn ($query) => $query->where('is_active', $validated['active'])
            )
            ->when(
                isset($validated['type']),
                fn ($query) => $query->where('type', $validated['type'])
            )
            ->latest()
            ->get();

        return response()->json([
            'data' => $devices,
        ]);
    }

    public function show(Device $device): JsonResponse
    {
        return response()->json([
            'data' => $device,
        ]);
    }
}
