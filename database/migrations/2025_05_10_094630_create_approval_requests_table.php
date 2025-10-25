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
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();
            $table->string('number_format', 50)->nullable();
            $table->integer('number')->nullable();
            $table->string('code_number', 50)->nullable();
            $table->string('title')->nullable();
            $table->nullableMorphs('model');
            $table->foreignId('type_id')->nullable()->constrained('approval_types')->onDelete('cascade');
            $table->foreignId('priority_id')->nullable()->constrained('options')->onDelete('cascade');
            $table->foreignId('group_id')->nullable()->constrained('options')->onDelete('cascade');
            $table->foreignId('nature_id')->nullable()->constrained('options')->onDelete('cascade');
            $table->foreignId('vendor_id')->nullable()->constrained('ledgers')->onDelete('set null');
            $table->foreignId('request_user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 25, 5)->default(0);
            $table->date('date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status')->nullable();
            $table->json('payment')->nullable();
            $table->json('contact')->nullable();
            $table->json('items')->nullable();
            $table->string('purpose')->nullable();
            $table->text('description')->nullable();
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
        Schema::dropIfExists('approval_requests');
    }
};
