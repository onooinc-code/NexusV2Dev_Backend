<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SoulyInstructionVersion extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
protected $fillable = [
        'version_number',
        'status',
        'content',
        'change_reason',
        'activated_at',
        'activated_by',
    ];

    protected $casts = [
        'content' => 'array',
        'activated_at' => 'datetime',
    ];

    public function activator()
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public function scopeDraft(Builder $query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('status', 'active');
    }

    public function scopeArchived(Builder $query)
    {
        return $query->where('status', 'archived');
    }
}
