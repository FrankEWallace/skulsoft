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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();
            $table->string('number_format')->nullable();
            $table->integer('number')->nullable();
            $table->string('code_number')->nullable();
            $table->date('date')->nullable();
            $table->string('type', 20)->nullable();
            $table->string('head', 50)->nullable();
            $table->decimal('amount', 25, 5)->default(0);
            $table->string('currency', 20)->nullable();

            $table->foreignId('period_id')->nullable()->constrained('periods')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('options')->onDelete('set null');

            $table->nullableMorphs('transactionable');

            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            $table->decimal('tax', 25, 5)->default(0);
            $table->boolean('is_online')->default(0);
            $table->dateTime('processed_at')->nullable();
            $table->decimal('handling_fee', 25, 5)->default(0);
            $table->text('description')->nullable();
            $table->text('remarks')->nullable();
            $table->date('reconciliation_date')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_remarks')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->text('rejection_remarks')->nullable();
            $table->json('rejection_record')->nullable();
            $table->json('payment_gateway')->nullable();
            $table->json('failed_logs')->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
