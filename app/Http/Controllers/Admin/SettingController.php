<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(private readonly SettingService $settings) {}

    public function index()
    {
        return view('admin.settings.index', [
            'grouped' => $this->settings->grouped(),
        ]);
    }

    public function update(Request $request)
    {
        $inputs = $request->except(['_token', '_method']);

        // Bool settings not present in the POST body mean "false"
        $boolKeys = \App\Models\Setting::where('type', 'bool')->pluck('key');
        foreach ($boolKeys as $key) {
            $inputs[$key] = isset($inputs[$key]) ? 'true' : 'false';
        }

        $this->settings->setMany($inputs);

        return redirect()->route('admin.settings.index')
            ->with('status', 'Settings saved.');
    }
}
