<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueryCitation extends Model
{
    protected $fillable = [
        'query_id', 'document_id', 'source_snippet', 'cited_text',
        'document_title', 'page_number', 'chunk_index',
        'relevance_score', 'verdict',
    ];

    protected $casts = [
        'relevance_score' => 'float',
    ];

    public function query(): BelongsTo
    {
        return $this->belongsTo(Query::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
