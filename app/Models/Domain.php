<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Domain extends Model
{
    protected $fillable = [
        'name', 'slug', 'display_name', 'icon', 'color',
        'system_prompt', 'citation_format', 'is_active',
        'safety_threshold', 'groundedness_level',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function queries(): HasMany
    {
        return $this->hasMany(Query::class);
    }

    public function evaluationMetrics(): HasMany
    {
        return $this->hasMany(EvaluationMetric::class);
    }
}
