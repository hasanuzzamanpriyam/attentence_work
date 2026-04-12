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
        Schema::table('attendance_raw_logs', function (Blueprint $table) {
            // Drop foreign key constraint
            $table->dropForeign(['user_id']);
            // Make user_id nullable
            $table->unsignedInteger('user_id')->nullable()->change();
            // Add foreign key constraint back as nullable
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_raw_logs', function (Blueprint $table) {
            // Drop nullable foreign key
            $table->dropForeign(['user_id']);
            // Make user_id not nullable again
            $table->unsignedInteger('user_id')->nullable(false)->change();
            // Add foreign key constraint back as not nullable
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
