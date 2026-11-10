<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('plan', ['plus', 'pro']);
            $table->enum('billing_mode', ['auto', 'manual']);
            $table->string('payment_method', 30)->nullable();
            $table->enum('pending_plan', ['plus', 'pro'])->nullable();
            $table->enum('status', ['pending', 'active', 'past_due', 'canceled', 'expired']);
            $table->enum('source', ['midtrans', 'manual'])->default('midtrans');
            $table->string('gateway_subscription_id')->nullable()->unique();
            $table->text('gateway_token')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('grace_ends_at')->nullable();
            $table->string('manual_reason')->nullable();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'status', 'current_period_end']);
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway_order_id')->unique();
            $table->enum('plan', ['plus', 'pro']);
            $table->string('method', 30)->nullable();
            $table->unsignedInteger('amount');
            $table->unsignedInteger('est_fee_idr')->nullable();
            $table->char('currency', 3)->default('IDR');
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded', 'expired'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('provider');
            $table->string('event_key')->unique();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
    }
};
