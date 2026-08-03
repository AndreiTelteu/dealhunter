<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->json('image_urls')->nullable()->after('description');
            $table->dropSoftDeletes();
        });

        Schema::table('hunted_deals', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('deal_snapshots', function (Blueprint $table) {
            $table->text('url')->nullable()->after('deal_id');
        });
    }

    public function down(): void
    {
        Schema::table('deal_snapshots', function (Blueprint $table) {
            $table->dropColumn('url');
        });

        Schema::table('hunted_deals', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('deals', function (Blueprint $table) {
            $table->softDeletes();
            $table->dropColumn('image_urls');
        });
    }
};
