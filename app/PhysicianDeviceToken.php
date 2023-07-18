<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PhysicianDeviceToken extends Model
{
    const iOS = 0;
    const Android = 1;

    protected $table = 'physician_device_tokens';
}
