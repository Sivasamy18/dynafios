<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAwsCognitoPublicKeysTable extends Migration
{
    protected $primaryKey = 'kid';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aws_cognito_public_keys', function (Blueprint $table) {
            $table->string('kid', 64)->unique();
            $table->text('public_key');
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
        Schema::dropIfExists('aws_cognito_public_keys');
    }
}
