<?php

namespace App\Models;

class EcaRule extends BaseModel
{
    protected $table = 'eca_rules';

    protected $fillable = [
        'name',
        'natural_language_rule',
        'event_type',
        'conditions',
        'actions',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
        'is_active' => 'boolean',
    ];

    public function triggers()
    {
        return $this->hasMany(ProactiveTrigger::class, 'eca_rule_id');
    }
}
