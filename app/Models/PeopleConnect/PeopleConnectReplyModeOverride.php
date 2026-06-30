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