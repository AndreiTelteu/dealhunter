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
        Schema::create('deal_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained()->onDelete('cascade');
            $table->string('title', 500);
            $table->decimal('price_amount', 12, 2)->nullable();
            $table->string('price_currency', 8)->default('RON');
            $table->string('price_raw', 100)->nullable();
            $table->text('description')->nullable();
            $table->json('image_urls')->nullable();
            $table->string('location')->nullable();
            $table->string('seller_name')->nullable();
            $table->text('seller_url')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->boolean('matches_intent')->nullable();
            $table->boolean('likely_working')->nullable();
            $table->decimal('confidence', 3, 2)->nullable();
            $table->timestamp('captured_at')->useCurrent();

            // Indexes for performance optimization
            $table->index('deal_id');
            $table->index('captured_at');
            $table->index('price_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deal_snapshots');
    }
};
