<?php

namespace App;

use App\Models\Files\File;
use Illuminate\Database\Eloquent\Model;

class AdminReport extends Model
{
    protected $table = 'admin_reports';

    public function file()
    {
        return $this->morphOne(File::class, 'fileable');
    }
}
