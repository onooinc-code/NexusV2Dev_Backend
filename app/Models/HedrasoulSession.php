<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class HedrasoulSession extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
protected $fillable = [
        'user_id',
        'title',
        'status',
        'topic',
        'task_count',
        'approval_count',
        'instruction_version_id',
        'last_autonomy_mode',
        'mode',
        'opened_at',
        'closed_at',
        'summary',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->title)) {
                $model->title = 'HedraSoul Session ' . now()->toDateTimeString();
            }
        });
    }

    public function getModeAttribute()
    {
        return $this->last_autonomy_mode;
    }

    public function setModeAttribute($value)
    {
        $this->attributes['last_autonomy_mode'] = $value;
    }


    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(HedrasoulMessage::class, 'session_id');
    }

    public function instructionVersion()
    {
        return $this->belongsTo(SoulyInstructionVersion::class, 'instruction_version_id');
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('status', 'active');
    }

    public function scopeArchived(Builder $query)
    {
        return $query->where('status', 'archived');
    }

    public function scopeClosed(Builder $query)
    {
        return $query->where('status', 'closed');
    }
}
