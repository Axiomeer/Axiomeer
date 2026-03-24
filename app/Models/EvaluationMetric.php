<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationMetric extends Model
{
    protected $fillable = [
        'query_id', 'domain_id', 'run_type',
        'faithfulness', 'answer_relevancy', 'context_precision', 'context_recall',
        'groundedness_pct', 'unsupported_token_pct',
        'total_claims', 'supported_claims', 'unsupported_claims',
        'details',
    ];

    protected $casts = [
        'faithfulness' => 'float',
        'answer_relevancy' => 'float',
        'context_precision' => 'float',
        'context_recall' => 'float',
        'groundedness_pct' => 'float',
        'unsupported_token_pct' => 'float',
        'details' => 'array',
    ];

    public function query(): BelongsTo
    {
        return $this->belongsTo(Query::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
