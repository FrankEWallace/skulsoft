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
        Schema::create('vehicle_fuel_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->onDelete('cascade');
            $table->foreignId('vendor_id')->nullable()->constrained('ledgers')->onDelete('cascade');

            $table->string('fuel_type', 20)->nullable();
            $table->decimal('quantity', 25, 5)->default(0);
            $table->decimal('price_per_unit', 25, 5)->default(0);
            $table->date('date')->nullable();
            $table->integer('log')->nullable();
            $table->text('remarks')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_fuel_records');
    }
};
