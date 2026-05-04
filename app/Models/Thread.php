<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Thread extends Model
{
    protected $fillable = ['forum_id', 'user_id', 'title', 'locked', 'sticky'];

    protected $casts = [
        'locked'       => 'boolean',
        'sticky'       => 'boolean',
        'last_post_at' => 'datetime',
    ];

    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class)->orderBy('created_at');
    }

    public function latestPost(): HasMany
    {
        return $this->hasMany(Post::class)->latest()->limit(1);
    }
}
