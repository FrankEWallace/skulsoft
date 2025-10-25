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
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();
            $table->string('title')->nullable();
            $table->foreignId('period_id')->constrained('periods')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('options')->onDelete('set null');
            $table->nullableMorphs('model');
            $table->date('date')->nullable();
            $table->string('nature', 50)->nullable();
            $table->string('severity', 50)->nullable();
            $table->text('description')->nullable();
            $table->string('reported_by')->nullable();
            $table->text('action')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
