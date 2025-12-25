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
        Schema::table('leaderboards', function (Blueprint $table) {
            // Add leaderboard type field
            $table->enum('type', ['competitive', 'target'])->default('competitive')->after('referral_type');
            
            // Add target-based fields
            $table->integer('target_referrals')->nullable()->after('type');
            $table->decimal('target_prize_amount', 10, 2)->nullable()->after('target_referrals');
            $table->integer('max_winners')->nullable()->after('target_prize_amount'); // Optional limit on winners
            
            // Add index for performance
            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leaderboards', function (Blueprint $table) {
            $table->dropIndex(['type', 'status']);
            $table->dropColumn(['type', 'target_referrals', 'target_prize_amount', 'max_winners']);
        });
    }
};