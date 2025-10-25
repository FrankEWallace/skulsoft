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
        Schema::create('fee_concession_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->foreignId('fee_concession_id')->nullable()->constrained('fee_concessions')->onDelete('cascade');
            $table->foreignId('fee_head_id')->nullable()->constrained('fee_heads')->onDelete('cascade');

            $table->decimal('value', 25, 5)->default(0);
            $table->string('type', 20)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_concession_records');
    }
};
