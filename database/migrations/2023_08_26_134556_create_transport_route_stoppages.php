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
        Schema::create('transport_route_stoppages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->foreignId('route_id')->nullable()->constrained('transport_routes')->onDelete('cascade');
            $table->foreignId('stoppage_id')->nullable()->constrained('transport_stoppages')->onDelete('cascade');

            $table->integer('arrival_time')->nullable();
            $table->integer('arrival_waiting_time')->default(0);
            $table->integer('departure_time')->nullable();
            $table->integer('departure_waiting_time')->default(0);

            $table->integer('position')->default(0);
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
        Schema::dropIfExists('transport_route_stoppages');
    }
};
