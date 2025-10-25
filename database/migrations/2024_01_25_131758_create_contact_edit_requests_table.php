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
        Schema::create('contact_edit_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');

            $table->nullableMorphs('model');

            $table->json('data')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->nullable();
            $table->datetime('processed_at')->nullable();
            $table->text('comment')->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_edit_requests');
    }
};
