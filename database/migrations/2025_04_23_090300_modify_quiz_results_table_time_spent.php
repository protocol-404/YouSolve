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
        Schema::table('quiz_results', function (Blueprint $table) {
            // Drop the time_spent column and recreate it as nullable
            $table->dropColumn('time_spent');
        });

        Schema::table('quiz_results', function (Blueprint $table) {
            // Add the time_spent column back as nullable
            $table->integer('time_spent')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_results', function (Blueprint $table) {
            // Drop the nullable time_spent column
            $table->dropColumn('time_spent');
        });

        Schema::table('quiz_results', function (Blueprint $table) {
            // Add the time_spent column back as non-nullable
            $table->integer('time_spent');
        });
    }
};
