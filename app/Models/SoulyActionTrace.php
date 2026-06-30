<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoulyActionTrace extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
protected $fillable = [
        'message_id',
        'trace_id',
        'parsed_intent',
        'selected_action',
        'model_instance_id',
        'agent_id',
        'instruction_version_id',
        'context_snapshot_id',
        'tools_invoked',
        'tasks_created',
        'workflows_triggered',
        'approval_decision',
        'final_output',
        'cost_usd',
        'duration_ms',
        'errors',
    ];

    protected $casts = [
        'tools_invoked' => 'array',
        'tasks_created' => 'array',
        'workflows_triggered' => 'array',
        'errors' => 'array',
        'cost_usd' => 'decimal:4',
    ];

    public function message()
    {
        return $this->belongsTo(HedrasoulMessage::class, 'message_id');
    }
}
