<?php

namespace App\Http\Controllers;

use App\Models\BannedIp;
use App\Services\BEncodeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ScrapeController extends Controller
{
    public function __construct(private readonly BEncodeService $bencode) {}

    public function handle(Request $request): Response
    {
        ignore_user_abort(true);

        // xbtt redirect (same guard as announce)
        $xbttEnabled = DB::table('settings')->where('key', 'xbtt_enabled')->value('value');
        if ($xbttEnabled === 'true') {
            return $this->xbttRedirect($request);
        }

        // IP ban check
        if (BannedIp::isBanned($request->ip())) {
            return $this->failure('Your IP address is banned.');
        }

        $hashes = $this->parseInfoHashes($request);

        $body = $this->buildResponse($hashes);

        return response($body, 200, [
            'Content-Type'  => 'text/plain',
            'Pragma'        => 'no-cache',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Parse info_hash values from the raw QUERY_STRING.
     *
     * PHP's $_GET / $request->query() deduplicates repeated keys — the last value wins,
     * silently dropping all but one hash in a multi-scrape request.
     * We must parse QUERY_STRING manually, exactly as the legacy code did.
     */
    private function parseInfoHashes(Request $request): array
    {
        $qs = $request->server('QUERY_STRING', '');

        // Strip pid= from the query string before parsing
        $qs = preg_replace('/(?:^|&)pid=[^&]*/', '', $qs);

        $hashes = [];
        foreach (explode('&', $qs) as $segment) {
            if (!str_starts_with($segment, 'info_hash=')) {
                continue;
            }

            $raw = urldecode(substr($segment, 10));

            if (strlen($raw) === 20) {
                // 20-byte binary — convert to lowercase hex
                $hex = bin2hex($raw);
                $hashes[] = $hex;
            } elseif (strlen($raw) === 40) {
                // 40-char hex — legacy bug fix: only discard if INVALID, not if valid
                if (ctype_xdigit($raw)) {
                    $hashes[] = strtolower($raw);  // valid — keep it (fixes both-continue bug)
                }
                // invalid hex — discard (continue)
            }
            // Any other length — discard
        }

        return array_unique($hashes);
    }

    private function buildResponse(array $hashes): string
    {
        if (empty($hashes)) {
            // No hashes — return all torrents (scrape all)
            $rows = DB::table('files')
                ->select('info_hash', 'seeds', 'leechers', 'finished', 'filename')
                ->where('external', 'no')
                ->get();
        } else {
            $rows = DB::table('files')
                ->select('info_hash', 'seeds', 'leechers', 'finished', 'filename')
                ->where('external', 'no')
                ->whereIn('info_hash', $hashes)
                ->get();
        }

        $body = 'd5:filesd';

        foreach ($rows as $row) {
            $binaryHash = hex2bin($row->info_hash);
            $body .= '20:' . $binaryHash;
            $body .= 'd';
            $body .= '8:completei'   . (int) $row->seeds    . 'e';
            $body .= '10:downloadedi' . (int) $row->finished . 'e';  // "downloaded" = times completed
            $body .= '10:incompletei' . (int) $row->leechers . 'e';
            if (!empty($row->filename)) {
                $name  = $row->filename;
                $body .= '4:name' . strlen($name) . ':' . $name;
            }
            $body .= 'e';
        }

        $body .= 'ee';

        return $body;
    }

    private function xbttRedirect(Request $request): Response
    {
        $xbttUrl = DB::table('settings')->where('key', 'xbtt_url')->value('value');
        $pid     = preg_replace('/[^a-zA-Z0-9+\/=_-]/', '', (string) $request->query('pid', ''));

        $qs = $request->server('QUERY_STRING', '');
        $qs = preg_replace('/(?:^|&)pid=[^&]*/', '', $qs);
        $qs = ltrim($qs, '&');

        $location = $pid
            ? rtrim($xbttUrl, '/') . "/{$pid}/scrape?{$qs}"
            : rtrim($xbttUrl, '/') . "/scrape?{$qs}";

        return response('', 302, ['Location' => $location]);
    }

    private function failure(string $message): Response
    {
        return response(
            $this->bencode->failure($message),
            200,
            ['Content-Type' => 'text/plain']
        );
    }
}
