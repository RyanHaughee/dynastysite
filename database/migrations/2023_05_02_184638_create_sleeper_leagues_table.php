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
            $table->integer('total_rosters')->nullable();
            $table->string('status')->nullable();
            $table->string('sport')->nullable();
            $table->json('settings')->nullable();
            $table->string('season_type')->nullable();
            $table->string('season')->nullable();
            $table->json('scoring_settings')->nullable();
            $table->json('roster_positions')->nullable();
            $table->string('sleeper_previous_league_id')->nullable();
            $table->string('name')->nullable();
            $table->string('sleeper_league_id')->nullable();
            $table->string('sleeper_draft_id')->nullable();
            $table->string('avatar')->nullable();
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
