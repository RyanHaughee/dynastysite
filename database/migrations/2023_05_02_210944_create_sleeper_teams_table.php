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
        Schema::create('sleeper_teams', function (Blueprint $table) {
            $table->id();
            $table->integer('league_id');
            $table->string('sleeper_user_id');
            $table->string('sleeper_league_id');
            $table->string('team_name');
            $table->string('team_logo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sleeper_teams');
    }
};
