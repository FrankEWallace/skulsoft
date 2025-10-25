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
        Schema::create('transport_fee_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->foreignId('transport_fee_id')->nullable()->constrained('transport_fees')->onDelete('cascade');
            $table->foreignId('transport_circle_id')->nullable()->constrained('transport_circles')->onDelete('cascade');

            $table->decimal('arrival_amount', 25, 5)->default(0);
            $table->decimal('departure_amount', 25, 5)->default(0);
            $table->decimal('roundtrip_amount', 25, 5)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_fee_records');
    }
};
