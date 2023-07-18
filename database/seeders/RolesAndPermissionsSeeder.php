<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Check if the seeder has already been run
        if (Role::count() <> 1) {
            $this->command->info('RolesAndPermissionsSeeder already executed, skipping...');
            return;
        }
        // Create Roles
        $systemAdmin = Role::create(['name' => 'system-administrator']);
        $hospitalUser = Role::create(['name' => 'hospital-user']);
        $practiceManager = Role::create(['name' => 'practice-manager']);
        $physician = Role::create(['name' => 'physician']);
        $healthSystemUser = Role::create(['name' => 'health-system-user']);
        $healRegionUser = Role::create(['name' => 'health-system-region-user']);

        if (Permission::count() <> 1) {
            $this->command->info('RolesAndPermissionsSeeder already executed, skipping...');
            return;
        }

        // Create Permissions
        Permission::create(['name' => 'physician_log.create']);
        Permission::create(['name' => 'physician_log.read']);
        Permission::create(['name' => 'physician_log.update']);
        Permission::create(['name' => 'physician_log.delete']);
        Permission::create(['name' => 'amount_paid.create']);
        Permission::create(['name' => 'amount_paid.read']);
        Permission::create(['name' => 'amount_paid.update']);
        Permission::create(['name' => 'amount_paid.delete']);
        Permission::create(['name' => 'hospital.create']);
        Permission::create(['name' => 'hospital.read']);
        Permission::create(['name' => 'hospital.update']);
        Permission::create(['name' => 'hospital.delete']);
        Permission::create(['name' => 'practice.create']);
        Permission::create(['name' => 'practice.read']);
        Permission::create(['name' => 'practice.update']);
        Permission::create(['name' => 'practice.delete']);
        Permission::create(['name' => 'agreement.create']);
        Permission::create(['name' => 'agreement.read']);
        Permission::create(['name' => 'agreement.update']);
        Permission::create(['name' => 'agreement.delete']);
        Permission::create(['name' => 'contract.create']);
        Permission::create(['name' => 'contract.read']);
        Permission::create(['name' => 'contract.update']);
        Permission::create(['name' => 'contract.delete']);
        Permission::create(['name' => 'user.create']);
        Permission::create(['name' => 'user.read']);
        Permission::create(['name' => 'user.update']);
        Permission::create(['name' => 'user.delete']);
        Permission::create(['name' => 'physician.create']);
        Permission::create(['name' => 'physician.read']);
        Permission::create(['name' => 'physician.update']);
        Permission::create(['name' => 'physician.delete']);
        Permission::create(['name' => 'role.create']);
        Permission::create(['name' => 'role.read']);
        Permission::create(['name' => 'role.update']);
        Permission::create(['name' => 'role.delete']);
        Permission::create(['name' => 'permission.create']);
        Permission::create(['name' => 'permission.read']);
        Permission::create(['name' => 'permission.update']);
        Permission::create(['name' => 'permission.delete']);







        //map basic permission for roles 
        $systemAdmin->syncPermission(['physician_log.create', 
                                    'physician_log.read',
                                    'physician_log.update',
                                    'physician_log.delete',
                                    'amount_paid.create',
                                    'amount_paid.read',
                                    'amount_paid.update',
                                    'amount_paid.delete',
                                    'hospital.create',
                                    'hospital.read',
                                    'hospital.update',
                                    'hospital.delete',
                                    'practice.create',
                                    'practice.read',
                                    'practice.update',
                                    'practice.delete',
                                    'agreement.create',
                                    'agreement.read',
                                    'agreement.update',
                                    'agreement.delete',
                                    'contract.create',
                                    'contract.read',
                                    'contract.update',
                                    'contract.delete',
                                    'physician.create',
                                    'physician.read',
                                    'physician.update',
                                    'physician.delete',
                                    'user.create',
                                    'user.read',
                                    'user.update',
                                    'user.delete',
                                    'role.create',
                                    'role.read',
                                    'role.update',
                                    'role.delete',
                                    'permission.create',
                                    'permission.read',
                                    'permission.update',
                                    'permission.delete'
                                ]);
        $hospitalUser->syncPermission([]);
        $practiceManager->syncPermission([]);
        $physician->syncPermission([]);
        $healthSystemUser->syncPermission([]);
        $healRegionUser->syncPermission([]);

        $this->command->info('RolesAndPermissionsSeeder executed successfully!');
    }
}
