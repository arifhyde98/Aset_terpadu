<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntegrationAuditLog extends Model
{
    use HasFactory;

    protected $table = 'integration_audit_logs';

    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'correlation_id',
        'nibar',
        'event_name',
        'source_system',
        'direction',
        'changes',
        'reason',
        'sync_status',
        'error_message',
        'data_version',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'changes'    => 'array',
        'created_at' => 'datetime',
    ];
}

