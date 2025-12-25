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
        Schema::create('leaderboards', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'completed'])->default('inactive');
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->boolean('show_to_users')->default(true);
            $table->integer('max_positions')->default(10);
            $table->enum('referral_type', ['all', 'first_level', 'verified_only'])->default('all');
            $table->json('prize_structure')->nullable(); // For storing prize information
            $table->boolean('prizes_distributed')->default(false);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('prizes_distributed_at')->nullable();
            $table->foreignId('prizes_distributed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes for performance
            $table->index(['status', 'start_date', 'end_date']);
            $table->index(['status', 'show_to_users']);
        });

        Schema::create('leaderboard_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leaderboard_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('position');
            $table->integer('referral_count');
            $table->decimal('prize_amount', 10, 2)->nullable();
            $table->boolean('prize_awarded')->default(false);
            $table->timestamp('prize_awarded_at')->nullable();
            $table->timestamps();

            // Unique constraint and indexes
            $table->unique(['leaderboard_id', 'user_id']);
            $table->index(['leaderboard_id', 'position']);
            $table->index(['user_id', 'prize_awarded']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaderboard_positions');
        Schema::dropIfExists('leaderboards');
    }
};