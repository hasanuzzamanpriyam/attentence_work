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
        // Drop smtp_settings table as SMTP functionality is removed
        if (Schema::hasTable('smtp_settings')) {
            Schema::dropIfExists('smtp_settings');
        }
        
        // Remove email_verified column from global_settings if exists
        if (Schema::hasColumn('global_settings', 'email_verified')) {
            Schema::table('global_settings', function (Blueprint $table) {
                $table->dropColumn('email_verified');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate smtp_settings table for rollback
        if (!Schema::hasTable('smtp_settings')) {
            Schema::create('smtp_settings', function (Blueprint $table) {
                $table->increments('id');
                $table->string('mail_driver')->default('smtp');
                $table->string('mail_host')->default('smtp.gmail.com');
                $table->string('mail_port')->default('587');
                $table->string('mail_username')->default('youremail@gmail.com');
                $table->text('mail_password');
                $table->string('mail_from_name')->default('your name');
                $table->string('mail_from_email')->default('from@email.com');
                $table->enum('mail_encryption', ['tls', 'ssl'])->default('tls');
                $table->boolean('verified')->default(0);
                $table->boolean('email_verified')->default(0);
                $table->string('mail_connection')->default('sync');
                $table->timestamps();
            });
        }
    }
};
