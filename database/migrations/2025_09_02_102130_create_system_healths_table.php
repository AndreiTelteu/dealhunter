<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_healths', function (Blueprint $table) {
            $table->id();
            $table->string('component'); // 'database', 'mcp', 'crawler', 'ai'
            $table->string('status'); // 'healthy', 'warning', 'critical', 'unknown'
            $table->text('message')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->json('details')->nullable(); // Additional diagnostic info
            $table->timestamp('checked_at');
            $table->timestamps();
            
            $table->index(['component', 'status']);
            $table->index(['checked_at']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_healths');
    }
};
