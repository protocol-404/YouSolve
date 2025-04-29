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
            // Drop the total_points column and recreate it as nullable
            $table->dropColumn('total_points');
        });

        Schema::table('quiz_results', function (Blueprint $table) {
            // Add the total_points column back as nullable
            $table->integer('total_points')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_results', function (Blueprint $table) {
            // Drop the nullable total_points column
            $table->dropColumn('total_points');
        });

        Schema::table('quiz_results', function (Blueprint $table) {
            // Add the total_points column back as non-nullable
            $table->integer('total_points');
        });
    }
};
