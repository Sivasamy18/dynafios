<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFacilityTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('facility_types', function (Blueprint $table) {
            $table->id();
			$table->string('type', 100);
			$table->string('extension', 20);
			$table->boolean('is_active')->default(1);
			$table->integer('created_by');
			$table->integer('updated_by');
            $table->timestamps();
        });
		
		DB::table('facility_types')->insert([
			['type' => "Short Term Acute Care Hospital", 'extension' => 'STAC', 'created_by' => 0, 'updated_by' => 0, 'created_at' => now(), 'updated_at' => now()],
			['type' => "Women's Hospital", 'extension' => 'WTAC', 'created_by' => 0, 'updated_by' => 0, 'created_at' => now(), 'updated_at' => now()],
			['type' => "Children's Hospital", 'extension' => 'CTAC', 'created_by' => 0, 'updated_by' => 0, 'created_at' => now(), 'updated_at' => now()],
			['type' => "Long Term Acute Care Hospital", 'extension' => 'LTAC', 'created_by' => 0, 'updated_by' => 0, 'created_at' => now(), 'updated_at' => now()],
			['type' => "Micro Hospital", 'extension' => 'MCRO', 'created_by' => 0, 'updated_by' => 0, 'created_at' => now(), 'updated_at' => now()],
			['type' => "Ambulatory Surgery Center", 'extension' => 'ASC', 'created_by' => 0, 'updated_by' => 0, 'created_at' => now(), 'updated_at' => now()],
			['type' => "Home Health Facility", 'extension' => 'HHF', 'created_by' => 0, 'updated_by' => 0, 'created_at' => now(), 'updated_at' => now()],
			['type' => "Urgent Care Clinic", 'extension' => 'URG', 'created_by' => 0, 'updated_by' => 0, 'created_at' => now(), 'updated_at' => now()],
			['type' => "Behavorial Health Facility", 'extension' => 'BHF', 'created_by' => 0, 'updated_by' => 0, 'created_at' => now(), 'updated_at' => now()],
			['type' => "Skilled Nursing Facility", 'extension' => 'SNF', 'created_by' => 0, 'updated_by' => 0, 'created_at' => now(), 'updated_at' => now()]
		]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('facility_types');
    }
}
