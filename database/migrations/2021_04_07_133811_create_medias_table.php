<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('medias', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();
            $table->uuid('token')->nullable();

            $table->nullableMorphs('model');

            $table->string('collection')->nullable();
            $table->string('name')->nullable();
            $table->string('file_name')->nullable();
            $table->boolean('status')->default(0);

            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('medias');
    }
};
