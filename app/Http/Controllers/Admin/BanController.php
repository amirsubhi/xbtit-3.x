<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BannedIp;
use Illuminate\Http\Request;

class BanController extends Controller
{
    public function index()
    {
        $bans = BannedIp::orderByDesc('id')->paginate(30);
        return view('admin.bans.index', compact('bans'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'ip_start' => ['required', 'ipv4'],
            'ip_end'   => ['nullable', 'ipv4'],
            'comment'  => ['nullable', 'string', 'max:255'],
        ]);

        $first = sprintf('%u', ip2long($data['ip_start']));
        $last  = $data['ip_end']
            ? sprintf('%u', ip2long($data['ip_end']))
            : $first;

        BannedIp::create([
            'first'   => $first,
            'last'    => $last,
            'addedby' => $request->user()->id,
            'comment' => $data['comment'] ?? '',
        ]);

        AuditLog::record('admin.ban.add', ['ip_start' => $data['ip_start'], 'ip_end' => $data['ip_end'] ?? $data['ip_start']]);

        return back()->with('status', 'IP ban added.');
    }

    public function destroy(BannedIp $ban)
    {
        AuditLog::record('admin.ban.remove', ['first' => $ban->first, 'last' => $ban->last]);
        $ban->delete();

        return back()->with('status', 'IP ban removed.');
    }
}
