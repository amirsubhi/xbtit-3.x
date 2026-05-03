<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    private const CACHE_KEY = 'app_settings';

    /** Return all settings indexed by key, values cast to their PHP type. */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return Setting::all()->mapWithKeys(fn ($s) => [$s->key => $s->getTypedValue()])->all();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /** Persist a single setting and bust the cache. */
    public function set(string $key, mixed $value): void
    {
        $setting = Setting::findOrFail($key);
        $setting->value = Setting::serializeValue($value, $setting->type);
        $setting->save();
        $this->bust();
    }

    /** Persist multiple key→value pairs in one pass, then bust once. */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $setting = Setting::find($key);
            if (!$setting) {
                continue;
            }
            $setting->value = Setting::serializeValue($value, $setting->type);
            $setting->save();
        }
        $this->bust();
    }

    /** Return Setting rows grouped by their 'group' column. */
    public function grouped(): \Illuminate\Support\Collection
    {
        return Setting::orderBy('group')->orderBy('key')->get()->groupBy('group');
    }

    public function bust(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
