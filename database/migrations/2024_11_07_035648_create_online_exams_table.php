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
        Schema::create('online_exams', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->string('title')->nullable();
            $table->foreignId('period_id')->constrained('periods')->onDelete('cascade');
            $table->string('type', 50)->nullable();
            $table->longText('instructions')->nullable();
            $table->date('date')->nullable();
            $table->time('start_time')->nullable();
            $table->date('end_date')->nullable();
            $table->time('end_time')->nullable();
            $table->float('max_mark')->default(0);
            $table->float('pass_percentage')->default(0);
            $table->dateTime('published_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('result_published_at')->nullable();
            $table->longText('description')->nullable();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('set null');

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
        Schema::dropIfExists('online_exams');
    }
};
