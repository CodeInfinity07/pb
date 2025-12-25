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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('type')->default('info'); // info, warning, success, danger
            $table->string('target_audience')->default('all'); // all, active, verified, kyc_verified
            $table->json('target_user_ids')->nullable(); // specific user IDs
            $table->integer('priority')->default(1); // 1 = highest
            $table->string('status')->default('active'); // active, inactive, scheduled
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('show_once')->default(true);
            $table->boolean('is_dismissible')->default(true);
            $table->string('button_text')->default('Got it');
            $table->string('button_link')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['status', 'scheduled_at']);
            $table->index(['target_audience', 'priority']);
        });

        Schema::create('user_announcement_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('announcement_id');
            $table->timestamp('viewed_at');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('announcement_id')->references('id')->on('announcements')->onDelete('cascade');
            $table->unique(['user_id', 'announcement_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_announcement_views');
        Schema::dropIfExists('announcements');
    }
};