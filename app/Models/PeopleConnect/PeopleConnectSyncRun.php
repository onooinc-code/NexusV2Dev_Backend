<?php
namespace App\Models\PeopleConnect;
use Illuminate\Database\Eloquent\Model;

class PeopleConnectSyncRun extends Model
{
    protected $table = 'peopleconnect_sync_runs';
    protected $fillable = ['type', 'status', 'started_at', 'completed_at', 'contacts_found', 'conversations_found', 'messages_found', 'errors', 'triggered_by'];
    protected $casts = ['errors' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
}