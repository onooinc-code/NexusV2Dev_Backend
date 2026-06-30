<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HedrasoulMessageMention extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
protected $fillable = [
        'message_id',
        'mention_type',
        'object_id',
        'object_type',
        'display_name',
        'sensitivity',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function message()
    {
        return $this->belongsTo(HedrasoulMessage::class, 'message_id');
    }
}
