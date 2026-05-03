<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forum_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200);
            $table->boolean('locked')->default(false);
            $table->boolean('sticky')->default(false);
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('reply_count')->default(0);
            $table->timestamp('last_post_at')->nullable();
            $table->timestamps();

            $table->index(['forum_id', 'sticky', 'last_post_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('threads');
    }
};
