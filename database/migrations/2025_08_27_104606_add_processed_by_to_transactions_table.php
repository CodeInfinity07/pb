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
        Schema::table('transactions', function (Blueprint $table) {
            // Add processed_by column to track which admin processed the transaction
            $table->unsignedBigInteger('processed_by')->nullable()->after('processed_at');
            
            // Add foreign key constraint
            $table->foreign('processed_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null')
                  ->name('fk_transactions_processed_by');
            
            // Add index for performance
            $table->index('processed_by', 'idx_transactions_processed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign('fk_transactions_processed_by');
            
            // Drop index
            $table->dropIndex('idx_transactions_processed_by');
            
            // Drop the column
            $table->dropColumn('processed_by');
        });
    }
};