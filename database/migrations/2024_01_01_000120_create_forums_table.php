<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forum_category_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('description', 300)->default('');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->unsignedInteger('thread_count')->default(0);
            $table->unsignedInteger('post_count')->default(0);
            $table->timestamp('last_post_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forums');
    }
};
