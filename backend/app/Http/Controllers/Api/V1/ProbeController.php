<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProbeController extends Controller
{
    public function httpEcho(Request $request): JsonResponse
    {
        $payload = (string) $request->input('payload', '');

        return response()->json([
            'run_id' => $request->input('run_id'),
            'sequence_no' => $request->input('sequence_no'),
            'payload_size_bytes' => strlen($payload),
            'server_received_at' => now()->toIso8601String(),
        ]);
    }
}
