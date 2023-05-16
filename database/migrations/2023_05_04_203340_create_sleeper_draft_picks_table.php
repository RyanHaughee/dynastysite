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
        Schema::create('sleeper_draft_picks', function (Blueprint $table) {
            $table->id();
            $table->integer('league_id');
            $table->integer('round');
            $table->integer('pick')->nullable();
            $table->integer('team_id');
            $table->integer('player_id')->nullable();
            $table->integer('year');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sleeper_draft_picks');
    }
};
