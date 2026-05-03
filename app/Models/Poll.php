<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poll extends Model
{
    protected $fillable = ['user_id', 'title', 'active'];
    protected $casts    = ['active' => 'boolean'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class)->orderBy('display_order');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    public function totalVotes(): int
    {
        return $this->votes()->count();
    }

    public function userHasVoted(int $userId): bool
    {
        return $this->votes()->where('user_id', $userId)->exists();
    }
}
