<?php 

// database/migrations/xxxx_xx_xx_create_support_ticket_replies_table.php
// Run: php artisan make:migration create_support_ticket_replies_table

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('support_ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->boolean('is_internal_note')->default(false); // For admin-only notes
            $table->timestamps();

            $table->index('ticket_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('support_ticket_replies');
    }
};