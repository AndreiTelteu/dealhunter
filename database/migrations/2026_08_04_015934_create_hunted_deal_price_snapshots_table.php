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
        Schema::create('hunted_deal_price_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hunted_deal_id')->constrained()->onDelete('cascade');
            $table->decimal('average_price', 12, 2);
            $table->decimal('min_price', 12, 2);
            $table->decimal('max_price', 12, 2);
            $table->unsignedInteger('deals_count');
            $table->string('price_currency', 8)->default('RON');
            $table->timestamp('captured_at')->useCurrent();

            // Indexes for performance optimization
            $table->index('hunted_deal_id');
            $table->index('captured_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hunted_deal_price_snapshots');
    }
};
