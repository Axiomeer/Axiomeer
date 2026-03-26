<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'user_id', 'domain_id', 'title', 'last_activity_at',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function queries(): HasMany
    {
        return $this->hasMany(Query::class)->orderBy('created_at');
    }

    public function latestQuery(): BelongsTo
    {
        return $this->belongsTo(Query::class, 'id', 'conversation_id')->latest();
    }
}
