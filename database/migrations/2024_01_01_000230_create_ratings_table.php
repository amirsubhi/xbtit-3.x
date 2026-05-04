<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Star ratings: 1–10 scale (displayed as half-stars, 0.5–5.0).
        // One vote per user per torrent enforced by unique index.
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->string('info_hash', 40)->index();
            $table->unsignedBigInteger('uid');
            $table->unsignedTinyInteger('rating'); // 1-10 (maps to 0.5–5.0 stars)
            $table->unsignedInteger('added');
            $table->unique(['info_hash', 'uid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
