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
        Schema::create('leave_allocation_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->foreignId('leave_allocation_id')->nullable()->constrained('leave_allocations')->onDelete('cascade');
            $table->foreignId('leave_type_id')->nullable()->constrained('leave_types')->onDelete('cascade');

            $table->float('allotted')->default(0);
            $table->float('used')->default(0);

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_allocation_records');
    }
};
