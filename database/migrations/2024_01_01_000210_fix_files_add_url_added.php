<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            // Filesystem path to the stored .btf file (info_hash + '.btf').
            $table->string('url', 100)->default('')->after('info_hash');
            // Upload timestamp — used for WT gate and default sort order.
            $table->unsignedInteger('added')->default(0)->after('url');
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn(['url', 'added']);
        });
    }
};
