<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->integer('position')->default(0);
            $table->dateTime('published_at')->nullable();
            $table->string('title')->nullable();
            $table->text('sub_title')->nullable();
            $table->string('slug')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('options')->onDelete('set null');
            $table->longText('content')->nullable();
            $table->dateTime('pinned_at')->nullable();
            $table->dateTime('archived_at')->nullable();
            $table->string('status', 20)->nullable();
            $table->string('visibility')->nullable();
            $table->json('assets')->nullable();
            $table->json('seo')->nullable();
            $table->json('author')->nullable();
            $table->json('analytics')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('config')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
