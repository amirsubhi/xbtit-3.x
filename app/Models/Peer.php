<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Peer extends Model
{
    protected $table = 'peers';
    public $timestamps = false;

    protected $fillable = [
        'infohash', 'peer_id', 'ip', 'port', 'status',
        'uploaded', 'downloaded', 'lastupdate', 'natuser',
        'client', 'dns', 'passkey', 'compact',
    ];

    protected function casts(): array
    {
        return [
            'uploaded'   => 'integer',
            'downloaded' => 'integer',
            'lastupdate' => 'integer',
            'port'       => 'integer',
        ];
    }

    public function torrent()
    {
        return $this->belongsTo(Torrent::class, 'infohash', 'info_hash');
    }

    public function isSeeder(): bool
    {
        return $this->status === 'seeder';
    }
}
