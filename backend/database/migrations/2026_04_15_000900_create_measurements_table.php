<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('protocol', 16);
            $table->decimal('value', 10, 2);
            $table->string('unit', 16);
            $table->timestamp('recorded_at', 6);
            $table->json('metadata')->nullable();
            $table->timestamps(6);

            $table->index(['device_id', 'recorded_at']);
            $table->index(['protocol', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
};
