<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps  = false;
    public $updatedAt   = false;

    protected $fillable = ['user_id', 'action', 'ip', 'passkey_hint', 'context'];

    protected function casts(): array
    {
        return ['context' => 'array'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, array $context = []): void
    {
        static::create([
            'user_id'    => auth()->id(),
            'action'     => $action,
            'ip'         => request()->ip(),
            'context'    => $context,
            'created_at' => now(),
        ]);
    }
}
