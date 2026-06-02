<?php

namespace App\Models;

class ProactiveTrigger extends BaseModel
{
    protected $table = 'proactive_triggers';

    protected $fillable = [
        'eca_rule_id',
        'trigger_type',
        'next_run_at',
        'context_payload',
        'status',
    ];

    protected $casts = [
        'next_run_at' => 'datetime',
        'context_payload' => 'array',
    ];

    public function rule()
    {
        return $this->belongsTo(EcaRule::class, 'eca_rule_id');
    }
}
