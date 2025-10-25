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
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->foreignId('student_id')->nullable()->constrained('students')->onDelete('cascade');
            $table->foreignId('exam_id')->nullable()->constrained('exams')->onDelete('cascade');
            $table->foreignId('term_id')->nullable()->constrained('exam_terms')->onDelete('cascade');
            $table->string('attempt', 20)->nullable();
            $table->boolean('is_cumulative')->default(false);
            $table->string('result', 20)->nullable();
            $table->json('marks')->nullable();
            $table->json('subjects')->nullable();
            $table->float('total_marks')->default(0);
            $table->float('obtained_marks')->default(0);
            $table->float('percentage')->default(0);
            $table->json('summary')->nullable();
            $table->dateTime('generated_at')->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_results');
    }
};
