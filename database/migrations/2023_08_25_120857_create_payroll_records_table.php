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
        Schema::create('payroll_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->foreignId('payroll_id')->nullable()->constrained('payrolls')->onDelete('cascade');
            $table->foreignId('pay_head_id')->nullable()->constrained('pay_heads')->onDelete('cascade');

            $table->decimal('calculated', 25, 5)->default(0);
            $table->decimal('amount', 25, 5)->default(0);

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_records');
    }
};
