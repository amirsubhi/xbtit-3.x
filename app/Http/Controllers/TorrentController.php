<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\News;
use App\Models\Thread;
use App\Models\Torrent;
use App\Services\BEncodeService;
use App\Services\PasskeyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TorrentController extends Controller
{
    public function __construct(
        private readonly BEncodeService $bencode,
        private readonly PasskeyService $passkeys,
    ) {}

    public function index(Request $request): View
    {
        $query = Torrent::with(['category', 'uploader'])
            ->where('external', 'no');

        // Search
        if ($search = $request->query('search')) {
            foreach (explode(' ', $search) as $word) {
                $word = trim($word);
                if ($word !== '') {
                    $query->where('filename', 'like', '%' . $word . '%');
                }
            }
        }

        // Category filter
        if ($cat = $request->query('category')) {
            $ids = array_map('intval', explode(';', $cat));
            $query->whereIn('category', array_filter($ids));
        }

        // Activity filter (1 = active, 2 = dead, 0 = all)
        $active = (int) $request->query('active', 1);
        if ($active === 1) {
            $query->where(fn ($q) => $q->where('seeds', '>', 0)->orWhere('leechers', '>', 0));
        } elseif ($active === 2) {
            $query->where('seeds', 0)->where('leechers', 0);
        }

        // Sorting
        $orderMap = [
            1 => 'filename', 2 => 'filename', 3 => 'added',
            4 => 'size',     5 => 'seeds',    6 => 'leechers',
            7 => 'finished', 8 => 'dlbytes',  9 => 'speed',
        ];
        $orderCol = $orderMap[(int) $request->query('order', 3)] ?? 'added';
        $orderDir = $request->query('by') === '1' ? 'asc' : 'desc';
        $query->orderBy($orderCol, $orderDir);

        $perPage  = auth()->user()?->torrentsperpage ?? 15;
        $torrents = $query->paginate($perPage, pageName: 'pages');

        $categories = Category::where('sub', 0)->with('children')->orderBy('sort_index')->get();

        // Front page sidebar: latest news + recent forum activity (C-31)
        $latestNews    = News::latest()->limit(5)->get();
        $recentThreads = Thread::with(['forum', 'latestPost.author'])
            ->where('locked', false)
            ->latest()
            ->limit(5)
            ->get();

        return view('torrents.index', compact('torrents', 'categories', 'latestNews', 'recentThreads'));
    }

    public function show(string $infoHash): View
    {
        $torrent = Torrent::with(['category', 'uploader', 'comments.author'])
            ->where('info_hash', $infoHash)
            ->firstOrFail();

        $peers = $torrent->peers()->orderByDesc('lastupdate')->limit(100)->get();

        return view('torrents.show', compact('torrent', 'peers'));
    }

    public function create(): View
    {
        $categories = Category::where('sub', 0)->with('children')->orderBy('sort_index')->get();

        return view('torrents.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'torrent'     => ['required', 'file', 'mimes:torrent', 'max:4096'],
            'description' => ['required', 'string', 'max:2000'],
            'category'    => ['required', 'integer', 'exists:categories,id'],
            'anonymous'   => ['sometimes', 'boolean'],
        ]);

        $file     = $request->file('torrent');
        $contents = file_get_contents($file->getRealPath());

        $torrentData = $this->bencode->decode($contents);
        if (!is_array($torrentData) || !isset($torrentData['info'])) {
            return back()->withErrors(['torrent' => 'Invalid .torrent file structure.']);
        }

        // DHT private-flag injection (C-12): when disable_dht is enabled, set info.private=1
        // and recompute info_hash so DHT-found peers can't bypass the tracker.
        $disableDht = DB::table('settings')->where('key', 'disable_dht')->value('value');
        if ($disableDht === 'true' || $disableDht === '1') {
            $torrentData['info']['private'] = 1;
            $contents = $this->bencode->encode($torrentData);
        }

        $infoHash = sha1($this->bencode->encode($torrentData['info']));

        if (!$this->bencode->isValidHash($infoHash)) {
            return back()->withErrors(['torrent' => 'Could not compute info hash.']);
        }

        if (Torrent::where('info_hash', $infoHash)->exists()) {
            return back()->withErrors(['torrent' => 'This torrent already exists on the tracker.']);
        }

        $category = Category::findOrFail($request->integer('category'));

        $storeName = $infoHash . '.btf';
        Storage::disk('torrents')->put($storeName, $contents);

        $info = $torrentData['info'];

        if (isset($info['length'])) {
            $size = (int) $info['length'];
        } elseif (isset($info['files']) && is_array($info['files'])) {
            $size = array_sum(array_column($info['files'], 'length'));
        } else {
            $size = 0;
        }

        $displayName = (isset($info['name']) && $info['name'] !== '')
            ? $info['name']
            : $file->getClientOriginalName();

        $torrent = Torrent::create([
            'info_hash'    => $infoHash,
            'filename'     => $displayName,
            'url'          => $storeName,
            'info'         => $request->input('description'),
            'size'         => $size,
            'category'     => $category->id,
            'external'     => 'no',
            'announce_url' => '',
            'uploader'     => $request->user()->id,
            'anonymous'    => $request->boolean('anonymous') ? 'true' : 'false',
            'bin_hash'     => hex2bin($infoHash),
            'added'        => time(),
        ]);

        return redirect()->route('torrents.show', $infoHash)
            ->with('status', 'Torrent uploaded successfully.');
    }

    public function edit(string $infoHash): View
    {
        $torrent = Torrent::where('info_hash', $infoHash)->firstOrFail();

        $this->authorize('update', $torrent);

        $categories = Category::where('sub', 0)->with('children')->orderBy('sort_index')->get();

        return view('torrents.edit', compact('torrent', 'categories'));
    }

    public function update(Request $request, string $infoHash): RedirectResponse
    {
        $torrent = Torrent::where('info_hash', $infoHash)->firstOrFail();

        $this->authorize('update', $torrent);

        $data = $request->validate([
            'filename' => ['required', 'string', 'max:255'],
            'category' => ['required', 'integer', 'exists:categories,id'],
            'info'     => ['nullable', 'string', 'max:5000'],
        ]);

        $torrent->update([
            'filename' => $data['filename'],
            'category' => $data['category'],
            'info'     => $data['info'] ?? '',
        ]);

        return redirect()->route('torrents.show', $infoHash)->with('status', 'Torrent updated.');
    }

    public function destroy(string $infoHash): RedirectResponse
    {
        $torrent = Torrent::where('info_hash', $infoHash)->firstOrFail();

        $this->authorize('delete', $torrent);

        // Remove orphan rows and the stored .btf file
        DB::table('comments')->where('info_hash', $infoHash)->delete();
        DB::table('peers')->where('infohash', $infoHash)->delete();
        DB::table('history')->where('infohash', $infoHash)->delete();

        $path = $infoHash . '.btf';
        if (Storage::disk('torrents')->exists($path)) {
            Storage::disk('torrents')->delete($path);
        }

        $torrent->delete();

        return redirect()->route('torrents.index')
            ->with('status', 'Torrent deleted.');
    }

    public function download(Request $request, string $infoHash): Response
    {
        $torrent = Torrent::where('info_hash', $infoHash)->firstOrFail();

        $user = $request->user();
        if ($user->level?->can_download === false) {
            abort(403, 'Your account level cannot download torrents.');
        }

        // Ensure user has a passkey
        if (empty($user->passkey)) {
            $user->passkey = $this->passkeys->generate();
            $user->save();
        }

        $path = $infoHash . '.btf';
        if (!Storage::disk('torrents')->exists($path)) {
            abort(404, 'Torrent file not found.');
        }

        $contents = Storage::disk('torrents')->get($path);

        if (!$torrent->isExternal()) {
            $torrentData = $this->bencode->decode($contents);
            if (is_array($torrentData)) {
                $announceUrl = route('home') . '/announce.php?pid=' . $user->passkey;

                // Rewrite top-level announce
                $torrentData['announce'] = $announceUrl;

                // Rewrite all tiers of announce-list so clients that prefer the
                // list (qBittorrent, Deluge) also get the passkey-keyed URL
                if (isset($torrentData['announce-list']) && is_array($torrentData['announce-list'])) {
                    $torrentData['announce-list'] = [[$announceUrl]];
                }

                $contents = $this->bencode->encode($torrentData);
            }
        }

        $displayName = $torrent->filename ?: ($infoHash . '.torrent');

        return response($contents, 200, [
            'Content-Type'        => 'application/x-bittorrent',
            'Content-Disposition' => 'attachment; filename="' . rawurlencode($displayName) . '"',
            'Content-Length'      => strlen($contents),
        ]);
    }
}
