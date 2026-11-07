<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clip_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('title_id')->constrained()->cascadeOnDelete();
            $table->enum('video_platform', ['youtube', 'other']);
            $table->string('video_id')->nullable();
            $table->string('url', 500);
            $table->string('channel_name')->nullable();
            $table->string('channel_id')->nullable();
            $table->string('label')->nullable();
            $table->enum('trust', ['official', 'trusted', 'unverified'])->default('unverified');
            $table->enum('source', ['admin', 'resolver']);
            $table->char('scene_key', 40)->nullable();
            $table->enum('status', ['active', 'reported', 'removed'])->default('active');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['title_id', 'status']);
            $table->index(['title_id', 'scene_key']);
            $table->unique(['title_id', 'url']);
        });

        Schema::create('channel_allowlist', function (Blueprint $table) {
            $table->id();
            $table->enum('platform', ['youtube'])->default('youtube');
            $table->string('channel_id')->nullable();
            $table->string('channel_name');
            $table->enum('trust', ['official', 'trusted', 'blocked']);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('channel_name');
            $table->unique(['platform', 'channel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_allowlist');
        Schema::dropIfExists('clip_links');
    }
};
