<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    public $timestamps = false;

    protected $fillable = ['info_hash', 'uid', 'rating', 'added'];

    public function torrent()
    {
        return $this->belongsTo(Torrent::class, 'info_hash', 'info_hash');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'uid');
    }

    /** Average rating for a torrent as a float (0.0–5.0 half-star scale). */
    public static function averageFor(string $infoHash): float
    {
        $avg = static::where('info_hash', $infoHash)->avg('rating');

        return round(($avg ?? 0) / 2, 1); // convert 1-10 scale to 0.5-5.0
    }
}
