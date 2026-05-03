<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    const THEMES = [
        'xbtit-default' => 'xbtit Default (classic blue-grey)',
        'darklair'      => 'Dark Lair (dark + orange)',
        'modern'        => 'Modern (slate dark)',
    ];

    protected $fillable = [
        'username',
        'email',
        'password',
        'passkey',
        'legacy_passkey',
        'legacy_passkey_expires_at',
        'id_level',
        'theme',
        'salt',
        'pass_type',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'passkey',
        'legacy_passkey',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'          => 'datetime',
            'locked_at'                  => 'datetime',
            'legacy_passkey_expires_at'  => 'datetime',
            'password'                   => 'hashed',
            'downloaded'                 => 'integer',
            'uploaded'                   => 'integer',
        ];
    }

    public function level()
    {
        return $this->belongsTo(UserLevel::class, 'id_level', 'id_level');
    }

    public function snatches()
    {
        return $this->hasMany(\App\Models\Snatch::class, 'uid');
    }

    public function torrentsUploaded()
    {
        return $this->hasMany(\App\Models\Torrent::class, 'uploader');
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    public function isAdmin(): bool
    {
        return (bool) $this->level?->admin_access;
    }

    /**
     * Whether this user still has a legacy (MD5/SHA1) password hash.
     * Cleared once the user logs in and gets rehashed to argon2id.
     */
    public function hasLegacyPassword(): bool
    {
        return $this->pass_type !== null;
    }

    public function incrementFailedLogins(): void
    {
        $this->increment('failed_login_attempts');

        if ($this->failed_login_attempts >= 10) {
            $this->locked_at = now();
            $this->save();
        }
    }

    public function clearFailedLogins(): void
    {
        $this->update(['failed_login_attempts' => 0, 'locked_at' => null]);
    }
}
