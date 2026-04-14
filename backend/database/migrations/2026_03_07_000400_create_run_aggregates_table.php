<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('run_aggregates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('run_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failure_count')->default(0);
            $table->decimal('avg_latency_ms', 10, 3)->nullable();
            $table->decimal('p95_latency_ms', 10, 3)->nullable();
            $table->decimal('throughput_per_sec', 10, 3)->nullable();
            $table->decimal('success_rate', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('run_aggregates');
    }
};
