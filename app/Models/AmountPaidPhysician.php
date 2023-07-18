<?php

namespace App\Models;

use App\Amount_paid;
use App\Contract;
use App\Physician;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmountPaidPhysician extends Model
{
    use HasFactory;

    protected $table = 'amount_paid_physicians';

    public function physician()
    {
        return $this->belongsTo(Physician::class);
    }

    public function amountPaid()
    {
        return $this->belongsTo(Amount_paid::class, 'amt_paid_id');
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
