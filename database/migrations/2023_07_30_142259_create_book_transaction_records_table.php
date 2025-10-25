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
        Schema::create('book_transaction_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();
            $table->foreignId('book_transaction_id')->nullable()->constrained('book_transactions')->onDelete('cascade');
            $table->foreignId('book_copy_id')->nullable()->constrained('book_copies')->onDelete('cascade');
            $table->date('return_date')->nullable();
            $table->foreignId('condition_id')->nullable()->constrained('options')->onDelete('set null');
            $table->string('return_status', 50)->nullable();
            $table->json('charges')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_transaction_records');
    }
};
