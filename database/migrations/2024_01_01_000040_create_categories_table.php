<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->unsignedBigInteger('sub')->default(0);
            $table->unsignedInteger('sort_index')->default(0);
            $table->string('image', 255)->default('');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
