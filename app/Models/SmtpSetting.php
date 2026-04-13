<?php

namespace App\Models;

/**
 * Stub model for backward compatibility
 * The smtp_settings table has been removed
 * This prevents errors in code that references SmtpSetting
 */
class SmtpSetting extends BaseModel
{
    protected $guarded = ['id'];
    
    /**
     * Override to prevent database queries
     * Returns a stub instance instead
     */
    public static function first($columns = ['*'])
    {
        $stub = new self();
        $stub->mail_driver = 'mail';
        $stub->mail_from_email = config('mail.from.address', 'noreply@example.com');
        $stub->mail_from_name = config('mail.from.name', config('app.name', 'Worksuite'));
        $stub->verified = 0;
        $stub->email_verified = 0;
        $stub->mail_connection = 'sync';
        $stub->mail_password = null;
        $stub->mail_host = null;
        $stub->mail_port = null;
        $stub->mail_username = null;
        $stub->mail_encryption = null;
        
        return $stub;
    }

    public function verifySmtp()
    {
        return [
            'success' => false,
            'message' => 'SMTP functionality has been removed from this installation.'
        ];
    }

    public function getSetSmtpMessageAttribute()
    {
        return null;
    }
}
