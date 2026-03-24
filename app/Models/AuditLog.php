<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'query_id', 'action', 'entity_type', 'entity_id',
        'description', 'details', 'ip_address', 'severity',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function query(): BelongsTo
    {
        return $this->belongsTo(Query::class);
    }
}
