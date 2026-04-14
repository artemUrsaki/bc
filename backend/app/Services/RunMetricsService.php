<?php

namespace App\Services;

use App\Models\Run;

class RunMetricsService
{
    /**
     * @return array{
     *     total_count:int,
     *     success_count:int,
     *     failure_count:int,
     *     avg_latency_ms:float|null,
     *     p95_latency_ms:float|null,
     *     throughput_per_sec:float|null,
     *     success_rate:float
     * }
     */
    public function computeForRun(Run $run): array
    {
        $samples = $run->samples()->get(['latency_ms', 'success', 'sent_at', 'received_at', 'created_at']);

        $totalCount = $samples->count();
        $successCount = $samples->where('success', true)->count();
        $failureCount = $totalCount - $successCount;

        $latencies = $samples
            ->pluck('latency_ms')
            ->filter(static fn ($value): bool => $value !== null)
            ->map(static fn ($value): float => (float) $value)
            ->sort()
            ->values();

        $avgLatency = $latencies->isNotEmpty()
            ? round($latencies->sum() / $latencies->count(), 3)
            : null;

        $p95Latency = $latencies->isNotEmpty()
            ? $this->percentile($latencies->all(), 0.95)
            : null;

        $throughput = $this->throughputPerSecond($samples->all());

        $successRate = $totalCount > 0
            ? round(($successCount / $totalCount) * 100, 2)
            : 0.0;

        return [
            'total_count' => $totalCount,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'avg_latency_ms' => $avgLatency,
            'p95_latency_ms' => $p95Latency,
            'throughput_per_sec' => $throughput,
            'success_rate' => $successRate,
        ];
    }

    /**
     * @param array<int,float> $sortedValues
     */
    private function percentile(array $sortedValues, float $percentile): float
    {
        $count = count($sortedValues);

        if ($count === 0) {
            return 0.0;
        }

        if ($count === 1) {
            return round($sortedValues[0], 3);
        }

        $rank = ($count - 1) * $percentile;
        $lowerIndex = (int) floor($rank);
        $upperIndex = (int) ceil($rank);

        if ($lowerIndex === $upperIndex) {
            return round($sortedValues[$lowerIndex], 3);
        }

        $weight = $rank - $lowerIndex;
        $value = ($sortedValues[$lowerIndex] * (1 - $weight)) + ($sortedValues[$upperIndex] * $weight);

        return round($value, 3);
    }

    /**
     * @param array<int,mixed> $samples
     */
    private function throughputPerSecond(array $samples): ?float
    {
        $count = count($samples);

        if ($count === 0) {
            return null;
        }

        $start = null;
        $end = null;

        foreach ($samples as $sample) {
            $sampleStart = $sample->sent_at ?? $sample->created_at;
            $sampleEnd = $sample->received_at ?? $sample->created_at;

            if ($sampleStart === null || $sampleEnd === null) {
                continue;
            }

            $start ??= $sampleStart;
            $end ??= $sampleEnd;

            if ($sampleStart->lt($start)) {
                $start = $sampleStart;
            }

            if ($sampleEnd->gt($end)) {
                $end = $sampleEnd;
            }
        }

        if ($start === null || $end === null) {
            return null;
        }

        $durationSeconds = max($start->diffInMilliseconds($end) / 1000, 0.001);

        return round($count / $durationSeconds, 3);
    }
}
