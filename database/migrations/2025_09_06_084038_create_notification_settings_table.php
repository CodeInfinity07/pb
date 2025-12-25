<?php

// Create this migration: php artisan make:migration create_notification_settings_table

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
        // Only create notification_settings since notifications table already exists
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('class_name')->unique(); // Notification class name (e.g., 'WelcomeUser')
            $table->string('description')->nullable(); // Human-readable description
            $table->json('channels'); // Available channels ['mail', 'database', etc.]
            $table->json('settings'); // Full settings data (templates, content, etc.)
            $table->boolean('is_active')->default(true); // Whether notification is active
            $table->integer('usage_count')->default(0); // How many times sent
            $table->timestamp('last_used_at')->nullable(); // When last sent
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['class_name', 'is_active']);
            $table->index('last_used_at');
            $table->index('usage_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};