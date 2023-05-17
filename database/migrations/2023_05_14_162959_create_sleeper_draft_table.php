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
        Schema::create('sleeper_drafts', function (Blueprint $table) {
            $table->id();
            $table->integer('league_id');
            $table->integer('season');
            $table->string('status');
            $table->json('settings');
            $table->string('start_time');
            $table->string('last_picked');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sleeper_drafts');
    }
};
