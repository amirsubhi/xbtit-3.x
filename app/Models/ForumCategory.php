<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumCategory extends Model
{
    protected $fillable = ['name', 'display_order'];

    public function forums(): HasMany
    {
        return $this->hasMany(Forum::class)->orderBy('display_order');
    }
}
