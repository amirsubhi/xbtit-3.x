<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Snatch extends Model
{
    // Legacy table is named 'history'
    protected $table = 'history';
    public $timestamps = false;

    protected $fillable = [
        'uid', 'infohash', 'uploaded', 'downloaded', 'active', 'agent', 'date',
    ];

    protected function casts(): array
    {
        return [
            'uploaded'   => 'integer',
            'downloaded' => 'integer',
            'date'       => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'uid');
    }

    public function torrent()
    {
        return $this->belongsTo(Torrent::class, 'infohash', 'info_hash');
    }
}
