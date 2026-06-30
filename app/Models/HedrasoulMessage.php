<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HedrasoulMessage extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
protected $fillable = [
        'session_id',
        'sender_type',
        'sender_id',
        'body',
        'body_format',
        'status',
        'intent',
        'topic',
        'tone',
        'sentiment',
        'risk_level',
        'context_snapshot_id',
        'trace_id',
        'model_instance_id',
        'token_count',
        'cost_usd',
        'is_streaming',
    ];

    protected $casts = [
        'is_streaming' => 'boolean',
        'cost_usd' => 'decimal:4',
    ];

    public function session()
    {
        return $this->belongsTo(HedrasoulSession::class, 'session_id');
    }

    public function mentions()
    {
        return $this->hasMany(HedrasoulMessageMention::class, 'message_id');
    }

    public function contextSnapshot()
    {
        return $this->belongsTo(HedrasoulContextSnapshot::class, 'context_snapshot_id');
    }

    public function trace()
    {
        return $this->belongsTo(SoulyActionTrace::class, 'trace_id', 'trace_id');
    }
}
