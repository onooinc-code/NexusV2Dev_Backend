<?php
$modelsPath = __DIR__ . '/app/Models/PeopleConnect';
if (!is_dir($modelsPath)) {
    mkdir($modelsPath, 0755, true);
}

$models = [
    'PeopleConnectConversation' => <<<'PHP'
<?php
namespace App\Models\PeopleConnect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Contact;

class PeopleConnectConversation extends Model
{
    protected $table = 'peopleconnect_conversations';
    protected $fillable = ['contact_id', 'channel', 'provider', 'provider_conversation_id', 'status', 'last_message_at', 'last_message_preview', 'unread_count', 'reply_mode_effective', 'agent_status'];
    protected $casts = ['last_message_at' => 'datetime'];

    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
    public function sessions(): HasMany { return $this->hasMany(PeopleConnectSession::class, 'conversation_id'); }
    public function messages(): HasMany { return $this->hasMany(PeopleConnectMessage::class, 'conversation_id'); }
    public function topics(): HasMany { return $this->hasMany(PeopleConnectConversationTopic::class, 'conversation_id'); }
}
PHP,

    'PeopleConnectSession' => <<<'PHP'
<?php
namespace App\Models\PeopleConnect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Contact;

class PeopleConnectSession extends Model
{
    protected $table = 'peopleconnect_sessions';
    protected $fillable = ['conversation_id', 'contact_id', 'status', 'opened_at', 'closed_at', 'closed_reason', 'message_count', 'summary'];
    protected $casts = ['opened_at' => 'datetime', 'closed_at' => 'datetime'];
    public $timestamps = false; 

    public function conversation(): BelongsTo { return $this->belongsTo(PeopleConnectConversation::class, 'conversation_id'); }
    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
    public function messages(): HasMany { return $this->hasMany(PeopleConnectMessage::class, 'session_id'); }
}
PHP,

    'PeopleConnectContextSnapshot' => <<<'PHP'
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
PHP,

    'PeopleConnectMessage' => <<<'PHP'
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
PHP,

    'PeopleConnectMessageAnalysis' => <<<'PHP'
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
PHP,

    'PeopleConnectMessageTag' => <<<'PHP'
<?php
namespace App\Models\PeopleConnect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeopleConnectMessageTag extends Model
{
    protected $table = 'peopleconnect_message_tags';
    protected $fillable = ['message_id', 'tag'];

    public function message(): BelongsTo { return $this->belongsTo(PeopleConnectMessage::class, 'message_id'); }
}
PHP,

    'PeopleConnectReplyDraft' => <<<'PHP'
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
PHP,

    'PeopleConnectDeliveryAttempt' => <<<'PHP'
<?php
namespace App\Models\PeopleConnect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeopleConnectDeliveryAttempt extends Model
{
    protected $table = 'peopleconnect_delivery_attempts';
    protected $fillable = ['message_id', 'attempt_number', 'status', 'waha_response', 'attempted_at', 'error_message'];
    protected $casts = ['waha_response' => 'array', 'attempted_at' => 'datetime'];

    public function message(): BelongsTo { return $this->belongsTo(PeopleConnectMessage::class, 'message_id'); }
}
PHP,

    'PeopleConnectSyncRun' => <<<'PHP'
<?php
namespace App\Models\PeopleConnect;
use Illuminate\Database\Eloquent\Model;

class PeopleConnectSyncRun extends Model
{
    protected $table = 'peopleconnect_sync_runs';
    protected $fillable = ['type', 'status', 'started_at', 'completed_at', 'contacts_found', 'conversations_found', 'messages_found', 'errors', 'triggered_by'];
    protected $casts = ['errors' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
}
PHP,

    'PeopleConnectRawProviderEvent' => <<<'PHP'
<?php
namespace App\Models\PeopleConnect;
use Illuminate\Database\Eloquent\Model;

class PeopleConnectRawProviderEvent extends Model
{
    protected $table = 'peopleconnect_raw_provider_events';
    protected $fillable = ['event_type', 'payload', 'session_name', 'received_at', 'processed_at', 'processing_status'];
    protected $casts = ['payload' => 'array', 'received_at' => 'datetime', 'processed_at' => 'datetime'];
}
PHP,

    'PeopleConnectProcessingLog' => <<<'PHP'
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
PHP,

    'PeopleConnectConversationTopic' => <<<'PHP'
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
PHP,

    'PeopleConnectReplyModeOverride' => <<<'PHP'
<?php
namespace App\Models\PeopleConnect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Contact;

class PeopleConnectReplyModeOverride extends Model
{
    protected $table = 'peopleconnect_reply_mode_overrides';
    protected $fillable = ['contact_id', 'reply_mode', 'set_by', 'reason'];

    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
}
PHP,
];

foreach ($models as $name => $content) {
    file_put_contents($modelsPath . '/' . $name . '.php', $content);
    echo "Created $name.php\n";
}
