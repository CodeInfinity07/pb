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
        Schema::create('kyc_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Veriff Session Information
            $table->string('session_id')->unique();
            $table->string('attempt_id')->nullable();
            
            // Verification Status
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->enum('decision', ['approved', 'declined', 'resubmission_requested'])->nullable();
            $table->decimal('decision_score', 3, 2)->nullable();
            
            // Person Information (from verification)
            $table->string('verified_first_name')->nullable();
            $table->string('verified_last_name')->nullable();
            $table->date('verified_date_of_birth')->nullable();
            $table->enum('verified_gender', ['M', 'F'])->nullable();
            $table->string('verified_id_number')->nullable();
            
            // Document Information
            $table->string('document_type')->nullable(); // id_card, passport, etc.
            $table->string('document_country')->nullable();
            $table->string('document_number')->nullable();
            $table->date('document_valid_until')->nullable();
            
            // Key Verification Checks (boolean)
            $table->boolean('document_verified')->nullable();
            $table->boolean('face_verified')->nullable();
            $table->boolean('liveness_check')->nullable();
            
            // Processing
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('raw_data')->nullable(); // Store full webhook data
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kyc_verifications');
    }
};