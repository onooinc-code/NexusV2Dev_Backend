<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HedraMemoryVersion extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
protected $fillable = [
        'fact_id',
        'content',
        'version_number',
        'changed_by',
        'change_reason',
    ];

    public function fact()
    {
        return $this->belongsTo(HedraProfileFact::class, 'fact_id');
    }

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
