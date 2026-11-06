<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_budget', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('window_start');
            $table->date('window_end');
            $table->unsignedInteger('spent_idr')->default(0);
            $table->unsignedSmallInteger('clip_searches')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'window_start']);
        });

        Schema::create('ai_free_pool', function (Blueprint $table): void {
            $table->date('used_on')->primary();
            $table->unsignedSmallInteger('taken')->default(0);
        });

        Schema::create('ai_free_places', function (Blueprint $table): void {
            $table->id();
            $table->date('used_on');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('taken_at')->useCurrent();
            $table->unique(['used_on', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_free_places');
        Schema::dropIfExists('ai_free_pool');
        Schema::dropIfExists('ai_budget');
    }
};
