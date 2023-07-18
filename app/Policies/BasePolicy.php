<?php

namespace App\Policies;

use App\Group;

class BasePolicy
{
    protected $superuser = GROUP::SUPER_USER;
    protected $superhospitaluser = GROUP::SUPER_HOSPITAL_USER;
    protected $hospitaluser = GROUP::HOSPITAL_ADMIN;
    protected $practicemanager = GROUP::PRACTICE_MANAGER;
    protected $physicians = GROUP::PHYSICIANS;
    protected $healthsystemuser = GROUP::HEALTH_SYSTEM_USER;
    protected $healthsystemregionuser = GROUP::HEALTH_SYSTEM_REGION_USER;
    protected $hospitalcfo = GROUP::HOSPITAL_CFO;
}
