<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online', function (Blueprint $table) {
            $table->string('session_id', 40)->primary();
            $table->unsignedBigInteger('user_id')->index();
            // IPv6-ready — legacy was VARCHAR(15), too short
            $table->string('user_ip', 45);
            $table->string('location', 40)->default('');
            $table->unsignedInteger('lastaction')->default(0);
            $table->string('user_name', 40)->default('');
            $table->string('user_group', 50)->default('');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online');
    }
};
