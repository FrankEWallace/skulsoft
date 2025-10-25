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
        Schema::create('stock_item_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();
            $table->nullableMorphs('model');
            $table->foreignId('stock_item_id')->nullable()->constrained('stock_items')->onDelete('cascade');
            $table->float('quantity')->default(0);
            $table->decimal('unit_price')->default(0);
            $table->decimal('amount', 25, 5)->default(0);
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_item_records');
    }
};
