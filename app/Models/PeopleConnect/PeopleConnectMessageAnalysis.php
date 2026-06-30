<?php
namespace App\Models\PeopleConnect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeopleConnectMessageAnalysis extends Model
{
    protected $table = 'peopleconnect_message_analyses';
    protected $fillable = ['message_id', 'topic', 'intent', 'tone', 'sentiment', 'language', 'urgency', 'safety_flags', 'reply_needed', 'analyzed_at'];
    protected $casts = ['safety_flags' => 'array', 'reply_needed' => 'boolean', 'analyzed_at' => 'datetime'];

    public function message(): BelongsTo { return $this->belongsTo(PeopleConnectMessage::class, 'message_id'); }
}