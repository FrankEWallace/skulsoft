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
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->nullableMorphs('model');
            $table->foreignId('leave_type_id')->nullable()->constrained('leave_types')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('options')->onDelete('set null');
            $table->foreignId('request_user_id')->nullable()->constrained('users')->onDelete('cascade');

            $table->boolean('is_half_day')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('reason')->nullable();
            $table->string('status', 20)->nullable();

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
        Schema::dropIfExists('leave_requests');
    }
};
