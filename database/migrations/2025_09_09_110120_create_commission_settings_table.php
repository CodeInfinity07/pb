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
        Schema::create('commission_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('level')->unique(); // User tier level
            $table->string('name'); // Tier name (Bronze, Silver, Gold, etc.)
            $table->decimal('min_investment', 15, 2)->default(0); // Minimum total investment required
            $table->integer('min_direct_referrals')->default(0); // Minimum direct referrals (Level 1)
            $table->integer('min_indirect_referrals')->default(0); // Minimum indirect referrals (Level 2+3)
            $table->decimal('commission_level_1', 5, 2)->default(0); // Commission % for Level 1 referrals
            $table->decimal('commission_level_2', 5, 2)->default(0); // Commission % for Level 2 referrals
            $table->decimal('commission_level_3', 5, 2)->default(0); // Commission % for Level 3 referrals
            $table->boolean('is_active')->default(true);
            $table->string('color', 7)->nullable(); // Hex color for UI
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['level', 'is_active']);
            $table->index(['is_active', 'sort_order']);
            $table->index('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_settings');
    }
};