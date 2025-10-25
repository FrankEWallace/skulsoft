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
        Schema::create('exam_schedules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->foreignId('exam_id')->nullable()->constrained('exams')->onDelete('cascade');
            $table->foreignId('course_id')->nullable()->constrained('courses')->onDelete('cascade');
            $table->foreignId('batch_id')->nullable()->constrained('batches')->onDelete('cascade');
            $table->foreignId('assessment_id')->nullable()->constrained('exam_assessments')->onDelete('cascade');
            $table->foreignId('observation_id')->nullable()->constrained('exam_observations')->onDelete('set null');
            $table->foreignId('grade_id')->nullable()->constrained('exam_grades')->onDelete('cascade');

            $table->text('description')->nullable();
            $table->boolean('is_reassessment')->nullable();
            $table->string('attempt', 20)->default('first');
            $table->json('details')->nullable();
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
        Schema::dropIfExists('exam_schedules');
    }
};
