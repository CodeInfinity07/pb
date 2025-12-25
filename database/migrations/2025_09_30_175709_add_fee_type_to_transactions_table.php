<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, check for any invalid type values and log them
        $invalidTransactions = DB::table('transactions')
            ->whereNotIn('type', ['deposit', 'withdrawal', 'commission', 'roi', 'investment', 'bonus'])
            ->get();
        if ($invalidTransactions->isNotEmpty()) {
            \Log::warning('Found transactions with invalid type values:', [
                'count' => $invalidTransactions->count(),
                'transactions' => $invalidTransactions->pluck('id', 'type')->toArray()
            ]);

            // Update invalid types to a default value (e.g., 'deposit')
            // Or you can choose another appropriate default
            DB::table('transactions')
                ->whereNotIn('type', ['deposit', 'withdrawal', 'commission', 'roi', 'investment', 'bonus'])
                ->update(['type' => 'deposit']);
        }

        // Now modify the enum to add 'fee'
        DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('deposit', 'withdrawal', 'commission', 'roi', 'investment', 'bonus', 'fee') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Before removing 'fee', update any transactions with type 'fee' to something else
        DB::table('transactions')
            ->where('type', 'fee')
            ->update(['type' => 'deposit']);

        // Remove 'fee' type
        DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('deposit', 'withdrawal', 'commission', 'roi', 'investment', 'bonus') NOT NULL");
    }
};