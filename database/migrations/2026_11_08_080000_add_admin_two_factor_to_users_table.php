<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('admin_totp_secret')->nullable();
            $table->text('admin_recovery_codes')->nullable();
            $table->string('admin_last_ip_hash', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['admin_totp_secret', 'admin_recovery_codes', 'admin_last_ip_hash']);
        });
    }
};
