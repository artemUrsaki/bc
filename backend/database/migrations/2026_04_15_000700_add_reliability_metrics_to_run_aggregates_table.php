<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('run_aggregates', function (Blueprint $table): void {
            $table->unsignedInteger('timeout_count')->default(0)->after('failure_count');
            $table->unsignedInteger('connection_failure_count')->default(0)->after('timeout_count');
            $table->unsignedInteger('duplicate_count')->default(0)->after('connection_failure_count');
        });
    }

    public function down(): void
    {
        Schema::table('run_aggregates', function (Blueprint $table): void {
            $table->dropColumn([
                'timeout_count',
                'connection_failure_count',
                'duplicate_count',
            ]);
        });
    }
};
