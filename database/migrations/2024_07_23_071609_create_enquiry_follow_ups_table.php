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
        Schema::create('enquiry_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();
            $table->foreignId('enquiry_id')->nullable()->constrained('enquiries')->onDelete('cascade');
            $table->foreignId('stage_id')->nullable()->constrained('options')->onDelete('set null');
            $table->date('follow_up_date')->nullable();
            $table->date('next_follow_up_date')->nullable();
            $table->string('status', 50)->nullable();
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
        Schema::dropIfExists('enquiry_follow_ups');
    }
};
