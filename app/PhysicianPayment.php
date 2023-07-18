<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PhysicianPayment extends Model
{
    public function agreement()
    {
        return $this->belongsTo("Agreement");
    }

    public function physician()
    {
        return $this->belongsTo("Physician");
    }
}
