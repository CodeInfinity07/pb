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
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->string('message_id', 255)->nullable();
            $table->string('user_phone', 20)->nullable();
            $table->text('message_text')->nullable();
            $table->enum('message_type', ['incoming', 'outgoing'])->nullable();
            $table->integer('timestamp')->nullable();
            $table->json('webhook_data')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            // Add indexes for better performance
            $table->index('user_phone');
            $table->index('message_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};