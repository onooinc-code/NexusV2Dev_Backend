<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class HedrasoulApprovalRequest extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
protected $fillable = [
        'source_type',
        'source_id',
        'action_description',
        'inputs',
        'expected_side_effects',
        'risk_level',
        'cost_estimate',
        'context_snapshot_id',
        'agent_reasoning',
        'status',
        'decided_by',
        'decided_at',
        'decision_notes',
    ];

    protected $casts = [
        'inputs' => 'array',
        'cost_estimate' => 'decimal:4',
        'decided_at' => 'datetime',
    ];

    public function decider()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function scopePending(Builder $query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected(Builder $query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeDeferred(Builder $query)
    {
        return $query->where('status', 'deferred');
    }
}
