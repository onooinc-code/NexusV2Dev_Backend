<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HedrasoulContextSnapshot extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
public const UPDATED_AT = null;

    protected $fillable = [
        'session_id',
        'message_id',
        'instruction_version_id',
        'persona_id',
        'model_instance_id',
        'payload',
        'token_estimate',
        'risk_classification',
        'excluded_items',
    ];

    protected $casts = [
        'payload' => 'array',
        'excluded_items' => 'array',
    ];

    public function session()
    {
        return $this->belongsTo(HedrasoulSession::class, 'session_id');
    }

    public function message()
    {
        return $this->belongsTo(HedrasoulMessage::class, 'message_id');
    }
}
