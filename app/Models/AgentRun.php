<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentRun extends Model
{
    protected $fillable = [
        'query_id', 'agent_type', 'status',
        'input', 'output', 'token_count', 'latency_ms',
        'trace_id', 'span_id', 'error_message',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
    ];

    public function relatedQuery(): BelongsTo
    {
        return $this->belongsTo(Query::class, 'query_id');
    }
}
