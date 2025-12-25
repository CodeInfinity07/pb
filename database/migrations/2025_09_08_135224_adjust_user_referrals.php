<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_referrals', function (Blueprint $table) {
            // Add indexes for better performance
            // Laravel will handle duplicate index creation automatically
            try {
                $table->index('created_at', 'user_referrals_created_at_index');
            } catch (\Exception $e) {
                // Index might already exist, continue
            }
            
            try {
                $table->index('updated_at', 'user_referrals_updated_at_index');
            } catch (\Exception $e) {
                // Index might already exist, continue
            }
            
            try {
                $table->index('commission_earned', 'user_referrals_commission_earned_index');
            } catch (\Exception $e) {
                // Index might already exist, continue
            }
            
            // Add composite indexes for common queries
            try {
                $table->index(['sponsor_id', 'status', 'created_at'], 'user_referrals_sponsor_status_created_index');
            } catch (\Exception $e) {
                // Index might already exist, continue
            }
            
            try {
                $table->index(['status', 'created_at'], 'user_referrals_status_created_index');
            } catch (\Exception $e) {
                // Index might already exist, continue
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_referrals', function (Blueprint $table) {
            // Drop the indexes we added (using the specific index names)
            try {
                $table->dropIndex('user_referrals_created_at_index');
            } catch (\Exception $e) {
                // Index might not exist, continue
            }
            
            try {
                $table->dropIndex('user_referrals_updated_at_index');
            } catch (\Exception $e) {
                // Index might not exist, continue
            }
            
            try {
                $table->dropIndex('user_referrals_commission_earned_index');
            } catch (\Exception $e) {
                // Index might not exist, continue
            }
            
            try {
                $table->dropIndex('user_referrals_sponsor_status_created_index');
            } catch (\Exception $e) {
                // Index might not exist, continue
            }
            
            try {
                $table->dropIndex('user_referrals_status_created_index');
            } catch (\Exception $e) {
                // Index might not exist, continue
            }
        });
    }
};