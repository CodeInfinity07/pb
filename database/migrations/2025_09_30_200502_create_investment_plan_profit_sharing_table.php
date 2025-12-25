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
        Schema::create('investment_plan_profit_sharing', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('investment_plan_id')
                ->constrained('investment_plans')
                ->onDelete('cascade');
            
            $table->foreignId('investment_plan_tier_id')
                ->constrained('investment_plan_tiers')
                ->onDelete('cascade');
            
            // Commission Rates (stored as percentages)
            $table->decimal('level_1_commission', 8, 2)->default(0);
            $table->decimal('level_2_commission', 8, 2)->default(0);
            $table->decimal('level_3_commission', 8, 2)->default(0);
            
            // Commission Cap (optional maximum)
            $table->decimal('max_commission_cap', 10, 2)->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index('investment_plan_id');
            $table->index('investment_plan_tier_id');
            $table->index('is_active');
            
            // Unique constraint - one profit sharing config per plan-tier combination
            $table->unique(['investment_plan_id', 'investment_plan_tier_id'], 'plan_tier_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_plan_profit_sharing');
    }
};