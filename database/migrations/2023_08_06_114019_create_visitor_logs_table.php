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
        Schema::create('visitor_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();
            $table->string('number_format', 50)->nullable();
            $table->integer('number')->nullable();
            $table->string('code_number', 50)->nullable();
            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('cascade');
            $table->string('name', 100)->nullable();
            $table->json('company')->nullable();
            $table->string('contact_number', 20)->nullable();
            $table->string('type', 20)->nullable();
            $table->string('relation', 20)->nullable();
            $table->integer('count')->default(1);
            $table->foreignId('purpose_id')->nullable()->constrained('options')->onDelete('set null');
            $table->nullableMorphs('visitor');
            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('cascade');
            $table->dateTime('entry_at')->nullable();
            $table->dateTime('exit_at')->nullable();
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
        Schema::dropIfExists('visitor_logs');
    }
};
