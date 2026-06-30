<?php
namespace App\Models\PeopleConnect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeopleConnectContextSnapshot extends Model
{
    protected $table = 'peopleconnect_context_snapshots';
    protected $fillable = ['conversation_id', 'session_id', 'payload', 'token_estimate', 'agent_id', 'model_id'];
    protected $casts = ['payload' => 'array'];

    public function conversation(): BelongsTo { return $this->belongsTo(PeopleConnectConversation::class, 'conversation_id'); }
    public function session(): BelongsTo { return $this->belongsTo(PeopleConnectSession::class, 'session_id'); }
}