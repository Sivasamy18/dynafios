<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContractPsaWrvuRates extends Model
{

    protected $table = 'contract_psa_wrvu_rates';

    public function contract()
    {
        return $this->belongsTo('App\Contract');
    }

    public static function getRates($contract_id)
    {
        $rates = self::where('contract_id', '=', $contract_id)
            ->where('is_active', '=', true)
            ->orderBy('rate_index', 'asc')
            ->pluck('rate', 'rate_index');
        return $rates;
    }

    public static function getRanges($contract_id)
    {
        $ranges = self::where('contract_id', '=', $contract_id)
            ->where('is_active', '=', true)
            ->orderBy('rate_index', 'asc')
            ->pluck('upper_bound', 'rate_index');
        return $ranges;
    }

}
