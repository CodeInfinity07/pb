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
            // Block tracking fields
            $table->timestamp('blocked_at')->nullable()->after('last_login_ip');
            $table->unsignedBigInteger('blocked_by')->nullable()->after('blocked_at');
            $table->string('block_reason')->nullable()->after('blocked_by'); // spam, fraud, violation, abuse, security, other
            $table->text('block_notes')->nullable()->after('block_reason');
            $table->timestamp('block_expires_at')->nullable()->after('block_notes');
            
            // Unblock tracking fields
            $table->timestamp('unblocked_at')->nullable()->after('block_expires_at');
            $table->unsignedBigInteger('unblocked_by')->nullable()->after('unblocked_at');
            $table->string('unblock_reason')->nullable()->after('unblocked_by');
            
            // Foreign key constraints
            $table->foreign('blocked_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null')
                  ->name('fk_users_blocked_by');
                  
            $table->foreign('unblocked_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null')
                  ->name('fk_users_unblocked_by');
            
            // Indexes for performance
            $table->index('blocked_at', 'idx_users_blocked_at');
            $table->index('block_reason', 'idx_users_block_reason');
            $table->index('block_expires_at', 'idx_users_block_expires_at');
            $table->index(['status', 'blocked_at'], 'idx_users_status_blocked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign key constraints first
            $table->dropForeign('fk_users_blocked_by');
            $table->dropForeign('fk_users_unblocked_by');
            
            // Drop indexes
            $table->dropIndex('idx_users_blocked_at');
            $table->dropIndex('idx_users_block_reason');
            $table->dropIndex('idx_users_block_expires_at');
            $table->dropIndex('idx_users_status_blocked_at');
            
            // Drop columns
            $table->dropColumn([
                'blocked_at',
                'blocked_by', 
                'block_reason',
                'block_notes',
                'block_expires_at',
                'unblocked_at',
                'unblocked_by',
                'unblock_reason'
            ]);
        });
    }
};