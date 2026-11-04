<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('title_people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('title_id')->constrained('titles')->cascadeOnDelete();
            $table->string('name', 150);
            $table->enum('role', ['director', 'creator', 'cast']);
            $table->unsignedSmallInteger('position');
            $table->unsignedInteger('tmdb_person_id')->nullable();

            $table->index(['title_id', 'role']);
            $table->index(['role', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('title_people');
    }
};
