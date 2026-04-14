<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('run_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('run_id')->constrained()->cascadeOnDelete();
            $table->string('type', 64);
            $table->string('level', 16)->default('info');
            $table->string('message', 255);
            $table->json('context')->nullable();
            $table->timestamp('occurred_at', 6);
            $table->timestamps(6);

            $table->index(['run_id', 'occurred_at']);
            $table->index(['run_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('run_events');
    }
};
