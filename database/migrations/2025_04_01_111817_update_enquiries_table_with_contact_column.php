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
        Schema::table('enquiries', function (Blueprint $table) {
            $table->after('name', function (Blueprint $table) {
                $table->foreignId('contact_id')->nullable()->constrained('contacts')->onDelete('set null');
                $table->foreignId('course_id')->nullable()->constrained('courses')->onDelete('set null');
                $table->foreignId('stage_id')->nullable()->constrained('options')->onDelete('set null');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropColumn('contact_id');
            $table->dropForeign(['course_id']);
            $table->dropColumn('course_id');
            $table->dropForeign(['stage_id']);
            $table->dropColumn('stage_id');
        });
    }
};
