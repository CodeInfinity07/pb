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
        Schema::create('cryptocurrencies', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Bitcoin, Ethereum, etc.
            $table->string('symbol', 10)->unique(); // BTC, ETH, etc.
            $table->string('icon')->nullable(); // Icon filename
            $table->string('network')->nullable(); // Bitcoin, Ethereum, BSC, etc.
            $table->string('contract_address')->nullable(); // For tokens
            $table->tinyInteger('decimal_places')->default(8);
            $table->decimal('min_withdrawal', 20, 8)->default(0);
            $table->decimal('max_withdrawal', 20, 8)->nullable();
            $table->decimal('withdrawal_fee', 20, 8)->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cryptocurrencies');
    }
};