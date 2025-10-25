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
        Schema::create('book_copies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();
            $table->foreignId('book_addition_id')->nullable()->constrained('book_additions')->onDelete('cascade');
            $table->foreignId('book_id')->nullable()->constrained('books')->onDelete('cascade');
            $table->foreignId('condition_id')->nullable()->constrained('options')->onDelete('set null');
            $table->string('number_format', 50)->nullable();
            $table->integer('number')->nullable();
            $table->string('code_number', 50)->nullable();
            $table->string('vendor', 100)->nullable();
            $table->string('invoice_number', 100)->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('room_number', 100)->nullable();
            $table->string('rack_number', 100)->nullable();
            $table->string('shelf_number', 100)->nullable();
            $table->string('hold_status', 50)->nullable();
            $table->decimal('price', 25, 5)->default(0);
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
        Schema::dropIfExists('book_copies');
    }
};
