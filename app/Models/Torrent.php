<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Torrent extends Model
{
    // Table name kept as 'files' for announce/scrape compatibility
    protected $table = 'files';
    protected $primaryKey = 'info_hash';
    public $incrementing = false;
    protected $keyType = 'string';

    // Legacy table uses 'data' (upload date) and 'lastupdate', not created_at/updated_at
    public $timestamps = false;

    protected $fillable = [
        'info_hash', 'filename', 'url', 'info', 'size', 'comment',
        'category', 'external', 'announce_url', 'uploader',
        'anonymous', 'dlbytes', 'seeds', 'leechers', 'finished',
        'bin_hash', 'added', 'speed',
    ];

    protected function casts(): array
    {
        return [
            'size'     => 'integer',
            'seeds'    => 'integer',
            'leechers' => 'integer',
            'finished' => 'integer',
            'dlbytes'  => 'integer',
            'speed'    => 'integer',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploader');
    }

    public function peers()
    {
        return $this->hasMany(Peer::class, 'infohash', 'info_hash');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'info_hash', 'info_hash');
    }

    public function snatches()
    {
        return $this->hasMany(Snatch::class, 'infohash', 'info_hash');
    }

    public function isExternal(): bool
    {
        return $this->external === 'yes';
    }
}
