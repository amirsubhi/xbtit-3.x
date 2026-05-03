<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peers', function (Blueprint $table) {
            $table->id();
            $table->string('infohash', 40)->index();
            $table->string('peer_id', 40);
            $table->string('ip', 45);
            $table->unsignedSmallInteger('port');
            $table->enum('status', ['leecher', 'seeder'])->default('leecher');
            $table->unsignedBigInteger('uploaded')->default(0);
            $table->unsignedBigInteger('downloaded')->default(0);
            $table->unsignedInteger('lastupdate')->default(0);
            $table->enum('natuser', ['N', 'Y'])->default('N');
            $table->string('client', 60)->default('');
            $table->string('dns', 100)->default('');
            // Denormalized passkey to avoid per-announce user join
            $table->string('passkey', 64)->nullable()->index();

            // Compact peer response cache (avoids recomputing on every scrape)
            $table->string('compact', 6)->default('');

            $table->unique(['infohash', 'peer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peers');
    }
};
