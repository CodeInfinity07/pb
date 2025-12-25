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
        Schema::table('investment_plans', function (Blueprint $table) {
            $table->dropColumn(['roi_percentage', 'is_active']);
            
            $table->decimal('maximum_amount', 12, 2)->nullable(false)->change();
            $table->decimal('minimum_amount', 12, 2)->change();
            
            $table->decimal('interest_rate', 5, 2)->after('maximum_amount');
            $table->string('interest_type')->default('daily')->after('interest_rate');
            $table->string('return_type')->default('fixed')->after('duration_days');
            $table->boolean('capital_return')->default(true)->after('return_type');
            $table->string('status')->default('active')->after('capital_return');
            $table->integer('total_investors')->default(0)->after('status');
            $table->decimal('total_invested', 12, 2)->default(0)->after('total_investors');
            $table->json('features')->nullable()->after('total_invested');
            $table->string('badge')->nullable()->after('features');
            $table->string('color_scheme')->default('primary')->after('badge');
            
            $table->index(['status', 'sort_order']);
            $table->index('status');
        });

        Schema::table('user_investments', function (Blueprint $table) {
            $table->dropColumn([
                'roi_percentage',
                'duration_days',
                'daily_return',
                'start_date',
                'end_date',
                'last_payout_date'
            ]);
            
            $table->decimal('amount', 12, 2)->change();
            $table->decimal('total_return', 12, 2)->default(0)->change();
            
            $table->decimal('paid_return', 12, 2)->default(0)->after('total_return');
            $table->timestamp('started_at')->after('status');
            $table->timestamp('ends_at')->after('started_at');
            $table->timestamp('last_return_at')->nullable()->after('ends_at');
            $table->timestamp('completed_at')->nullable()->after('last_return_at');
            $table->json('return_history')->nullable()->after('completed_at');
            $table->text('notes')->nullable()->after('return_history');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE user_investments MODIFY COLUMN status ENUM('active', 'completed', 'cancelled', 'paused') DEFAULT 'active'");
        } elseif (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE user_investments DROP CONSTRAINT IF EXISTS user_investments_status_check");
            DB::statement("ALTER TABLE user_investments ALTER COLUMN status TYPE varchar(255)");
            DB::statement("ALTER TABLE user_investments ADD CONSTRAINT user_investments_status_check CHECK (status IN ('active', 'completed', 'cancelled', 'paused'))");
        }

        Schema::table('user_investments', function (Blueprint $table) {
            $table->index(['investment_plan_id', 'status']);
            $table->index(['status', 'ends_at']);
        });

        Schema::create('investment_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_investment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->string('type')->default('interest');
            $table->string('status')->default('pending');
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
        Schema::dropIfExists('investment_returns');

        Schema::table('user_investments', function (Blueprint $table) {
            $table->dropColumn([
                'paid_return',
                'started_at',
                'ends_at',
                'last_return_at',
                'completed_at',
                'return_history',
                'notes'
            ]);
            
            $table->dropIndex(['investment_plan_id', 'status']);
            $table->dropIndex(['status', 'ends_at']);
            
            $table->decimal('roi_percentage', 5, 2)->after('investment_plan_id');
            $table->integer('duration_days')->after('roi_percentage');
            $table->decimal('daily_return', 15, 2)->default(0)->after('total_return');
            $table->date('start_date')->after('status');
            $table->date('end_date')->after('start_date');
            $table->date('last_payout_date')->nullable()->after('end_date');
            
            $table->decimal('amount', 15, 2)->change();
            $table->decimal('total_return', 15, 2)->default(0)->change();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE user_investments MODIFY COLUMN status ENUM('active', 'completed', 'cancelled') DEFAULT 'active'");
        } elseif (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE user_investments DROP CONSTRAINT IF EXISTS user_investments_status_check");
            DB::statement("ALTER TABLE user_investments ADD CONSTRAINT user_investments_status_check CHECK (status IN ('active', 'completed', 'cancelled'))");
        }

        Schema::table('user_investments', function (Blueprint $table) {
            $table->index(['last_payout_date']);
        });

        Schema::table('investment_plans', function (Blueprint $table) {
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
            
            $table->dropIndex(['status', 'sort_order']);
            $table->dropIndex(['status']);
            
            $table->decimal('roi_percentage', 5, 2)->after('maximum_amount');
            $table->boolean('is_active')->default(true)->after('duration_days');
            
            $table->decimal('minimum_amount', 15, 2)->change();
            $table->decimal('maximum_amount', 15, 2)->nullable()->change();
        });
    }
};
