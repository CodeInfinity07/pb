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
        Schema::table('user_profiles', function (Blueprint $table) {
            // Add new column to track when session was created
            $table->timestamp('kyc_session_created_at')->nullable()->after('kyc_submitted_at');
            
            // Update enum values to include session_created status
            $table->enum('kyc_status', [
                'pending',
                'session_created',    // New intermediate status
                'submitted',
                'under_review',
                'verified',
                'rejected'
            ])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn('kyc_session_created_at');
            
            // Revert enum to original values
            $table->enum('kyc_status', [
                'pending',
                'submitted',
                'under_review',
                'verified',
                'rejected'
            ])->default('pending')->change();
        });
    }
};