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
        Schema::create('communications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->foreignId('period_id')->nullable()->constrained('periods')->onDelete('cascade');

            $table->string('type', 20)->nullable();
            $table->string('subject')->nullable();
            $table->text('content')->nullable();
            $table->json('recipients')->nullable();
            $table->json('lists')->nullable();
            $table->json('audience')->nullable();

            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');

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
        Schema::dropIfExists('communications');
    }
};
