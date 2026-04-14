<?php

namespace App\Services\Runners\Concerns;

trait BuildsBenchmarkPayload
{
    protected function benchmarkPayload(int $bytes, int $sequenceNo): string
    {
        $bytes = max($bytes, 1);
        $prefix = "seq:{$sequenceNo}|";

        if (strlen($prefix) >= $bytes) {
            return substr($prefix, 0, $bytes);
        }

        return $prefix.str_repeat('x', $bytes - strlen($prefix));
    }
}
