<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banned_ip', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('first')->nullable();
            $table->unsignedBigInteger('last')->nullable();
            $table->unsignedBigInteger('addedby')->default(0);
            $table->string('comment', 255)->default('');
            $table->timestamp('created_at')->nullable();

            $table->index(['first', 'last']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banned_ip');
    }
};
