<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_matches', function (Blueprint $table) {
            $table->foreignId('loser_next_match_id')->nullable()->after('next_slot')->constrained('competition_matches')->nullOnDelete();
            $table->unsignedTinyInteger('loser_next_slot')->nullable()->after('loser_next_match_id');
            $table->string('match_type')->default('standard')->after('loser_next_slot');
        });
    }

    public function down(): void
    {
        Schema::table('competition_matches', function (Blueprint $table) {
            $table->dropForeign(['loser_next_match_id']);
            $table->dropColumn(['loser_next_match_id', 'loser_next_slot', 'match_type']);
        });
    }
};
