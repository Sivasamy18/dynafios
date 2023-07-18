<?php

namespace Database\Seeders;

use Eloquent;
use Illuminate\Database\Seeder;

class DeploymentSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Eloquent::unguard();
        $this->call(PhysicianContractRelationshipChangedSeeder::class);
        $this->call(AmountPaidRelationshipChangedSeeder::class);
        //Roles and Permission seeder waiting for confirmation on basic permission to roles 
        // $this->call(RolesAndPermissionsSeeder::class);
    }
}
