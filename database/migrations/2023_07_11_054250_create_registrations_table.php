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
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();
            $table->string('number_format', 50)->nullable();
            $table->integer('number')->nullable();
            $table->string('code_number', 50)->nullable();

            $table->foreignId('contact_id')->nullable()->constrained('contacts')->onDelete('cascade');
            $table->foreignId('period_id')->nullable()->constrained('periods')->onDelete('cascade');
            $table->foreignId('course_id')->nullable()->constrained('courses')->onDelete('cascade');
            $table->foreignId('stage_id')->nullable()->constrained('options')->onDelete('set null');
            $table->foreignId('enrollment_type_id')->nullable()->constrained('options')->onDelete('set null');

            $table->date('date')->nullable();
            $table->decimal('fee', 25, 5)->default(0);
            $table->text('remarks')->nullable();
            $table->string('payment_status', 20)->nullable();
            $table->string('status', 20)->nullable();
            $table->boolean('is_online')->default(0);
            $table->text('rejection_remarks')->nullable();
            $table->datetime('rejected_at')->nullable();
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
        Schema::dropIfExists('registrations');
    }
};
