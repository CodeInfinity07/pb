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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Location Information (from registration)
            $table->string('country', 2); // Country code (e.g., 'US', 'PK')
            $table->string('city');
            
            // Additional Profile Information
            $table->string('avatar')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->text('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('state_province')->nullable();
            
            // MLM Specific Fields
            $table->string('referrallink'); // Auto-generated referral link
            $table->string('treferrallink')->nullable(); // Telegram referral link if needed
            $table->string('level')->default('TL - 0'); // Trading/MLM Level
            
            // Financial Summary Fields (for quick access)
            $table->decimal('total_investments', 15, 2)->default(0);
            $table->decimal('total_deposit', 15, 2)->default(0);
            $table->decimal('total_withdraw', 15, 2)->default(0);
            $table->decimal('last_deposit', 15, 2)->default(0);
            $table->decimal('last_withdraw', 15, 2)->default(0);
            
            // KYC (Know Your Customer) Fields
            $table->enum('kyc_status', [
                'pending', 
                'submitted', 
                'under_review', 
                'verified', 
                'rejected'
            ])->default('pending');
            $table->timestamp('kyc_submitted_at')->nullable();
            $table->timestamp('kyc_verified_at')->nullable();
            $table->text('kyc_rejection_reason')->nullable();
            $table->string('kyc_session_id')->nullable(); // For Veriff integration
            $table->json('kyc_documents')->nullable(); // Store document info
            
            // Additional MLM Fields
            $table->integer('referral_count')->default(0); // Cache for performance
            $table->decimal('total_commission_earned', 15, 2)->default(0);
            $table->decimal('pending_commission', 15, 2)->default(0);
            $table->integer('max_referral_level')->default(0); // Deepest level in tree
            
            // Account Settings
            $table->boolean('email_notifications')->default(true);
            $table->boolean('sms_notifications')->default(false);
            $table->string('preferred_language', 5)->default('en');
            $table->string('timezone')->default('UTC');
            
            // Verification and Security
            $table->boolean('phone_verified')->default(true);
            $table->timestamp('phone_verified_at')->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->string('two_factor_secret')->nullable();
            
            // Business/Tax Information (for compliance)
            $table->string('tax_id')->nullable();
            $table->string('business_name')->nullable();
            $table->text('business_address')->nullable();
            
            // Social Media (for referral purposes)
            $table->string('facebook_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('telegram_username')->nullable();
            $table->string('whatsapp_number')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable(); // For storing additional flexible data
            $table->text('notes')->nullable(); // Admin notes
            
            $table->timestamps();

            // Indexes for better performance
            $table->unique(['user_id']);
            $table->index(['country']);
            $table->index(['kyc_status']);
            $table->index(['level']);
            $table->index(['kyc_verified_at']);
            $table->index(['phone_verified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};