<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveNPIUniqueHospitalsTable extends Migration
{

    public function up()
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->dropUnique('npi');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hospitals ', function (Blueprint $table) {
            $table->unique('npi');
        });
    }

}
