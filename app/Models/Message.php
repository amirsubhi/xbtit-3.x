<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['sender_id', 'receiver_id', 'subject', 'body'];

    protected function casts(): array
    {
        return [
            'read_at'           => 'datetime',
            'sender_deleted'    => 'boolean',
            'receiver_deleted'  => 'boolean',
        ];
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
