<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class HedraMemorySuggestion extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'source_message_id',
        'content',
        'memory_type',
        'confidence',
        'status',
        'reviewed_at',
    ];

    protected $casts = [
        'confidence' => 'decimal:4',
        'reviewed_at' => 'datetime',
    ];

    public function sourceMessage()
    {
        return $this->belongsTo(HedrasoulMessage::class, 'source_message_id');
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
}
