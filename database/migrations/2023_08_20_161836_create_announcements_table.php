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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();
            $table->string('number_format', 50)->nullable();
            $table->integer('number')->nullable();
            $table->string('code_number', 50)->nullable();
            $table->foreignId('period_id')->nullable()->constrained('periods')->onDelete('cascade');
            $table->foreignId('type_id')->nullable()->constrained('options')->onDelete('set null');
            $table->string('title')->nullable();
            $table->boolean('is_public')->default(false);
            $table->dateTime('published_at')->nullable();
            $table->longText('description')->nullable();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('cascade');
            $table->json('audience')->nullable();
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
        Schema::dropIfExists('announcements');
    }
};
