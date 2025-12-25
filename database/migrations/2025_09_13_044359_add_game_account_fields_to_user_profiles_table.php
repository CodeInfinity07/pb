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
            // Game account fields
            $table->string('uname')->nullable()->after('kyc_documents'); // Game username
            $table->text('upwd')->nullable()->after('uname'); // Encrypted game password
            $table->decimal('umoney', 15, 2)->default(0)->after('upwd'); // Game balance
            $table->timestamp('game_linked_at')->nullable()->after('umoney'); // When game account was linked
            $table->json('game_settings')->nullable()->after('game_linked_at'); // Additional game settings
            
            // Add index for faster queries
            $table->index('uname');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropIndex(['uname']);
            $table->dropColumn([
                'uname',
                'upwd',
                'umoney',
                'game_linked_at',
                'game_settings'
            ]);
        });
    }
};