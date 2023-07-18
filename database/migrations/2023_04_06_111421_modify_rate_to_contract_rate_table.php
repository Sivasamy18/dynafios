<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyRateToContractRateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contract_rate', function (Blueprint $table) {
            DB::statement('ALTER TABLE contract_rate MODIFY rate decimal(10,2)');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contract_rate', function (Blueprint $table) {
            DB::statement('ALTER TABLE contract_rate MODIFY rate float(10,2)');
        });
    }
}
