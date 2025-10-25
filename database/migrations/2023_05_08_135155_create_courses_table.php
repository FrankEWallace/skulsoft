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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->foreignId('division_id')->nullable()->constrained('divisions')->onDelete('cascade');

            $table->string('name')->nullable();
            $table->string('term')->nullable();
            $table->string('code', 50)->nullable();
            $table->string('shortcode', 50)->nullable();
            $table->date('period_start_date')->nullable();
            $table->date('period_end_date')->nullable();
            $table->boolean('enable_registration')->default(false);
            $table->decimal('registration_fee', 25, 5)->default(0);
            $table->text('description')->nullable();
            $table->integer('position')->default(0);
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
        Schema::dropIfExists('courses');
    }
};
