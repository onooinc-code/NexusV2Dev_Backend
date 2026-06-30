<?php
namespace App\Models\PeopleConnect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeopleConnectReplyDraft extends Model
{
    protected $table = 'peopleconnect_reply_drafts';
    protected $fillable = ['conversation_id', 'message_id', 'agent_id', 'body', 'status', 'context_snapshot_id', 'trace_id', 'approved_by', 'approved_at', 'sent_at', 'rejected_at'];
    protected $casts = ['approved_at' => 'datetime', 'sent_at' => 'datetime', 'rejected_at' => 'datetime'];

    public function conversation(): BelongsTo { return $this->belongsTo(PeopleConnectConversation::class, 'conversation_id'); }
    public function message(): BelongsTo { return $this->belongsTo(PeopleConnectMessage::class, 'message_id'); }
    public function contextSnapshot(): BelongsTo { return $this->belongsTo(PeopleConnectContextSnapshot::class, 'context_snapshot_id'); }
}