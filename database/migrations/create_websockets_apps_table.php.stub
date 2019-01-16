<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWebsocketsAppsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('websockets.database.tables.apps'), function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('host')->nullable();
            $table->string('key');
            $table->string('secret');
            $table->unique(['key', 'secret']);
            $table->boolean('enable_client_messages')->default(false);
            $table->boolean('enable_statistics')->default(true);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('websockets.database.tables.apps'));
    }
}
