<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('titles', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tmdb_id')->nullable();
            $table->enum('source', ['tmdb', 'manual'])->default('tmdb');
            $table->enum('media_type', ['movie', 'tv']);
            $table->string('slug', 200)->unique();
            $table->string('title', 200);
            $table->string('original_title', 200)->nullable();
            $table->unsignedSmallInteger('release_year')->nullable();
            $table->string('poster_path', 255)->nullable();
            $table->string('backdrop_path', 255)->nullable();
            $table->text('overview')->nullable();
            $table->string('tagline', 300)->nullable();
            $table->unsignedSmallInteger('runtime_minutes')->nullable();
            $table->unsignedSmallInteger('seasons_count')->nullable();
            $table->unsignedSmallInteger('episodes_count')->nullable();
            $table->char('original_language', 10)->nullable();
            $table->decimal('vote_average', 3, 1)->default(0);
            $table->unsignedInteger('vote_count')->default(0);
            $table->float('popularity')->default(0);
            $table->string('trailer_youtube_key', 20)->nullable();
            $table->boolean('adult')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->json('locked_fields')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tmdb_id', 'media_type']);
            $table->index('release_year');
            $table->index('media_type');
            $table->index('popularity');
            $table->index('vote_average');
            $table->index('synced_at');
            $table->fullText(['title', 'original_title']);
            // fulltext already present
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('titles');
    }
};
