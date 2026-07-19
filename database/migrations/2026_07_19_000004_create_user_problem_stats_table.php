<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_problem_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('problem_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('best_score')->default(0);
            $table->unsignedInteger('attempts')->default(0);
            $table->string('status')->default('unsolved'); // unsolved | attempted | solved
            $table->timestamp('last_submission_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'problem_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_problem_stats');
    }
};
