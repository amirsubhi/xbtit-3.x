<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rolling window of per-torrent transfer speed samples (CAL-006).
        // Speed = sum(bytes) / sum(delta) over last 20 rows per info_hash.
        Schema::create('speed_samples', function (Blueprint $table) {
            $table->id();
            $table->string('info_hash', 40)->index();
            $table->unsignedBigInteger('bytes')->default(0);
            $table->unsignedInteger('delta')->default(1);
            $table->unsignedInteger('sampled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speed_samples');
    }
};
