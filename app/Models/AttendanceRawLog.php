<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRawLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_id',
        'timestamp',
        'type'
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'type' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
