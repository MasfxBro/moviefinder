<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_misses', function (Blueprint $table) {
            $table->id();
            $table->string('query_norm', 120)->unique();
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_seen_at')->useCurrent();
            $table->enum('status', ['open', 'requested', 'added', 'ignored'])->default('open');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_misses');
    }
};
