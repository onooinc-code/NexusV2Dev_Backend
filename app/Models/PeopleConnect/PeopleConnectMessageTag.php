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