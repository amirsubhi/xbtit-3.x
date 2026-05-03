<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Torrent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TorrentController extends Controller
{
    public function index(Request $request)
    {
        $torrents = Torrent::with('category', 'uploader')
            ->when($request->search, fn ($q) => $q->where('filename', 'like', '%' . $request->search . '%'))
            ->latest()
            ->paginate(30);

        return view('admin.torrents.index', compact('torrents'));
    }

    public function edit(Torrent $torrent)
    {
        $categories = Category::where('sub', 0)->with('children')->orderBy('sort_index')->get();
        return view('admin.torrents.edit', compact('torrent', 'categories'));
    }

    public function update(Request $request, Torrent $torrent)
    {
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

        return redirect()->route('admin.torrents.index')->with('status', 'Torrent updated.');
    }

    public function destroy(Torrent $torrent)
    {
        $infoHash = $torrent->info_hash;

        DB::table('comments')->where('info_hash', $infoHash)->delete();
        DB::table('peers')->where('infohash', $infoHash)->delete();
        DB::table('history')->where('infohash', $infoHash)->delete();

        $path = $infoHash . '.btf';
        if (Storage::disk('torrents')->exists($path)) {
            Storage::disk('torrents')->delete($path);
        }

        $torrent->delete();

        return back()->with('status', 'Torrent deleted.');
    }
}
