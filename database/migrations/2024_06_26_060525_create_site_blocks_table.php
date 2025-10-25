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
        Schema::create('site_blocks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->integer('position')->default(0);
            $table->string('name', 50)->nullable();
            $table->string('title')->nullable();
            $table->string('sub_title')->nullable();
            $table->foreignId('menu_id')->nullable()->constrained('site_menus')->onDelete('set null');
            $table->text('content')->nullable();
            $table->json('assets')->nullable();
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
        Schema::dropIfExists('site_blocks');
    }
};
