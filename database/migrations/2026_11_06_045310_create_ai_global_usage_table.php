<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_global_usage', function (Blueprint $table): void {
            $table->date('used_on')->primary();
            $table->unsignedInteger('count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_global_usage');
    }
};
