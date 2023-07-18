<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class PhysicianLogHistory extends Model
{

    use SoftDeletes;

    protected $table = 'physician_log_history';
    protected $softDelete = true;
    protected $dates = ['deleted_at'];
    const ENTERED_BY_PHYSICIAN = 1;
    const ENTERED_BY_USER = 0;

    //physician type used in physician logs
    const SHIFT_AM = 1;
    const SHIFT_PM = 2;


}
