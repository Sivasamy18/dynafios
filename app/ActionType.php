<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ActionType extends Model
{
    protected $table = 'action_types';

    public function actions()
    {
        return $this->hasMany('App\Action');
    }
}