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
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->timestamp('kyc_session_created_at')->nullable()->after('kyc_submitted_at');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE user_profiles DROP CONSTRAINT IF EXISTS user_profiles_kyc_status_check");
            DB::statement("ALTER TABLE user_profiles ALTER COLUMN kyc_status TYPE varchar(255)");
            DB::statement("ALTER TABLE user_profiles ADD CONSTRAINT user_profiles_kyc_status_check CHECK (kyc_status IN ('pending', 'session_created', 'submitted', 'under_review', 'verified', 'rejected'))");
        } else {
            Schema::table('user_profiles', function (Blueprint $table) {
                $table->enum('kyc_status', [
                    'pending',
                    'session_created',
                    'submitted',
                    'under_review',
                    'verified',
                    'rejected'
                ])->default('pending')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn('kyc_session_created_at');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE user_profiles DROP CONSTRAINT IF EXISTS user_profiles_kyc_status_check");
            DB::statement("ALTER TABLE user_profiles ADD CONSTRAINT user_profiles_kyc_status_check CHECK (kyc_status IN ('pending', 'submitted', 'under_review', 'verified', 'rejected'))");
        } else {
            Schema::table('user_profiles', function (Blueprint $table) {
                $table->enum('kyc_status', [
                    'pending',
                    'submitted',
                    'under_review',
                    'verified',
                    'rejected'
                ])->default('pending')->change();
            });
        }
    }
};
