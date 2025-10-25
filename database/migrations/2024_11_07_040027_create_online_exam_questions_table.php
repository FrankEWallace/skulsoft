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
        Schema::create('online_exam_questions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->foreignId('online_exam_id')->constrained('online_exams')->onDelete('cascade');
            $table->text('header')->nullable();
            $table->text('title')->nullable();
            $table->integer('position')->default(0);
            $table->string('type', 50)->nullable();
            $table->float('mark')->default(0);
            $table->json('options')->nullable();
            $table->longText('description')->nullable();

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
        Schema::dropIfExists('online_exam_questions');
    }
};
