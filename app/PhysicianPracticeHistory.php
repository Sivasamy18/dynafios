<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PhysicianPracticeHistory extends Model
{
    protected $table = 'physician_practice_history';

    public function physician()
    {
        return $this->belongsTo('App\Physician');
    }

    public function contract()
    {
        return $this->belongsTo('App\Contract');
    }

    public function action()
    {
        return $this->belongsTo('App\Action');
    }
}
