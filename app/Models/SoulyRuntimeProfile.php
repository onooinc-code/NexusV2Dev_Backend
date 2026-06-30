<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoulyRuntimeProfile extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
protected $fillable = [
        'autonomy_mode',
        'active_model_instance_id',
        'active_instruction_version_id',
        'active_persona_id',
        'tool_permissions',
        'memory_access',
        'contact_access',
        'task_execution_access',
        'workflow_execution_access',
        'external_messaging_access',
        'is_quarantined',
    ];

    protected $casts = [
        'tool_permissions' => 'array',
        'memory_access' => 'boolean',
        'contact_access' => 'boolean',
        'task_execution_access' => 'boolean',
        'workflow_execution_access' => 'boolean',
        'external_messaging_access' => 'boolean',
        'is_quarantined' => 'boolean',
    ];

    public function activeInstructionVersion()
    {
        return $this->belongsTo(SoulyInstructionVersion::class, 'active_instruction_version_id');
    }

    public function activeModelInstance()
    {
        return $this->belongsTo(AiInstance::class, 'active_model_instance_id');
    }

    public function activePersona()
    {
        return $this->belongsTo(AgentPersona::class, 'active_persona_id');
    }
}
