<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContractPsaMetrics extends Model
{

    protected $table = 'contract_psa_metrics';

    public function contract()
    {
        return $this->belongsTo('App\Contract');
    }

    public static function getMetrics($contract_id)
    {
        $contract_psa_metrics = self::select('*')
            ->where('contract_id', '=', $contract_id)
            ->where('end_date', '=', '2037-12-31')
            ->first();
        return $contract_psa_metrics;
    }

    public static function compareObjects($original, $new)
    {
        if ($original->annual_comp != $new->annual_comp) {
            return false;
        }
        if ($original->annual_comp_fifty != $new->annual_comp_fifty) {
            return false;
        }
        if ($original->wrvu_fifty != $new->wrvu_fifty) {
            return false;
        }
        if ($original->annual_comp_seventy_five != $new->annual_comp_seventy_five) {
            return false;
        }
        if ($original->wrvu_seventy_five != $new->wrvu_seventy_five) {
            return false;
        }
        if ($original->annual_comp_ninety != $new->annual_comp_ninety) {
            return false;
        }
        if ($original->wrvu_ninety != $new->wrvu_ninety) {
            return false;
        }
        return true;
    }

    public static function getBreakdown($contract)
    {
        $data = [];
        $metrics = ContractPsaMetrics::getMetrics($contract->id);
        $data['comp_per_period_90'] = $metrics->annual_comp_ninety / 12;
        $data['comp_per_period_75'] = $metrics->annual_comp_seventy_five / 12;
        $data['comp_per_period_50'] = $metrics->annual_comp_fifty / 12;
        $data['wrvu_per_period_90'] = $metrics->wrvu_ninety;
        $data['wrvu_per_period_75'] = $metrics->wrvu_seventy_five;
        $data['wrvu_per_period_50'] = $metrics->wrvu_fifty;
        $data['wrvu_rate_per_period_90'] = $data['comp_per_period_90'] / $metrics->wrvu_ninety;
        $data['wrvu_rate_per_period_75'] = $data['comp_per_period_75'] / $metrics->wrvu_seventy_five;
        $data['wrvu_rate_per_period_50'] = $data['comp_per_period_50'] / $metrics->wrvu_fifty;
        $data['comp_per_period'] = $metrics->annual_comp / 12;
        $data['expected_wrvu_per_period'] = $data['comp_per_period'] / (($data['wrvu_rate_per_period_90'] + $data['wrvu_rate_per_period_75'] + $data['wrvu_rate_per_period_50']) / 3);
        $data['annual_metrics'] = $metrics;
        //get logs and durations - this will drive the periods
        //need a summary ytd as well in this data set
        //evaluate if wrvu payments enabled, if yes, get amount paids per period
        //need a summary ytd as well in this data set
        return $data;
    }

}
