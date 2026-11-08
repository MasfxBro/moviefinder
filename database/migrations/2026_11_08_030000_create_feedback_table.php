<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['bug', 'suggestion', 'content', 'praise', 'other']);
            $table->string('message', 1000);
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('page_path', 200)->nullable();
            $table->enum('status', ['new', 'read', 'in_progress', 'done', 'archived'])->default('new');
            $table->string('admin_note', 1000)->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
