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
        // Create investment_plan_tiers table
        Schema::create('investment_plan_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_plan_id')->constrained()->onDelete('cascade');
            $table->integer('tier_level')->default(0); // 0, 1, 2, 3, etc.
            $table->string('tier_name'); // e.g., "Starter", "Bronze", "Silver", "Gold", "Platinum"
            $table->decimal('minimum_amount', 15, 2);
            $table->decimal('maximum_amount', 15, 2);
            $table->decimal('interest_rate', 5, 2); // Interest rate for this tier
            $table->integer('min_user_level')->default(0); // Minimum user level required
            $table->text('tier_description')->nullable();
            $table->json('tier_features')->nullable(); // Special features for this tier
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['investment_plan_id', 'tier_level']);
            $table->index(['investment_plan_id', 'is_active']);
            $table->unique(['investment_plan_id', 'tier_level']);
        });

        // Modify investment_plans table to remove single-tier fields
        Schema::table('investment_plans', function (Blueprint $table) {
            // Keep these fields for backward compatibility and plan-wide defaults
            // Remove the unique constraints if they exist
            $table->decimal('minimum_amount', 15, 2)->nullable()->change();
            $table->decimal('maximum_amount', 15, 2)->nullable()->change();
            $table->decimal('interest_rate', 5, 2)->nullable()->change();
            
            // Add new fields for tier-based plans
            $table->boolean('is_tiered')->default(false)->after('return_type');
            $table->integer('max_tier_level')->default(0)->after('is_tiered');
            $table->decimal('base_interest_rate', 5, 2)->nullable()->after('max_tier_level');
            $table->json('tier_settings')->nullable()->after('base_interest_rate');
        });

        // Add user_level to users table if it doesn't exist
        if (!Schema::hasColumn('users', 'user_level')) {
            Schema::table('users', function (Blueprint $table) {
                $table->integer('user_level')->default(0)->after('status');
                $table->decimal('total_invested', 15, 2)->default(0)->after('user_level');
                $table->decimal('total_earned', 15, 2)->default(0)->after('total_invested');
                $table->timestamp('level_updated_at')->nullable()->after('total_earned');
            });
        }

        // Modify user_investments table to track tier
        Schema::table('user_investments', function (Blueprint $table) {
            $table->integer('tier_level')->default(0)->after('investment_plan_id');
            $table->decimal('tier_interest_rate', 5, 2)->nullable()->after('tier_level');
            $table->integer('user_level_at_investment')->default(0)->after('tier_interest_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_plan_tiers');
        
        Schema::table('investment_plans', function (Blueprint $table) {
            $table->dropColumn([
                'is_tiered',
                'max_tier_level', 
                'base_interest_rate',
                'tier_settings'
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'user_level',
                'total_invested',
                'total_earned',
                'level_updated_at'
            ]);
        });

        Schema::table('user_investments', function (Blueprint $table) {
            $table->dropColumn([
                'tier_level',
                'tier_interest_rate',
                'user_level_at_investment'
            ]);
        });
    }
};