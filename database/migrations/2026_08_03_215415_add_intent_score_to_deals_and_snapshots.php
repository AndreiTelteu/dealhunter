<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->unsignedTinyInteger('intent_score')->nullable()->after('matches_intent');
            $table->index('intent_score');
        });

        Schema::table('deal_snapshots', function (Blueprint $table) {
            $table->unsignedTinyInteger('intent_score')->nullable()->after('matches_intent');
        });

        DB::table('deals')->whereNotNull('matches_intent')->update([
            'intent_score' => DB::raw('CASE WHEN matches_intent = TRUE THEN 100 ELSE 0 END'),
        ]);

        DB::table('deal_snapshots')->whereNotNull('matches_intent')->update([
            'intent_score' => DB::raw('CASE WHEN matches_intent = TRUE THEN 100 ELSE 0 END'),
        ]);
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropIndex(['intent_score']);
            $table->dropColumn('intent_score');
        });

        Schema::table('deal_snapshots', function (Blueprint $table) {
            $table->dropColumn('intent_score');
        });
    }
};
