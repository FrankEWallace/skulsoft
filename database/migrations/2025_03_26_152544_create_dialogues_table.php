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
        Schema::create('dialogues', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();
            $table->string('title')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('options')->onDelete('set null');
            $table->date('date')->nullable();
            $table->string('description')->nullable();

            $table->nullableMorphs('model');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dialogues');
    }
};
