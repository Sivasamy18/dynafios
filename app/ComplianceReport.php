<?php

namespace App;

use App\Models\Files\File;
use Artisan;
use Illuminate\Database\Eloquent\Model;
use Lang;
use Redirect;
use Request;

class ComplianceReport extends Model
{
    protected $table = 'compliance_report';

    const PHYSICIAN_REPORT = 1;
    const PRACTICE_REPORT = 2;
    const CONTRACT_TYPE_REPORT = 3;

    public function file()
    {
        return $this->morphOne(File::class, 'fileable');
    }
}
