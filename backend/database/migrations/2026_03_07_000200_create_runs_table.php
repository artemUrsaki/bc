<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();
            $table->string('protocol', 16);
            $table->string('status', 32)->default('queued');
            $table->json('config')->nullable();
            $table->timestamp('started_at', 6)->nullable();
            $table->timestamp('finished_at', 6)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps(6);

            $table->index(['experiment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('runs');
    }
};
