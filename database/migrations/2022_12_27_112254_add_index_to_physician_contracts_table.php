<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToPhysicianContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('physician_contracts', function (Blueprint $table) {
            $table->index('physician_id');
            $table->index('contract_id');
            $table->index('practice_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('physician_contracts', function (Blueprint $table) {
            $table->dropIndex('physician_contracts_physician_id_index');
            $table->dropIndex('physician_contracts_contract_id_index');
            $table->dropIndex('physician_contracts_practice_id_index');
        });
    }
}
