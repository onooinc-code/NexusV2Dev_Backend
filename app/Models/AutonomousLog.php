<?php

namespace App\Models;

class AutonomousLog extends BaseModel
{
    protected $table = 'autonomous_logs';

    protected $fillable = [
        'action_taken',
        'reasoning',
        'status',
    ];
}
