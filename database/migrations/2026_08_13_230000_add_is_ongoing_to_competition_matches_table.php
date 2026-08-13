<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_matches', function (Blueprint $table) {
            $table->boolean('is_ongoing')->default(false)->after('status');
            $table->index(['is_ongoing', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('competition_matches', function (Blueprint $table) {
            $table->dropIndex(['is_ongoing', 'status']);
            $table->dropColumn('is_ongoing');
        });
    }
};
