<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PracticeType extends Model
{
    protected $table = 'practice_types';

    public function practices()
    {
        return $this->hasMany('App\Practice');
    }
}
