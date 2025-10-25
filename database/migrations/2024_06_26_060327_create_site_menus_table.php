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
        Schema::create('site_menus', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->integer('position')->default(0);
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('placement')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('site_menus')->onDelete('set null');
            $table->foreignId('page_id')->nullable()->constrained('site_pages')->onDelete('set null');
            $table->boolean('is_default')->default(0);
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
        Schema::dropIfExists('site_menus');
    }
};
