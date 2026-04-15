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
        Schema::table('users', function (Blueprint $table) {
            $table->time('duty_time')->nullable()->comment('Total duty hours expected (e.g., 09:00:00 for 9 hours)');
            $table->time('check_in_time')->nullable()->comment('Expected check-in time (e.g., 09:00:00)');
            $table->time('check_out_time')->nullable()->comment('Expected check-out time (e.g., 18:00:00)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['duty_time', 'check_in_time', 'check_out_time']);
        });
    }
};
