<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TrackerSanity extends Command
{
    protected $signature   = 'tracker:sanity';
    protected $description = 'Expire stale peers and recount seeds/leechers per torrent (C-06, C-07)';

    public function handle(): int
    {
        $maxAnnounce = (int) (DB::table('settings')->where('key', 'max_announce')->value('value') ?: 1800);
        $cutoff      = time() - ($maxAnnounce * 2);

        // Find expired peers before deleting so we know which torrents need recounting.
        $expiredHashes = DB::table('peers')
            ->where('lastupdate', '<', $cutoff)
            ->distinct()
            ->pluck('infohash')
            ->toArray();

        if (empty($expiredHashes)) {
            $this->info('No stale peers found.');
            return self::SUCCESS;
        }

        // Mark history inactive for all expiring peer sessions.
        $expiredRows = DB::table('peers')
            ->select('infohash', 'passkey')
            ->where('lastupdate', '<', $cutoff)
            ->get();

        foreach ($expiredRows as $row) {
            $uid = DB::table('users')
                ->where(function ($q) use ($row) {
                    $q->where('passkey', $row->passkey)
                      ->orWhere('legacy_passkey', $row->passkey);
                })
                ->value('id');

            if ($uid) {
                DB::table('history')
                    ->where('uid', $uid)
                    ->where('infohash', $row->infohash)
                    ->where('active', 'yes')
                    ->update(['active' => 'no']);
            }
        }

        // Delete the stale peers.
        $deleted = DB::table('peers')
            ->where('lastupdate', '<', $cutoff)
            ->delete();

        // Recount seeds/leechers from scratch for each affected torrent (C-07).
        // A raw recount is authoritative — avoids counter drift from failed announces.
        foreach ($expiredHashes as $infoHash) {
            $counts = DB::table('peers')
                ->selectRaw('status, COUNT(*) as cnt')
                ->where('infohash', $infoHash)
                ->groupBy('status')
                ->pluck('cnt', 'status');

            $seeds    = (int) ($counts['seeder']  ?? 0);
            $leechers = (int) ($counts['leecher'] ?? 0);

            DB::table('files')
                ->where('info_hash', $infoHash)
                ->update(['seeds' => $seeds, 'leechers' => $leechers]);
        }

        $this->info("Sanity complete: deleted {$deleted} stale peers, recounted " . count($expiredHashes) . " torrents.");

        return self::SUCCESS;
    }
}
