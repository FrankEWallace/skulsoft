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
        Schema::table('divisions', function (Blueprint $table) {
            // add program_id column
            $table->after('code', function (Blueprint $table) {
                $table->foreignId('program_id')->nullable()->constrained('programs')->onDelete('set null');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            // remove program_id column
            $table->dropForeign(['program_id']);
            $table->dropColumn('program_id');
        });
    }
};
