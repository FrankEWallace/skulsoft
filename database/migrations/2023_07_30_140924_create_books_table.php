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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();
            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('cascade');
            $table->foreignId('author_id')->nullable()->constrained('options')->onDelete('set null');
            $table->foreignId('publisher_id')->nullable()->constrained('options')->onDelete('set null');
            $table->foreignId('topic_id')->nullable()->constrained('options')->onDelete('set null');
            $table->foreignId('language_id')->nullable()->constrained('options')->onDelete('set null');
            $table->foreignId('category_id')->nullable()->constrained('options')->onDelete('set null');
            $table->string('title')->nullable();
            $table->string('sub_title')->nullable();
            $table->string('subject')->nullable();
            $table->string('year_published', 50)->nullable();
            $table->string('volume', 50)->nullable();
            $table->string('isbn_number', 50)->nullable();
            $table->string('call_number', 50)->nullable();
            $table->string('edition', 50)->nullable();
            $table->string('type', 50)->nullable();
            $table->integer('page')->default(0);
            $table->integer('price')->default(0);
            $table->text('summary')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
