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
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hunted_deal_id')->constrained()->onDelete('cascade');
            $table->string('external_id');
            $table->text('url');
            $table->string('title', 500);
            $table->decimal('price_amount', 12, 2)->nullable();
            $table->string('price_currency', 8)->default('RON');
            $table->string('price_raw', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('seller_name')->nullable();
            $table->text('seller_url')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->boolean('matches_intent')->nullable();
            $table->boolean('likely_working')->nullable();
            $table->decimal('confidence', 3, 2)->nullable();
            $table->timestamp('last_seen_at')->useCurrent();
            $table->timestamps();

            // Unique constraint for external_id per hunted_deal
            $table->unique(['hunted_deal_id', 'external_id']);

            // Indexes for performance optimization
            $table->index('hunted_deal_id');
            $table->index('external_id');
            $table->index('last_seen_at');
            $table->index('price_amount');
            $table->index('matches_intent');
            $table->index('likely_working');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
