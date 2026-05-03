<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'sub', 'sort_index', 'image'];

    public function torrents()
    {
        return $this->hasMany(Torrent::class, 'category');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'sub');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'sub');
    }
}
