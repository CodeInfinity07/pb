<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile');
            $table->string('whatsapp')->nullable();
            $table->string('country')->nullable();
            $table->string('source')->nullable();
            $table->enum('status', ['hot', 'warm', 'cold', 'converted'])->default('cold');
            $table->enum('interest', ['Low', 'Medium', 'High'])->nullable();
            $table->text('notes');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['source', 'created_at']);
            $table->index(['created_by']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('leads');
    }
};
