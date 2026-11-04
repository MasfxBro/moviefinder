<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('title_genre', function (Blueprint $table) {
            $table->foreignId('title_id')->constrained('titles')->cascadeOnDelete();
            $table->unsignedInteger('genre_id');

            $table->primary(['title_id', 'genre_id']);
            $table->foreign('genre_id')->references('id')->on('genres')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('title_genre');
    }
};
