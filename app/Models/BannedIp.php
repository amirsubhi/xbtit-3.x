<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BannedIp extends Model
{
    public $timestamps = false;

    protected $table = 'banned_ip';

    protected $fillable = ['first', 'last', 'addedby', 'comment'];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'addedby');
    }

    public static function isBanned(string $ip): bool
    {
        $ipLong = sprintf('%u', ip2long($ip));

        if (!$ipLong) {
            return false;
        }

        return static::where('first', '<=', $ipLong)
            ->where('last', '>=', $ipLong)
            ->exists();
    }
}
