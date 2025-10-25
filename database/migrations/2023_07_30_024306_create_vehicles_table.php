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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('cascade');

            $table->string('name')->nullable();
            $table->json('registration')->nullable();
            $table->string('model_number', 50)->nullable();
            $table->string('make', 50)->nullable();
            $table->string('class', 50)->nullable();
            $table->integer('seating_capacity')->default(0);
            $table->integer('max_seating_allowed')->default(0);
            $table->string('fuel_type', 20)->nullable();
            $table->integer('fuel_capacity')->default(0);
            $table->json('owner')->nullable();
            $table->json('driver')->nullable();
            $table->json('helper')->nullable();
            $table->json('disposal')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
