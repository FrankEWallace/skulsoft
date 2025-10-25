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
        Schema::table('periods', function (Blueprint $table) {
            // remove program_id column
            $table->dropForeign(['program_id']);
            $table->dropColumn('program_id');

            // add session_id column
            $table->after('alias', function (Blueprint $table) {
                $table->foreignId('session_id')->nullable()->constrained('sessions')->onDelete('set null');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('periods', function (Blueprint $table) {
            // remove session_id column
            $table->dropForeign(['session_id']);
            $table->dropColumn('session_id');

            // add program_id column
            $table->foreignId('program_id')->nullable()->constrained('programs')->onDelete('set null');
        });
    }
};
