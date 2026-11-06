<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('used_on');
            $table->unsignedSmallInteger('count')->default(0);
            $table->unsignedSmallInteger('smart_count')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'used_on']);
        });

        Schema::create('ai_usage_log', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('mode', ['global', 'title']);
            $table->foreignId('title_id')->nullable()->constrained()->nullOnDelete();
            $table->string('model')->nullable();
            $table->enum('model_tier', ['standard', 'smart']);
            $table->boolean('grounded')->default(false);
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('cost_idr')->default(0);
            $table->enum('status', ['ok', 'error', 'refused']);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_log');
        Schema::dropIfExists('ai_usage');
    }
};
