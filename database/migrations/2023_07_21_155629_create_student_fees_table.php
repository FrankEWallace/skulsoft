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
        Schema::create('student_fees', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->foreignId('student_id')->nullable()->constrained('students')->onDelete('cascade');
            $table->foreignId('fee_installment_id')->nullable()->constrained('fee_installments')->onDelete('cascade');
            $table->foreignId('transport_circle_id')->nullable()->constrained('transport_circles')->onDelete('set null');
            $table->foreignId('fee_concession_id')->nullable()->constrained('fee_concessions')->onDelete('set null');

            $table->string('transport_direction', 20)->nullable();
            $table->json('fee')->nullable();
            $table->decimal('additional_charge', 25, 5)->default(0);
            $table->decimal('additional_discount', 25, 5)->default(0);
            $table->decimal('total', 25, 5)->default(0);
            $table->decimal('paid', 25, 5)->default(0);
            $table->date('due_date')->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_fees');
    }
};
