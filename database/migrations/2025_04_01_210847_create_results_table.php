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
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->onDelete('cascade');
            $table->boolean('success')->default(false);
            $table->integer('execution_time')->nullable(); // in milliseconds
            $table->integer('memory_usage')->nullable(); // in KB
            $table->text('output')->nullable();
            $table->text('error_message')->nullable();
            $table->json('test_results')->nullable(); // JSON encoded test results
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
