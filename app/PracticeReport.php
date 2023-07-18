<?php

namespace App;

use App\Models\Files\File;
use Illuminate\Database\Eloquent\Model;

class PracticeReport extends Model
{
    protected $table = 'practice_reports';

    public function practice()
    {
        return $this->belongsTo('App\Practice');
    }

    public function file()
    {
        return $this->morphOne(File::class, 'fileable');
    }

}
