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
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('cascade');
            $table->foreignId('salary_template_id')->nullable()->constrained('salary_templates')->onDelete('cascade');

            $table->date('effective_date')->nullable();
            $table->decimal('hourly_pay')->default(0);
            $table->decimal('net_earning', 25, 5)->default(0);
            $table->decimal('net_deduction', 25, 5)->default(0);
            $table->decimal('net_employee_contribution', 25, 5)->default(0);
            $table->decimal('net_employer_contribution', 25, 5)->default(0);
            $table->decimal('net_salary', 25, 5)->default(0);
            $table->text('description')->nullable();

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
        Schema::dropIfExists('salary_structures');
    }
};
