<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('type'); // 'single_user', 'broadcast'
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->json('recipients')->nullable();
            $table->integer('recipients_count')->default(0);
            $table->string('target_type')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('admin_id');
            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notification_logs');
    }
};