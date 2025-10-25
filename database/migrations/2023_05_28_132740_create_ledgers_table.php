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
        Schema::create('ledgers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->string('name')->nullable();
            $table->string('alias')->nullable();
            $table->string('code_prefix')->nullable();
            $table->string('code_digit')->nullable();
            $table->string('code_suffix')->nullable();
            $table->text('description')->nullable();

            $table->foreignId('ledger_type_id')->nullable()->constrained('ledger_types')->onDelete('cascade');

            $table->decimal('opening_balance', 25, 5)->default(0);
            $table->decimal('current_balance', 25, 5)->default(0);
            $table->string('contact_number', 20)->nullable();
            $table->string('email', 50)->nullable();
            $table->json('account')->nullable();
            $table->json('address')->nullable();
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
        Schema::dropIfExists('ledgers');
    }
};
