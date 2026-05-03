<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->enum('type', ['bool', 'int', 'string', 'json'])->default('string')->after('value');
            $table->string('group', 40)->default('site')->after('type')->index();
            $table->string('label', 100)->default('')->after('group');
        });

        // Backfill existing rows with type / group / label metadata
        $meta = [
            // site
            'name'                   => ['string', 'site',    'Site Name'],
            'url'                    => ['string', 'site',    'Site URL'],
            'email'                  => ['string', 'site',    'Admin Email'],
            'external'               => ['bool',   'site',    'Open Registration'],
            // tracker
            'announce'               => ['json',   'tracker', 'Announce URLs (JSON array)'],
            'disable_dht'            => ['bool',   'tracker', 'Disable DHT'],
            'dynamic'                => ['bool',   'tracker', 'Dynamic Torrents'],
            'xbtt_enabled'           => ['bool',   'tracker', 'Enable XBT Tracker'],
            'xbtt_url'               => ['string', 'tracker', 'XBT Tracker URL'],
            'gzip'                   => ['bool',   'tracker', 'Enable GZip Responses'],
            'debug'                  => ['bool',   'tracker', 'Debug Mode'],
            // peers
            'max_announce'           => ['int',    'peers',   'Announce Interval (s)'],
            'min_announce'           => ['int',    'peers',   'Min Announce Interval (s)'],
            'max_peers_per_announce' => ['int',    'peers',   'Max Peers Per Announce'],
            'maxpid_seeds'           => ['int',    'peers',   'Max Concurrent Seeds Per Passkey'],
            'maxpid_leech'           => ['int',    'peers',   'Max Concurrent Leeches Per Passkey'],
            'nat'                    => ['bool',   'peers',   'Allow NAT Users'],
            'allow_override_ip'      => ['bool',   'peers',   'Allow Client IP Override'],
            'countbyte'              => ['bool',   'peers',   'Count Transfer Bytes'],
            'peercaching'            => ['bool',   'peers',   'Peer Caching'],
            // users
            'max_users'              => ['int',    'users',   'Max Users (0 = unlimited)'],
            'validation'             => ['string', 'users',   'Account Validation Mode'],
            // storage
            'torrentdir'             => ['string', 'storage', 'Torrent File Directory'],
            'default_charset'        => ['string', 'storage', 'Default Charset'],
        ];

        foreach ($meta as $key => [$type, $group, $label]) {
            DB::table('settings')
                ->where('key', $key)
                ->update(compact('type', 'group', 'label'));
        }
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['type', 'group', 'label']);
        });
    }
};
