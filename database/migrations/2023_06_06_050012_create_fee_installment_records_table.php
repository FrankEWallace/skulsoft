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
        Schema::create('fee_installment_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->foreignId('fee_installment_id')->nullable()->constrained('fee_installments')->onDelete('cascade');
            $table->foreignId('fee_head_id')->nullable()->constrained('fee_heads')->onDelete('cascade');

            $table->decimal('amount', 25, 5)->default(0);
            $table->boolean('is_optional')->default(0);

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_installment_records');
    }
};
