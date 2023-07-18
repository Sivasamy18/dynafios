<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToPhysicianDeviceTokenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('physician_device_tokens', function (Blueprint $table) {
            $table->index('physician_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('physician_device_tokens', function (Blueprint $table) {
            $table->dropIndex('physician_device_tokens_physician_id_index');
        });
    }
}
