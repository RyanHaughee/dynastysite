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
            $table->integer('division')->nullable();
            $table->integer('alltime_wins')->nullable();
            $table->integer('alltime_losses')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sleeper_teams', function (Blueprint $table) {
            $table->dropColumn('division');
            $table->dropColumn('alltime_wins');
            $table->dropColumn('alltime_losses');
        });
    }
};
