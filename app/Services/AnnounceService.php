<?php

namespace App\Services;

use App\Models\BannedIp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnnounceService
{
    // Browser user-agent fragments that indicate a web browser, not a BT client (C-10).
    private const BROWSER_UA_FRAGMENTS = [
        'Mozilla/', 'Opera/', 'Links ', 'Lynx/', 'AppleWebKit/',
    ];

    private array $summary = [];

    private int $maxSeeds    = 3;
    private int $maxLeech    = 2;
    private int $maxPeers    = 50;
    private int $interval    = 1800;
    private int $minInterval = 300;

    public function __construct(
        private readonly BEncodeService $bencode,
        private readonly PasskeyService $passkeys,
        private readonly SettingService $settings,
    ) {}

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

    public function handle(Request $request): string
    {
        ignore_user_abort(true);

        $this->loadSettings();

        // Reject browser user-agents — BT clients never send Mozilla/Opera/etc. (C-10)
        $ua = $request->userAgent() ?? '';
        foreach (self::BROWSER_UA_FRAGMENTS as $fragment) {
            if (str_contains($ua, $fragment)) {
                return $this->bencode->failure('Browser access is not allowed on the announce endpoint.');
            }
        }

        $pid = (string) ($request->query('pid', ''));
        if (!preg_match($this->passkeys->validationPattern(), $pid)) {
            return $this->bencode->failure('Invalid passkey format.');
        }

        // info_hash and peer_id arrive as raw 20-byte binary; PHP URL-decodes them.
        $rawInfoHash = $request->query('info_hash', '');
        $rawPeerId   = $request->query('peer_id', '');

        if (strlen($rawInfoHash) !== 20 || strlen($rawPeerId) !== 20) {
            return $this->bencode->failure('Invalid info_hash or peer_id.');
        }

        $infoHash = bin2hex($rawInfoHash);
        $peerId   = bin2hex($rawPeerId);

        if (!$request->has(['port', 'downloaded', 'uploaded', 'left'])) {
            return $this->bencode->failure('Missing required fields.');
        }

        $port       = (int) $request->query('port');
        $downloaded = (int) $request->query('downloaded');
        $uploaded   = (int) $request->query('uploaded');
        $left       = (int) $request->query('left');
        $event      = $request->query('event', '');
        $numwant    = $request->has('numwant')
            ? min((int) $request->query('numwant'), $this->maxPeers)
            : $this->maxPeers;

        $ip = $request->ip();

        if ($port < 1 || $port > 65535) {
            return $this->bencode->failure('Invalid port.');
        }

        if (BannedIp::isBanned($ip)) {
            return $this->bencode->failure('Your IP address is banned.');
        }

        $torrent = DB::table('files')
            ->select('info_hash', 'external', 'added')
            ->where('info_hash', $infoHash)
            ->first();

        if (!$torrent) {
            if (!$this->settings->get('dynamic', false)) {
                return $this->bencode->failure('Torrent not authorized on this tracker.');
            }
        }

        $user = $this->passkeys->findUser($pid);
        if (!$user) {
            return $this->bencode->failure('Invalid passkey. Please re-download the torrent.');
        }

        if ($user->level?->can_download === false) {
            return $this->bencode->failure('Your account level cannot download.');
        }

        // Wait-time gate (C-04): leechers must wait WT hours after torrent was added.
        // Seeds are always allowed through; completed leechers also skip this check.
        if ($left > 0 && $torrent && $event !== 'completed') {
            if ($waitError = $this->checkWaitTime($torrent, $user)) {
                return $waitError;
            }
        }

        if ($error = $this->checkConcurrencyLimit($pid, $infoHash, $peerId)) {
            return $error;
        }

        $this->updateLiveStats($pid, $infoHash, $peerId, $uploaded, $downloaded);

        $result = match ($event) {
            'started'        => $this->handleStarted($infoHash, $peerId, $ip, $port, $left, $uploaded, $downloaded, $pid, $user),
            'stopped'        => $this->handleStopped($infoHash, $peerId, $left, $pid, $uploaded, $downloaded),
            'completed'      => $this->handleCompleted($infoHash, $peerId, $ip, $port, $left, $uploaded, $downloaded, $pid, $user),
            '', 'paused'     => $this->handleAnnounce($infoHash, $peerId, $ip, $port, $left, $uploaded, $downloaded, $pid),
            default          => $this->bencode->failure('Invalid event.'),
        };

        $this->flushSummary($infoHash);
        $this->recordSpeedSample($infoHash);

        return $this->buildPeerResponse($infoHash, $numwant);
    }

    // -------------------------------------------------------------------------
    // Wait-time gate (C-04)
    // -------------------------------------------------------------------------

    private function checkWaitTime(object $torrent, User $user): ?string
    {
        // WT hours come from the torrent row first, then fall back to the user's level WT.
        $wtHours = (int) ($torrent->wt ?? $user->level?->wt ?? 0);

        if ($wtHours <= 0) {
            return null;
        }

        $added   = (int) $torrent->added;
        $elapsed = time() - $added;

        if ($elapsed < ($wtHours * 3600)) {
            $remaining = (int) ceil(($wtHours * 3600 - $elapsed) / 3600);
            return $this->bencode->failure(
                "You must wait {$remaining} more hour(s) before you can download this torrent."
            );
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Event handlers
    // -------------------------------------------------------------------------

    private function handleStarted(
        string $infoHash, string $peerId, string $ip, int $port,
        int $left, int $uploaded, int $downloaded, string $pid, User $user
    ): void {
        $this->insertOrUpdatePeer($infoHash, $peerId, $ip, $port, $left, $uploaded, $downloaded, $pid);
        $this->logHistory($infoHash, $user->id, 'yes');
    }

    private function handleStopped(
        string $infoHash, string $peerId, int $left,
        string $pid, int $uploaded, int $downloaded
    ): void {
        $peer = $this->getPeer($infoHash, $peerId);

        if ($peer) {
            $prevLeft = max(0, $peer->bytes ?? 0);

            $deleted = DB::table('peers')
                ->where('peer_id', $peerId)
                ->where('infohash', $infoHash)
                ->delete();

            if ($deleted) {
                $this->summaryAdd($peer->status === 'seeder' ? 'seeds' : 'leechers', -1);

                $delta = max(0, $prevLeft - $left);
                if ($delta > 0) {
                    $this->summaryAdd('dlbytes', $delta);
                }

                if ($prevLeft > 0 && $left === 0) {
                    $this->summaryAdd('finished', 1);
                }

                $this->summaryAdd('lastcycle', time(), true);
            }
        }

        $this->updateHistoryActive($infoHash, $pid, 'no');
    }

    private function handleCompleted(
        string $infoHash, string $peerId, string $ip, int $port,
        int $left, int $uploaded, int $downloaded, string $pid, User $user
    ): void {
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
                    'lastupdate' => time(),
                    'downloaded' => $downloaded,
                    'uploaded'   => $uploaded,
                    'passkey'    => $pid,
                ]);

            if ($updated === 1) {
                $this->summaryAdd('leechers', -1);
                $this->summaryAdd('seeds', 1);
                $this->summaryAdd('finished', 1);
                $this->summaryAdd('lastcycle', time(), true);
            }
        }

        $this->logHistory($infoHash, $user->id, 'yes', true);
    }

    private function handleAnnounce(
        string $infoHash, string $peerId, string $ip, int $port,
        int $left, int $uploaded, int $downloaded, string $pid
    ): void {
        $peer = $this->getPeer($infoHash, $peerId);

        if (!$peer) {
            $this->insertOrUpdatePeer($infoHash, $peerId, $ip, $port, $left, $uploaded, $downloaded, $pid);
            return;
        }

        $prevLeft = $peer->bytes ?? 0;

        if ($prevLeft !== 0 && $left === 0) {
            DB::table('peers')
                ->where('id', $peer->id)
                ->where('infohash', $infoHash)
                ->update([
                    'bytes'      => 0,
                    'status'     => 'seeder',
                    'lastupdate' => time(),
                    'downloaded' => $downloaded,
                    'uploaded'   => $uploaded,
                    'passkey'    => $pid,
                ]);

            $this->summaryAdd('leechers', -1);
            $this->summaryAdd('seeds', 1);
            $this->summaryAdd('finished', 1);
            $this->summaryAdd('lastcycle', time(), true);
        } else {
            $update = [
                'lastupdate' => time(),
                'downloaded' => $downloaded,
                'uploaded'   => $uploaded,
                'passkey'    => $pid,
            ];

            $diff = $prevLeft - $left;
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
                'lastupdate' => time(),
            ]);

            $this->summaryAdd($status === 'seeder' ? 'seeds' : 'leechers', 1);
            $this->summaryAdd('lastcycle', time(), true);
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
    // Peer response (BEP 23 compact format, C-02)
    // -------------------------------------------------------------------------

    private function buildPeerResponse(string $infoHash, int $numwant): string
    {
        $peers = DB::table('peers')
            ->select('ip', 'port', 'peer_id')
            ->where('infohash', $infoHash)
            ->where('natuser', 'N')
            ->inRandomOrder()
            ->limit($numwant)
            ->get();

        $compactStr = '';
        foreach ($peers as $peer) {
            $packed = @inet_pton($peer->ip);
            if ($packed === false || strlen($packed) !== 4) {
                // Skip IPv6 — deferred; compact format is IPv4-only (BEP 23)
                continue;
            }
            $compactStr .= $packed . pack('n', $peer->port);
        }

        return 'd'
            . '8:intervali' . $this->interval . 'e'
            . '12:min intervali' . $this->minInterval . 'e'
            . '5:peers' . strlen($compactStr) . ':' . $compactStr
            . 'e';
    }

    private function buildCompact(string $ip, int $port): string
    {
        $packed = @inet_pton($ip);
        if ($packed === false || strlen($packed) !== 4) {
            $packed = pack('N', ip2long($ip));
        }

        return $packed . pack('n', $port);
    }

    // -------------------------------------------------------------------------
    // Speed sampling (C-17) — rolling 20-sample window per torrent
    // -------------------------------------------------------------------------

    private function recordSpeedSample(string $infoHash): void
    {
        $prevSample = DB::table('speed_samples')
            ->where('info_hash', $infoHash)
            ->orderByDesc('sampled_at')
            ->select('bytes', 'sampled_at')
            ->first();

        $now          = time();
        $currentBytes = (int) DB::table('files')
            ->where('info_hash', $infoHash)
            ->value('dlbytes');

        $delta = $prevSample ? max(1, $now - $prevSample->sampled_at) : 1;
        $bytes = $prevSample ? max(0, $currentBytes - $prevSample->bytes) : 0;

        DB::table('speed_samples')->insert([
            'info_hash'  => $infoHash,
            'bytes'      => $bytes,
            'delta'      => $delta,
            'sampled_at' => $now,
        ]);

        // Keep only last 20 samples — delete oldest beyond that.
        $keep = DB::table('speed_samples')
            ->where('info_hash', $infoHash)
            ->orderByDesc('sampled_at')
            ->limit(20)
            ->pluck('id');

        if ($keep->count() >= 20) {
            DB::table('speed_samples')
                ->where('info_hash', $infoHash)
                ->whereNotIn('id', $keep)
                ->delete();

            // Recalculate cached speed: total bytes / total time over the window.
            $window = DB::table('speed_samples')
                ->where('info_hash', $infoHash)
                ->selectRaw('SUM(bytes) as total_bytes, SUM(delta) as total_delta')
                ->first();

            $speed = $window && $window->total_delta > 0
                ? (int) ($window->total_bytes / $window->total_delta)
                : 0;

            DB::table('files')
                ->where('info_hash', $infoHash)
                ->update(['speed' => $speed]);
        }
    }

    // -------------------------------------------------------------------------
    // summaryAdd — batched UPDATE to files table
    // -------------------------------------------------------------------------

    private function summaryAdd(string $column, int|string $value, bool $abs = false): void
    {
        if ($abs) {
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
                $v      = $entry['value'];
                $sets[] = "`$col` = " . (is_numeric($v) ? (int) $v : $v);
            } else {
                $v      = (int) $entry['value'];
                $sets[] = "`$col` = IF((`$col` < ABS($v) AND $v < 0), 0, `$col` + $v)";
            }
        }

        $sql = 'UPDATE `files` SET ' . implode(', ', $sets) . ' WHERE `info_hash` = ?';
        DB::statement($sql, [$infoHash]);

        $this->summary = [];
    }

    // -------------------------------------------------------------------------
    // Concurrency limits (C-09)
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
        $update = ['active' => $active];
        if ($isComplete) {
            $update['date'] = time();
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
        $uid = DB::table('users')
            ->where(function ($q) use ($pid) {
                $q->where('passkey', $pid)->orWhere('legacy_passkey', $pid);
            })
            ->orderByDesc('updated_at')
            ->value('id');

        if ($uid) {
            DB::table('history')
                ->where('uid', $uid)
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
