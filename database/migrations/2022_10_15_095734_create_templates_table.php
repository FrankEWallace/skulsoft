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
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index()->unique();

            $table->string('type', 30)->default('mail');
            $table->string('name')->nullable();
            $table->string('code', 50)->nullable();
            $table->string('subject')->nullable();
            $table->text('content')->nullable();
            $table->dateTime('enabled_at')->nullable()->useCurrent();
            $table->string('from')->nullable();
            $table->string('from_name')->nullable();
            $table->string('reply_to')->nullable();
            $table->string('reply_to_name')->nullable();
            $table->string('cc')->nullable();
            $table->string('bcc')->nullable();

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
        Schema::dropIfExists('templates');
    }
};
