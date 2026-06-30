<?php
namespace App\Models\PeopleConnect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeopleConnectConversationTopic extends Model
{
    protected $table = 'peopleconnect_conversation_topics';
    protected $fillable = ['conversation_id', 'name', 'first_message_id', 'last_message_id', 'message_count', 'first_seen_at', 'last_seen_at'];
    protected $casts = ['first_seen_at' => 'datetime', 'last_seen_at' => 'datetime'];

    public function conversation(): BelongsTo { return $this->belongsTo(PeopleConnectConversation::class, 'conversation_id'); }
    public function firstMessage(): BelongsTo { return $this->belongsTo(PeopleConnectMessage::class, 'first_message_id'); }
    public function lastMessage(): BelongsTo { return $this->belongsTo(PeopleConnectMessage::class, 'last_message_id'); }
}