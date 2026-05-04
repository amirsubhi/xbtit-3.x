<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peers', function (Blueprint $table) {
            // 'bytes' stores the 'left' value from announce — bytes remaining.
            // Zero means seeder. Used by AnnounceService to detect transitions and compute deltas.
            $table->unsignedBigInteger('bytes')->default(0)->after('downloaded');
        });
    }

    public function down(): void
    {
        Schema::table('peers', function (Blueprint $table) {
            $table->dropColumn('bytes');
        });
    }
};
