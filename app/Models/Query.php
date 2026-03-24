<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Query extends Model
{
    protected $fillable = [
        'user_id', 'domain_id', 'question', 'answer', 'status',
        'groundedness_score', 'lettuce_score', 'confidence_score',
        'composite_safety_score', 'safety_level',
        'retrieved_chunks', 'provenance_dag',
        'token_count', 'latency_ms',
    ];

    protected $casts = [
        'retrieved_chunks' => 'array',
        'provenance_dag' => 'array',
        'groundedness_score' => 'float',
        'lettuce_score' => 'float',
        'confidence_score' => 'float',
        'composite_safety_score' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function citations(): HasMany
    {
        return $this->hasMany(QueryCitation::class);
    }

    public function agentRuns(): HasMany
    {
        return $this->hasMany(AgentRun::class);
    }

    public function evaluationMetrics(): HasMany
    {
        return $this->hasMany(EvaluationMetric::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
