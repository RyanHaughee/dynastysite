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
        Schema::table('sleeper_trades', function (Blueprint $table) {
            $table->string('team1_value')->nullable();
            $table->string('team2_value')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sleeper_trades', function (Blueprint $table) {
            $table->dropColumn('team1_value');
            $table->dropColumn('team2_value');
        });
    }
};
