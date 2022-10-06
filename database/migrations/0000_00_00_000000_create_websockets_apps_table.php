<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebSocketsAppsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('websockets_apps', function (Blueprint $table) {
            $table->string('id')->index();
            $table->string('key');
            $table->string('secret');
            $table->string('name');
            $table->string('host')->nullable();
            $table->string('path')->nullable();
            $table->boolean('enable_client_messages')->default(false);
            $table->boolean('enable_statistics')->default(true);
            $table->unsignedInteger('capacity')->nullable();
            $table->string('allowed_origins');
            $table->nullableTimestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('websockets_apps');
    }
}
