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
        Schema::table('users', function (Blueprint $table) {
            // Add new name fields
            $table->string('first_name')->after('id');
            $table->string('last_name')->after('first_name');
            
            // Add MLM specific fields  
            $table->string('username')->unique()->after('email');
            $table->string('phone')->unique()->after('username');
            $table->string('referral_code')->unique()->after('phone');
            $table->unsignedBigInteger('sponsor_id')->nullable()->after('referral_code');
            
            // Add status and login tracking
            $table->enum('status', [
                'pending_verification', 
                'active', 
                'inactive', 
                'blocked'
            ])->default('pending_verification')->after('sponsor_id');
            
            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
            
            // Add foreign key constraint
            $table->foreign('sponsor_id')->references('id')->on('users')->onDelete('set null');
            
            // Add indexes for better performance
            $table->index(['sponsor_id']);
            $table->index(['referral_code']);
            $table->index(['status']);
            $table->index(['username']);
            $table->index(['phone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['sponsor_id']);
            
            // Drop indexes
            $table->dropIndex(['sponsor_id']);
            $table->dropIndex(['referral_code']);
            $table->dropIndex(['status']);
            $table->dropIndex(['username']);
            $table->dropIndex(['phone']);
            
            // Drop columns
            $table->dropColumn([
                'first_name',
                'last_name',
                'username',
                'phone',
                'referral_code',
                'sponsor_id',
                'status',
                'last_login_at',
                'last_login_ip'
            ]);
        });
    }
};