<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLevel extends Model
{
    public $timestamps = false;

    protected $table = 'users_level';

    protected $fillable = [
        'id_level',
        'level',
        'predef_level',
        'admin_access',
        'can_download',
        'prefixcolor',
        'suffixcolor',
        'WT',
        'can_upload',
        'can_comment',
        'can_invite',
    ];

    protected function casts(): array
    {
        return [
            'admin_access' => 'boolean',
            'can_download' => 'boolean',
        ];
    }
}
