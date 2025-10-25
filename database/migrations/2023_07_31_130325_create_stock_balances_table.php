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
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();
            $table->nullableMorphs('place');
            $table->foreignId('stock_item_id')->nullable()->constrained('stock_items')->onDelete('cascade');
            $table->float('opening_quantity')->default(0);
            $table->float('current_quantity')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
    }
};
