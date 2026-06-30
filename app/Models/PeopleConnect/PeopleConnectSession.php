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