<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $fillable = [
        'domain_id', 'uploaded_by', 'title', 'filename', 'original_filename',
        'mime_type', 'file_size', 'storage_path', 'status', 'index_name',
        'chunk_count', 'metadata', 'indexed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'indexed_at' => 'datetime',
        'file_size' => 'integer',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function citations(): HasMany
    {
        return $this->hasMany(QueryCitation::class);
    }
}
