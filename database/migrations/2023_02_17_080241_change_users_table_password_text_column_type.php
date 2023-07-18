<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeUsersTablePasswordTextColumnType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->longText('password_text')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //We need to do the opposite of the up function, for consistency purposes, we revert it back to its old type here.
        Schema::table('users', function (Blueprint $table) {
            $table->string('password_text', 60)->change();
        });
    }
}
