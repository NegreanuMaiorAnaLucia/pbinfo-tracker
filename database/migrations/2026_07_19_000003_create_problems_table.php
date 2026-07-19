<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('problems', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pbinfo_id')->unique();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->string('difficulty')->nullable();
            $table->string('url');
            $table->json('metadata')->nullable();
            $table->string('source_hash')->nullable();
            $table->timestamps();

            $table->index('category_id');
            $table->index('difficulty');
            $table->index('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('problems');
    }
};
