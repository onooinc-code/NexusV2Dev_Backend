<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoulyActionPolicy extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
protected $fillable = [
        'policy_type',
        'rule_key',
        'rule_value',
        'applies_to_mode',
    ];
}
