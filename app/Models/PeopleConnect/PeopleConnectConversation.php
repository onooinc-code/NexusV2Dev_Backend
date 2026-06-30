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