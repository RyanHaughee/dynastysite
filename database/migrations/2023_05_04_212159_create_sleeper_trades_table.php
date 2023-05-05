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
        Schema::create('sleeper_trades', function (Blueprint $table) {
            $table->id();
            $table->string('sleeper_transaction_id');
            $table->integer('team1_roster_id');
            $table->integer('team2_roster_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sleeper_trades');
    }
};
