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