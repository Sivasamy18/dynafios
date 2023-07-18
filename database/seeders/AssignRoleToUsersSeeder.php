<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\User;
use Illuminate\Database\Seeder;

class AssignRoleToUsersSeeder extends Seeder
{
    public function run()
    {
        $system_admin = Role::where('name', 'system-administrator')->first();
        $hospital_user = Role::where('name', 'hospital-user')->first();
        $practice_manager = Role::where('name', 'practice-manager')->first();
        $physician = Role::where('name', 'physician')->first();
        $health_system_user = Role::where('name', 'health-system-user')->first();
        $health_region_user = Role::where('name', 'health-system-region-user')->first();

        // Assign roles to users in batches of 100

        User::where('group_id', 2)->chunk(100, function ($users) use ($hospital_user) {
            foreach ($users as $user) {
                $user->assignRole($hospital_user);
                $user->save();
            }
        });

        User::where('group_id', 1)->chunk(100, function ($users) use ($system_admin) {
            foreach ($users as $user) {
                $user->assignRole($system_admin);
                $user->save();
            }
        });

        User::where('group_id', 3)->chunk(100, function ($users) use ($practice_manager) {
            foreach ($users as $user) {
                $user->assignRole($practice_manager);
                $user->save();
            }
        });

        User::where('group_id', 6)->chunk(100, function ($users) use ($physician) {
            foreach ($users as $user) {
                $user->assignRole($physician);
                $user->save();
            }
        });

        User::where('group_id', 7)->chunk(100, function ($users) use ($health_system_user) {
            foreach ($users as $user) {
                $user->assignRole($health_system_user);
                $user->save();
            }
        });

        User::where('group_id', 8)->chunk(100, function ($users) use ($health_region_user) {
            foreach ($users as $user) {
                $user->assignRole($health_region_user);
                $user->save();
            }
        });
    }
}