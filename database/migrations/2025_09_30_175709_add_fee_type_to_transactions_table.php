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
        $invalidTransactions = DB::table('transactions')
            ->whereNotIn('type', ['deposit', 'withdrawal', 'commission', 'roi', 'investment', 'bonus', 'profit', 'adjust'])
            ->get();
        if ($invalidTransactions->isNotEmpty()) {
            \Log::warning('Found transactions with invalid type values:', [
                'count' => $invalidTransactions->count(),
                'transactions' => $invalidTransactions->pluck('id', 'type')->toArray()
            ]);

            DB::table('transactions')
                ->whereNotIn('type', ['deposit', 'withdrawal', 'commission', 'roi', 'investment', 'bonus', 'profit', 'adjust'])
                ->update(['type' => 'deposit']);
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('deposit', 'withdrawal', 'commission', 'roi', 'investment', 'bonus', 'fee', 'profit', 'adjust') NOT NULL");
        } elseif (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_type_check");
            DB::statement("ALTER TABLE transactions ALTER COLUMN type TYPE varchar(255)");
            DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_type_check CHECK (type IN ('deposit', 'withdrawal', 'commission', 'roi', 'investment', 'bonus', 'fee', 'profit', 'adjust'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('transactions')
            ->where('type', 'fee')
            ->update(['type' => 'deposit']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('deposit', 'withdrawal', 'commission', 'roi', 'investment', 'bonus', 'profit', 'adjust') NOT NULL");
        } elseif (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_type_check");
            DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_type_check CHECK (type IN ('deposit', 'withdrawal', 'commission', 'roi', 'investment', 'bonus', 'profit', 'adjust'))");
        }
    }
};
