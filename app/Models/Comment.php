<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = ['info_hash', 'user_id', 'body', 'ori_text'];

    public function torrent()
    {
        return $this->belongsTo(Torrent::class, 'info_hash', 'info_hash');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
