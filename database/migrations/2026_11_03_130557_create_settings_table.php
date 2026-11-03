<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->json('value');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
