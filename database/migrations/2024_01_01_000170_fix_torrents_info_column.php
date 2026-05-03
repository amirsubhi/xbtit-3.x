<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            // Widen info (user description) from VARCHAR(250) to TEXT
            $table->text('info')->default('')->change();
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->string('info', 250)->default('')->change();
        });
    }
};
