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
        Schema::create('service_allocations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->nullableMorphs('model');

            $table->string('type', 50)->nullable();
            $table->foreignId('transport_stoppage_id')->nullable()->constrained('transport_stoppages')->onDelete('set null');

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_allocations');
    }
};
