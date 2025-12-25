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
        // Modify the enum column to add 'adjust' type
        DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('profit', 'deposit', 'withdrawal', 'commission', 'roi', 'investment', 'bonus', 'adjust') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'adjust' from the enum column
        DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('profit', 'deposit', 'withdrawal', 'commission', 'roi', 'investment', 'bonus') NOT NULL");
    }
};