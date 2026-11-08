<?php

declare(strict_types=1);

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
        Schema::create('title_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('media_type', ['movie', 'tv'])->default('movie');
            $table->string('title_text', 200);
            $table->unsignedSmallInteger('release_year')->nullable();
            $table->string('note', 500)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'added'])->default('pending');
            $table->string('admin_note', 500)->nullable();
            $table->foreignId('resolved_title_id')->nullable()->constrained('titles')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('title_requests');
    }
};
