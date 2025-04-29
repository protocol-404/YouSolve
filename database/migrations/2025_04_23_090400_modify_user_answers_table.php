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
        Schema::table('user_answers', function (Blueprint $table) {
            // Drop the is_correct column and recreate it as nullable
            $table->dropColumn('is_correct');
        });

        Schema::table('user_answers', function (Blueprint $table) {
            // Add the is_correct column back as nullable
            $table->boolean('is_correct')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_answers', function (Blueprint $table) {
            // Drop the nullable is_correct column
            $table->dropColumn('is_correct');
        });

        Schema::table('user_answers', function (Blueprint $table) {
            // Add the is_correct column back as non-nullable
            $table->boolean('is_correct');
        });
    }
};
