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
        Schema::create('sleeper_players', function (Blueprint $table) {
            $table->id();
            $table->string('sleeper_player_id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('full_name')->nullable();
            $table->string('position')->nullable();
            $table->string('team')->nullable();
            $table->integer('age')->nullable();
            $table->string('college')->nullable();
            $table->json('fantasy_positions')->nullable();
            $table->string('weight')->nullable();
            $table->string('height')->nullable();
            $table->integer('number')->nullable();
            $table->integer('years_exp')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sleeper_players');
    }
};
