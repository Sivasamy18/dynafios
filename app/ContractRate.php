<?php

namespace App;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Validations\ContractValidation;
use Request;
use Redirect;
use Lang;
use stdClass;
use DateTime;
use NumberFormatter;
use View;
use Log;
use Auth;

class ContractRate extends Model
{

    protected $table = 'contract_rate';
    //protected $softDelete = true;
    //protected $dates = ['deleted_at'];
    const FMV_RATE = 1;
    const ON_CALL_RATE = 2;
    const CALLED_BACK_RATE = 3;
    const CALLED_IN_RATE = 4;
    const WEEKDAY_RATE = 5;
    const WEEKEND_RATE = 6;
    const HOLIDAY_RATE = 7;
    const ON_CALL_UNCOMPENSATED_RATE = 8;
    const MONTHLY_STIPEND_RATE = 9;//Chaitraly::Monthly stipend

    public static function insertContractRate($contractid, $effective_start_date, $effective_end_date, $rate, $ratetype, $range_index = 0, $range_start_day = 0, $range_end_day = 0)
    {
        $user_id = Auth::user()->id;
        $contract_rate = new ContractRate();
        $contract_rate->contract_id = $contractid;
        $contract_rate->effective_start_date = $effective_start_date;
        $contract_rate->effective_end_date = $effective_end_date;
        $contract_rate->rate = $rate;
        $contract_rate->rate_type = $ratetype;
        $contract_rate->created_by = $user_id;
        $contract_rate->status = '1';
        $contract_rate->rate_index = $range_index;
        $contract_rate->range_start_day = $range_start_day;
        $contract_rate->range_end_day = $range_end_day;

        $contract_rate->save();

        return 1;
    }

    public static function updateContractRate($contractid, $effective_start_date, $rate, $ratetype, $rateindex = 0, $range_start_day = 0, $range_end_day = 0)
    {
        $user_id = Auth::user()->id;
        $contract = Contract::findOrFail($contractid);
        $contract_rate_data = DB::table('contract_rate')
            ->where("effective_start_date", "=", @mysql_date($effective_start_date))
            ->where("contract_id", "=", $contractid)
            ->where("rate_type", "=", $ratetype)
            // ->where("rate_index","=",$rateindex)
            ->where("status", "=", '1')
            ->get();

        if (count($contract_rate_data) >= 1) {
            DB::table('contract_rate')
                ->where("effective_start_date", ">=", @mysql_date($effective_start_date))
                ->where("effective_end_date", "<=", @mysql_date($contract->manual_contract_end_date))
                ->where("contract_id", "=", $contractid)
                ->where("rate_type", "=", $ratetype)
                // ->where("rate_index","=",$rateindex)
                ->where("status", "=", '1')
                ->update(["status" => '0', "updated_by" => $user_id]);
        } else {

            //Log::Info("in range else");
            $old_end_date = date('Y-m-d', strtotime($effective_start_date . ' -1 day'));

            //find  previous data in  range adn update with end date by one of start date
            DB::table('contract_rate')
                ->where("effective_start_date", "<=", @mysql_date($effective_start_date))
                ->where("effective_end_date", ">=", @mysql_date($effective_start_date))
                ->where("contract_id", "=", $contractid)
                ->where("rate_type", "=", $ratetype)
                ->where("status", "=", '1')
                // ->where("rate_index","=",$rateindex)
                ->update(['effective_end_date' => $old_end_date, "updated_by" => $user_id]);

            // update all the records from this start date till contract end date with status 0
            DB::table('contract_rate')
                ->where("effective_start_date", ">=", @mysql_date($effective_start_date))
                ->where("effective_end_date", "<=", @mysql_date($contract->manual_contract_end_date))
                ->where("contract_id", "=", $contractid)
                ->where("rate_type", "=", $ratetype)
                // ->where("rate_index","=",$rateindex)
                ->where("status", "=", '1')
                ->update(["status" => '0', "updated_by" => $user_id]);
        }
        $effective_end_date = @mysql_date($contract->manual_contract_end_date);
        $insertRecordContractRate = self::insertContractRate($contractid, $effective_start_date, $effective_end_date, $rate, $ratetype, $rateindex, $range_start_day, $range_end_day);

        return 1;
    }

    public static function findAnnualStipendSpend($contractid, $months, $ratetype)
    {
        $contract = Contract::findOrFail($contractid);
        $agreement = Agreement::findOrFail($contract->agreement_id);
        $start_date = $agreement->start_date;
        $month_number = 1;
        $start_date = date("Y-m-d", strtotime($start_date));
        $start_date = with(new DateTime($start_date))->setTime(0, 0, 0);
        $contract_total_spend = 0;
        $rate = 0.00;
        while ($month_number <= $months) {
            $start_date_string = $start_date->format('m/d/Y');
            $data = self::where("effective_start_date", "<=", @mysql_date($start_date_string))
                ->where("contract_id", "=", $contractid)
                ->where("rate_type", "=", $ratetype)
                ->where("status", "=", '1')
                ->orderBy("effective_start_date", 'DESC')
                ->limit(1)
                ->get();
            if (count($data) > 0) {
                foreach ($data as $data) {
                    $rate = $data->rate;
                }
            } else {
                $rate = 0.00;
            }
            $contract_total_spend += $contract->max_hours * $rate;
            $start_date = $start_date->modify('+1 month')->setTime(0, 0, 0);
            $month_number++;
        }
        return $contract_total_spend;
    }

    /*function used for annualHourlySpend, max_expected_payment for hourly contract*/
    public static function findAnnualHourlySpend($contractid, $start_date, $ratetype)
    {
        $contract = Contract::findOrFail($contractid);
        $month_number = 1;
        $start_date = date("Y-m-d", strtotime($start_date->format('m/d/Y')));
        $start_date = with(new DateTime($start_date))->setTime(0, 0, 0);
        $contract_total_spend = 0;
        $rate = 0.00;
        while ($month_number <= 12) {
            $start_date_string = $start_date->format('m/d/Y');
            $data = self::where("effective_start_date", "<=", @mysql_date($start_date_string))
                ->where("contract_id", "=", $contractid)
                ->where("rate_type", "=", $ratetype)
                ->where("status", "=", '1')
                ->orderBy("effective_start_date", 'DESC')
                ->limit(1)
                ->get();
            if (count($data) > 0) {
                foreach ($data as $data) {
                    $rate = $data->rate;
                }
            } else {
                $rate = 0.00;
            }
            $contract_total_spend += ($contract->annual_cap / 12) * $rate;
            $start_date = $start_date->modify('+1 month')->setTime(0, 0, 0);
            $month_number++;
        }
        return $contract_total_spend;
    }

    public static function findAnnualPerDiemSpend($contractid, $start_date)
    {
        $contract = Contract::findOrFail($contractid);
        $month_number = 1;
        $start_date = date("Y-m-d", strtotime($start_date->format('m/d/Y')));
        $start_date = with(new DateTime($start_date))->setTime(0, 0, 0);//start date of first month
        $end_date = with(clone $start_date)->modify('+1 month')->modify('-1 day')->setTime(23, 59, 59);//end date of first month
        $contract_total_spend = 0;
        //$rate=0.00;
        while ($month_number <= 12) {
            $start_date_string = $start_date->format('m/d/Y');
            $end_date_string = $end_date->format('m/d/Y');
            if ($contract->weekday_rate > 0 || $contract->weekend_rate > 0 || $contract->holiday_rate > 0) {//for per diem -weekday,weekend,holiday
                //find total weeks of the month
                $totalWeeks = Agreement::week_between_two_dates($start_date->format('m/d/Y'), $end_date->format('m/d/Y'));
                $d = date_parse_from_format("m/d/Y", $start_date->format('m/d/Y'));
                $de = date_parse_from_format("m/d/Y", $end_date->format('m/d/Y'));
                $holidays = Physician::getHolidaysForperiod($d["year"], $start_date->format('m/d/Y'), $end_date->format('m/d/Y'), $de["year"]);

                $data_weekday = self::where("effective_start_date", "<=", @mysql_date($start_date_string))
                    ->where("contract_id", "=", $contractid)
                    ->where("rate_type", "=", self::WEEKDAY_RATE)
                    ->where("status", "=", '1')
                    ->orderBy("effective_start_date", 'DESC')
                    ->limit(1)
                    ->get();
                if (count($data_weekday) > 0) {
                    foreach ($data_weekday as $data) {
                        $weekday_rate = $data->rate;
                    }
                } else {
                    $weekday_rate = 0.00;
                }

                $data_weekend = self::where("effective_start_date", "<=", @mysql_date($start_date_string))
                    ->where("contract_id", "=", $contractid)
                    ->where("rate_type", "=", self::WEEKEND_RATE)
                    ->where("status", "=", '1')
                    ->orderBy("effective_start_date", 'DESC')
                    ->limit(1)
                    ->get();
                if (count($data_weekend) > 0) {
                    foreach ($data_weekend as $data) {
                        $weekend_rate = $data->rate;
                    }
                } else {
                    $weekend_rate = 0.00;
                }

                $data_holiday = self::where("effective_start_date", "<=", @mysql_date($start_date_string))
                    ->where("contract_id", "=", $contractid)
                    ->where("rate_type", "=", self::HOLIDAY_RATE)
                    ->where("status", "=", '1')
                    ->orderBy("effective_start_date", 'DESC')
                    ->limit(1)
                    ->get();
                if (count($data_holiday) > 0) {
                    foreach ($data_holiday as $data) {
                        $holiday_rate = $data->rate;
                    }
                } else {
                    $holiday_rate = 0.00;
                }

                $weekdayspend = ($weekday_rate * 5) * $totalWeeks;
                $weekendspend = ($weekend_rate * 2) * $totalWeeks;
                $holidayspend = ($contract->holiday_rate * ($holidays['week'] + $holidays['weekend']));
                $weekdayspend = $weekdayspend - ($weekday_rate * $holidays['week']);
                $weekendspend = $weekendspend - ($weekend_rate * $holidays['weekend']);
                $contract_total_spend += $weekdayspend + $weekendspend + $holidayspend;
            } else {//for on call, called back,called in
                //$start_date_string=$start_date->format('m/d/Y');
                $data_oncall = self::where("effective_start_date", "<=", @mysql_date($start_date_string))
                    ->where("contract_id", "=", $contractid)
                    ->where("rate_type", "=", self::ON_CALL_RATE)
                    ->where("status", "=", '1')
                    ->orderBy("effective_start_date", 'DESC')
                    ->limit(1)
                    ->get();
                if (count($data_oncall) > 0) {
                    foreach ($data_oncall as $data) {
                        $oncallrate = $data->rate;
                    }
                } else {
                    $oncallrate = 0.00;
                }

                // Calculating the difference in timestamps
                $diff = strtotime($end_date_string) - strtotime($start_date_string);
                // 1 day = 24 hours
                // 24 * 60 * 60 = 86400 seconds
                $monthdays = abs(round($diff / 86400));
                $contract_total_spend += $monthdays * $oncallrate;
            }
            $start_date = $start_date->modify('+1 month')->setTime(0, 0, 0);
            $end_date = with(clone $start_date)->modify('+1 month')->modify('-1 day')->setTime(23, 59, 59);//end date of month
            $month_number++;
        }
        return $contract_total_spend;
    }

    //find Expected Payment from expected hours for FMV
    public static function findTotalExpectedPayment($contractid, $start_date, $months)
    {
        $contract = Contract::findOrFail($contractid);
        $month_number = 1;
        $start_date = date("Y-m-d", strtotime($start_date->format('m/d/Y')));
        $start_date = with(new DateTime($start_date))->setTime(0, 0, 0);
        $contract_total_expected_payment = 0;
        $rate = 0.00;
        while ($month_number <= $months) {
            $start_date_string = $start_date->format('m/d/Y');
            $data = self::where("effective_start_date", "<=", @mysql_date($start_date_string))
                ->where("contract_id", "=", $contractid)
                ->where("rate_type", "=", self::FMV_RATE)
                ->where("status", "=", '1')
                ->orderBy("effective_start_date", 'DESC')
                ->limit(1)
                ->get();
            if (count($data) > 0) {
                foreach ($data as $data) {
                    $rate = $data->rate;
                }
            } else {
                $rate = 0.00;
            }
            $contract_total_expected_payment += $contract->expected_hours * $rate;
            $start_date = $start_date->modify('+1 month')->setTime(0, 0, 0);
            $month_number++;
        }
        return $contract_total_expected_payment;
    }

    public static function getRate($contractid, $start_date, $ratetype)
    {
        $start_date_string = $start_date;//->format('m/d/Y');

        if ($ratetype == contractRate::ON_CALL_UNCOMPENSATED_RATE) {
            $data = self::select("rate", "rate_index", "range_start_day", "range_end_day")
                // ->where("effective_start_date",">=",@mysql_date($start_date))
                ->where("contract_id", "=", $contractid)
                ->where("rate_type", "=", contractRate::ON_CALL_UNCOMPENSATED_RATE)
                ->where("status", "=", '1')
                ->orderBy("effective_start_date", 'DESC')
                ->orderBy("rate_index", 'ASC');
            // ->pluck("rate","range_start_day","range_end_day")->toArray();

            $max_date = $data->max('effective_start_date');
            // ->get();
            return $data->where("effective_start_date", "=", $max_date)->get();

        } else {
            $data = self::where("effective_start_date", "<=", @mysql_date($start_date_string))
                ->where("contract_id", "=", $contractid)
                ->where("rate_type", "=", $ratetype)
                ->where("status", "=", '1')
                ->orderBy("effective_start_date", 'DESC')
                ->limit(1)
                ->get();
        }
        if (count($data) > 0) {
            foreach ($data as $data) {
                $rate = $data->rate;
            }
        } else {
            $rate = 0.00;
        }

        return $rate;
    }


    public static function updateExistingContractsRate()
    {
        $contracts = Contract::select(
            'contracts.id as contract_id',
            'agreements.start_date as start_date',
            'contracts.manual_contract_end_date as end_date',
            'contracts.payment_type_id as payment_type_id',
            'contracts.rate as rate',
            'contracts.weekday_rate as weekday_rate',
            'contracts.weekend_rate as weekend_rate',
            'contracts.holiday_rate as holiday_rate',
            'contracts.on_call_rate as on_call_rate',
            'contracts.called_in_rate as called_in_rate',
            'contracts.called_back_rate as called_back_rate'
        )
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->get();
        foreach ($contracts as $contract) {
            $contract_id = $contract->contract_id;
            $effective_start_date = $contract->start_date;
            $end_date = $contract->end_date;
            $rate = $contract->rate;
            $weekday_rate = $contract->weekday_rate;
            $weekend_rate = $contract->weekend_rate;
            $holiday_rate = $contract->holiday_rate;
            $oncall_rate = $contract->on_call_rate;
            $calledin_rate = $contract->called_in_rate;
            $calledback_rate = $contract->called_back_rate;
            $contractRate = ContractRate::select('contract_rate.*')
                ->where("contract_rate.contract_id", "=", $contract_id)
                ->get();
            if (count($contractRate) > 0) {
                // Log::info('Contract rate for contract exist---- ',array($contractRate));
            } else {
                if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                    $contractOnCallRate = ContractRate::insertContractRate($contract_id, $effective_start_date, $end_date, $oncall_rate, ContractRate::ON_CALL_RATE);
                    $contractCalledBackRate = ContractRate::insertContractRate($contract_id, $effective_start_date, $end_date, $calledback_rate, ContractRate::CALLED_BACK_RATE);
                    $contractCalledInRate = ContractRate::insertContractRate($contract_id, $effective_start_date, $end_date, $calledin_rate, ContractRate::CALLED_IN_RATE);
                    $contractWeekdayRate = ContractRate::insertContractRate($contract_id, $effective_start_date, $end_date, $weekday_rate, ContractRate::WEEKDAY_RATE);
                    $contractWeekendRate = ContractRate::insertContractRate($contract_id, $effective_start_date, $end_date, $weekend_rate, ContractRate::WEEKEND_RATE);
                    $contractHolidayRate = ContractRate::insertContractRate($contract_id, $effective_start_date, $end_date, $holiday_rate, ContractRate::HOLIDAY_RATE);
                } else {
                    $contractFMVRate = ContractRate::insertContractRate($contract_id, $effective_start_date, $end_date, $rate, ContractRate::FMV_RATE);
                }
            }
        }
        return 1;
    }

    /***
     * This function is used for getting the rates for the contracts.
     * It takes the contract_ids array and return the contract rate in combination of rate_type and contract_id.
     */
    public static function contractRates($contracts_for_user)
    {
        try {
            $ratesArray = array();
            $contractRates = ContractRate::whereIn('contract_id', $contracts_for_user)
                ->where("status", "=", '1')
                ->orderBy("effective_start_date", 'DESC')
                ->get();
            foreach ($contractRates as $contractRate) {
                $ratesArray[$contractRate->rate_type][$contractRate->contract_id][] = ["start_date" => $contractRate->effective_start_date,
                    "end_date" => $contractRate->effective_end_date,
                    "rate" => $contractRate->rate];
            }

            return $ratesArray;
        } catch (\Exception $e) {
            // Log::info("From class ContractTypeCustom and method contractRates :" . $e->getMessage());
        }
    }


}
