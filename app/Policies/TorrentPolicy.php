<?php

namespace App\Policies;

use App\Models\Torrent;
use App\Models\User;

class TorrentPolicy
{
    public function update(User $user, Torrent $torrent): bool
    {
        return $user->isAdmin() || (int) $torrent->uploader === $user->id;
    }

    public function delete(User $user, Torrent $torrent): bool
    {
        return $user->isAdmin() || (int) $torrent->uploader === $user->id;
    }
}
