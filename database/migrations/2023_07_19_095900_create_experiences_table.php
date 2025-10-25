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
        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();
            $table->string('headline')->nullable();
            $table->string('title')->nullable();
            $table->string('organization_name', 100)->nullable();
            $table->string('location')->nullable();

            $table->nullableMorphs('model');
            $table->foreignId('employment_type_id')->nullable()->constrained('options')->onDelete('set null');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->text('job_profile')->nullable();
            $table->dateTime('verified_at')->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('experiences');
    }
};
