<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    const SUPER_USER = 1;
    const HOSPITAL_ADMIN = 2;
    const PRACTICE_MANAGER = 3;
    const HOSPITAL_CFO = 4;
    const SUPER_HOSPITAL_USER = 5;
    const Physicians = 6;
    const HEALTH_SYSTEM_USER = 7;
    const HEALTH_SYSTEM_REGION_USER = 8;
    //new duplicate for nomenclature
    const PHYSICIANS = 6;

    protected $table = 'groups';

    public function users()
    {
        return $this->hasMany('App\User');
    }
}
