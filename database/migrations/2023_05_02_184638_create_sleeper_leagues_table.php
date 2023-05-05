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
        Schema::create('sleeper_leagues', function (Blueprint $table) {
            $table->id();
            $table->integer('total_rosters');
            $table->string('status');
            $table->string('sport');
            $table->json('settings');
            $table->string('season_type');
            $table->string('season');
            $table->json('scoring_settings');
            $table->json('roster_positions');
            $table->string('sleeper_previous_league_id');
            $table->string('name');
            $table->string('sleeper_league_id');
            $table->string('sleeper_draft_id');
            $table->string('avatar');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sleeper_leagues');
    }
};
