<?php
// Create a new migration to fix the google2fa_secret column size
// Run: php artisan make:migration fix_google2fa_secret_column_size

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
            // Change google2fa_secret from VARCHAR to TEXT to accommodate encrypted data
            $table->text('google2fa_secret')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert back to string if needed (but this might cause issues)
            $table->string('google2fa_secret')->nullable()->change();
        });
    }
};

// Alternative: If you want to set a specific VARCHAR size instead of TEXT
// You can use this instead in the up() method:
// $table->string('google2fa_secret', 500)->nullable()->change();

// QUICK FIX: If you want to manually run SQL command instead of migration:
/*
ALTER TABLE users MODIFY COLUMN google2fa_secret TEXT NULL;
*/