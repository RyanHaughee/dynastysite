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
        Schema::table('sleeper_teams', function (Blueprint $table) {
            $table->integer('wins');
            $table->integer('waiver_position');
            $table->integer('waiver_budget_used');
            $table->integer('total_moves');
            $table->integer('ties');
            $table->integer('losses');
            $table->integer('fpts_decimal');
            $table->integer('fpts_against_decimal');
            $table->integer('fpts_against');
            $table->integer('fpts');
            $table->integer('roster_id');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
