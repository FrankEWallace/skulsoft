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
        Schema::create('subject_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->foreignId('course_id')->nullable()->constrained('courses')->onDelete('cascade');
            $table->foreignId('batch_id')->nullable()->constrained('batches')->onDelete('cascade');
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->onDelete('cascade');

            $table->integer('position')->default(0);
            $table->float('credit')->default(0);
            $table->integer('max_class_per_week')->default(0);
            $table->decimal('course_fee', 25, 5)->default(0);
            $table->decimal('exam_fee', 25, 5)->default(0);
            $table->boolean('is_elective')->default(false);
            $table->boolean('has_no_exam')->default(false);
            $table->boolean('has_grading')->default(false);
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
        Schema::dropIfExists('subject_records');
    }
};
