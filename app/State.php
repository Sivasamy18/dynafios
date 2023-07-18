<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $table = 'states';

    public function hospitals()
    {
        return $this->hasMany('App\Hospital');
    }

    public function practices()
    {
        return $this->hasMany('App\Practice');
    }
}
