<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Forum extends Model
{
    protected $fillable = ['forum_category_id', 'name', 'description', 'display_order'];

    protected $casts = ['last_post_at' => 'datetime'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class, 'forum_category_id');
    }

    public function threads(): HasMany
    {
        return $this->hasMany(Thread::class);
    }
}
