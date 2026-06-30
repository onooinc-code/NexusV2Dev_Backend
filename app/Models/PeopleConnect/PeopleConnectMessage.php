<?php
namespace App\Models\PeopleConnect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Contact;

class PeopleConnectMessage extends Model
{
    protected $table = 'peopleconnect_messages';
    protected $fillable = ['conversation_id', 'session_id', 'contact_id', 'sender_type', 'sender_id', 'direction', 'body', 'body_format', 'status', 'provider', 'waha_message_id', 'provider_payload_hash', 'topic_id', 'intent', 'tone', 'sentiment', 'emotional_baseline_snapshot', 'tone_mirroring_snapshot', 'context_snapshot_id', 'trace_id', 'sent_at', 'delivered_at', 'read_at', 'failed_at', 'error_message'];
    protected $casts = ['emotional_baseline_snapshot' => 'array', 'tone_mirroring_snapshot' => 'array', 'sent_at' => 'datetime', 'delivered_at' => 'datetime', 'read_at' => 'datetime', 'failed_at' => 'datetime'];

    public function conversation(): BelongsTo { return $this->belongsTo(PeopleConnectConversation::class, 'conversation_id'); }
    public function session(): BelongsTo { return $this->belongsTo(PeopleConnectSession::class, 'session_id'); }
    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
    public function contextSnapshot(): BelongsTo { return $this->belongsTo(PeopleConnectContextSnapshot::class, 'context_snapshot_id'); }
    public function analysis(): HasOne { return $this->hasOne(PeopleConnectMessageAnalysis::class, 'message_id'); }
    public function tags(): HasMany { return $this->hasMany(PeopleConnectMessageTag::class, 'message_id'); }
    public function deliveryAttempts(): HasMany { return $this->hasMany(PeopleConnectDeliveryAttempt::class, 'message_id'); }
}