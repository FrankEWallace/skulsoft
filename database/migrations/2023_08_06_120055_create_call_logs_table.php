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
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();
            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('cascade');
            $table->foreignId('purpose_id')->nullable()->constrained('options')->onDelete('set null');
            $table->string('type', 20)->nullable();
            $table->nullableMorphs('model');
            $table->string('name', 100)->nullable();
            $table->json('company')->nullable();
            $table->string('incoming_number', 20)->nullable();
            $table->string('outgoing_number', 20)->nullable();
            $table->dateTime('call_at')->nullable();
            $table->integer('duration')->default(0);
            $table->text('conversation')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
