<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HedraProfileFact extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'memory_type',
        'content',
        'confidence',
        'evidence',
        'sensitivity',
        'visibility_scope',
        'is_approved',
        'approved_at',
        'version',
    ];

    protected $casts = [
        'confidence' => 'decimal:4',
        'evidence' => 'array',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function versions()
    {
        return $this->hasMany(HedraMemoryVersion::class, 'fact_id');
    }
}
