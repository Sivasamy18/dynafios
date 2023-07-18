<?php

namespace App;

use App\Models\Files\File;
use Artisan;
use Illuminate\Database\Eloquent\Model;
use Lang;
use Redirect;
use Request;

class AttestationReport extends Model
{
    protected $table = 'attestation_report';

    public function file()
    {
        return $this->morphOne(File::class, 'fileable');
    }
}
