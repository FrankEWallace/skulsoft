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
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();
            $table->text('title')->nullable();
            $table->foreignId('period_id')->nullable()->constrained('periods')->onDelete('cascade');
            $table->foreignId('type_id')->nullable()->constrained('options')->onDelete('set null');
            $table->date('date')->nullable();
            $table->date('due_date')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->boolean('enable_marking')->default(0);
            $table->integer('max_mark')->nullable();
            $table->longText('description')->nullable();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('cascade');
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
        Schema::dropIfExists('assignments');
    }
};
