<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users_level', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('id_level')->unique();
            $table->string('level', 30)->default('');
            $table->enum('predef_level', [
                'guest', 'validating', 'member', 'uploader', 'vip', 'moderator', 'admin', 'owner',
            ])->default('member');
            $table->boolean('admin_access')->default(false);
            $table->boolean('can_download')->default(true);
            $table->string('prefixcolor', 200)->default('');
            $table->string('suffixcolor', 200)->default('');
            $table->unsignedSmallInteger('WT')->default(0);

            // Per-level permissions
            $table->enum('can_upload', ['yes', 'no'])->default('yes');
            $table->enum('can_comment', ['yes', 'no'])->default('yes');
            $table->enum('can_invite', ['yes', 'no'])->default('no');
        });

        // Seed the default 8 permission tiers
        DB::table('users_level')->insert([
            ['id_level' => 0, 'level' => 'Guest',     'predef_level' => 'guest',      'admin_access' => false, 'can_download' => false],
            ['id_level' => 1, 'level' => 'Validating', 'predef_level' => 'validating', 'admin_access' => false, 'can_download' => false],
            ['id_level' => 2, 'level' => 'Member',    'predef_level' => 'member',      'admin_access' => false, 'can_download' => true],
            ['id_level' => 3, 'level' => 'Uploader',  'predef_level' => 'uploader',    'admin_access' => false, 'can_download' => true],
            ['id_level' => 4, 'level' => 'VIP',       'predef_level' => 'vip',         'admin_access' => false, 'can_download' => true],
            ['id_level' => 5, 'level' => 'Moderator', 'predef_level' => 'moderator',   'admin_access' => true,  'can_download' => true],
            ['id_level' => 6, 'level' => 'Admin',     'predef_level' => 'admin',       'admin_access' => true,  'can_download' => true],
            ['id_level' => 7, 'level' => 'Owner',     'predef_level' => 'owner',       'admin_access' => true,  'can_download' => true],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('users_level');
    }
};
