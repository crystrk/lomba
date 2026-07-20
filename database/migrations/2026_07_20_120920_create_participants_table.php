<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('normalized_name');
            $table->string('short_name', 10)->nullable();
            $table->string('logo_path')->nullable();
            $table->unsignedInteger('draw_position')->nullable();
            $table->timestamps();

            $table->unique(['competition_id', 'normalized_name']);
            $table->index('draw_position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
