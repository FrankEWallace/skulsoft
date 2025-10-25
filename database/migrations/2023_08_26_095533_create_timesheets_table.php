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
        Schema::create('timesheets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();
            $table->date('date')->nullable();
            $table->dateTime('in_at')->nullable();
            $table->dateTime('out_at')->nullable();

            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('cascade');
            $table->foreignId('work_shift_id')->nullable()->constrained('work_shifts')->onDelete('cascade');

            $table->string('type', 20)->nullable();
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
        Schema::dropIfExists('timesheets');
    }
};
