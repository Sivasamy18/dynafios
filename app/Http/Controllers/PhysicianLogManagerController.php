<?php

namespace App\Http\Controllers;

use Dynafios\Managers\PhysicianLogManager;

class PhysicianLogManagerController extends ResourceController
{
    public function postSaveLog()
    {
        $save_log_factory = new PhysicianLogManager();
        return $save_log_factory->postSaveLog();
    }
}
