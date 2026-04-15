<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RunAggregate extends Model
{
    use HasFactory;

    protected $fillable = [
        'run_id',
        'total_count',
        'success_count',
        'failure_count',
        'timeout_count',
        'connection_failure_count',
        'duplicate_count',
        'avg_latency_ms',
        'p95_latency_ms',
        'throughput_per_sec',
        'success_rate',
    ];

    protected function casts(): array
    {
        return [
            'avg_latency_ms' => 'float',
            'p95_latency_ms' => 'float',
            'throughput_per_sec' => 'float',
            'success_rate' => 'float',
            'timeout_count' => 'integer',
            'connection_failure_count' => 'integer',
            'duplicate_count' => 'integer',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(Run::class);
    }
}
