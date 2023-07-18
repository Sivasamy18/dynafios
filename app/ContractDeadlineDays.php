<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ContractDeadlineDays extends Model
{

    protected $table = 'contract_deadline_days';

    public static function get_contract_deadline_days($contract_id)
    {
        return DB::table('contract_deadline_days')
            ->select('contract_deadline_days')
            ->where('contract_id', '=', $contract_id)
            ->where('is_active', '=', '1')
            ->first();
    }

}
