<?php
namespace App\Models\PeopleConnect;
use Illuminate\Database\Eloquent\Model;

class PeopleConnectRawProviderEvent extends Model
{
    protected $table = 'peopleconnect_raw_provider_events';
    protected $fillable = ['event_type', 'payload', 'session_name', 'received_at', 'processed_at', 'processing_status'];
    protected $casts = ['payload' => 'array', 'received_at' => 'datetime', 'processed_at' => 'datetime'];
}