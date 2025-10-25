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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();
            $table->string('roll_number', 50)->nullable();
            $table->integer('number')->nullable();

            $table->foreignId('contact_id')->nullable()->constrained('contacts')->onDelete('cascade');
            $table->foreignId('period_id')->nullable()->constrained('periods')->onDelete('cascade');
            $table->foreignId('admission_id')->nullable()->constrained('admissions')->onDelete('cascade');
            $table->foreignId('batch_id')->nullable()->constrained('batches')->onDelete('cascade');
            $table->foreignId('fee_structure_id')->nullable()->constrained('fee_structures')->onDelete('set null');
            $table->foreignId('fee_concession_type_id')->nullable()->constrained('options')->onDelete('set null');
            $table->foreignId('enrollment_type_id')->nullable()->constrained('options')->onDelete('set null');
            $table->foreignId('enrollment_status_id')->nullable()->constrained('options')->onDelete('set null');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            $table->text('remarks')->nullable();
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
        Schema::dropIfExists('students');
    }
};
