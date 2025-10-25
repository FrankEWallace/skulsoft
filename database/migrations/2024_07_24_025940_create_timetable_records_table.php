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
        Schema::create('timetable_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();
            $table->foreignId('timetable_id')->nullable()->constrained('timetables')->onDelete('cascade');
            $table->string('day', 20)->nullable();
            $table->foreignId('class_timing_id')->nullable()->constrained('class_timings')->onDelete('set null');
            $table->boolean('is_holiday')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetable_records');
    }
};
