<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class HedrasoulNotification extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
protected $fillable = [
        'notification_type',
        'priority',
        'title',
        'body',
        'related_type',
        'related_id',
        'action_buttons',
        'is_read',
        'snoozed_until',
        'is_dismissed',
    ];

    protected $casts = [
        'action_buttons' => 'array',
        'is_read' => 'boolean',
        'is_dismissed' => 'boolean',
        'snoozed_until' => 'datetime',
    ];

    public function scopeUnread(Builder $query)
    {
        return $query->where('is_read', false);
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('is_dismissed', false)
            ->where(function ($q) {
                $q->whereNull('snoozed_until')
                  ->orWhere('snoozed_until', '<', now());
            });
    }
}
