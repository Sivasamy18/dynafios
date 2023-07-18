<?php

namespace App\Http\Controllers;

use App\Physician;

class LogReminderController extends ResourceController
{
    public function getLogReminderForPhysicians()
    {
        Physician::getActivePhysicians();
    }

    public function getLogReminderForPhysiciansDirectership()
    {
        Physician::getActivePhysicians('directorship');
    }
}

?>