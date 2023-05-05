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
        Schema::create('sleeper_trade_pieces', function (Blueprint $table) {
            $table->id();
            $table->integer('trade_id');
            $table->integer('player_id')->nullable();
            $table->integer('draft_pick_id')->nullable();
            $table->integer('old_team_id')->nullable();
            $table->integer('new_team_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sleeper_trade_pieces');
    }
};
