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
        Schema::create('student_fee_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->foreignId('student_fee_id')->nullable()->constrained('student_fees')->onDelete('cascade');
            $table->foreignId('fee_head_id')->nullable()->constrained('fee_heads')->onDelete('cascade');

            $table->string('default_fee_head', 50)->nullable();
            $table->date('due_date')->nullable();
            $table->boolean('is_optional')->default(0);
            $table->decimal('amount', 25, 5)->default(0);
            $table->boolean('has_custom_amount')->default(0);
            $table->decimal('paid', 25, 5)->default(0);
            $table->decimal('concession', 25, 5)->default(0);
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
        Schema::dropIfExists('student_fee_records');
    }
};
