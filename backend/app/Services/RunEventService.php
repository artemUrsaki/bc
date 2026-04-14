<?php

namespace App\Services;

use App\Models\Run;

class RunEventService
{
    /**
     * @param array<string,mixed> $context
     */
    public function record(
        Run $run,
        string $type,
        string $message,
        string $level = 'info',
        array $context = []
    ): void {
        $run->events()->create([
            'type' => $type,
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }
}
