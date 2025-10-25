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
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();
            $table->string('number_format', 50)->nullable();
            $table->integer('number')->nullable();
            $table->string('code_number', 50)->nullable();

            $table->boolean('is_provisional')->default(false);
            $table->string('provisional_number_format', 50)->nullable();
            $table->integer('provisional_number')->nullable();
            $table->string('provisional_code_number', 50)->nullable();

            $table->foreignId('registration_id')->nullable()->constrained('registrations')->onDelete('cascade');
            $table->foreignId('batch_id')->nullable()->constrained('batches')->onDelete('set null');
            $table->foreignId('transfer_reason_id')->nullable()->constrained('options')->onDelete('set null');

            $table->date('joining_date')->nullable();
            $table->text('remarks')->nullable();
            $table->date('leaving_date')->nullable();
            $table->text('leaving_remarks')->nullable();
            $table->dateTime('cancelled_at')->nullable();

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
        Schema::dropIfExists('admissions');
    }
};
