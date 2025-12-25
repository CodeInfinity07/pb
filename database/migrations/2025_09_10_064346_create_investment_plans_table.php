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
        // Update investment_plans table
        Schema::table('investment_plans', function (Blueprint $table) {
            // Drop columns that need to be replaced or aren't needed
            $table->dropColumn(['roi_percentage', 'is_active']);
            
            // Modify maximum_amount to be non-nullable with different precision
            $table->decimal('maximum_amount', 12, 2)->nullable(false)->change();
            
            // Modify minimum_amount precision if needed
            $table->decimal('minimum_amount', 12, 2)->change();
            
            // Add new columns
            $table->decimal('interest_rate', 5, 2)->after('maximum_amount'); // Percentage
            $table->enum('interest_type', ['daily', 'weekly', 'monthly', 'yearly'])->default('daily')->after('interest_rate');
            $table->enum('return_type', ['fixed', 'compound'])->default('fixed')->after('duration_days');
            $table->boolean('capital_return')->default(true)->after('return_type'); // Return capital after duration
            $table->enum('status', ['active', 'inactive', 'paused'])->default('active')->after('capital_return');
            $table->integer('total_investors')->default(0)->after('status');
            $table->decimal('total_invested', 12, 2)->default(0)->after('total_investors');
            $table->json('features')->nullable()->after('total_invested'); // Additional features as JSON
            $table->string('badge')->nullable()->after('features'); // Popular, Recommended, etc.
            $table->string('color_scheme')->default('primary')->after('badge'); // UI color scheme
            
            // Add new indexes
            $table->index(['status', 'sort_order']);
            $table->index('status');
        });

        // Update user_investments table
        Schema::table('user_investments', function (Blueprint $table) {
            // First, backup any active investments data if needed
            // You might want to convert existing date fields to timestamps
            
            // Drop columns that are being replaced
            $table->dropColumn([
                'roi_percentage',
                'duration_days',
                'daily_return',
                'start_date',
                'end_date',
                'last_payout_date'
            ]);
            
            // Modify existing columns
            $table->decimal('amount', 12, 2)->change();
            $table->decimal('total_return', 12, 2)->default(0)->change();
            
            // Add new columns
            $table->decimal('paid_return', 12, 2)->default(0)->after('total_return');
            $table->timestamp('started_at')->after('status');
            $table->timestamp('ends_at')->after('started_at');
            $table->timestamp('last_return_at')->nullable()->after('ends_at');
            $table->timestamp('completed_at')->nullable()->after('last_return_at');
            $table->json('return_history')->nullable()->after('completed_at'); // Track return payments
            $table->text('notes')->nullable()->after('return_history');
        });

        // Modify the status enum to include 'paused' if your database supports it
        // Note: Modifying ENUMs can be tricky in some databases
        // For MySQL, you might need to use raw SQL
        if (config('database.default') === 'mysql') {
            DB::statement("ALTER TABLE user_investments MODIFY COLUMN status ENUM('active', 'completed', 'cancelled', 'paused') DEFAULT 'active'");
        } else {
            // For PostgreSQL or other databases, you might need a different approach
            Schema::table('user_investments', function (Blueprint $table) {
                $table->enum('status', ['active', 'completed', 'cancelled', 'paused'])->default('active')->change();
            });
        }

        // Add new indexes to user_investments
        Schema::table('user_investments', function (Blueprint $table) {
            $table->index(['investment_plan_id', 'status']);
            $table->index(['status', 'ends_at']);
        });

        // Create the new investment_returns table
        Schema::create('investment_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_investment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->enum('type', ['interest', 'capital'])->default('interest');
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->timestamp('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->string('transaction_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['status', 'due_date']);
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the investment_returns table
        Schema::dropIfExists('investment_returns');

        // Revert user_investments table changes
        Schema::table('user_investments', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn([
                'paid_return',
                'started_at',
                'ends_at',
                'last_return_at',
                'completed_at',
                'return_history',
                'notes'
            ]);
            
            // Drop new indexes
            $table->dropIndex(['investment_plan_id', 'status']);
            $table->dropIndex(['status', 'ends_at']);
            
            // Re-add old columns
            $table->decimal('roi_percentage', 5, 2)->after('investment_plan_id');
            $table->integer('duration_days')->after('roi_percentage');
            $table->decimal('daily_return', 15, 2)->default(0)->after('total_return');
            $table->date('start_date')->after('status');
            $table->date('end_date')->after('start_date');
            $table->date('last_payout_date')->nullable()->after('end_date');
            
            // Revert column changes
            $table->decimal('amount', 15, 2)->change();
            $table->decimal('total_return', 15, 2)->default(0)->change();
        });

        // Revert status enum
        if (config('database.default') === 'mysql') {
            DB::statement("ALTER TABLE user_investments MODIFY COLUMN status ENUM('active', 'completed', 'cancelled') DEFAULT 'active'");
        } else {
            Schema::table('user_investments', function (Blueprint $table) {
                $table->enum('status', ['active', 'completed', 'cancelled'])->default('active')->change();
            });
        }

        // Re-add old indexes
        Schema::table('user_investments', function (Blueprint $table) {
            $table->index(['last_payout_date']);
        });

        // Revert investment_plans table changes
        Schema::table('investment_plans', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn([
                'interest_rate',
                'interest_type',
                'return_type',
                'capital_return',
                'status',
                'total_investors',
                'total_invested',
                'features',
                'badge',
                'color_scheme'
            ]);
            
            // Drop new indexes
            $table->dropIndex(['status', 'sort_order']);
            $table->dropIndex(['status']);
            
            // Re-add old columns
            $table->decimal('roi_percentage', 5, 2)->after('maximum_amount');
            $table->boolean('is_active')->default(true)->after('duration_days');
            
            // Revert column changes
            $table->decimal('minimum_amount', 15, 2)->change();
            $table->decimal('maximum_amount', 15, 2)->nullable()->change();
        });
    }
};