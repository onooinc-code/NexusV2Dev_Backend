<?php
namespace App\Models\PeopleConnect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeopleConnectProcessingLog extends Model
{
    protected $table = 'peopleconnect_processing_logs';
    protected $fillable = ['conversation_id', 'event_type', 'description', 'payload'];
    protected $casts = ['payload' => 'array'];

    public function conversation(): BelongsTo { return $this->belongsTo(PeopleConnectConversation::class, 'conversation_id'); }
}