<?php

namespace App\Services;

use App\Models\BannedIp;
use App\Models\Peer;
use App\Models\Torrent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnnounceService
{
    // summaryAdd accumulator — flushed as one UPDATE to files at the end
    private array $summary = [];
    private ?string $currentInfoHash = null;

    // Limits from settings (loaded once per request)
    private int $maxSeeds = 3;
    private int $maxLeech = 2;
    private int $maxPeers = 50;
    private int $interval = 1800;
    private int $minInterval = 300;

    public function __construct(
        private readonly BEncodeService $bencode,
        private readonly PasskeyService $passkeys,
        private readonly SettingService $settings,
    ) {}

    /**
     * Build the xbtt redirect URL if xbtt is enabled, or null if not.
     * Called by the controller so it can return a proper HTTP 302.
     */
    public function xbttRedirectUrl(Request $request): ?string
    {
        if (!$this->settings->get('xbtt_enabled', false)) {
            return null;
        }

        $xbttUrl = $this->settings->get('xbtt_url', 'http://localhost:2710');
        $pid     = (string) ($request->query('pid', ''));

        if (!preg_match('/^[a-zA-Z0-9+\/=_-]*$/', $pid)) {
            $pid = '';
        }

        $qs = $request->server('QUERY_STRING', '');
        $qs = preg_replace('/(?:^|&)pid=[^&]*/', '', $qs);
        $qs = ltrim($qs, '&');

        return $pid
            ? rtrim($xbttUrl, '/') . "/{$pid}/announce?{$qs}"
            : rtrim($xbttUrl, '/') . "/announce?{$qs}";
    }

    /**
     * Main entry point — returns a raw bencoded string (always HTTP 200).
     */
    public function handle(Request $request): string
    {
        // Must complete even if client disconnects (e.g., event=stopped)
        ignore_user_abort(true);

        $this->loadSettings();

        // Validate pid immediately — kills header injection + SQLi in one rule
        $pid = (string) ($request->query('pid', ''));
        if (!preg_match($this->passkeys->validationPattern(), $pid)) {
            return $this->bencode->failure('Invalid passkey format.');
        }

        // info_hash and peer_id arrive as raw 20-byte binary in the query string;
        // PHP URL-decodes them automatically, so we hex-encode for storage
        $rawInfoHash = $request->query('info_hash', '');
        $rawPeerId   = $request->query('peer_id', '');

        if (strlen($rawInfoHash) !== 20 || strlen($rawPeerId) !== 20) {
            return $this->bencode->failure('Invalid info_hash or peer_id.');
        }

        $infoHash = bin2hex($rawInfoHash);
        $peerId   = bin2hex($rawPeerId);
        $this->currentInfoHash = $infoHash;

        // Required numeric fields
        if (!$request->has(['port', 'downloaded', 'uploaded', 'left'])) {
            return $this->bencode->failure('Missing required fields.');
        }

        $port       = (int) $request->query('port');
        $downloaded = (int) $request->query('downloaded');
        $uploaded   = (int) $request->query('uploaded');
        $left       = (int) $request->query('left');
        $event      = $request->query('event', '');
        $isCompact  = $request->query('compact') === '1';
        $numwant    = $request->has('numwant') ? min((int) $request->query('numwant'), $this->maxPeers) : $this->maxPeers;

        // IP — use REMOTE_ADDR only; never trust forwarded headers from clients
        $ip = $request->ip();

        // Port range check
        if ($port < 1 || $port > 65535) {
            return $this->bencode->failure('Invalid port.');
        }

        // IP ban check (inline — announce skips CheckIpBan middleware)
        if (BannedIp::isBanned($ip)) {
            return $this->bencode->failure('Your IP address is banned.');
        }

        // Torrent authorization
        $torrent = Torrent::where('info_hash', $infoHash)
            ->where('external', 'no')
            ->first();

        if (!$torrent) {
            if (!$this->settings->get('dynamic', false)) {
                return $this->bencode->failure('Torrent not authorized on this tracker.');
            }
        }

        // User lookup by passkey
        $user = $this->passkeys->findUser($pid);
        if (!$user) {
            return $this->bencode->failure('Invalid passkey. Please re-download the torrent.');
        }

        if ($user->level?->can_download === false) {
            return $this->bencode->failure('Your account level cannot download.');
        }

        // Per-passkey concurrency limits (anti-account-sharing)
        if ($error = $this->checkConcurrencyLimit($pid, $infoHash, $peerId)) {
            return $error;
        }

        // Update live stats (uploaded/downloaded deltas)
        $this->updateLiveStats($pid, $infoHash, $peerId, $uploaded, $downloaded);

        // Dispatch to the appropriate event handler
        $result = match ($event) {
            'started'   => $this->handleStarted($infoHash, $peerId, $ip, $port, $left, $uploaded, $downloaded, $pid, $user),
            'stopped'   => $this->handleStopped($infoHash, $peerId, $left, $pid, $uploaded, $downloaded),
            'completed' => $this->handleCompleted($infoHash, $peerId, $ip, $port, $left, $uploaded, $downloaded, $pid, $user),
            '', 'paused' => $this->handleAnnounce($infoHash, $peerId, $ip, $port, $left, $uploaded, $downloaded, $pid),
            default     => $this->bencode->failure('Invalid event.'),
        };

        // Flush the batched files UPDATE (one query, not many)
        $this->flushSummary($infoHash);

        return $result;
    }

    // -------------------------------------------------------------------------
    // Event handlers
    // -------------------------------------------------------------------------

    private function handleStarted(
        string $infoHash, string $peerId, string $ip, int $port,
        int $left, int $uploaded, int $downloaded, string $pid, User $user
    ): string {
        $this->insertOrUpdatePeer($infoHash, $peerId, $ip, $port, $left, $uploaded, $downloaded, $pid);

        $this->logHistory($infoHash, $user->id, 'yes');

        return $this->buildPeerResponse($infoHash, $pid, true);
    }

    private function handleStopped(
        string $infoHash, string $peerId, int $left,
        string $pid, int $uploaded, int $downloaded
    ): string {
        $peer = $this->getPeer($infoHash, $peerId);

        if ($peer) {
            $prevLeft = max(0, $peer->bytes ?? 0);

            $deleted = DB::table('peers')
                ->where('peer_id', $peerId)
                ->where('infohash', $infoHash)
                ->delete();

            if ($deleted) {
                // Decrement the correct counter (seeds or leechers)
                $this->summaryAdd($peer->isSeeder() ? 'seeds' : 'leechers', -1);

                // Bytes downloaded since last announce = previous left minus current left
                $delta = max(0, $prevLeft - $left);
                if ($delta > 0) {
                    $this->summaryAdd('dlbytes', $delta);
                }

                // Peer completed without sending event=completed (e.g. client crash-quit)
                if ($prevLeft > 0 && $left === 0) {
                    $this->summaryAdd('finished', 1);
                }

                $this->summaryAdd('lastcycle', 'UNIX_TIMESTAMP()', true);
            }
        }

        // Do NOT update users.uploaded/downloaded here — updateLiveStats() already
        // credited the incremental delta at the start of handle() for this announce.

        $this->updateHistoryActive($infoHash, $pid, 'no');

        return $this->buildPeerResponse($infoHash, $pid, false);
    }

    private function handleCompleted(
        string $infoHash, string $peerId, string $ip, int $port,
        int $left, int $uploaded, int $downloaded, string $pid, User $user
    ): string {
        $peer = $this->getPeer($infoHash, $peerId);

        if (!$peer) {
            $this->insertOrUpdatePeer($infoHash, $peerId, $ip, $port, 0, $uploaded, $downloaded, $pid);
        } else {
            $updated = DB::table('peers')
                ->where('id', $peer->id)
                ->where('infohash', $infoHash)
                ->update([
                    'bytes'      => 0,
                    'status'     => 'seeder',
                    'lastupdate' => DB::raw('UNIX_TIMESTAMP()'),
                    'downloaded' => $downloaded,
                    'uploaded'   => $uploaded,
                    'passkey'    => $pid,
                ]);

            if ($updated === 1) {
                $this->summaryAdd('leechers', -1);
                $this->summaryAdd('seeds', 1);
                $this->summaryAdd('finished', 1);
                $this->summaryAdd('lastcycle', 'UNIX_TIMESTAMP()', true);
            }
        }

        $this->logHistory($infoHash, $user->id, 'yes', true);

        return $this->buildPeerResponse($infoHash, $pid, true);
    }

    private function handleAnnounce(
        string $infoHash, string $peerId, string $ip, int $port,
        int $left, int $uploaded, int $downloaded, string $pid
    ): string {
        $peer = $this->getPeer($infoHash, $peerId);

        if (!$peer) {
            $this->insertOrUpdatePeer($infoHash, $peerId, $ip, $port, $left, $uploaded, $downloaded, $pid);
        } else {
            $prevLeft = $peer->bytes ?? 0;

            // Peer completed since last announce
            if ($prevLeft !== 0 && $left === 0) {
                $updated = DB::table('peers')
                    ->where('id', $peer->id)
                    ->where('infohash', $infoHash)
                    ->update([
                        'bytes'      => 0,
                        'status'     => 'seeder',
                        'lastupdate' => DB::raw('UNIX_TIMESTAMP()'),
                        'downloaded' => $downloaded,
                        'uploaded'   => $uploaded,
                        'passkey'    => $pid,
                    ]);

                if ($updated === 1) {
                    $this->summaryAdd('leechers', -1);
                    $this->summaryAdd('seeds', 1);
                    $this->summaryAdd('finished', 1);
                    $this->summaryAdd('lastcycle', 'UNIX_TIMESTAMP()', true);
                }
            } else {
                $diff = $prevLeft - $left;

                $update = [
                    'lastupdate' => DB::raw('UNIX_TIMESTAMP()'),
                    'downloaded' => $downloaded,
                    'uploaded'   => $uploaded,
                    'passkey'    => $pid,
                ];

                if ($diff > 0) {
                    $update['bytes'] = $left;
                    $this->summaryAdd('dlbytes', $diff);
                }

                DB::table('peers')
                    ->where('id', $peer->id)
                    ->where('infohash', $infoHash)
                    ->update($update);
            }
        }

        return $this->buildPeerResponse($infoHash, $pid, true);
    }

    // -------------------------------------------------------------------------
    // Peer management
    // -------------------------------------------------------------------------

    private function insertOrUpdatePeer(
        string $infoHash, string $peerId, string $ip, int $port,
        int $left, int $uploaded, int $downloaded, string $pid
    ): void {
        $status  = $left === 0 ? 'seeder' : 'leecher';
        $compact = $this->buildCompact($ip, $port);

        try {
            DB::table('peers')->insertOrIgnore([
                'infohash'   => $infoHash,
                'peer_id'    => $peerId,
                'ip'         => $ip,
                'port'       => $port,
                'status'     => $status,
                'bytes'      => $left,
                'uploaded'   => $uploaded,
                'downloaded' => $downloaded,
                'passkey'    => $pid,
                'compact'    => $compact,
                'client'     => '',
                'dns'        => '',
                'natuser'    => 'N',
                'lastupdate' => DB::raw('UNIX_TIMESTAMP()'),
            ]);

            $this->summaryAdd($status === 'seeder' ? 'seeds' : 'leechers', 1);
            $this->summaryAdd('lastcycle', 'UNIX_TIMESTAMP()', true);
        } catch (\Exception $e) {
            Log::error('Announce peer insert failed', ['error' => $e->getMessage()]);
        }
    }

    private function getPeer(string $infoHash, string $peerId): ?object
    {
        return DB::table('peers')
            ->where('infohash', $infoHash)
            ->where('peer_id', $peerId)
            ->first();
    }

    // -------------------------------------------------------------------------
    // Peer response building (BEP 23)
    // -------------------------------------------------------------------------

    private function buildPeerResponse(string $infoHash, string $pid, bool $includeCompact): string
    {
        $peers = DB::table('peers')
            ->select('ip', 'port', 'peer_id')
            ->where('infohash', $infoHash)
            ->where('natuser', 'N')
            ->inRandomOrder()
            ->limit($this->maxPeers)
            ->get();

        $body  = 'd';
        $body .= '8:intervali' . $this->interval . 'e';
        $body .= '12:min intervali' . $this->minInterval . 'e';

        // Compact format (BEP 23): 6-byte binary per peer (4-byte IP + 2-byte port, big-endian)
        $compactStr = '';
        foreach ($peers as $peer) {
            $packed = @inet_pton($peer->ip);
            if ($packed === false || strlen($packed) !== 4) {
                // Skip IPv6 peers for now (deferred per plan)
                $packed = pack('N', ip2long($peer->ip));
            }
            $compactStr .= $packed . pack('n', $peer->port);
        }
        $body .= '5:peers' . strlen($compactStr) . ':' . $compactStr;

        $body .= 'e';

        return $body;
    }

    /** Build 6-byte binary compact representation for a single peer. */
    private function buildCompact(string $ip, int $port): string
    {
        $packed = @inet_pton($ip);
        if ($packed === false || strlen($packed) !== 4) {
            $packed = pack('N', ip2long($ip));
        }

        return $packed . pack('n', $port);
    }

    // -------------------------------------------------------------------------
    // summaryAdd — batched UPDATE to files table
    // -------------------------------------------------------------------------

    /**
     * Accumulate a files column update. Flushed once at end of request.
     *
     * @param bool $abs  If true, sets column to an absolute value (e.g. UNIX_TIMESTAMP())
     *                   rather than incrementing it.
     */
    private function summaryAdd(string $column, int|string $value, bool $abs = false): void
    {
        if ($abs) {
            if (isset($this->summary[$column]) && !($this->summary[$column]['abs'] ?? false)) {
                Log::warning('summaryAdd: column already queued as non-absolute', ['col' => $column]);
            }
            $this->summary[$column] = ['value' => $value, 'abs' => true];
        } else {
            if (isset($this->summary[$column])) {
                $this->summary[$column]['value'] += $value;
            } else {
                $this->summary[$column] = ['value' => $value, 'abs' => false];
            }
        }
    }

    private function flushSummary(string $infoHash): void
    {
        if (empty($this->summary)) {
            return;
        }

        $sets = [];
        foreach ($this->summary as $col => $entry) {
            if ($entry['abs']) {
                // Absolute value — used for timestamps; value may be a SQL expression
                $sets[] = "`$col` = " . (is_numeric($entry['value']) ? (int) $entry['value'] : $entry['value']);
            } else {
                $v = (int) $entry['value'];
                // Guard against negative counts
                $sets[] = "`$col` = IF((`$col` < ABS($v) AND $v < 0), 0, `$col` + $v)";
            }
        }

        $sql = 'UPDATE `files` SET ' . implode(', ', $sets) . ' WHERE `info_hash` = ?';
        DB::statement($sql, [$infoHash]);

        $this->summary = [];
    }

    // -------------------------------------------------------------------------
    // Concurrency limits
    // -------------------------------------------------------------------------

    private function checkConcurrencyLimit(string $pid, string $infoHash, string $peerId): ?string
    {
        $counts = DB::table('peers')
            ->selectRaw('status, COUNT(status) as cnt')
            ->where('passkey', $pid)
            ->where('infohash', $infoHash)
            ->where('peer_id', '!=', $peerId)
            ->groupBy('status')
            ->pluck('cnt', 'status');

        if (($counts['seeder'] ?? 0) >= $this->maxSeeds || ($counts['leecher'] ?? 0) >= $this->maxLeech) {
            return $this->bencode->failure('Max simultaneous connections reached. Please redownload the torrent.');
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Live stats + history
    // -------------------------------------------------------------------------

    private function updateLiveStats(string $pid, string $infoHash, string $peerId, int $uploaded, int $downloaded): void
    {
        $prev = DB::table('peers')
            ->select('uploaded', 'downloaded')
            ->where('passkey', $pid)
            ->where('infohash', $infoHash)
            ->where('peer_id', $peerId)
            ->first();

        if (!$prev) {
            return;
        }

        $newUp   = max(0, $uploaded   - $prev->uploaded);
        $newDown = max(0, $downloaded - $prev->downloaded);

        if ($newUp > 0 || $newDown > 0) {
            DB::table('users')
                ->where('passkey', $pid)
                ->where('id', '>', 1)
                ->limit(1)
                ->update([
                    'uploaded'   => DB::raw("IFNULL(uploaded,0) + $newUp"),
                    'downloaded' => DB::raw("IFNULL(downloaded,0) + $newDown"),
                ]);
        }
    }

    private function logHistory(string $infoHash, int $userId, string $active, bool $isComplete = false): void
    {
        // date = snatch timestamp; only set on actual completion, not on re-announce or started
        $update = ['active' => $active];
        if ($isComplete) {
            $update['date'] = DB::raw('UNIX_TIMESTAMP()');
        }

        $updated = DB::table('history')
            ->where('uid', $userId)
            ->where('infohash', $infoHash)
            ->update($update);

        if (!$updated && $active === 'yes') {
            DB::table('history')->insertOrIgnore([
                'uid'        => $userId,
                'infohash'   => $infoHash,
                'active'     => $active,
                'date'       => 0,
                'uploaded'   => 0,
                'downloaded' => 0,
                'agent'      => '',
            ]);
        }
    }

    private function updateHistoryActive(string $infoHash, string $pid, string $active): void
    {
        $user = DB::table('users')
            ->select('id')
            ->where(function ($q) use ($pid) {
                $q->where('passkey', $pid)->orWhere('legacy_passkey', $pid);
            })
            ->orderByDesc('updated_at')
            ->value('id');

        if ($user) {
            DB::table('history')
                ->where('uid', $user)
                ->where('infohash', $infoHash)
                ->update(['active' => $active]);
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function loadSettings(): void
    {
        $this->maxSeeds    = $this->settings->get('maxpid_seeds',           3);
        $this->maxLeech    = $this->settings->get('maxpid_leech',           2);
        $this->maxPeers    = $this->settings->get('max_peers_per_announce', 50);
        $this->interval    = $this->settings->get('max_announce',           1800);
        $this->minInterval = $this->settings->get('min_announce',           300);
    }
}
