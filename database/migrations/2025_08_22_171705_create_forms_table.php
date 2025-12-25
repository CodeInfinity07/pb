<?php

// Migration: 2024_01_01_000004_create_forms_table.php (FIXED)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('submit_button_text')->default('Submit Application');
            $table->string('success_message')->default('Thank you! We will contact you soon.');
            $table->json('standard_fields')->nullable(); // Array of enabled standard fields
            $table->json('custom_fields')->nullable(); // Array of custom field definitions
            $table->boolean('is_active')->default(true);
            $table->integer('submissions_count')->default(0);
            $table->string('slug')->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['is_active', 'created_at']);
            $table->index(['slug']);
            $table->index(['created_by']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('forms');
    }
};
