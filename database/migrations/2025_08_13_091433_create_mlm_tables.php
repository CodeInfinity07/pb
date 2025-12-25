<?php

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
        // User Referrals Table (Direct referrals only)
        Schema::create('user_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('level')->default(1); // Direct referral level
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            $table->decimal('commission_earned', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['sponsor_id', 'user_id']);
            $table->index(['sponsor_id', 'status']);
            $table->index(['user_id']);
            $table->index(['level']);
        });

        // Referral Tree Table (Multi-level structure)
        Schema::create('referral_tree', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('level'); // 1 = direct, 2 = second level, etc.
            $table->timestamps();

            $table->unique(['sponsor_id', 'user_id', 'level']);
            $table->index(['sponsor_id', 'level']);
            $table->index(['user_id']);
        });

        // Commission Structure Table
        Schema::create('commission_structures', function (Blueprint $table) {
            $table->id();
            $table->integer('level'); // Referral level (1, 2, 3, etc.)
            $table->decimal('percentage', 5, 2); // Commission percentage
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['level']);
            $table->index(['is_active']);
        });

        // Investment Plans Table
        Schema::create('investment_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('minimum_amount', 15, 2);
            $table->decimal('maximum_amount', 15, 2)->nullable();
            $table->decimal('roi_percentage', 5, 2); // ROI rate
            $table->integer('duration_days'); // Investment period
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        // User Investments Table
        Schema::create('user_investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('investment_plan_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->decimal('roi_percentage', 5, 2);
            $table->integer('duration_days');
            $table->decimal('total_return', 15, 2)->default(0);
            $table->decimal('daily_return', 15, 2)->default(0);
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('last_payout_date')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'end_date']);
            $table->index(['last_payout_date']);
        });

        // Transactions Table
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('transaction_id')->unique();
            $table->enum('type', ['deposit', 'withdrawal', 'commission', 'roi', 'investment', 'bonus']);
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('USD');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('crypto_address')->nullable();
            $table->string('crypto_txid')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // For additional data
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'status']);
            $table->index(['transaction_id']);
            $table->index(['status']);
            $table->index(['type']);
            $table->index(['created_at']);
        });

        // Crypto Wallets Table
        Schema::create('crypto_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('currency', 10); // BTC, ETH, USDT, etc.
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('balance', 20, 8)->default(0);
            $table->decimal('usd_rate', 15, 2)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'currency']);
            $table->index(['user_id', 'is_active']);
            $table->index(['currency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_wallets');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('user_investments');
        Schema::dropIfExists('investment_plans');
        Schema::dropIfExists('commission_structures');
        Schema::dropIfExists('referral_tree');
        Schema::dropIfExists('user_referrals');
    }
};