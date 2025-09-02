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
        Schema::create('crawl_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('crawl'); // crawl, manual_crawl, health_check
            $table->string('status'); // started, completed, failed, partial
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->integer('hunted_deals_processed')->default(0);
            $table->integer('hunted_deals_failed')->default(0);
            $table->integer('total_listings_found')->default(0);
            $table->integer('new_deals_created')->default(0);
            $table->integer('deals_updated')->default(0);
            $table->integer('snapshots_created')->default(0);
            $table->integer('total_errors')->default(0);
            $table->decimal('success_rate', 5, 2)->nullable();
            $table->decimal('listings_per_second', 8, 2)->nullable();
            $table->json('configuration')->nullable(); // Store crawler config at time of run
            $table->json('errors')->nullable(); // Store error messages
            $table->text('notes')->nullable(); // For manual crawls or additional info
            $table->string('triggered_by')->nullable(); // 'scheduler', 'manual', 'admin'
            $table->unsignedBigInteger('user_id')->nullable(); // For manual triggers
            $table->timestamps();
            
            $table->index(['type', 'status']);
            $table->index(['started_at']);
            $table->index(['status']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crawl_logs');
    }
};
