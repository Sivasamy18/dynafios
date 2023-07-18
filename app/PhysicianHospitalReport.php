<?php

namespace App;

use App\Models\Files\File;
use Illuminate\Database\Eloquent\Model;

class PhysicianHospitalReport extends Model
{
    protected $table = 'physician_hospital_reports';

    public function file()
    {
        return $this->morphOne(File::class, 'fileable');
    }

}
