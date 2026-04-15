<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('run_aggregates', function (Blueprint $table): void {
            $table->decimal('median_latency_ms', 10, 3)->nullable()->after('avg_latency_ms');
            $table->decimal('min_latency_ms', 10, 3)->nullable()->after('median_latency_ms');
            $table->decimal('max_latency_ms', 10, 3)->nullable()->after('min_latency_ms');
            $table->decimal('p99_latency_ms', 10, 3)->nullable()->after('p95_latency_ms');
            $table->unsignedInteger('retry_count')->default(0)->after('duplicate_count');
            $table->unsignedInteger('reconnect_count')->default(0)->after('retry_count');
        });
    }

    public function down(): void
    {
        Schema::table('run_aggregates', function (Blueprint $table): void {
            $table->dropColumn([
                'median_latency_ms',
                'min_latency_ms',
                'max_latency_ms',
                'p99_latency_ms',
                'retry_count',
                'reconnect_count',
            ]);
        });
    }
};
