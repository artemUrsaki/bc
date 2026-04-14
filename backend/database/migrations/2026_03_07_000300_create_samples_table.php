<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('samples', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('run_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence_no');
            $table->timestamp('sent_at', 6)->nullable();
            $table->timestamp('received_at', 6)->nullable();
            $table->decimal('latency_ms', 10, 3)->nullable();
            $table->boolean('success')->default(true);
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps(6);

            $table->index(['run_id', 'sequence_no']);
            $table->index(['run_id', 'success']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('samples');
    }
};
