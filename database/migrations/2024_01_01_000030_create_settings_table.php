<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            // TEXT instead of legacy VARCHAR(200) — long values like CSP headers, announce URL arrays
            $table->text('value');
        });

        // Seed defaults matching legacy btit_settings defaults
        $defaults = [
            ['key' => 'name',                   'value' => 'xbtit'],
            ['key' => 'url',                    'value' => 'http://localhost'],
            ['key' => 'announce',               'value' => '["http://localhost/announce.php"]'],
            ['key' => 'email',                  'value' => 'admin@localhost'],
            ['key' => 'torrentdir',             'value' => 'torrents'],
            ['key' => 'external',               'value' => 'true'],
            ['key' => 'gzip',                   'value' => 'false'],
            ['key' => 'debug',                  'value' => 'false'],
            ['key' => 'disable_dht',            'value' => 'true'],
            ['key' => 'nat',                    'value' => 'false'],
            ['key' => 'dynamic',                'value' => 'false'],
            ['key' => 'allow_override_ip',      'value' => 'false'],
            ['key' => 'countbyte',              'value' => 'true'],
            ['key' => 'peercaching',            'value' => 'true'],
            ['key' => 'max_announce',           'value' => '1800'],
            ['key' => 'min_announce',           'value' => '300'],
            ['key' => 'max_peers_per_announce', 'value' => '50'],
            ['key' => 'maxpid_seeds',           'value' => '3'],
            ['key' => 'maxpid_leech',           'value' => '2'],
            ['key' => 'max_users',              'value' => '0'],
            ['key' => 'validation',             'value' => 'email'],
            ['key' => 'default_charset',        'value' => 'UTF-8'],
            ['key' => 'xbtt_enabled',           'value' => 'false'],
            ['key' => 'xbtt_url',               'value' => 'http://localhost:2710'],
            ['key' => 'default_theme',          'value' => 'xbtit-default'],
        ];

        DB::table('settings')->insert($defaults);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
