<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->onDelete('cascade');
            $table->date('followup_date');
            $table->enum('type', ['call', 'email', 'meeting', 'whatsapp', 'other'])->default('call');
            $table->text('notes');
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['lead_id', 'followup_date']);
            $table->index(['followup_date', 'completed']);
            $table->index(['created_by']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('followups');
    }
};
