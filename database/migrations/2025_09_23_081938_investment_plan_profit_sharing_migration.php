<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add profit sharing column to investment_plans table
        Schema::table('investment_plans', function (Blueprint $table) {
            $table->boolean('profit_sharing_enabled')->default(false)->after('is_tiered');
        });

        // Create profit sharing configuration table with shortened name
        Schema::create('plan_profit_sharing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('investment_plan_tier_id')->constrained()->onDelete('cascade');
            $table->decimal('level_1_commission', 5, 2)->default(0); // Direct sponsor
            $table->decimal('level_2_commission', 5, 2)->default(0); // Sponsor's sponsor
            $table->decimal('level_3_commission', 5, 2)->default(0); // Third level up
            $table->decimal('max_commission_cap', 10, 2)->nullable(); // Optional cap per user
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Custom index names to avoid MySQL 64-char limit
            $table->index(['investment_plan_id', 'investment_plan_tier_id'], 'plan_profit_idx');
            $table->index(['investment_plan_id'], 'plan_profit_plan_idx');
            $table->index(['investment_plan_tier_id'], 'plan_profit_tier_idx');
        });

        // Create profit sharing transactions table with shortened name  
        Schema::create('profit_sharing_txns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_investment_id')->constrained()->onDelete('cascade');
            $table->foreignId('beneficiary_user_id')->constrained('users')->onDelete('cascade'); // Who receives commission
            $table->foreignId('source_user_id')->constrained('users')->onDelete('cascade'); // Who made the investment
            $table->integer('commission_level'); // 1, 2, or 3
            $table->decimal('commission_amount', 10, 2);
            $table->decimal('source_investment_amount', 10, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->enum('status', ['pending', 'paid', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Custom index names
            $table->index(['beneficiary_user_id', 'status'], 'profit_txn_beneficiary_idx');
            $table->index(['source_user_id', 'commission_level'], 'profit_txn_source_idx');
            $table->index(['user_investment_id'], 'profit_txn_investment_idx');
            $table->index(['status'], 'profit_txn_status_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('profit_sharing_txns');
        Schema::dropIfExists('plan_profit_sharing');
        
        Schema::table('investment_plans', function (Blueprint $table) {
            $table->dropColumn('profit_sharing_enabled');
        });
    }
};