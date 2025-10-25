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
        Schema::create('fee_installments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->foreignId('fee_structure_id')->nullable()->constrained('fee_structures')->onDelete('cascade');
            $table->foreignId('fee_group_id')->nullable()->constrained('fee_groups')->onDelete('cascade');
            $table->foreignId('transport_fee_id')->nullable()->constrained('transport_fees')->onDelete('set null');

            $table->string('title', 100)->nullable();
            $table->date('due_date')->nullable();
            $table->json('late_fee')->nullable();

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
        Schema::dropIfExists('fee_installments');
    }
};
