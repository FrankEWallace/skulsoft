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
        Schema::create('student_fee_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->foreignId('student_fee_id')->nullable()->constrained('student_fees')->onDelete('cascade');
            $table->foreignId('fee_head_id')->nullable()->constrained('fee_heads')->onDelete('cascade');
            $table->string('default_fee_head', 50)->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('cascade');
            $table->decimal('amount', 25, 2)->default(0);
            $table->decimal('concession_amount', 25, 2)->default(0);

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_fee_payments');
    }
};
