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
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();
            $table->string('number_format', 50)->nullable();
            $table->integer('number')->nullable();
            $table->string('code_number', 50)->nullable();

            $table->nullableMorphs('model');

            $table->string('type', 50)->nullable();
            $table->string('request_type', 50)->nullable();
            $table->date('date')->nullable();
            $table->string('transport_direction', 50)->nullable();
            $table->foreignId('transport_stoppage_id')->nullable()->constrained('transport_stoppages')->onDelete('set null');

            $table->text('description')->nullable();
            $table->string('status', 20)->nullable();
            $table->datetime('processed_at')->nullable();

            $table->foreignId('request_user_id')->nullable()->constrained('users')->onDelete('set null');

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
