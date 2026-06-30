<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WahaSyncProcess extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'status',
        'progress',
        'total_items',
        'processed_items',
        'failed_items',
        'last_cursor_id',
        'config',
        'errors',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'config' => 'array',
        'errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
