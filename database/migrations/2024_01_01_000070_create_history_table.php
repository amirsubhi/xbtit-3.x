<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Named 'history' to match legacy schema — the Snatch model maps to this table
        Schema::create('history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uid')->index();
            $table->string('infohash', 40)->index();
            $table->unsignedBigInteger('uploaded')->default(0);
            $table->unsignedBigInteger('downloaded')->default(0);
            $table->enum('active', ['yes', 'no'])->default('no');
            $table->string('agent', 60)->default('');
            $table->unsignedInteger('date')->nullable();

            $table->unique(['uid', 'infohash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('history');
    }
};
