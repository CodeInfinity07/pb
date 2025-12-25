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
        Schema::table('user_profiles', function (Blueprint $table) {
            // If the country column already exists, modify it to set default value
            $table->string('country', 2)->default('PK')->change();
            
            // If the country column doesn't exist, uncomment the line below instead:
            // $table->string('country', 2)->default('PK');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            // Remove the default value (set to null)
            $table->string('country', 2)->default(null)->change();
            
            // If you added the column in up(), uncomment the line below to drop it:
            // $table->dropColumn('country');
        });
    }
};