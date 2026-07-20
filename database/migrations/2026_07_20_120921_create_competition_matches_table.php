<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('round');
            $table->unsignedTinyInteger('leg')->default(1);
            $table->unsignedSmallInteger('sequence');
            $table->foreignId('participant_id_home')->nullable()->constrained('participants')->nullOnDelete();
            $table->foreignId('participant_id_away')->nullable()->constrained('participants')->nullOnDelete();
            $table->unsignedSmallInteger('score_home')->nullable();
            $table->unsignedSmallInteger('score_away')->nullable();
            $table->foreignId('winner_id')->nullable()->constrained('participants')->nullOnDelete();
            $table->string('win_method')->nullable();
            $table->string('status');
            $table->foreignId('next_match_id')->nullable()->constrained('competition_matches')->nullOnDelete();
            $table->unsignedTinyInteger('next_slot')->nullable();
            $table->unsignedBigInteger('result_version')->default(0);
            $table->foreignId('result_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('result_updated_at')->nullable();
            $table->timestamps();

            $table->index('round');
            $table->index('status');
            $table->unique(['competition_id', 'round', 'leg', 'sequence'], 'match_unique_position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_matches');
    }
};
