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
        Schema::create('student_timesheets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();
            $table->date('date')->nullable();
            $table->time('in_at')->nullable();
            $table->time('out_at')->nullable();

            $table->foreignId('student_id')->nullable()->constrained('students')->onDelete('cascade');

            $table->integer('duration')->default(0);
            $table->boolean('is_manual')->default(0);
            $table->string('status', 50)->nullable();

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
        Schema::dropIfExists('student_timesheets');
    }
};
