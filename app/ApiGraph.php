<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Response;
use Log;
use Lang;
use DateTime;

class ApiGraph extends Model
{
    const STATUS_FAILURE = 0;
    const STATUS_SUCCESS = 1;

    public static function getTotalEarningData($startDate, $endDate, $accrualInterval, $physician_id) 
	{
        $contract_ids = Contract::select('contracts.id')
            ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
            ->where("physician_contracts.physician_id", "=", $physician_id)->distinct()->get();

        $results = [];
        if(count($contract_ids) > 0){
            $start_date = date('Y-m-d', strtotime($startDate));         // '2022-01-01';  
            $end_date = date('Y-m-d', strtotime($endDate));             //'2022-12-31';

            if($accrualInterval == 'annual'){
                $sum_amount_paid = self::getSumAmountPaid($contract_ids->toArray(), $physician_id, $start_date, $end_date);
                $total_sum_paid = $sum_amount_paid->sum_amount_paid != null ? $sum_amount_paid->sum_amount_paid : "0.00";

                $results[$start_date . ' to ' . $end_date] = $total_sum_paid;
            }else{
                while($start_date < $end_date){
                    $temp_end_date = date('Y-m-d', strtotime('+1 months', strtotime($start_date)));
                    $temp_end_date = date('Y-m-d', strtotime('-1 days', strtotime($temp_end_date)));
                    $sum_amount_paid = self::getSumAmountPaid($contract_ids->toArray(), $physician_id, $start_date, $temp_end_date);
                    $total_sum_paid = $sum_amount_paid->sum_amount_paid != null ? $sum_amount_paid->sum_amount_paid : "0.00";
                    
                    $results[$start_date . ' to ' . $temp_end_date] = $total_sum_paid;
    
                    $start_date = date('Y-m-d', strtotime('+1 months', strtotime($start_date)));
                }
            }
        }

        return $results;
    }

    public static function getCompensationSummary($response, $startDate, $endDate, $accrualInterval, $physician) 
	{
        $physician_id = $physician->id;
        $results = [];
        $bodys = [];
        $productivMd_result = [];

        foreach ($response as $key => $header) {
            if($key == 'body'){
                foreach ($header as $key => $body) {
                    if($key == 'compensationSummaryTable'){
                        foreach ($body as $key => $value) {
                            array_push($bodys, $value);
                        }
                    }
                }
                break;
            }
        }
        
        foreach($bodys as $body){
            $period = "";
            $amount = 0.00;
            foreach($body as $key => $body1){
                if($key == 'Accrual Period'){
                    $period = $body1->shortFormat;
                }else if($key == 'Total Compensation'){
                    $amount = str_replace(',','',str_replace('$', '', $body1->shortFormat));
                }
            }

            if($period != ""){
                $productivMd_result [] = [
                    'period' => $period,
                    'compensation' => str_replace('--', '0.00', $amount)
                ];
            }
        }
        
        $trace_results = self::getTotalEarningData($startDate, $endDate, $accrualInterval, $physician_id);
        
        $contracts = Contract::select('contracts.id', 'contracts.payment_type_id', 'contracts.rate as fmv', 'contracts.expected_hours', 'agreements.start_date as contract_start_date', 'agreements.end_date as contract_end_date', 'contracts.manual_contract_valid_upto')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
            ->where("physician_contracts.physician_id", "=", $physician_id)
            ->whereNull('agreements.deleted_at')
            ->whereNull('contracts.deleted_at')
            ->distinct()->get();

        $total_compensation = 0.00;
        $compensation_arr = [];
        $total_owed = 0.00;
        $production_total_owed = 0.00;
        $total_ytd = 0.00;
        $expected_amount = 0.00;
        $compensation = 0.00;
        foreach($productivMd_result as $res){
            if($res['period'] == "Totals"){
                $total_ytd = $res['compensation'];
            }else if($res['period'] != "Totals"){
                $trace_amount = $trace_results[$res['period']];
                $compensation = $res['compensation'];
                $production_total_owed += $compensation;
                $total_compensation = $compensation + $trace_amount;

                $expected_amount = 0.00;
                if($accrualInterval == 'annual'){
                    $months = date('m', strtotime($endDate));
                    foreach($contracts as $contract){
                        if($contract->contract_start_date <= date('Y-m-d', strtotime($endDate))){
                            $expected_amount += $contract->fmv * $contract->expected_hours * $months;
                        }
                    }
                } else if($accrualInterval == 'monthly'){
                    $periods = str_replace(' to ', ',', $res['period']);
                    $date_arr = explode(',', $periods);

                    foreach($contracts as $contract){
                        if($contract->contract_start_date <= $date_arr[0] && $contract->manual_contract_valid_upto >= $date_arr[1]){
                            $amount_paid = self::getSumAmountPaid([$contract->id], $physician_id, date('Y-m-d', strtotime($date_arr[0])), date('Y-m-d', strtotime($date_arr[1])));
                            if($contract->payment_type_id == 3 || $contract->payment_type_id == 5 || $contract->payment_type_id == 8){
                                if($amount_paid->sum_amount_paid){
                                    $expected_amount += $amount_paid->sum_amount_paid != null ? $amount_paid->sum_amount_paid : "0.00";
                                }
                            }else if($contract->payment_type_id == 6){
                                $rate = self::getContractRate($contract->id, $date_arr[0], $date_arr[1], 9);
                                $expected_amount += $rate;
                            }else{
                                $rate = self::getContractRate($contract->id, $date_arr[0], $date_arr[1], 1);
                                $expected_amount += $rate * $contract->expected_hours;
                            }
                        }
                    }
                }

                $total_compensation = ($expected_amount + $compensation) > 0 ? (($total_compensation / ($expected_amount + $compensation)) * 100) : 0.00;
                $total_owed += $trace_amount;
                $compensation_arr [] = [
                    'period' => $res['period'],
                    'compensation' => number_format(($total_compensation > 100.00 ? 100.00 : $total_compensation), 2)
                ];
            }
        }

        $data["compensation"] = $compensation_arr;
        $data["agreements"] = [];
        $data["physician_name"] = $physician->first_name . " " . $physician->last_name;

        $data["production"] = $productivMd_result;
        $data["total_owed"] = number_format(($total_owed + $production_total_owed), 2);
        $data["production_total_owed"] = number_format($production_total_owed, 2);
        $data["total_ytd"] = number_format($total_ytd, 2);
        return $data;
    }

    public static function getTtmProductivity($response, $physician) 
	{
        $results = [];
        $percentile25th = 0.00;
        $percentile50th = 0.00;
        $percentile75th = 0.00;

        foreach ($response as $key => $header) {
            if($key == 'body'){
                foreach ($header as $key => $body) {
                    if($key == 'productivityChart'){
                        foreach ($body as $key => $value) {
                            if($key == 'percentile25th'){
                                if($value){
                                    foreach($value as $val){
                                        if($val){
                                            $percentile25th = $val * 12;
                                            break;
                                        }
                                    }
                                }
                            } else if($key == 'percentile50th'){
                                if($value){
                                    foreach($value as $val){
                                        if($val){
                                            $percentile50th = $val * 12;
                                            break;
                                        }
                                    }
                                }
                            } else if($key == 'percentile75th'){
                                if($value){
                                    foreach($value as $val){
                                        if($val){
                                            $percentile75th = $val * 12;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                break;
            }
        }

        $max = $percentile25th;
        if($percentile50th > $percentile25th){
            $max = $percentile50th;
        }

        if($percentile75th > $max){
            $max = $percentile75th;
        }

        $data["max"] = $max + 200;
        $data["min"] = 0;
        $data["current"] = 200;
        $data["percentile25th"] = $percentile25th;
        $data["percentile50th"] = $percentile50th;
        $data["percentile75th"] = $percentile75th;

        $data["physician_name"] = $physician->first_name . " " . $physician->last_name;

        return $data;
    }

    public static function getProductivityCompensation($response, $year, $physician) 
	{
        $results = [];
        $temp_results = [];
        $bodys = [];
        $net_volume = [];

        foreach ($response as $key => $header) {
            if($key == 'body'){
                foreach ($header as $key => $body) {
                    if($key == 'productivityTable'){
                        foreach ($body as $value) {
                            array_push($bodys, $value);
                        }
                    }
                }
                break;
            }
        }

        $month = 1;
        foreach($bodys[3] as $key => $body){
            $date = $month .'/' . $year;
            if($key == $date) {
                $net_volume[$key] = str_replace("--", "0", $body->shortFormat);
                $month ++;
            }
        }

        $month = 1;
        foreach($bodys[0] as $key => $body){
            $date = $month .'/' . $year;
            if($key == $date) {
                $volume = $net_volume[$key];
                $temp_results [$month] = [
                    'volume' => $volume,
                    'cytd' => str_replace("--", "0", $body->shortFormat)
                ];
                $month ++;
            }
        }

        $results [$year] = [ $temp_results ];
        return $results;
    }

    public static function getCompensationSummaryGuage($startDate, $endDate, $physician){
        $physician_id = $physician->id;
        $compensation_arr = [];
        $expected_amount = 0.00;
        $contract_list = [];

        $trace_sum = self::getTotalEarningDataForGuage($startDate, $endDate, $physician_id);
        $contracts = Contract::select('contracts.*', 'agreements.start_date as contract_start_date', 'agreements.end_date as contract_end_date', 'contract_names.name')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
            ->where("physician_contracts.physician_id", "=", $physician_id)
            ->whereNull('agreements.deleted_at')
            ->distinct()->get();

        $months = date('m', strtotime($endDate));
        foreach($contracts as $contract){
            if($contract->contract_start_date <= date('Y-m-d', strtotime($endDate))){
                $temp_start_date = date('Y-m-d', strtotime($startDate));
                $temp_end_date = date('Y-m-d', strtotime($endDate));
                if($contract->contract_start_date >= date('Y-m-d', strtotime($startDate))){
                    $temp_start_date = $contract->contract_start_date;
                    if($contract->manual_contract_valid_upto <= date('Y-m-d', strtotime($endDate))){
                        $temp_end_date = $contract->manual_contract_valid_upto;
                    }
                    $months = self::diffMonth($temp_start_date, $temp_end_date);
                }
                $amount_paid = self::getSumAmountPaid([$contract->id], $physician_id, date('Y-m-d', strtotime($startDate)), date('Y-m-d', strtotime($endDate)));
                if($contract->payment_type_id == 3 || $contract->payment_type_id == 5 || $contract->payment_type_id == 8){
                    if($amount_paid->sum_amount_paid){
                        $expected_amount += $amount_paid->sum_amount_paid != null ? $amount_paid->sum_amount_paid  : "0.00";
                    }
                }else if($contract->payment_type_id == 6){
                    $rate = self::getContractRate($contract->id, $temp_start_date, $temp_end_date, 9);
                    $expected_amount += $rate * $months;
                }else{
                    $rate = self::getContractRate($contract->id, $temp_start_date, $temp_end_date, 1);
                    $expected_amount += ($rate * $contract->expected_hours) * $months;
                }
            }

            $contract_list[] = [
                'id' => $contract->id,
                'name' => $contract->name,
            ];
        }

        $total_compensation = $expected_amount > 0 ? (($trace_sum / $expected_amount) * 100) : 0.00;

        $compensation_arr [] = [
            'period' => $startDate . ' to ' . $endDate ,
            'compensation' => number_format(($total_compensation > 100.00 ? 100.00 : $total_compensation), 2)
        ];

        $data["compensation"] = $compensation_arr;
        $data["agreements"] = $contract_list;
        $data["physician_name"] = $physician->first_name . " " . $physician->last_name;

        return $data;
    }

    public static function getTotalEarningDataForGuage($startDate, $endDate, $physician_id){
        $contract_ids = Contract::select('contracts.id')
            ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
            ->where("physician_contracts.physician_id", "=", $physician_id)->distinct()->get();

        $results = 0.00;
        if(count($contract_ids) > 0){
            $start_date = date('Y-m-d', strtotime($startDate));         // '2022-01-01';
            $end_date = date('Y-m-d', strtotime($endDate));             //'2022-12-31';
            $sum_amount_paid = self::getSumAmountPaid($contract_ids->toArray(), $physician_id, $start_date, $end_date);

            $total_sum_paid = $sum_amount_paid->sum_amount_paid != null ? $sum_amount_paid->sum_amount_paid : "0.00";
            $results = $total_sum_paid;
        }

        return $results;
    }

    private function getSumAmountPaid($contract_ids, $physician_id, $start_date, $end_date){
        $sum_amount_paid = Amount_paid::select(DB::raw("sum(amount_paid.amountPaid) as sum_amount_paid"))
            ->join('amount_paid_physicians', 'amount_paid_physicians.amt_paid_id', '=', 'amount_paid.id')
            ->whereIn('amount_paid.contract_id', $contract_ids)
            ->where('amount_paid_physicians.physician_id', '=', $physician_id)
            ->where('amount_paid.start_date', '>=', $start_date)
            ->where('amount_paid.end_date', '<=', $end_date)
            ->first();

        return $sum_amount_paid;
    }

    private function diffMonth($from_date, $to_date) {
        $from_year = date("Y", strtotime($from_date));
        $from_month = date("m", strtotime($from_date));
        $to_year = date("Y", strtotime($to_date));
        $to_month = date("m", strtotime($to_date));
        if ($from_year == $to_year) {
            return ($to_month - $from_month) + 1;
        } else {
            return (12 - $from_month) + 1 + $to_month;
        }
    }

    private function getContractRate($contract_id, $start_date, $end_date, $rate_type) {
        $contract_rate = ContractRate::select('rate')
            ->where('contract_id', '=', $contract_id)
            ->where('effective_start_date', '<=', $start_date)
            ->where('effective_end_date', '>=', $end_date)
            ->where('rate_type', '=', $rate_type)
            ->where('status', '=', '1')
            ->first();

        return $contract_rate != null ? $contract_rate->rate : 0;
    }
}
