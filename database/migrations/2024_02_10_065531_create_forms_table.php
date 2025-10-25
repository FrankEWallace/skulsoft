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
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->foreignId('period_id')->nullable()->constrained('periods')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->json('audience')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->date('due_date')->nullable();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();

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
        Schema::dropIfExists('forms');
    }
};
