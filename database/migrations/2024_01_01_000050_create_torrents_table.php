<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy table was named 'files' — keeping that name for announce compatibility
        Schema::create('files', function (Blueprint $table) {
            $table->string('info_hash', 40)->primary();
            $table->string('filename', 250);
            $table->string('info', 250)->default('');
            $table->unsignedBigInteger('size')->default(0);
            $table->text('comment')->nullable();
            $table->unsignedBigInteger('category')->default(0)->index();
            $table->enum('external', ['yes', 'no'])->default('no');
            $table->string('announce_url', 100)->default('');
            $table->unsignedBigInteger('uploader')->index();
            $table->enum('anonymous', ['true', 'false'])->default('false');
            $table->unsignedBigInteger('dlbytes')->default(0);
            $table->unsignedInteger('seeds')->default(0);
            $table->unsignedInteger('leechers')->default(0);
            $table->unsignedInteger('finished')->default(0);
            $table->unsignedInteger('lastcycle')->default(0);
            $table->unsignedBigInteger('speed')->default(0);
            $table->binary('bin_hash');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
