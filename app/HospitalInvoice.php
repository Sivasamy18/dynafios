<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HospitalInvoice extends Model
{
    protected $table = 'hospital_invoices';

    public function hospital()
    {
        return $this->belongsTo('App\Hospital');
    }
}
