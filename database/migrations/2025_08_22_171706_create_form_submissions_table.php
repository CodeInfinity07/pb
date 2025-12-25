<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->onDelete('cascade');
            $table->foreignId('lead_id')->nullable()->constrained()->onDelete('set null'); // Created lead from submission
            $table->json('form_data'); // All submitted form data
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referrer')->nullable();
            $table->enum('status', ['new', 'processed', 'converted'])->default('new');
            $table->timestamps();

            $table->index(['form_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['lead_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('form_submissions');
    }
};
