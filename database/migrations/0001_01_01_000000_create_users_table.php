<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 40)->unique();
            $table->string('email', 100)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 255);
            $table->rememberToken();

            // Legacy password migration — null once rehashed to argon2id
            $table->enum('pass_type', ['1', '2', '3', '4', '5', '6', '7'])->nullable();
            $table->string('salt', 20)->default('');
            $table->string('dupe_hash', 20)->default('');

            // Announce passkey system (replaces legacy users.pid INT random)
            $table->string('passkey', 64)->unique();
            $table->string('legacy_passkey', 40)->nullable()->unique();
            $table->timestamp('legacy_passkey_expires_at')->nullable();

            // Permission level FK (references users_level.id_level)
            $table->unsignedInteger('id_level')->default(1)->index();

            // Stats
            $table->unsignedBigInteger('downloaded')->default(0);
            $table->unsignedBigInteger('uploaded')->default(0);

            // Profile
            $table->string('avatar', 200)->nullable();
            $table->unsignedTinyInteger('flag')->default(0);
            $table->unsignedTinyInteger('topicsperpage')->default(15);
            $table->unsignedTinyInteger('postsperpage')->default(15);
            $table->unsignedTinyInteger('torrentsperpage')->default(15);
            $table->string('time_offset', 4)->default('0');
            $table->unsignedSmallInteger('language')->default(1);
            $table->unsignedSmallInteger('style')->default(1);

            // IP tracking (IPv6-ready)
            $table->string('cip', 45)->nullable()->index();
            $table->bigInteger('lip')->nullable();

            // Unverified email change
            $table->string('temp_email', 100)->default('');

            // Account security
            $table->unsignedTinyInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_at')->nullable();

            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
