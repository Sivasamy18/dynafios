<?php

namespace App;

use App\Console\Commands\HospitalReportCommand;
use App\Console\Commands\PaymentStatusReport;
use App\Models\Files\File;
use Artisan;
use Auth;
use DateTime;
use DateTimeZone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Request;
use Redirect;
use Lang;
use StdClass;
use function App\Start\is_physician;

class HospitalReport extends Model
{
    protected $table = 'hospital_reports';

    public static function getReportData($hospital)
    {

        $timestamp = Request::input("current_timestamp");
        $timeZone = Request::input("current_zoneName");
        //log::info("timestamp",array($timestamp));

        if ($timestamp != null && $timeZone != null && $timeZone != '' && $timestamp != '') {
            if (!strtotime($timestamp)) {
                $zone = new DateTime(strtotime($timestamp));
            } else {
                $zone = new DateTime(false);
            }
            $zone->setTimezone(new DateTimeZone($timeZone));
            $localtimeZone = $zone->format('m/d/Y h:i A T');
        } else {
            $localtimeZone = '';
        }

        //log::info("local time zone",array($localtimeZone));

        $agreement_ids = Request::input("agreements");
        //$agreement_ids = explode(',', Request::input("agreements"));

        if (is_physician()) {
            $email_id = Auth::user()->email;
            $physician_id = DB::table("physicians")->select("physicians.id")->where("physicians.email", "=", $email_id)->first();
            $physician_ids[] = $physician_id->id;
            $physicians = $physician_ids;
        } else {
            $physician_ids = Request::input("physicians");
            $physicians = $physician_ids;
        }

        // if (count($agreement_ids) == 0 || count($physician_ids) == 0) { //Old condition
        if ($agreement_ids == null || $physician_ids == null) {
            return Redirect::back()->with([
                'error' => Lang::get('hospitals.report_selection_error')
            ]);
        }

        $months = [];
        $period_data = array();
        $cytpm_data = array();
        $cytd_data = array();
        $practice_data = array();
        $action_array = array();
        $perDiem = false;
        $hourly = false;
        $stipend = false;
        $uncompensated = false;
        $psa = false;
        $timeStudy = false;
        $perUnit = false;
        foreach ($agreement_ids as $index => $agreement_id) {
            $months[] = Request::input("agreement_{$agreement_id}_start_month");
            $months[] = Request::input("agreement_{$agreement_id}_start_month");
            $agreement_data = Agreement::getAgreementData($agreement_id);

            $start_month = $months[$index * 2 + 0];
            $end_month = $months[$index * 2 + 1];
            $agreement = new StdClass;
            $agreement->id = $agreement_id;
            $agreement->start_date = $agreement_data->months[$start_month]->start_date;
            if (isset($agreement_data->months[$end_month]->end_date))
                $agreement->end_date = $agreement_data->months[$end_month]->end_date;
            else
                $agreement->end_date = $agreement_data->months[$start_month]->end_date;
            $agreement->start_month = $start_month;
            $agreement->end_month = $end_month;
            $agreement->month_range = 1 + abs($start_month - $end_month);

//get current practice id

//drop column practice_id from table 'physicians' changes by 1254 : codereview
            $practice = DB::table("physician_practices")
                ->select("physician_practices.practice_id")
                ->where("physician_practices.hospital_id", "=", $hospital->id)
                ->whereIn("physician_practices.physician_id", $physician_ids)
                ->whereRaw("physician_practices.start_date <= now()")
                ->whereRaw("physician_practices.end_date >= now()")
                ->whereNull("physician_practices.deleted_at")
                ->orderBy("start_date", "desc")
                ->first();

            if (empty($practice)) {

                return Redirect::back()->with([
                    'error' => Lang::get('physicians.practice_enddate_error')
                ]);
            }

            $contract_names = self::getContractNames($agreement, Request::input("contract_type"), $physicians, $practice->practice_id);
            foreach ($contract_names as $contract_name) {
                //$practice_data = array_splice($practice_data, 0, count($practice_data));
                unset($practice_data); //  is gone
                $practice_data = array(); // is here again
                $contracts = self::queryContracts($agreement, $agreement->start_date, $agreement->end_date, Request::input("contract_type"), $physicians, $contract_name->name_id);
                //$action_array = array_splice($action_array, 0, count($action_array));
//                log::debug('$contracts', array($contracts));

                unset($action_array); //  is gone
                $action_array = array(); // is here again

                $practice_period_data = self::getPeriodData($agreement, $contracts);
//                log::debug('$practice_period_data', array($practice_period_data));

                $practice_cytpm_data = self::getCYTPMData($agreement, $contracts);
//                log::debug('$practice_cytpm_data', array($practice_cytpm_data));
                $practice_cytd_data = self::getCYTDData($agreement, $contracts);
//                log::debug('$practice_cytd_data', array($practice_cytd_data));

                $perDiem = $practice_period_data['perDiem'] ? true : $perDiem;
                $hourly = $practice_period_data['hourly'] ? true : $hourly;
                $stipend = $practice_period_data['stipend'] ? true : $stipend;
                $uncompensated = $practice_period_data['uncompensated'] ? true : $uncompensated;
                $psa = $practice_period_data['psa'] ? true : $psa;
                $timeStudy = $practice_period_data['timeStudy'] ? true : $timeStudy;
                $perUnit = $practice_period_data['perUnit'] ? true : $perUnit;

                $period_data[$contract_name->name_id] = ["contract_name" => $contract_name->contract_name,
                    "contract_period" => format_date($contracts->agreement_start_date) . " - " . format_date($contracts->agreement_end_date),
                    "contract_totals" => $practice_period_data['contract_totals'],
                    "perDiem" => $practice_period_data['perDiem'],
                    "hourly" => $practice_period_data['hourly'],
                    "stipend" => $practice_period_data['stipend'],
                    "uncompensated" => $practice_period_data['uncompensated'],
                    "psa" => $practice_period_data['psa'],
                    "practice_data" => $practice_period_data['practice_data'],
                    "timeStudy" => $practice_period_data['timeStudy'],
                    "perUnit" => $practice_period_data['perUnit']];
                $cytpm_data[$contract_name->name_id] = ["contract_name" => $contract_name->contract_name,
                    "contract_period" => format_date($contracts->agreement_start_date) . " - " . format_date($contracts->agreement_end_date),
                    "contract_totals" => $practice_cytpm_data['contract_totals'],
                    "perDiem" => $practice_period_data['perDiem'],
                    "hourly" => $practice_period_data['hourly'],
                    "stipend" => $practice_period_data['stipend'],
                    "psa" => $practice_period_data['psa'],
                    "uncompensated" => $practice_period_data['uncompensated'],
                    "practice_data" => $practice_cytpm_data['practice_data'],
                    "timeStudy" => $practice_period_data['timeStudy'],
                    "perUnit" => $practice_period_data['perUnit']];
                $cytd_data[$contract_name->name_id] = ["contract_name" => $contract_name->contract_name,
                    "contract_period" => format_date($contracts->agreement_start_date) . " - " . format_date($contracts->agreement_end_date),
                    "contract_totals" => $practice_cytd_data['contract_totals'],
                    "perDiem" => $practice_period_data['perDiem'],
                    "hourly" => $practice_period_data['hourly'],
                    "stipend" => $practice_period_data['stipend'],
                    "psa" => $practice_period_data['psa'],
                    "uncompensated" => $practice_period_data['uncompensated'],
                    "practice_data" => $practice_cytd_data['practice_data'],
                    "timeStudy" => $practice_period_data['timeStudy'],
                    "perUnit" => $practice_period_data['perUnit']];
            }
        }
        $data = [
            "perDiem" => $perDiem,
            "hourly" => $hourly,
            "stipend" => $stipend,
            "uncompensated" => $uncompensated,
            "psa" => $psa,
            "period_data" => $period_data,
            "cytpm_data" => $cytpm_data,
            "cytd_data" => $cytd_data,
            "localtimeZone" => $localtimeZone,
            "timeStudy" => $timeStudy,
            "perUnit" => $perUnit,
        ];

        Artisan::call("reports:hospital", [
            "hospital" => $hospital->id,
            "contract_type" => Request::input("contract_type"),
            "agreements" => implode(",", $agreement_ids),
            "physicians" => implode(",", $physician_ids),
            "months" => implode(",", $months),
            "data" => $data,
            "finalized" => Request::input("finalized")
        ]);

        if (!HospitalReportCommand::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(HospitalReportCommand::$message)
            ]);
        }

        $report_id = HospitalReportCommand::$report_id;
        $report_filename = HospitalReportCommand::$report_filename;

        return Redirect::back()->with([
            'success' => Lang::get(HospitalReportCommand::$message),
            'report_id' => $report_id,
            'report_filename' => $report_filename
        ]);
    }

    protected static function getContractNames($agreement, $contract_type, $physicians, $practice_id)
    {
        $contract_names = DB::table('physician_practice_history')->select(
            DB::raw("contract_names.name as contract_name"),
            DB::raw("contract_names.id as name_id"),
            DB::raw("physician_practice_history.physician_id as physician_id"))
            ->join('practices', 'practices.id', '=', 'physician_practice_history.practice_id')
            ->join('agreements', 'agreements.hospital_id', '=', 'practices.hospital_id')
            ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
            ->groupBy('contract_names.name')
            ->orderBy('practices.id', "asc")
            ->whereRaw("physician_practice_history.start_date <= now()")
            ->whereRaw("physician_practice_history.end_date >= now()")
            ->where("agreements.id", "=", $agreement->id)
            ->where("physician_practice_history.practice_id", "=", $practice_id)
//			->whereIn("contracts.physician_id", $physicians)
            //issue fixed: showing old practice contracts   :pending
            ->where('contracts.end_date', '=', '0000-00-00 00:00:00')
            ->whereNull('contracts.deleted_at');
        //->whereor('contracts.manual_date_end', '=', '0000-00-00 00:00:00');)

        if (is_physician()) {
            $contract_names = $contract_names->whereNull("contracts.deleted_at");
        }

        if ($contract_type != -1) {
            $contract_names = $contract_names->where("contracts.contract_type_id", "=", $contract_type);
        }

        $contract_names = $contract_names->whereIn("physician_practice_history.physician_id", $physicians)->get();
        return $contract_names;

    }

//physician to multiple hospital by 1254

    protected static function queryContracts($agreement_data, $start_date, $end_date, $contract_type_id, $physicians, $contract_name_id)
    {
        $agreement = Agreement::findOrFail($agreement_data->id);

        $start_date = mysql_date($start_date);
        $end_date = mysql_date($end_date);
        $start_period = $start_date;
        $end_period = $end_date;
        //echo $agreement->start_date;die;
        //$contract_month = months($agreement->start_date, 'now');
        //contract month for period
        $contract_month = months($agreement->start_date, $end_date);
        $contract_term = months($agreement->start_date, $agreement->end_date);
        $log_range = months($start_date, $end_date);

        if ($contract_month > $contract_term) {
            $contract_month = $contract_term;
        }
        $i = 0;
        $count_py = 0;
        $physicians1 = array();
        $practices = array();
        $py_data = DB::table('physician_practice_history')->select(
            DB::raw("practices.name as practice_name"),
            DB::raw("practices.id as practice_id"),
            DB::raw("physician_practice_history.physician_id as physician_id"))
            ->join('practices', 'practices.id', '=', 'physician_practice_history.practice_id')
            ->join('physician_contracts', 'physician_contracts.practice_id', '=', 'physician_practice_history.practice_id')
            ->join('contracts', 'contracts.id', '=', 'physician_contracts.contract_id')
            ->whereIn("physician_practice_history.physician_id", $physicians)
            ->where("contracts.agreement_id", '=', $agreement_data->id)
            ->where("contracts.contract_name_id", "=", $contract_name_id)
            ->groupBy('physician_practice_history.physician_id', 'physician_practice_history.practice_id')
            ->orderBy('practices.id', "asc")
            ->orderBy('physician_practice_history.physician_id', 'asc')
            ->get();
//		print_r($py_data);die;
        foreach ($py_data as $py_data1) {
            //echo $py_data1->physician_id;
            $physicians1[$count_py] = $py_data1->physician_id;
            $practices[$count_py] = $py_data1->practice_id;
            $count_py++;
            # code...
        }

        foreach ($physicians1 as $key => $physician) {
            $physician_details = Physician::withTrashed()->findOrFail($physician);
            $period_query[$i] = DB::table('physician_logs')->select(
                DB::raw("practices.name as practice_name"),
                DB::raw("practices.id as practice_id"),
                DB::raw("physician_practice_history.physician_id as physician_id"),
                DB::raw("concat(physician_practice_history.last_name, ', ', physician_practice_history.first_name) as physician_name"),
                DB::raw("specialties.name as specialty_name"),
                DB::raw("contracts.id as contract_id"),
                DB::raw("contracts.contract_name_id as contract_name_id"),
                DB::raw("contracts.payment_type_id as payment_type_id"),
                DB::raw("contracts.partial_hours as partial_hours"),
                DB::raw("contracts.contract_type_id as contract_type_id"),
                DB::raw("contract_types.name as contract_name"),
                DB::raw("contracts.min_hours as min_hours"),
                DB::raw("contracts.max_hours as max_hours"),
                DB::raw("contracts.expected_hours as expected_hours"),
                DB::raw("contracts.rate as rate"),
                DB::raw("contracts.weekday_rate as weekday_rate"),
                DB::raw("contracts.weekend_rate as weekend_rate"),
                DB::raw("contracts.holiday_rate as holiday_rate"),
                DB::raw("contracts.on_call_rate as on_call_rate"),
                DB::raw("contracts.called_back_rate as called_back_rate"),
                DB::raw("contracts.called_in_rate as called_in_rate"),
                DB::raw("contracts.on_call_process as on_call_process"),
                DB::raw("contracts.manual_contract_end_date as manual_contract_end_date"),
                DB::raw("contracts.wrvu_payments as wrvu_payments"),
                DB::raw("sum(physician_logs.duration) as worked_hours"),
                DB::raw("'{$contract_month}' as contract_month"),
                DB::raw("'{$contract_term}' as contract_term"),
                DB::raw("'{$log_range}' as log_range"),
                DB::raw("contracts.prior_worked_hours as prior_worked_hours"),
                DB::raw("contracts.prior_amount_paid as prior_amount_paid")
            );

            //added for soft delete
            if ($physician_details->deleted_at != Null) {
                $period_query[$i] = $period_query[$i]->join("physicians", function ($join) {
                    $join->on("physicians.id", "=", "physician_logs.physician_id")
                        ->on("physicians.deleted_at", ">=", "physician_logs.deleted_at");
                });
            }
            $period_query[$i] = $period_query[$i]->leftJoin('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                ->leftJoin('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                ->leftJoin('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                ->leftJoin('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
                ->leftJoin('physician_practice_history', 'physician_practice_history.physician_id', '=', 'physician_contracts.physician_id')
                ->leftJoin('specialties', 'specialties.id', '=', 'physician_practice_history.specialty_id')
                ->leftJoin("practices", function ($join) {
                    $join->on("physician_practice_history.practice_id", "=", "practices.id")
                        ->on("practices.hospital_id", "=", "agreements.hospital_id");
                })
                ->where('contracts.agreement_id', '=', $agreement->id)
                ->where("physician_contracts.physician_id", "=", $physician)
                ->where("physician_logs.physician_id", "=", $physician)
                ->where("physician_practice_history.physician_id", "=", $physician)
                ->where("practices.id", "=", $practices[$key])
                ->where("physician_logs.practice_id", "=", $practices[$key])
                ->whereBetween('physician_logs.date', array($start_date, $end_date));
            if ($contract_type_id != -1) {
                $period_query[$i] = $period_query[$i]->where("contract_types.id", "=", $contract_type_id);
            }
            //added for soft delete
            if ($physician_details->deleted_at == Null) {
                $period_query[$i] = $period_query[$i]->where('physician_logs.deleted_at', '=', Null);
            }
            $period_query[$i] = $period_query[$i]->where("contracts.contract_name_id", "=", $contract_name_id)
                ->groupBy('physician_practice_history.physician_id', 'agreements.id', 'contracts.id')
                ->orderBy('contract_types.name', 'asc')
                ->orderBy('practices.name', 'asc')
                ->orderBy('physician_practice_history.last_name', 'asc')
                ->orderBy('physician_practice_history.first_name', 'asc');

            $period_query2[$i] = DB::table('physician_practice_history')->select(
                DB::raw("practices.name as practice_name"),
                DB::raw("practices.id as practice_id"),
                DB::raw("physician_practice_history.physician_id as physician_id"),
                DB::raw("concat(physician_practice_history.last_name, ', ', physician_practice_history.first_name) as physician_name"),
                DB::raw("specialties.name as specialty_name"),
                DB::raw("contracts.id as contract_id"),
                DB::raw("contracts.contract_name_id as contract_name_id"),
                DB::raw("contracts.payment_type_id as payment_type_id"),
                DB::raw("contracts.partial_hours as partial_hours"),
                DB::raw("contracts.contract_type_id as contract_type_id"),
                DB::raw("contract_types.name as contract_name"),
                DB::raw("contracts.min_hours as min_hours"),
                DB::raw("contracts.max_hours as max_hours"),
                DB::raw("contracts.expected_hours as expected_hours"),
                DB::raw("contracts.rate as rate"),
                DB::raw("contracts.weekday_rate as weekday_rate"),
                DB::raw("contracts.weekend_rate as weekend_rate"),
                DB::raw("contracts.holiday_rate as holiday_rate"),
                DB::raw("contracts.on_call_rate as on_call_rate"),
                DB::raw("contracts.called_back_rate as called_back_rate"),
                DB::raw("contracts.called_in_rate as called_in_rate"),
                DB::raw("contracts.on_call_process as on_call_process"),
                DB::raw("contracts.manual_contract_end_date as manual_contract_end_date"),
                DB::raw("contracts.wrvu_payments as wrvu_payments"),
                DB::raw("0 as worked_hours"),
                DB::raw("'{$contract_month}' as contract_month"),
                DB::raw("'{$contract_term}' as contract_term"),
                DB::raw("'{$log_range}' as log_range"),
                DB::raw("contracts.prior_worked_hours as prior_worked_hours"),
                DB::raw("contracts.prior_amount_paid as prior_amount_paid")
            )
                ->leftJoin('physician_contracts', 'physician_contracts.physician_id', '=', 'physician_practice_history.physician_id')
                ->leftJoin('contracts', 'contracts.id', '=', 'physician_contracts.contract_id')
                ->leftJoin('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                ->leftJoin('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                ->leftJoin('specialties', 'specialties.id', '=', 'physician_practice_history.specialty_id')
                ->leftJoin("practices", function ($join) {
                    $join->on("physician_practice_history.practice_id", "=", "practices.id")
                        ->on("practices.hospital_id", "=", "agreements.hospital_id");
                })
                ->where('contracts.agreement_id', '=', $agreement->id)
                ->where("physician_contracts.physician_id", "=", $physician)
                ->where("physician_practice_history.physician_id", "=", $physician)
                ->where("practices.id", "=", $practices[$key])/*->where("physician_practice_history.start_date", "<=", $end_date)*/
            ;
            if ($contract_type_id != -1) {
                $period_query2[$i] = $period_query2[$i]->where("contract_types.id", "=", $contract_type_id);
            }
            $period_query2[$i] = $period_query2[$i]->where("contracts.contract_name_id", "=", $contract_name_id)
                ->groupBy('physician_practice_history.physician_id', 'agreements.id', 'contracts.id')
                ->orderBy('contract_types.name', 'asc')
                ->orderBy('practices.name', 'asc')
                ->orderBy('physician_practice_history.last_name', 'asc')
                ->orderBy('physician_practice_history.first_name', 'asc');
            $i++;
        }


        $date_start_agreement = DB::table('agreements')->where("id", "=", $agreement->id)->first();
        $start_date_ytm = $date_start_agreement->start_date;
        if ($end_date > $date_start_agreement->end_date) {
            $end_date = date('Y-m-d', strtotime($date_start_agreement->end_date));
        }

        $ts1 = strtotime($start_date_ytm);
        $ts2 = strtotime($end_date);

        $year1 = date('Y', $ts1);
        $year2 = date('Y', $ts2);

        $month1 = date('m', $ts1);
        $month2 = date('m', $ts2);

        $diff = (($year2 - $year1) * 12) + ($month2 - $month1) + 1;

        //$end_month = date('m',strtotime($end_date));

        for ($i = 1; $i <= $diff; $i++) {
            //$start_month_date = $i."-".date('Y', strtotime($end_date));
            if ($month1 > 12) {
                $m = $month1 - (12 * floor($month1 / 12));
                $y = $year1 + floor($month1 / 12);
            } else {
                $m = $month1;
                $y = $year1;
            }
            $month1++;

            $end_month_date = mysql_date(date('d-m-Y', strtotime("01-" . $m . "-" . $y)));

            //$start_month_date = "01-".$i."-".date('Y', strtotime($end_date));
            //$first_date_month = mysql_date($start_month_date);
            //$first_date_month = mysql_date("01-".$end_month_date);
            //echo $end_month_date;
            $last_date_month = mysql_date(date('t-F-Y', strtotime($end_month_date)));
            $contract_month = months($agreement->start_date, $last_date_month);
            //echo $contract_month;
            //echo $last_date_month;die;
            $j = 0;
            foreach ($physicians as $physician) {
                $physician_details = Physician::withTrashed()->findOrFail($physician);
                $practices_history = PhysicianPracticeHistory::select('physician_practice_history.*')
                    ->join('practices', 'practices.id', '=', 'physician_practice_history.practice_id')
                    ->join('physician_contracts', 'physician_contracts.practice_id', '=', 'physician_practice_history.practice_id')
                    ->join('contracts', 'contracts.id', '=', 'physician_contracts.contract_id')
//					->join('agreements', 'agreements.hospital_id', '=', 'practices.hospital_id')
                    ->where('contracts.agreement_id', '=', $agreement->id)
                    ->where('physician_practice_history.physician_id', '=', $physician)
                    ->where("contracts.contract_name_id", "=", $contract_name_id)
                    ->groupBy('physician_practice_history.physician_id', 'physician_practice_history.practice_id')
                    ->orderBy('start_date', 'desc')->get();
                if (count($practices_history) > 1) {
                    $count_practices = count($practices_history);
                    $count_practice_ytm = 0;
                    foreach ($practices_history as $practice_present) {
                        $flag = 0;
                        $count_practice_ytm++;
                        $practice_present_start_date = $practice_present->start_date;
                        if ($count_practice_ytm == $count_practices) {
                            //Log::info("practice_present::",array($practice_present));
                            $practice_present->start_date = $start_date_ytm;
                            $practice_present_start_date = $start_date_ytm;
                        }
                        if (mysql_date($practice_present->start_date) <= $end_month_date && mysql_date($practice_present->end_date) >= $last_date_month) {
                            $flag++;
                            $practice = $practice_present->practice_id;
                        } elseif (mysql_date($practice_present->start_date) <= $end_month_date && mysql_date($practice_present->end_date) < $last_date_month) {
                            if (mysql_date($practice_present->end_date) > $end_month_date) {
                                $flag++;
                                $practice = $practice_present->practice_id;
                            }
                        } elseif (mysql_date($practice_present->start_date) > $end_month_date && mysql_date($practice_present->end_date) < $last_date_month) {
                            $flag++;
                            $practice = $practice_present->practice_id;
                        } elseif (mysql_date($practice_present->start_date) > $end_month_date && mysql_date($practice_present->end_date) >= $last_date_month) {
                            if (mysql_date($practice_present->start_date) < $last_date_month) {
                                $flag++;
                                $practice = $practice_present->practice_id;
                            }
                        }
                        if ($flag > 0) {
                            if ($practice_present_start_date < $start_date_ytm) {
                                $practice_present_start_date = $start_date_ytm;
                            }
                            $contract_month_change_practice1 = months($practice_present_start_date, $last_date_month);
                            $year_to_month_query[$i][$j] = DB::table('physician_logs')->select(
                                DB::raw("practices.name as practice_name"),
                                DB::raw("practices.id as practice_id"),
                                DB::raw("physician_practice_history.physician_id as physician_id"),
                                DB::raw("concat(physician_practice_history.last_name, ', ', physician_practice_history.first_name) as physician_name"),
                                DB::raw("specialties.name as specialty_name"),
                                DB::raw("contracts.id as contract_id"),
                                DB::raw("contracts.contract_name_id as contract_name_id"),
                                DB::raw("contracts.payment_type_id as payment_type_id"),
                                DB::raw("contracts.partial_hours as partial_hours"),
                                DB::raw("contracts.contract_type_id as contract_type_id"),
                                DB::raw("contract_types.name as contract_name"),
                                DB::raw("contracts.min_hours as min_hours"),
                                DB::raw("contracts.max_hours as max_hours"),
                                DB::raw("contracts.annual_cap as annual_cap"),
                                DB::raw("contracts.expected_hours as expected_hours"),
                                DB::raw("contracts.rate as rate"),
                                DB::raw("contracts.weekday_rate as weekday_rate"),
                                DB::raw("contracts.weekend_rate as weekend_rate"),
                                DB::raw("contracts.holiday_rate as holiday_rate"),
                                DB::raw("contracts.on_call_rate as on_call_rate"),
                                DB::raw("contracts.called_back_rate as called_back_rate"),
                                DB::raw("contracts.called_in_rate as called_in_rate"),
                                DB::raw("contracts.on_call_process as on_call_process"),
                                DB::raw("contracts.manual_contract_end_date as manual_contract_end_date"),
                                DB::raw("contracts.wrvu_payments as wrvu_payments"),
                                DB::raw("sum(physician_logs.duration) as worked_hours"),
                                DB::raw("'{$contract_month_change_practice1}' as contract_month"),
                                DB::raw("'{$contract_month}' as contract_month1"),
                                DB::raw("'{$contract_term}' as contract_term"),
                                DB::raw("'{$contract_month_change_practice1}' as log_range"),
                                DB::raw("'{$end_month_date}' as start_date_check"),
                                DB::raw("'{$last_date_month}' as end_date_check"),
                                DB::raw("contracts.prior_worked_hours as prior_worked_hours"),
                                DB::raw("contracts.prior_amount_paid as prior_amount_paid")
                            );

                            //added for soft delete
                            if ($physician_details->deleted_at != Null) {
                                $year_to_month_query[$i][$j] = $year_to_month_query[$i][$j]->join("physicians", function ($join) {
                                    $join->on("physicians.id", "=", "physician_logs.physician_id")
                                        ->on("physicians.deleted_at", ">=", "physician_logs.deleted_at");
                                });
                            }
                            $year_to_month_query[$i][$j] = $year_to_month_query[$i][$j]->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                                ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                                ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                                ->join('physician_practice_history', 'physician_practice_history.physician_id', '=', 'physician_logs.physician_id')
                                ->join('specialties', 'specialties.id', '=', 'physician_practice_history.specialty_id')
                                ->join("practices", function ($join) {
                                    $join->on("physician_practice_history.practice_id", "=", "practices.id")
                                        ->on("practices.hospital_id", "=", "agreements.hospital_id");
                                })
                                ->where('contracts.agreement_id', '=', $agreement->id)
                                ->where("physician_practice_history.physician_id", "=", $physician)
                                ->where("practices.id", "=", $practice)
                                ->where("physician_logs.practice_id", "=", $practice)
                                ->whereBetween('physician_logs.date', array($end_month_date, $last_date_month));
                            if ($contract_type_id != -1) {
                                $year_to_month_query[$i][$j] = $year_to_month_query[$i][$j]->where("contract_types.id", "=", $contract_type_id);
                            }
                            //added for soft delete
                            if ($physician_details->deleted_at == Null) {
                                $year_to_month_query[$i][$j] = $year_to_month_query[$i][$j]->where('physician_logs.deleted_at', '=', Null);
                            }
                            $year_to_month_query[$i][$j] = $year_to_month_query[$i][$j]->where("contracts.contract_name_id", "=", $contract_name_id)
                                ->groupBy('physician_practice_history.physician_id')
                                ->orderBy('contract_types.name', 'asc')
                                ->orderBy('practices.name', 'asc')
                                ->orderBy('physician_practice_history.last_name', 'asc')
                                ->orderBy('physician_practice_history.first_name', 'asc');

                            $year_to_month_query2[$i][$j] = DB::table('physician_practice_history')->select(
                                DB::raw("practices.name as practice_name"),
                                DB::raw("practices.id as practice_id"),
                                DB::raw("physician_practice_history.physician_id as physician_id"),
                                DB::raw("concat(physician_practice_history.last_name, ', ', physician_practice_history.first_name) as physician_name"),
                                DB::raw("specialties.name as specialty_name"),
                                DB::raw("contracts.id as contract_id"),
                                DB::raw("contracts.contract_name_id as contract_name_id"),
                                DB::raw("contracts.payment_type_id as payment_type_id"),
                                DB::raw("contracts.partial_hours as partial_hours"),
                                DB::raw("contracts.contract_type_id as contract_type_id"),
                                DB::raw("contract_types.name as contract_name"),
                                DB::raw("contracts.min_hours as min_hours"),
                                DB::raw("contracts.max_hours as max_hours"),
                                DB::raw("contracts.annual_cap as annual_cap"),
                                DB::raw("contracts.expected_hours as expected_hours"),
                                DB::raw("contracts.rate as rate"),
                                DB::raw("contracts.weekday_rate as weekday_rate"),
                                DB::raw("contracts.weekend_rate as weekend_rate"),
                                DB::raw("contracts.holiday_rate as holiday_rate"),
                                DB::raw("contracts.on_call_rate as on_call_rate"),
                                DB::raw("contracts.called_back_rate as called_back_rate"),
                                DB::raw("contracts.called_in_rate as called_in_rate"),
                                DB::raw("contracts.on_call_process as on_call_process"),
                                DB::raw("contracts.manual_contract_end_date as manual_contract_end_date"),
                                DB::raw("contracts.wrvu_payments as wrvu_payments"),
                                DB::raw("0 as worked_hours"),
                                DB::raw("'{$contract_month_change_practice1}' as contract_month"),
                                DB::raw("'{$contract_month}' as contract_month1"),
                                DB::raw("'{$contract_term}' as contract_term"),
                                DB::raw("'{$contract_month_change_practice1}' as log_range"),
                                DB::raw("'{$end_month_date}' as start_date_check"),
                                DB::raw("'{$last_date_month}' as end_date_check"),
                                DB::raw("contracts.prior_worked_hours as prior_worked_hours"),
                                DB::raw("contracts.prior_amount_paid as prior_amount_paid")
                            )
                                ->join('physician_contracts', 'physician_contracts.physician_id', '=', 'physician_practice_history.physician_id')
                                ->join('contracts', 'contracts.id', '=', 'physician_contracts.contract_id')
                                ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                                ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                                ->join('specialties', 'specialties.id', '=', 'physician_practice_history.specialty_id')
                                ->join("practices", function ($join) {
                                    $join->on("physician_practice_history.practice_id", "=", "practices.id")
                                        ->on("practices.hospital_id", "=", "agreements.hospital_id");
                                })
                                ->where('contracts.agreement_id', '=', $agreement->id)
                                ->where("physician_practice_history.physician_id", "=", $physician)
                                ->where("practices.id", "=", $practice);
                            if ($contract_type_id != -1) {
                                $year_to_month_query2[$i][$j] = $year_to_month_query2[$i][$j]->where("contract_types.id", "=", $contract_type_id);
                            }
                            $year_to_month_query2[$i][$j] = $year_to_month_query2[$i][$j]->where("contracts.contract_name_id", "=", $contract_name_id)
                                ->groupBy('physician_practice_history.physician_id')
                                ->orderBy('contract_types.name', 'asc')
                                ->orderBy('practices.name', 'asc')
                                ->orderBy('physician_practice_history.last_name', 'asc')
                                ->orderBy('physician_practice_history.first_name', 'asc');
                            $j++;
                        }
                    }
                } elseif (count($practices_history) != 0) {
                    foreach ($practices_history as $practice_present) {
                        $practice = $practice_present->practice_id;
                    }
                    $year_to_month_query[$i][$j] = DB::table('physician_logs')->select(
                        DB::raw("practices.name as practice_name"),
                        DB::raw("practices.id as practice_id"),
                        DB::raw("physician_practice_history.physician_id as physician_id"),
                        DB::raw("concat(physician_practice_history.last_name, ', ', physician_practice_history.first_name) as physician_name"),
                        DB::raw("specialties.name as specialty_name"),
                        DB::raw("contracts.id as contract_id"),
                        DB::raw("contracts.contract_name_id as contract_name_id"),
                        DB::raw("contracts.payment_type_id as payment_type_id"),
                        DB::raw("contracts.partial_hours as partial_hours"),
                        DB::raw("contracts.contract_type_id as contract_type_id"),
                        DB::raw("contract_types.name as contract_name"),
                        DB::raw("contracts.min_hours as min_hours"),
                        DB::raw("contracts.max_hours as max_hours"),
                        DB::raw("contracts.annual_cap as annual_cap"),
                        DB::raw("contracts.expected_hours as expected_hours"),
                        DB::raw("contracts.rate as rate"),
                        DB::raw("contracts.weekday_rate as weekday_rate"),
                        DB::raw("contracts.weekend_rate as weekend_rate"),
                        DB::raw("contracts.holiday_rate as holiday_rate"),
                        DB::raw("contracts.on_call_rate as on_call_rate"),
                        DB::raw("contracts.called_back_rate as called_back_rate"),
                        DB::raw("contracts.called_in_rate as called_in_rate"),
                        DB::raw("contracts.on_call_process as on_call_process"),
                        DB::raw("contracts.manual_contract_end_date as manual_contract_end_date"),
                        DB::raw("contracts.wrvu_payments as wrvu_payments"),
                        DB::raw("sum(physician_logs.duration) as worked_hours"),
                        DB::raw("'{$contract_month}' as contract_month"),
                        DB::raw("'{$contract_month}' as contract_month1"),
                        DB::raw("'{$contract_term}' as contract_term"),
                        DB::raw("'{$contract_month}' as log_range"),
                        DB::raw("'{$end_month_date}' as start_date_check"),
                        DB::raw("'{$last_date_month}' as end_date_check"),
                        DB::raw("contracts.prior_worked_hours as prior_worked_hours"),
                        DB::raw("contracts.prior_amount_paid as prior_amount_paid")
                    );

                    //added for soft delete
                    if ($physician_details->deleted_at != Null) {
                        $year_to_month_query[$i][$j] = $year_to_month_query[$i][$j]->join("physicians", function ($join) {
                            $join->on("physicians.id", "=", "physician_logs.physician_id")
                                ->on("physicians.deleted_at", ">=", "physician_logs.deleted_at");
                        });
                    }
                    $year_to_month_query[$i][$j] = $year_to_month_query[$i][$j]->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                        ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                        ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                        ->join('physician_practice_history', 'physician_practice_history.physician_id', '=', 'physician_logs.physician_id')
                        ->join('specialties', 'specialties.id', '=', 'physician_practice_history.specialty_id')
                        ->join("practices", function ($join) {
                            $join->on("physician_practice_history.practice_id", "=", "practices.id")
                                ->on("practices.hospital_id", "=", "agreements.hospital_id");
                        })
                        ->where('contracts.agreement_id', '=', $agreement->id)
                        ->where("physician_practice_history.physician_id", "=", $physician)
                        ->where("practices.id", "=", $practice)
                        ->where("physician_logs.practice_id", "=", $practice)
                        ->whereBetween('physician_logs.date', array($end_month_date, $last_date_month));
                    if ($contract_type_id != -1) {
                        $year_to_month_query[$i][$j] = $year_to_month_query[$i][$j]->where("contract_types.id", "=", $contract_type_id);
                    }
                    //added for soft delete
                    if ($physician_details->deleted_at == Null) {
                        $year_to_month_query[$i][$j] = $year_to_month_query[$i][$j]->where('physician_logs.deleted_at', '=', Null);
                    }
                    $year_to_month_query[$i][$j] = $year_to_month_query[$i][$j]->where("contracts.contract_name_id", "=", $contract_name_id)
                        ->groupBy('physician_practice_history.physician_id')
                        ->orderBy('contract_types.name', 'asc')
                        ->orderBy('practices.name', 'asc')
                        ->orderBy('physician_practice_history.last_name', 'asc')
                        ->orderBy('physician_practice_history.first_name', 'asc');

                    $year_to_month_query2[$i][$j] = DB::table('physician_practice_history')->select(
                        DB::raw("practices.name as practice_name"),
                        DB::raw("practices.id as practice_id"),
                        DB::raw("physician_practice_history.physician_id as physician_id"),
                        DB::raw("concat(physician_practice_history.last_name, ', ', physician_practice_history.first_name) as physician_name"),
                        DB::raw("specialties.name as specialty_name"),
                        DB::raw("contracts.id as contract_id"),
                        DB::raw("contracts.contract_name_id as contract_name_id"),
                        DB::raw("contracts.payment_type_id as payment_type_id"),
                        DB::raw("contracts.partial_hours as partial_hours"),
                        DB::raw("contracts.contract_type_id as contract_type_id"),
                        DB::raw("contract_types.name as contract_name"),
                        DB::raw("contracts.min_hours as min_hours"),
                        DB::raw("contracts.max_hours as max_hours"),
                        DB::raw("contracts.annual_cap as annual_cap"),
                        DB::raw("contracts.expected_hours as expected_hours"),
                        DB::raw("contracts.rate as rate"),
                        DB::raw("contracts.weekday_rate as weekday_rate"),
                        DB::raw("contracts.weekend_rate as weekend_rate"),
                        DB::raw("contracts.holiday_rate as holiday_rate"),
                        DB::raw("contracts.on_call_rate as on_call_rate"),
                        DB::raw("contracts.called_back_rate as called_back_rate"),
                        DB::raw("contracts.called_in_rate as called_in_rate"),
                        DB::raw("contracts.on_call_process as on_call_process"),
                        DB::raw("contracts.manual_contract_end_date as manual_contract_end_date"),
                        DB::raw("contracts.wrvu_payments as wrvu_payments"),
                        DB::raw("0 as worked_hours"),
                        DB::raw("'{$contract_month}' as contract_month"),
                        DB::raw("'{$contract_month}' as contract_month1"),
                        DB::raw("'{$contract_term}' as contract_term"),
                        DB::raw("'{$contract_month}' as log_range"),
                        DB::raw("'{$end_month_date}' as start_date_check"),
                        DB::raw("'{$last_date_month}' as end_date_check"),
                        DB::raw("contracts.prior_worked_hours as prior_worked_hours"),
                        DB::raw("contracts.prior_amount_paid as prior_amount_paid")
                    )
                        ->join('physician_contracts', 'physician_contracts.physician_id', '=', 'physician_practice_history.physician_id')
                        ->join('contracts', 'contracts.id', '=', 'physician_contracts.contract_id')
                        ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                        ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                        ->join('specialties', 'specialties.id', '=', 'physician_practice_history.specialty_id')
                        ->join("practices", function ($join) {
                            $join->on("physician_practice_history.practice_id", "=", "practices.id")
                                ->on("practices.hospital_id", "=", "agreements.hospital_id");
                        })
                        ->where('contracts.agreement_id', '=', $agreement->id)
                        ->where("physician_practice_history.physician_id", "=", $physician)
                        ->where("practices.id", "=", $practice);
                    if ($contract_type_id != -1) {
                        $year_to_month_query2[$i][$j] = $year_to_month_query2[$i][$j]->where("contract_types.id", "=", $contract_type_id);
                    }
                    $year_to_month_query2[$i][$j] = $year_to_month_query2[$i][$j]->where("contracts.contract_name_id", "=", $contract_name_id)
                        ->groupBy('physician_practice_history.physician_id')
                        ->orderBy('contract_types.name', 'asc')
                        ->orderBy('practices.name', 'asc')
                        ->orderBy('physician_practice_history.last_name', 'asc')
                        ->orderBy('physician_practice_history.first_name', 'asc');
                    $j++;
                }
            }
        }
        //print_r($year_to_month_query2[0][0]->get());die;

        $date_start_agreement = DB::table('agreements')->where("id", "=", $agreement->id)->first();
        $start_date_ytm = $date_start_agreement->start_date;

        if ($date_start_agreement->end_date > date('Y-m-t')) {
            $end_date = date('Y-m-t');
        } else {
            $end_date = mysql_date($date_start_agreement->end_date);
        }

        $ts1 = strtotime($start_date_ytm);
        $ts2 = strtotime($end_date);

        $year1 = date('Y', $ts1);
        $year2 = date('Y', $ts2);

        $month1 = date('m', $ts1);
        $month2 = date('m', $ts2);

        $diff = (($year2 - $year1) * 12) + ($month2 - $month1) + 1;

        //$end_month = date('m',strtotime($end_date));

        $contract_month = months($agreement->start_date, $start_date) + 1;

        for ($i = 1; $i <= $diff; $i++) {
            //$start_month_date = $i."-".date('Y', strtotime($end_date));
            if ($month1 > 12) {
                $m = $month1 - (12 * floor($month1 / 12));
                $y = $year1 + floor($month1 / 12);
            } else {
                $m = $month1;
                $y = $year1;
            }
            $month1++;

            $end_month_date = mysql_date(date('d-m-Y', strtotime("01-" . $m . "-" . $y)));

            //$start_month_date = "01-".$i."-".date('Y', strtotime($end_date));
            //$first_date_month = mysql_date($start_month_date);
            //$first_date_month = mysql_date("01-".$end_month_date);
            //echo $end_month_date;die;
            $last_date_month = mysql_date(date('t-F-Y', strtotime($end_month_date)));
            $contract_month1 = months($agreement->start_date, $last_date_month);


            $j = 0;
            foreach ($physicians as $physician) {
                $physician_details = Physician::withTrashed()->findOrFail($physician);
                $practices_history = PhysicianPracticeHistory::select('physician_practice_history.*')
                    ->join('practices', 'practices.id', '=', 'physician_practice_history.practice_id')
                    ->join('physician_contracts', 'physician_contracts.practice_id', '=', 'physician_practice_history.practice_id')
                    ->join('contracts', 'contracts.id', '=', 'physician_contracts.contract_id')
//					->join('agreements', 'agreements.hospital_id', '=', 'practices.hospital_id')
                    ->where('contracts.agreement_id', '=', $agreement->id)
                    ->where('physician_practice_history.physician_id', '=', $physician)
                    ->where("contracts.contract_name_id", "=", $contract_name_id)
                    ->groupBy('physician_practice_history.physician_id', 'physician_practice_history.practice_id')
                    ->orderBy('start_date', 'asc')->get();
                if (count($practices_history) > 1) {
                    $count_practice = 0;
                    foreach ($practices_history as $practice_present) {
                        $flag = 0;
                        $practice_present_start_date = $practice_present->start_date;
                        if ($count_practice == 0) {
                            $count_practice++;
                            $practice_present->start_date = $start_date_ytm;
                        }
                        if (mysql_date($practice_present->start_date) <= $end_month_date && mysql_date($practice_present->end_date) >= $last_date_month) {
                            $flag++;
                            $practice = $practice_present->practice_id;
                        } elseif (mysql_date($practice_present->start_date) <= $end_month_date && mysql_date($practice_present->end_date) < $last_date_month) {
                            if (mysql_date($practice_present->end_date) > $end_month_date) {
                                $flag++;
                                $practice = $practice_present->practice_id;
                            }
                        } elseif (mysql_date($practice_present->start_date) > $end_month_date && mysql_date($practice_present->end_date) < $last_date_month) {
                            $flag++;
                            $practice = $practice_present->practice_id;
                        } elseif (mysql_date($practice_present->start_date) > $end_month_date && mysql_date($practice_present->end_date) >= $last_date_month) {
                            if (mysql_date($practice_present->start_date) < $last_date_month) {
                                $flag++;
                                $practice = $practice_present->practice_id;
                            }
                        }
                        if ($flag > 0) {
                            if ($practice_present_start_date < $start_date_ytm) {
                                $practice_present_start_date = $start_date_ytm;
                            }
                            $contract_month_change_practice = months($practice_present_start_date, $last_date_month);
                            $year_to_date_query[$i][$j] = DB::table('physician_logs')->select(
                                DB::raw("practices.name as practice_name"),
                                DB::raw("practices.id as practice_id"),
                                DB::raw("physician_practice_history.physician_id as physician_id"),
                                DB::raw("concat(physician_practice_history.last_name, ', ', physician_practice_history.first_name) as physician_name"),
                                DB::raw("specialties.name as specialty_name"),
                                DB::raw("contracts.id as contract_id"),
                                DB::raw("contracts.contract_name_id as contract_name_id"),
                                DB::raw("contracts.contract_type_id as contract_type_id"),
                                DB::raw("contracts.payment_type_id as payment_type_id"),
                                DB::raw("contracts.partial_hours as partial_hours"),
                                DB::raw("contract_types.name as contract_name"),
                                DB::raw("contracts.min_hours as min_hours"),
                                DB::raw("contracts.max_hours as max_hours"),
                                DB::raw("contracts.annual_cap as annual_cap"),
                                DB::raw("contracts.expected_hours as expected_hours"),
                                DB::raw("contracts.rate as rate"),
                                DB::raw("contracts.weekday_rate as weekday_rate"),
                                DB::raw("contracts.weekend_rate as weekend_rate"),
                                DB::raw("contracts.holiday_rate as holiday_rate"),
                                DB::raw("contracts.on_call_rate as on_call_rate"),
                                DB::raw("contracts.called_back_rate as called_back_rate"),
                                DB::raw("contracts.called_in_rate as called_in_rate"),
                                DB::raw("contracts.on_call_process as on_call_process"),
                                DB::raw("contracts.manual_contract_end_date as manual_contract_end_date"),
                                DB::raw("contracts.wrvu_payments as wrvu_payments"),
                                DB::raw("sum(physician_logs.duration) as worked_hours"),
                                DB::raw("'{$contract_month}' as contract_month1"),
                                DB::raw("'{$contract_month_change_practice}' as contract_month"),
                                DB::raw("'{$contract_term}' as contract_term"),
                                DB::raw("'{$contract_month_change_practice}' as log_range"),
                                DB::raw("'{$end_month_date}' as start_date_check"),
                                DB::raw("'{$last_date_month}' as end_date_check"),
                                DB::raw("contracts.prior_worked_hours as prior_worked_hours"),
                                DB::raw("contracts.prior_amount_paid as prior_amount_paid")
                            );

                            //added for soft delete
                            if ($physician_details->deleted_at != Null) {
                                $year_to_date_query[$i][$j] = $year_to_date_query[$i][$j]->join("physicians", function ($join) {
                                    $join->on("physicians.id", "=", "physician_logs.physician_id")
                                        ->on("physicians.deleted_at", ">=", "physician_logs.deleted_at");
                                });
                            }
                            $year_to_date_query[$i][$j] = $year_to_date_query[$i][$j]->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                                ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                                ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                                ->join('physician_practice_history', 'physician_practice_history.physician_id', '=', 'physician_logs.physician_id')
                                ->join('specialties', 'specialties.id', '=', 'physician_practice_history.specialty_id')
                                ->join("practices", function ($join) {
                                    $join->on("physician_practice_history.practice_id", "=", "practices.id")
                                        ->on("practices.hospital_id", "=", "agreements.hospital_id");
                                })
                                ->where('contracts.agreement_id', '=', $agreement->id)
                                ->where("physician_practice_history.physician_id", "=", $physician)
                                ->where("practices.id", "=", $practice)
                                ->where("physician_logs.practice_id", "=", $practice)
                                ->whereBetween('physician_logs.date', array($end_month_date, $last_date_month));
                            if ($contract_type_id != -1) {
                                $year_to_date_query[$i][$j] = $year_to_date_query[$i][$j]->where("contract_types.id", "=", $contract_type_id);
                            }
                            //added for soft delete
                            if ($physician_details->deleted_at == Null) {
                                $year_to_date_query[$i][$j] = $year_to_date_query[$i][$j]->where('physician_logs.deleted_at', '=', Null);
                            }
                            $year_to_date_query[$i][$j] = $year_to_date_query[$i][$j]->where("contracts.contract_name_id", "=", $contract_name_id)
                                ->groupBy('physician_practice_history.physician_id')
                                ->orderBy('contract_types.name', 'asc')
                                ->orderBy('practices.name', 'asc')
                                ->orderBy('physician_practice_history.last_name', 'asc')
                                ->orderBy('physician_practice_history.first_name', 'asc');

                            $year_to_date_query2[$i][$j] = DB::table('physician_practice_history')->select(
                                DB::raw("practices.name as practice_name"),
                                DB::raw("practices.id as practice_id"),
                                DB::raw("physician_practice_history.physician_id as physician_id"),
                                DB::raw("concat(physician_practice_history.last_name, ', ', physician_practice_history.first_name) as physician_name"),
                                DB::raw("specialties.name as specialty_name"),
                                DB::raw("contracts.id as contract_id"),
                                DB::raw("contracts.contract_name_id as contract_name_id"),
                                DB::raw("contracts.contract_type_id as contract_type_id"),
                                DB::raw("contracts.payment_type_id as payment_type_id"),
                                DB::raw("contracts.partial_hours as partial_hours"),
                                DB::raw("contract_types.name as contract_name"),
                                DB::raw("contracts.min_hours as min_hours"),
                                DB::raw("contracts.max_hours as max_hours"),
                                DB::raw("contracts.annual_cap as annual_cap"),
                                DB::raw("contracts.expected_hours as expected_hours"),
                                DB::raw("contracts.rate as rate"),
                                DB::raw("contracts.weekday_rate as weekday_rate"),
                                DB::raw("contracts.weekend_rate as weekend_rate"),
                                DB::raw("contracts.holiday_rate as holiday_rate"),
                                DB::raw("contracts.on_call_rate as on_call_rate"),
                                DB::raw("contracts.called_back_rate as called_back_rate"),
                                DB::raw("contracts.called_in_rate as called_in_rate"),
                                DB::raw("contracts.on_call_process as on_call_process"),
                                DB::raw("contracts.manual_contract_end_date as manual_contract_end_date"),
                                DB::raw("contracts.wrvu_payments as wrvu_payments"),
                                DB::raw("0 as worked_hours"),
                                DB::raw("'{$contract_month}' as contract_month1"),
                                DB::raw("'{$contract_month_change_practice}' as contract_month"),
                                DB::raw("'{$contract_term}' as contract_term"),
                                DB::raw("'{$contract_month_change_practice}' as log_range"),
                                DB::raw("'{$end_month_date}' as start_date_check"),
                                DB::raw("'{$last_date_month}' as end_date_check"),
                                DB::raw("contracts.prior_worked_hours as prior_worked_hours"),
                                DB::raw("contracts.prior_amount_paid as prior_amount_paid")
                            )
                                ->join('physician_contracts', 'physician_contracts.physician_id', '=', 'physician_practice_history.physician_id')
                                ->join('contracts', 'contracts.id', '=', 'physician_contracts.contract_id')
                                ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                                ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                                ->join('specialties', 'specialties.id', '=', 'physician_practice_history.specialty_id')
                                ->join("practices", function ($join) {
                                    $join->on("physician_practice_history.practice_id", "=", "practices.id")
                                        ->on("practices.hospital_id", "=", "agreements.hospital_id");
                                })
                                ->where('contracts.agreement_id', '=', $agreement->id)
                                ->where("physician_practice_history.physician_id", "=", $physician)
                                ->where("practices.id", "=", $practice);
                            if ($contract_type_id != -1) {
                                $year_to_date_query2[$i][$j] = $year_to_date_query2[$i][$j]->where("contract_types.id", "=", $contract_type_id);
                            }
                            $year_to_date_query2[$i][$j] = $year_to_date_query2[$i][$j]->where("contracts.contract_name_id", "=", $contract_name_id)
                                ->groupBy('physician_practice_history.physician_id')
                                ->orderBy('contract_types.name', 'asc')
                                ->orderBy('practices.name', 'asc')
                                ->orderBy('physician_practice_history.last_name', 'asc')
                                ->orderBy('physician_practice_history.first_name', 'asc');
                            $j++;
                        }
                    }
                } elseif (count($practices_history) != 0) {
                    foreach ($practices_history as $practice_present) {
                        $practice = $practice_present->practice_id;
                    }
                    $year_to_date_query[$i][$j] = DB::table('physician_logs')->select(
                        DB::raw("practices.name as practice_name"),
                        DB::raw("practices.id as practice_id"),
                        DB::raw("physician_practice_history.physician_id as physician_id"),
                        DB::raw("concat(physician_practice_history.last_name, ', ', physician_practice_history.first_name) as physician_name"),
                        DB::raw("specialties.name as specialty_name"),
                        DB::raw("contracts.id as contract_id"),
                        DB::raw("contracts.contract_name_id as contract_name_id"),
                        DB::raw("contracts.contract_type_id as contract_type_id"),
                        DB::raw("contracts.payment_type_id as payment_type_id"),
                        DB::raw("contracts.partial_hours as partial_hours"),
                        DB::raw("contract_types.name as contract_name"),
                        DB::raw("contracts.min_hours as min_hours"),
                        DB::raw("contracts.max_hours as max_hours"),
                        DB::raw("contracts.annual_cap as annual_cap"),
                        DB::raw("contracts.expected_hours as expected_hours"),
                        DB::raw("contracts.rate as rate"),
                        DB::raw("contracts.weekday_rate as weekday_rate"),
                        DB::raw("contracts.weekend_rate as weekend_rate"),
                        DB::raw("contracts.holiday_rate as holiday_rate"),
                        DB::raw("contracts.on_call_rate as on_call_rate"),
                        DB::raw("contracts.called_back_rate as called_back_rate"),
                        DB::raw("contracts.called_in_rate as called_in_rate"),
                        DB::raw("contracts.on_call_process as on_call_process"),
                        DB::raw("contracts.manual_contract_end_date as manual_contract_end_date"),
                        DB::raw("contracts.wrvu_payments as wrvu_payments"),
                        DB::raw("sum(physician_logs.duration) as worked_hours"),
                        DB::raw("'{$contract_month}' as contract_month1"),
                        DB::raw("'{$contract_month1}' as contract_month"),
                        DB::raw("'{$contract_term}' as contract_term"),
                        DB::raw("'{$contract_month}' as log_range"),
                        DB::raw("'{$end_month_date}' as start_date_check"),
                        DB::raw("'{$last_date_month}' as end_date_check"),
                        DB::raw("contracts.prior_worked_hours as prior_worked_hours"),
                        DB::raw("contracts.prior_amount_paid as prior_amount_paid")
                    );

                    //added for soft delete
                    if ($physician_details->deleted_at != Null) {
                        $year_to_date_query[$i][$j] = $year_to_date_query[$i][$j]->join("physicians", function ($join) {
                            $join->on("physicians.id", "=", "physician_logs.physician_id")
                                ->on("physicians.deleted_at", ">=", "physician_logs.deleted_at");
                        });
                    }
                    $year_to_date_query[$i][$j] = $year_to_date_query[$i][$j]->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                        ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                        ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                        ->join('physician_practice_history', 'physician_practice_history.physician_id', '=', 'physician_logs.physician_id')
                        ->join('specialties', 'specialties.id', '=', 'physician_practice_history.specialty_id')
                        ->join("practices", function ($join) {
                            $join->on("physician_practice_history.practice_id", "=", "practices.id")
                                ->on("practices.hospital_id", "=", "agreements.hospital_id");
                        })
                        ->where('contracts.agreement_id', '=', $agreement->id)
                        ->where("physician_practice_history.physician_id", "=", $physician)
                        ->where("practices.id", "=", $practice)
                        ->where("physician_logs.practice_id", "=", $practice)
                        ->whereBetween('physician_logs.date', array($end_month_date, $last_date_month));
                    if ($contract_type_id != -1) {
                        $year_to_date_query[$i][$j] = $year_to_date_query[$i][$j]->where("contract_types.id", "=", $contract_type_id);
                    }
                    //added for soft delete
                    if ($physician_details->deleted_at == Null) {
                        $year_to_date_query[$i][$j] = $year_to_date_query[$i][$j]->where('physician_logs.deleted_at', '=', Null);
                    }
                    $year_to_date_query[$i][$j] = $year_to_date_query[$i][$j]->where("contracts.contract_name_id", "=", $contract_name_id)
                        ->groupBy('physician_practice_history.physician_id')
                        ->orderBy('contract_types.name', 'asc')
                        ->orderBy('practices.name', 'asc')
                        ->orderBy('physician_practice_history.last_name', 'asc')
                        ->orderBy('physician_practice_history.first_name', 'asc');

                    $year_to_date_query2[$i][$j] = DB::table('physician_practice_history')->select(
                        DB::raw("practices.name as practice_name"),
                        DB::raw("practices.id as practice_id"),
                        DB::raw("physician_practice_history.physician_id as physician_id"),
                        DB::raw("concat(physician_practice_history.last_name, ', ', physician_practice_history.first_name) as physician_name"),
                        DB::raw("specialties.name as specialty_name"),
                        DB::raw("contracts.id as contract_id"),
                        DB::raw("contracts.contract_name_id as contract_name_id"),
                        DB::raw("contracts.contract_type_id as contract_type_id"),
                        DB::raw("contracts.payment_type_id as payment_type_id"),
                        DB::raw("contracts.partial_hours as partial_hours"),
                        DB::raw("contract_types.name as contract_name"),
                        DB::raw("contracts.min_hours as min_hours"),
                        DB::raw("contracts.max_hours as max_hours"),
                        DB::raw("contracts.annual_cap as annual_cap"),
                        DB::raw("contracts.expected_hours as expected_hours"),
                        DB::raw("contracts.rate as rate"),
                        DB::raw("contracts.weekday_rate as weekday_rate"),
                        DB::raw("contracts.weekend_rate as weekend_rate"),
                        DB::raw("contracts.holiday_rate as holiday_rate"),
                        DB::raw("contracts.on_call_rate as on_call_rate"),
                        DB::raw("contracts.called_back_rate as called_back_rate"),
                        DB::raw("contracts.called_in_rate as called_in_rate"),
                        DB::raw("contracts.on_call_process as on_call_process"),
                        DB::raw("contracts.manual_contract_end_date as manual_contract_end_date"),
                        DB::raw("contracts.wrvu_payments as wrvu_payments"),
                        DB::raw("0 as worked_hours"),
                        DB::raw("'{$contract_month}' as contract_month1"),
                        DB::raw("'{$contract_month1}' as contract_month"),
                        DB::raw("'{$contract_term}' as contract_term"),
                        DB::raw("'{$contract_month}' as log_range"),
                        DB::raw("'{$end_month_date}' as start_date_check"),
                        DB::raw("'{$last_date_month}' as end_date_check"),
                        DB::raw("contracts.prior_worked_hours as prior_worked_hours"),
                        DB::raw("contracts.prior_amount_paid as prior_amount_paid")
                    )
                        ->join('physician_contracts', 'physician_contracts.physician_id', '=', 'physician_practice_history.physician_id')
                        ->join('contracts', 'contracts.id', '=', 'physician_contracts.contract_id')
                        ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                        ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                        ->join('specialties', 'specialties.id', '=', 'physician_practice_history.specialty_id')
                        ->join("practices", function ($join) {
                            $join->on("physician_practice_history.practice_id", "=", "practices.id")
                                ->on("practices.hospital_id", "=", "agreements.hospital_id");
                        })
                        ->where('contracts.agreement_id', '=', $agreement->id)
                        ->where("physician_practice_history.physician_id", "=", $physician)
                        ->where("practices.id", "=", $practice);
                    if ($contract_type_id != -1) {
                        $year_to_date_query2[$i][$j] = $year_to_date_query2[$i][$j]->where("contract_types.id", "=", $contract_type_id);
                    }
                    $year_to_date_query2[$i][$j] = $year_to_date_query2[$i][$j]->where("contracts.contract_name_id", "=", $contract_name_id)
                        ->groupBy('physician_practice_history.physician_id')
                        ->orderBy('contract_types.name', 'asc')
                        ->orderBy('practices.name', 'asc')
                        ->orderBy('physician_practice_history.last_name', 'asc')
                        ->orderBy('physician_practice_history.first_name', 'asc');
                    $j++;
                }
            }
        }
        //print_r(count($year_to_date_query));die;
        //$year_to_date_query->where('contracts.contract_type_id', '=', $contract_type_id);

        $results = new StdClass;
        $temp = 0;
        if (isset($period_query)) {
            foreach ($period_query as $period_query1) {
                $results->period[$temp] = $period_query1->first();
                if (empty($results->period[$temp])) {
                    $results->period[$temp] = $period_query2[$temp]->first();
                }
                $temp++;
            }
        } else {
            $results->period[$temp] = "";
        }
        $results->period = array_values(array_filter($results->period));

        $results->agreement_start_date = date('m/d/Y', strtotime($agreement->start_date));
        $results->agreement_end_date = date('m/d/Y', strtotime($agreement->end_date));

        //$results->year_to_month = $year_to_month_query->get();
        $results->year_to_month = array();
        $temp = 0;
        if (isset($year_to_month_query)) {
            foreach ($year_to_month_query as $key => $year_to_date_query_arr) {
                $temp1 = 0;
                foreach ($year_to_date_query_arr as $key1 => $year_to_date_query_arr2) {
                    $year_to_date_query_arr2_first = $year_to_date_query_arr2->first();
                    if (!empty($year_to_date_query_arr2_first)) {
                        if ($year_to_date_query_arr2_first->manual_contract_end_date == "0000-00-00" || strtotime($year_to_date_query_arr2_first->manual_contract_end_date) >= strtotime($year_to_date_query_arr2_first->end_date_check)) {
                            $results->year_to_month[$temp][$temp1] = $year_to_date_query_arr2_first;
                        }
                    } else {
                        $year_to_month_query2_first = $year_to_month_query2[$key][$key1]->first();
                        if (!empty($year_to_month_query2_first)) {
                            if ($year_to_month_query2_first->manual_contract_end_date == "0000-00-00" || strtotime($year_to_month_query2_first->manual_contract_end_date) >= strtotime($year_to_month_query2_first->end_date_check)) {
                                $results->year_to_month[$temp][$temp1] = $year_to_month_query2_first;
                            }
                        } else {
                            $results->year_to_month[$temp][$temp1] = $year_to_month_query2_first;
                        }
                    }
                    $temp1++;
                }

                //$queries = DB::getQueryLog();
                //$last_query = end($queries);
                //print_r($last_query);
                //echo "<pre>";
                //print_r($results->year_to_month);
                $temp++;
            }
        } else {
            $results->year_to_month[$temp][0] = "";
        }
        $results->year_to_month = array_values(array_filter($results->year_to_month));
        $i = 0;
        foreach ($results->year_to_month as $result1) {
            $results->year_to_month[$i] = array_values(array_filter($result1));
            $i++;
        }
        $results->year_to_date = array();
        $temp = 0;
        if (isset($year_to_date_query)) {
            foreach ($year_to_date_query as $key2 => $year_to_date_query_arr) {
                //$results->year_to_date[$temp] = $year_to_date_query_arr->get();
                //$temp++;
                $temp1 = 0;
                foreach ($year_to_date_query_arr as $key3 => $year_to_date_query_arr2) {
                    $year_to_date_query_arr2_first = $year_to_date_query_arr2->first();
                    if (!empty($year_to_date_query_arr2_first)) {
                        if ($year_to_date_query_arr2_first->manual_contract_end_date == "0000-00-00" || strtotime($year_to_date_query_arr2_first->manual_contract_end_date) >= strtotime($year_to_date_query_arr2_first->start_date_check)) {
                            $results->year_to_date[$temp][$temp1] = $year_to_date_query_arr2_first;
                        }
                    } else {
                        $year_to_date_query2_first = $year_to_date_query2[$key2][$key3]->first();
                        if (!empty($year_to_date_query2_first)) {
                            if ($year_to_date_query2_first->manual_contract_end_date == "0000-00-00" || strtotime($year_to_date_query2_first->manual_contract_end_date) >= strtotime($year_to_date_query2_first->start_date_check)) {
                                $results->year_to_date[$temp][$temp1] = $year_to_date_query2_first;
                            }
                        } else {
                            $results->year_to_date[$temp][$temp1] = $year_to_date_query2_first;
                        }
                    }
                    $temp1++;
                }
                $temp++;
            }
        } else {
            $results->year_to_date[$temp][0] = "";
        }
        $results->year_to_date = array_values(array_filter($results->year_to_date));
        $i = 0;

        foreach ($results->year_to_date as $result1) {
            $results->year_to_date[$i] = array_values(array_filter($result1));
            foreach ($result1 as $key => $value) {
                if ($value) {
                    if ($value->payment_type_id == PaymentType::PSA) {
                        if ($value->wrvu_payments) {
                            $value->rate = intval(Contract::getPsaRate($value->contract_id, $value->worked_hours));
                        }
                    }
                }
            }
            $i++;
        }

        foreach ($results->period as $result) {
            if (isset($result->contract_name_id)) {
                $result->contract_name = ContractName::findOrFail($result->contract_name_id)->name;

                $result->paid = DB::table("physician_payments")
                    ->where("physician_payments.physician_id", "=", $result->physician_id)
                    ->whereBetween("physician_payments.month", [$agreement_data->start_month, $agreement_data->end_month])
                    ->sum("amount");

                if ($result->payment_type_id == PaymentType::PSA) {
                    if ($result->wrvu_payments) {
                        $result->rate = intval(Contract::getPsaRate($result->contract_id, $result->worked_hours));
                    }
                }

                $result->start_period = $start_period;
                $result->end_period = $end_period;
            }
        }

        foreach ($results->year_to_month as $result1) {
            foreach ($result1 as $result) {
                if (isset($result->contract_name_id)) {
                    $result->contract_name = ContractName::findOrFail($result->contract_name_id)->name;
                    $result->paid = DB::table("physician_payments")
                        ->where("physician_payments.physician_id", "=", $result->physician_id)
                        ->where("physician_payments.agreement_id", "=", $agreement_data->id)
                        ->sum("amount");
                    if ($result->payment_type_id == PaymentType::PSA) {
                        if ($result->wrvu_payments) {
                            $result->rate = intval(Contract::getPsaRate($result->contract_id, $result->worked_hours));
                        }
                    }
                }
                $result->start_period = $start_period;
                $result->end_period = $end_period;
            }
        }

        foreach ($results->year_to_date as $result1) {
            foreach ($result1 as $result) {
                if (isset($result->contract_name_id)) {
                    $result->contract_name = ContractName::findOrFail($result->contract_name_id)->name;

                    $result->paid = DB::table("physician_payments")
                        ->where("physician_payments.physician_id", "=", $result->physician_id)
                        ->where("physician_payments.agreement_id", "=", $agreement_data->id)
                        ->sum("amount");
                    if ($result->payment_type_id == PaymentType::PSA) {
                        if ($result->wrvu_payments) {
                            $result->rate = intval(Contract::getPsaRate($result->contract_id, $result->worked_hours));
                        }
                    }
                }
                $result->start_period = $start_period;
                $result->end_period = $end_period;
            }
        }
        return $results;
    }

    protected static function getPeriodData($agreement, $contracts)
    {
        $colD_val = "-";
        $colE_val = "-";
        $colF_val = "-";
        $colH_val = 0.00;
        $colI_val = 0.00;
        $colJ_val = 0.00;
        $total_days = 0;
        $total_dues = 0.00;
        $min_hours = 0;
        $physician_pmt_status = "";
        $potential_pay = 0.00;
        $worked_hrs = 0;
        $stipend_worked_hrs = 0;
        $amount_paid_for_month = 0.00;
        $stipend_amount_paid_for_month = 0.00;
        $amount_to_be_paid_for_month = 0.00;
        $actual_hourly_rate = 0.00;
        $actual_stipend_rate = 0.00;
        $expected_hours = 0.00;
        $expected_payment = 0.00;
        $fmv_hourly = 0.00;
        $fmv_stipend = 0.00;
        $practice_data = array();
        $contract_totals = array();
        $isPerDiem = false;
        $isHourly = false;
        $isStipend = false;
        $isPsa = false;
        $perDiem = false;
        $hourly = false;
        $stipend = false;
        $psa = false;
        $isuncompensated = false;
        $uncompensated = false;
        $colAD_val = 0.00;
        $colAF_val = 0.00;
        $ex_pay = 0.00;
        $time_study = false;
        $per_unit = false;
        $isTimeStudy = false;
        $isPerUnit = false;
        $processed_contract_ids = [];
        foreach ($contracts->period as $index => $contract) {
            $is_shared_contract = false;
            $check_shared_contract = PhysicianContracts::where('contract_id', '=', $contract->contract_id)->whereNull('deleted_at')->get();
            if (count($check_shared_contract) > 1) {
                $is_shared_contract = true;
            }
            unset($action_array); //  is gone
            $action_array = array(); // is here again

            $perDiem = $contract->payment_type_id == PaymentType::PER_DIEM ? true : $perDiem;
            $hourly = $contract->payment_type_id == PaymentType::HOURLY ? true : $hourly;
            $stipend = $contract->payment_type_id == PaymentType::STIPEND ? true : $stipend;
            $psa = $contract->payment_type_id == PaymentType::PSA ? true : $psa;
            $uncompensated = $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS ? true : $uncompensated;
            $time_study = $contract->payment_type_id == PaymentType::TIME_STUDY ? true : $time_study;
            $per_unit = $contract->payment_type_id == PaymentType::PER_UNIT ? true : $per_unit;

            if (!isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id])) {
                $colD_val = "-";
                $colE_val = "-";
                $colF_val = "-";
                $colH_val = 0.00;
                $colI_val = 0.00;
                $colJ_val = 0.00;
                $colAD_val = 0.00;
                $colAF_val = 0.00;
                $total_days = 0;
                $total_dues = 0.00;
                $min_hours = 0;
                $physician_pmt_status = "";
                $potential_pay = 0.00;
                $worked_hrs = 0;
                $stipend_worked_hrs = 0;
                $amount_paid_for_month = 0.00;
                $stipend_amount_paid_for_month = 0.00;
                $amount_to_be_paid_for_month = 0.00;
                $actual_hourly_rate = 0.00;
                $actual_stipend_rate = 0.00;
                $expected_hours = 0.00;
                $expected_payment = 0.00;
                $fmv_hourly = 0.00;
                $fmv_stipend = 0.00;
                $isPerDiem = false;
                $isHourly = false;
                $isStipend = false;
                $isPsa = false;
                $isuncompensated = false;
                $perDiem_rate_type = 0;
                $isTimeStudy = false;
                $isPerUnit = false;
                $fmv_time_study = 0.00;
                $fmv_per_unit = 0.00;
            }
            $contract->month_end_date = mysql_date(date($agreement->end_date));
            $amount_paid = Amount_paid::amountPaid($contract->contract_id, $agreement->start_date, $contract->physician_id, $contract->practice_id);
            //if (isset($amount_paid->amountPaid)) {
            if (count($amount_paid) > 0) {
                $contract->amount_paid = 0;
                foreach ($amount_paid as $amount_paid) {
                    $contract->amount_paid += $amount_paid->amountPaid;
                }
                //$contract->amount_paid = $amount_paid->amountPaid;
            } else {
                $contract->amount_paid = 0;
            }
            $contract->applyFormula_startDate = $agreement->start_date;
            $formula = self::applyFormula($contract);
            if ($formula->payment_override) {
                $contract->has_override = true;
            }

            /*if($formula->payment_status){
				$practice_data[$contract->practice_id]['practice_info']["practice_pmt_status"] = 'Y';
			}*/

            /*$weekday_rate = $contract->weekday_rate;
			$weekend_rate = $contract->weekend_rate;
			$holiday_rate = $contract->holiday_rate;
			$on_call_rate = $contract->on_call_rate;
			$called_back_rate = $contract->called_back_rate;
			$called_in_rate = $contract->called_in_rate;
            $rate = $contract->rate;*/
            $weekday_rate = ContractRate::getRate($contract->contract_id, $agreement->start_date, ContractRate::WEEKDAY_RATE);
            $weekend_rate = ContractRate::getRate($contract->contract_id, $agreement->start_date, ContractRate::WEEKEND_RATE);
            $holiday_rate = ContractRate::getRate($contract->contract_id, $agreement->start_date, ContractRate::HOLIDAY_RATE);
            $on_call_rate = ContractRate::getRate($contract->contract_id, $agreement->start_date, ContractRate::ON_CALL_RATE);
            $called_back_rate = ContractRate::getRate($contract->contract_id, $agreement->start_date, ContractRate::CALLED_BACK_RATE);
            $called_in_rate = ContractRate::getRate($contract->contract_id, $agreement->start_date, ContractRate::CALLED_IN_RATE);
            $rate = ContractRate::getRate($contract->contract_id, $agreement->start_date, ContractRate::FMV_RATE);
            $uncompensated_rate = ContractRate::getRate($contract->contract_id, $agreement->start_date, contractRate::ON_CALL_UNCOMPENSATED_RATE);
            $contract_rate_type = 0; /* for non on call contracts */

            if ($weekday_rate == 0 && $weekend_rate == 0 && $holiday_rate == 0 && ($on_call_rate > 0 || $called_back_rate > 0 || $called_in_rate > 0 || ($contract->on_call_process == 1))) {

                $contract_rate_type = 2; /* for on-call, called back and called in*/
                $perDiem_rate_type = 2; /* for on-call, called back and called in*/
            } else if ($rate == 0) {
                $contract_rate_type = 1; /* for weekday, weekend, holiday */
                $perDiem_rate_type = 1; /* for weekday, weekend, holiday */
            }
            switch ($contract->payment_type_id) {
                case 2:
                    $startcol = "L";
                    $endcol = "R";
                    $hourly = true;
                    $contract_worked_hours_for_period = 0;
                    $contract_logs_for_period = PhysicianLog::select('physician_logs.*')
                        ->where("physician_logs.contract_id", "=", $contract->contract_id)
                        ->whereBetween("physician_logs.date", array($contract->start_period, $contract->end_period))
                        ->whereNull("physician_logs.deleted_at")
                        ->get();

                    if (count($contract_logs_for_period) > 0) {
                        $contract_worked_hours_for_period = $contract_logs_for_period->sum('duration');
                    }

                    //$contract->expected_payment = $contract->expected_hours * $contract->rate;
                    $contract->expected_payment = $contract->expected_hours * $rate;
                    $min_hours = $contract->min_hours;
                    $potential_pay = $contract->expected_hours * $rate;
                    $worked_hrs = $contract->worked_hours;
                    $amount_paid_for_month = $contract->amount_paid;
                    $amount_to_be_paid_for_month = ($rate * $contract_worked_hours_for_period) - $contract->amount_paid;
                    $actual_hourly_rate = $contract->worked_hours != 0 ? $contract->amount_paid / $contract->worked_hours : 0;
                    $fmv_hourly = $rate;
                    $isHourly = true;
                    break;
                case 3:
                    $startcol = "D";
                    $endcol = "K";
                    $perDiem = true;
                    $isPerDiem = true;
                    $physician_details = Physician::withTrashed()->findOrFail($contract->physician_id);
                    $physician_logs = DB::table('physician_logs')->select('physician_logs.duration', 'physician_logs.action_id', 'physician_logs.date', 'physician_logs.log_hours');
                    //added for soft delete
                    if ($physician_details->deleted_at != Null) {
                        $physician_logs = $physician_logs->join("physicians", function ($join) {
                            $join->on("physicians.id", "=", "physician_logs.physician_id")
                                ->on("physicians.deleted_at", ">=", "physician_logs.deleted_at");
                        });
                    } else {
                        $physician_logs = $physician_logs->where('physician_logs.deleted_at', '=', Null);
                    }
                    $physician_logs = $physician_logs->where('physician_logs.physician_id', '=', $contract->physician_id)
                        ->where('physician_logs.contract_id', '=', $contract->contract_id)
                        ->where('physician_logs.practice_id', '=', $contract->practice_id)
                        ->whereBetween("physician_logs.date", [mysql_date($agreement->start_date), mysql_date($agreement->end_date)])
                        ->orderBy("physician_logs.date", "DESC")
                        ->get();

                    foreach ($physician_logs as $physician_log) {

                        /**
                         * Below line of change is addded for partial shift hours by akash.
                         * Creaed new array combination for taking log_hours alongwith the duration of a log for calculation for partial shift ON.
                         */

                        if (isset($action_array[$physician_log->action_id]) && count($action_array[$physician_log->action_id]) > 0) {
                            if (isset($action_array[$physician_log->action_id]['duration'])) {
                                $action_array[$physician_log->action_id]['duration'] = $action_array[$physician_log->action_id]['duration'] + $physician_log->duration;
                                $action_array[$physician_log->action_id]['log_hours'] = $action_array[$physician_log->action_id]['log_hours'] + $physician_log->log_hours;
                            } else {
                                $action_array[$physician_log->action_id]['duration'] = $physician_log->duration;
                                $action_array[$physician_log->action_id]['log_hours'] = $physician_log->log_hours;
                            }
                        } else {
                            $action_array[$physician_log->action_id]['duration'] = $physician_log->duration;
                            $action_array[$physician_log->action_id]['log_hours'] = $physician_log->log_hours;
                        }
                    }


                    /**
                     * Below code is added for newly created action_array for looping over it and performing the partial shift calculation as well.
                     */
                    foreach ($action_array as $action_id => $data_arr) {
                        $action_name = DB::table('actions')->select('name')
                            ->where('id', '=', $action_id)
                            ->first();
                        $action_sum = $data_arr['duration'];
                        $log_hours = $data_arr['log_hours'];
                        if (strlen(strstr(strtoupper($action_name->name), "WEEKDAY")) > 0 || $action_name->name == "On-Call") {
                            $colD_val = $action_sum;
                            $colH_val = $log_hours * ($contract_rate_type == 1 ? $weekday_rate : $on_call_rate);
                        }
                        if (strlen(strstr(strtoupper($action_name->name), "WEEKEND")) > 0 || $action_name->name == "Called-Back") {
                            $colE_val = $action_sum;
                            $colI_val = $log_hours * ($contract_rate_type == 1 ? $weekend_rate : $called_back_rate);
                        }
                        if (strlen(strstr(strtoupper($action_name->name), "HOLIDAY")) > 0 || $action_name->name == "Called-In") {
                            $colF_val = $action_sum;
                            $colJ_val = $log_hours * ($contract_rate_type == 1 ? $holiday_rate : $called_in_rate);
                        }
                    }

                    $total_days = ($colD_val != "-" ? $colD_val : 0) + ($colE_val != "-" ? $colE_val : 0) + ($colF_val != "-" ? $colF_val : 0);
                    $total_dues = ($colH_val != "-" ? $colH_val : 0) + ($colI_val != "-" ? $colI_val : 0) + ($colJ_val != "-" ? $colJ_val : 0);

                    break;
                case 5:
                    $startcol = "AD";
                    $endcol = "AG";
                    $uncompensated = true;
                    $isuncompensated = true;
                    $physician_details = Physician::withTrashed()->findOrFail($contract->physician_id);
                    $physician_logs = DB::table('physician_logs')->select('physician_logs.duration', 'physician_logs.action_id', 'physician_logs.date', 'physician_logs.log_hours');
                    //added for soft delete
                    if ($physician_details->deleted_at != Null) {
                        $physician_logs = $physician_logs->join("physicians", function ($join) {
                            $join->on("physicians.id", "=", "physician_logs.physician_id")
                                ->on("physicians.deleted_at", ">=", "physician_logs.deleted_at");
                        });
                    } else {
                        $physician_logs = $physician_logs->where('physician_logs.deleted_at', '=', Null);
                    }
                    $physician_logs = $physician_logs->where('physician_logs.physician_id', '=', $contract->physician_id)
                        ->where('physician_logs.contract_id', '=', $contract->contract_id)
                        ->where('physician_logs.practice_id', '=', $contract->practice_id)
                        ->whereBetween("physician_logs.date", [mysql_date($agreement->start_date), mysql_date($agreement->end_date)])
                        ->orderBy("physician_logs.date", "DESC")
                        ->get();

                    foreach ($physician_logs as $physician_log) {

                        /**
                         * Below line of change is addded for partial shift hours by akash.
                         * Created new array combination for taking log_hours alongwith the duration of a log for calculation for partial shift ON.
                         */

                        if (isset($action_array[$physician_log->action_id]) && count($action_array[$physician_log->action_id]) > 0) {
                            if (isset($action_array[$physician_log->action_id]['duration'])) {
                                $action_array[$physician_log->action_id]['duration'] = $action_array[$physician_log->action_id]['duration'] + $physician_log->duration;
                                $action_array[$physician_log->action_id]['log_hours'] = $action_array[$physician_log->action_id]['log_hours'] + $physician_log->log_hours;
                            } else {
                                $action_array[$physician_log->action_id]['duration'] = $physician_log->duration;
                                $action_array[$physician_log->action_id]['log_hours'] = $physician_log->log_hours;
                            }
                        } else {
                            $action_array[$physician_log->action_id]['duration'] = $physician_log->duration;
                            $action_array[$physician_log->action_id]['log_hours'] = $physician_log->log_hours;
                        }
                    }

                    /**
                     * Below code is added for newly created action_array for looping over it and performing the partial shift calculation as well.
                     */
                    foreach ($action_array as $action_id => $data_arr) {
                        $action_name = DB::table('actions')->select('name')
                            ->where('id', '=', $action_id)
                            ->first();
                        $action_sum = $data_arr['duration'];
                        $log_hours = $data_arr['log_hours'];
                        if ($action_name->name == "On-Call/Uncompensated") {
                            $colAD_val = $action_sum;

                            /**
                             * Below is condition used for calculating payment based on payment ranges for the contract.
                             */
                            $total_day = $log_hours;
                            $temp_day_remaining = $total_day;
                            $temp_calculated_payment = 0.00;

                            foreach ($uncompensated_rate as $range_val_arr) {
                                $start_day = 0;
                                $end_day = 0;
                                $rate = 0.00;
                                extract($range_val_arr->toArray()); // This line will convert the key into variable to create dynamic ranges from received data.
                                if ($total_day >= $start_day) {
                                    if ($temp_day_remaining > 0) {
                                        $days_in_range = ($range_end_day - $range_start_day) + 1; // Calculating the number of days in a range.
                                        if ($temp_day_remaining < $days_in_range) {
                                            $temp_calculated_payment += $temp_day_remaining * $rate;
                                        } else {
                                            $temp_calculated_payment += $days_in_range * $rate;
                                        }
                                        $temp_day_remaining = $temp_day_remaining - $days_in_range;
                                    }
                                } else if ($temp_day_remaining >= 0) {
                                    $temp_calculated_payment += $temp_day_remaining * $rate;
                                    $temp_day_remaining = 0;
                                }
                                // Log::Info('rem', array($temp_day_remaining));
                                // Log::Info('test', array($temp_calculated_payment));
                            }
                            $colAF_val = $colAF_val + $temp_calculated_payment;
                        }
                    }

                    $total_days = ($colAD_val != "-" ? $colAD_val : 0);
                    $total_dues = ($colAF_val != "-" ? $colAF_val : 0);

                    break;
                case PaymentType::PSA:
                    $startcol = "Z";
                    $endcol = "AC";
                    $psa = true;
                    $contract->expected_payment = $contract->expected_hours * $contract->rate;
                    $hourly_rate = $contract->worked_hours != 0 ? $contract->amount_paid / $contract->worked_hours : 0;
                    $physician_pmt_status = $contract->rate >= $hourly_rate && $contract->worked_hours != 0 ? 'Y' : 'N';
                    $expected_hours = $contract->expected_hours;
                    $expected_payment = $contract->expected_payment;
                    $stipend_worked_hrs = $contract->worked_hours;
                    $stipend_amount_paid_for_month = $contract->amount_paid;
                    $actual_stipend_rate = $contract->worked_hours > 0 ? $contract->amount_paid / $contract->worked_hours : $contract->amount_paid;
                    $fmv_stipend = $rate;
                    $isPsa = true;
                    break;
                case 7:
                    $startcol = "AH";
                    $endcol = "AN";
                    $time_study = true;
                    $contract->expected_payment = $contract->expected_hours * $rate;
                    $hourly_rate = $contract->worked_hours != 0 ? $contract->amount_paid / $contract->worked_hours : 0;
                    $physician_pmt_status = $rate >= $hourly_rate && $contract->worked_hours != 0 ? 'Y' : 'N';
                    $expected_hours = $contract->expected_hours;
                    $expected_payment = $contract->expected_payment;
                    $stipend_worked_hrs = $contract->worked_hours;
                    $stipend_amount_paid_for_month = $contract->amount_paid;
                    $actual_stipend_rate = $contract->worked_hours > 0 ? $contract->amount_paid / $contract->worked_hours : $contract->amount_paid;
                    $fmv_time_study = $rate;
                    $isTimeStudy = true;
                    break;
                case 8:
                    $startcol = "AO";
                    $endcol = "AU";
                    $per_unit = true;

                    $contract_worked_hours_for_period = 0;
                    $contract_logs_for_period = PhysicianLog::select('physician_logs.*')
                        ->where("physician_logs.contract_id", "=", $contract->contract_id)
                        ->whereBetween("physician_logs.date", array($contract->start_period, $contract->end_period))
                        ->whereNull("physician_logs.deleted_at")
                        ->get();

                    if (count($contract_logs_for_period) > 0) {
                        $contract_worked_hours_for_period = $contract_logs_for_period->sum('duration');
                    }

                    $contract->expected_payment = $contract->expected_hours * $rate;
                    $min_hours = $contract->min_hours;
                    $potential_pay = $contract->expected_hours * $rate;
                    $worked_hrs = $contract->worked_hours;
                    $amount_paid_for_month = $contract->amount_paid;
                    $amount_to_be_paid_for_month = ($rate * $contract_worked_hours_for_period) - $contract->amount_paid;
                    $actual_hourly_rate = $contract->worked_hours != 0 ? $contract->amount_paid / $contract->worked_hours : 0;
                    $fmv_per_unit = $rate;
                    $isPerUnit = true;
                    break;
                default:
                    $startcol = "S";
                    $endcol = "Y";
                    $stipend = true;
                    //$contract->expected_payment = $contract->expected_hours * $contract->rate;
                    $contract->expected_payment = $contract->expected_hours * $rate;
                    $hourly_rate = $contract->worked_hours != 0 ? $contract->amount_paid / $contract->worked_hours : 0;
                    $physician_pmt_status = $rate >= $hourly_rate && $contract->worked_hours != 0 ? 'Y' : 'N';
                    $expected_hours = $contract->expected_hours;
                    $expected_payment = $contract->expected_payment;
                    $stipend_worked_hrs = $contract->worked_hours;
                    $stipend_amount_paid_for_month = $contract->amount_paid;
                    $actual_stipend_rate = $contract->worked_hours > 0 ? $contract->amount_paid / $contract->worked_hours : $contract->amount_paid;
                    $fmv_stipend = $rate;
                    $isStipend = true;
                    break;
            }

            $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id] = ["physician_name" => $contract->physician_name,
                "isPerDiem" => $isPerDiem,
                "isHourly" => $isHourly,
                "isStipend" => $isStipend,
                "isuncompensated" => $isuncompensated,
                "isPsa" => $isPsa,
                "perDiem_rate_type" => $perDiem_rate_type,
                "colD" => $colD_val,
                "colE" => $colE_val,
                "colF" => $colF_val,
                "colG" => $total_days,
                "colH" => $colH_val,
                "colI" => $colI_val,
                "colJ" => $colJ_val,
                "colK" => $total_dues,
                "colAD" => $colAD_val,
                "colAE" => $total_days,
                "colAF" => ($is_shared_contract) ? 0.00 : $colAF_val,
                "colAG" => ($is_shared_contract) ? 0.00 : $total_dues,
                "min_hours" => ($is_shared_contract) ? 0.00 : $min_hours,
                "potential_pay" => ($is_shared_contract) ? 0.00 : $potential_pay,
                "worked_hrs" => $worked_hrs,
                "amount_paid_for_month" => ($is_shared_contract) ? 0.00 : $amount_paid_for_month,
                "amount_to_be_paid_for_month" => ($is_shared_contract) ? 0.00 : $amount_to_be_paid_for_month,
                "actual_hourly_rate" => ($is_shared_contract) ? 0.00 : $actual_hourly_rate,
                "FMV_hourly" => ($is_shared_contract) ? 0.00 : $fmv_hourly,
                "PMT_status" => $physician_pmt_status,
                "expected_hours" => ($is_shared_contract) ? 0.00 : $expected_hours,
                "expected_payment" => ($is_shared_contract) ? 0.00 : $expected_payment,
                "stipend_worked_hrs" => $stipend_worked_hrs,
                "stipend_amount_paid_for_month" => ($is_shared_contract) ? 0.00 : $stipend_amount_paid_for_month,
                "actual_stipend_rate" => ($is_shared_contract) ? 0.00 : $actual_stipend_rate,
                "fmv_stipend" => ($is_shared_contract) ? 0.00 : $fmv_stipend,
                "isTimeStudy" => $isTimeStudy,
                "isPerUnit" => $isPerUnit,
                "fmv_time_study" => ($is_shared_contract) ? 0.00 : $fmv_time_study,
                "fmv_per_unit" => ($is_shared_contract) ? 0.00 : $fmv_per_unit
            ];

            if (!in_array($contract->contract_id, $processed_contract_ids)) {

                // Expected Hours block
                if (isset($practice_data[$contract->practice_id]['practice_info']["expected_hours"])) {
                    $expected_practice_total = $practice_data[$contract->practice_id]['practice_info']["expected_hours"] + $expected_hours;
                } else {
                    $expected_practice_total = $expected_hours;
                }

                // Expected Payment block
                if (isset($practice_data[$contract->practice_id]['practice_info']["expected_payment"])) {
                    $expected_practice_pmt_total = $practice_data[$contract->practice_id]['practice_info']["expected_payment"] + $expected_payment;
                } else {
                    $expected_practice_pmt_total = $expected_payment;
                }

                // Expected fmv rate block
                if (isset($practice_data[$contract->practice_id]['practice_info']["fmv_stipend"])) {
                    $expected_practice_fmv_stipend_total = $practice_data[$contract->practice_id]['practice_info']["fmv_stipend"] + $fmv_stipend;
                } else {
                    $expected_practice_fmv_stipend_total = $fmv_stipend;
                }

                // Expected fmv rate time study block
                if (isset($practice_data[$contract->practice_id]['practice_info']["fmv_time_study"])) {
                    $practice_fmv_time_study = $practice_data[$contract->practice_id]['practice_info']["fmv_time_study"] + $fmv_time_study;
                } else {
                    $practice_fmv_time_study = $fmv_time_study;
                }

                // Expected fmv rate per unit block
                if (isset($practice_data[$contract->practice_id]['practice_info']["fmv_per_unit"])) {
                    $practice_fmv_per_unit = $practice_data[$contract->practice_id]['practice_info']["fmv_per_unit"] + $fmv_per_unit;
                } else {
                    $practice_fmv_per_unit = $fmv_per_unit;
                }

                // Total contracts Expected Hours block
                if (isset($contract_totals["expected_hours"])) {
                    $contract_expected_hours = $contract_totals["expected_hours"] + $expected_hours;
                } else {
                    $contract_expected_hours = $expected_hours;
                }

                // Contract Expected Payment block
                if (isset($contract_totals["expected_payment"])) {
                    $contract_expected_pmt_total = $contract_totals["expected_payment"] + $expected_payment;
                } else {
                    $contract_expected_pmt_total = $expected_payment;
                }

                // Contract FMV Stipend block
                if (isset($contract_totals["fmv_stipend"])) {
                    $contract_fmv_stipend = $contract_totals["fmv_stipend"] + $fmv_stipend;
                } else {
                    $contract_fmv_stipend = $fmv_stipend;
                }

                // Contract FMV Per Unit block
                if (isset($contract_totals["fmv_per_unit"])) {
                    $contract_fmv_per_unit = $contract_totals["fmv_per_unit"] + $fmv_per_unit;
                } else {
                    $contract_fmv_per_unit = $fmv_per_unit;
                }

                // Practice amount paid block
                if (isset($practice_data[$contract->practice_id]['practice_info']["stipend_amount_paid_for_month"])) {
                    $practice_stipend_amount_paid_for_month = $practice_data[$contract->practice_id]['practice_info']["stipend_amount_paid_for_month"] + $stipend_amount_paid_for_month;
                } else {
                    $practice_stipend_amount_paid_for_month = $stipend_amount_paid_for_month;
                }

                // Practice amount paid block
                if (isset($contract_totals["stipend_amount_paid_for_month"])) {
                    $contract_stipend_amount_paid_for_month = $contract_totals["stipend_amount_paid_for_month"] + $stipend_amount_paid_for_month;
                } else {
                    $contract_stipend_amount_paid_for_month = $stipend_amount_paid_for_month;
                }

                // Practice min hours block
                if (isset($practice_data[$contract->practice_id]['practice_info']["min_hours"])) {
                    $practice_min_hours = $practice_data[$contract->practice_id]['practice_info']["min_hours"] + $min_hours;
                } else {
                    $practice_min_hours = $min_hours;
                }

                // Practice potential pay block
                if (isset($practice_data[$contract->practice_id]['practice_info']["potential_pay"])) {
                    $practice_potential_pay = $practice_data[$contract->practice_id]['practice_info']["potential_pay"] + $potential_pay;
                } else {
                    $practice_potential_pay = $potential_pay;
                }

                // Practice amount paid for month block
                if (isset($practice_data[$contract->practice_id]['practice_info']["amount_paid_for_month"])) {
                    $practice_amount_paid_for_month = $practice_data[$contract->practice_id]['practice_info']["amount_paid_for_month"] + $amount_paid_for_month;
                } else {
                    $practice_amount_paid_for_month = $amount_paid_for_month;
                }

                // Practice amount to be paid for month block
                if (isset($practice_data[$contract->practice_id]['practice_info']["amount_to_be_paid_for_month"])) {
                    $practice_amount_to_be_paid_for_month = $practice_data[$contract->practice_id]['practice_info']["amount_to_be_paid_for_month"] + $amount_to_be_paid_for_month;
                } else {
                    $practice_amount_to_be_paid_for_month = $amount_to_be_paid_for_month;
                }

                // Practice fmv hourly block
                if (isset($practice_data[$contract->practice_id]['practice_info']["FMV_hourly"])) {
                    $practice_fmv_hourly = $practice_data[$contract->practice_id]['practice_info']["FMV_hourly"] + $fmv_hourly;
                } else {
                    $practice_fmv_hourly = $fmv_hourly;
                }

                // Contract min hours block
                if (isset($contract_totals["min_hours"])) {
                    $contract_min_hours = $contract_totals["min_hours"] + $min_hours;
                } else {
                    $contract_min_hours = $min_hours;
                }

                // Contract potential pay block
                if (isset($contract_totals["potential_pay"])) {
                    $contract_potential_pay = $contract_totals["potential_pay"] + $potential_pay;
                } else {
                    $contract_potential_pay = $potential_pay;
                }

                // Contract amount paid for month block
                if (isset($contract_totals["amount_paid_for_month"])) {
                    $contract_amount_paid_for_month = $contract_totals["amount_paid_for_month"] + $amount_paid_for_month;
                } else {
                    $contract_amount_paid_for_month = $amount_paid_for_month;
                }

                // Contract amount to be paid for month block
                if (isset($contract_totals["amount_to_be_paid_for_month"])) {
                    $contract_amount_to_be_paid_for_month = $contract_totals["amount_to_be_paid_for_month"] + $amount_to_be_paid_for_month;
                } else {
                    $contract_amount_to_be_paid_for_month = $amount_to_be_paid_for_month;
                }

                // Contract fmv hourly block
                if (isset($contract_totals["FMV_hourly"])) {
                    $contract_fmv_hourly = $contract_totals["FMV_hourly"] + $fmv_hourly;
                } else {
                    $contract_fmv_hourly = $fmv_hourly;
                }

                // Contract fmv time study block
                if (isset($contract_totals["fmv_time_study"])) {
                    $contract_fmv_time_study = $contract_totals["fmv_time_study"] + $fmv_time_study;
                } else {
                    $contract_fmv_time_study = $fmv_time_study;
                }
            } else {
                // Expected Hours block
                if (isset($practice_data[$contract->practice_id]['practice_info']["expected_hours"])) {
                    $expected_practice_total = $practice_data[$contract->practice_id]['practice_info']["expected_hours"];
                } else {
                    $expected_practice_total = $expected_hours;
                }

                // Expected Payment block
                if (isset($practice_data[$contract->practice_id]['practice_info']["expected_payment"])) {
                    $expected_practice_pmt_total = $practice_data[$contract->practice_id]['practice_info']["expected_payment"];
                } else {
                    $expected_practice_pmt_total = $expected_payment;
                }

                // Expected fmv rate block
                if (isset($practice_data[$contract->practice_id]['practice_info']["fmv_stipend"])) {
                    $expected_practice_fmv_stipend_total = $practice_data[$contract->practice_id]['practice_info']["fmv_stipend"];
                } else {
                    $expected_practice_fmv_stipend_total = $fmv_stipend;
                }

                // Expected fmv rate time study block
                if (isset($practice_data[$contract->practice_id]['practice_info']["fmv_time_study"])) {
                    $practice_fmv_time_study = $practice_data[$contract->practice_id]['practice_info']["fmv_time_study"];
                } else {
                    $practice_fmv_time_study = $fmv_time_study;
                }

                // Expected fmv rate per unit block
                if (isset($practice_data[$contract->practice_id]['practice_info']["fmv_per_unit"])) {
                    $practice_fmv_per_unit = $practice_data[$contract->practice_id]['practice_info']["fmv_per_unit"];
                } else {
                    $practice_fmv_per_unit = $fmv_per_unit;
                }

                // Total contracts Expected Hours block
                if (isset($contract_totals["expected_hours"])) {
                    $contract_expected_hours = $contract_totals["expected_hours"];
                } else {
                    $contract_expected_hours = $expected_hours;
                }

                // Contract Expected Payment block
                if (isset($contract_totals["expected_payment"])) {
                    $contract_expected_pmt_total = $contract_totals["expected_payment"];
                } else {
                    $contract_expected_pmt_total = $expected_payment;
                }

                // Contract FMV Stipend block
                if (isset($contract_totals["fmv_stipend"])) {
                    $contract_fmv_stipend = $contract_totals["fmv_stipend"];
                } else {
                    $contract_fmv_stipend = $fmv_stipend;
                }

                // Contract FMV Per Unit block
                if (isset($contract_totals["fmv_per_unit"])) {
                    $contract_fmv_per_unit = $contract_totals["fmv_per_unit"];
                } else {
                    $contract_fmv_per_unit = $fmv_per_unit;
                }

                // Practice amount paid block
                if (isset($practice_data[$contract->practice_id]['practice_info']["stipend_amount_paid_for_month"])) {
                    $practice_stipend_amount_paid_for_month = $practice_data[$contract->practice_id]['practice_info']["stipend_amount_paid_for_month"];
                } else {
                    $practice_stipend_amount_paid_for_month = $stipend_amount_paid_for_month;
                }

                // Practice amount paid block
                if (isset($contract_totals["stipend_amount_paid_for_month"])) {
                    $contract_stipend_amount_paid_for_month = $contract_totals["stipend_amount_paid_for_month"];
                } else {
                    $contract_stipend_amount_paid_for_month = $stipend_amount_paid_for_month;
                }

                // Practice min hours block
                if (isset($practice_data[$contract->practice_id]['practice_info']["min_hours"])) {
                    $practice_min_hours = $practice_data[$contract->practice_id]['practice_info']["min_hours"];
                } else {
                    $practice_min_hours = $min_hours;
                }

                // Practice potential pay block
                if (isset($practice_data[$contract->practice_id]['practice_info']["potential_pay"])) {
                    $practice_potential_pay = $practice_data[$contract->practice_id]['practice_info']["potential_pay"];
                } else {
                    $practice_potential_pay = $potential_pay;
                }

                // Practice amount paid for month block
                if (isset($practice_data[$contract->practice_id]['practice_info']["amount_paid_for_month"])) {
                    $practice_amount_paid_for_month = $practice_data[$contract->practice_id]['practice_info']["amount_paid_for_month"];
                } else {
                    $practice_amount_paid_for_month = $amount_paid_for_month;
                }

                // Practice amount to be paid for month block
                if (isset($practice_data[$contract->practice_id]['practice_info']["amount_to_be_paid_for_month"])) {
                    $practice_amount_to_be_paid_for_month = $practice_data[$contract->practice_id]['practice_info']["amount_to_be_paid_for_month"];
                } else {
                    $practice_amount_to_be_paid_for_month = $amount_to_be_paid_for_month;
                }

                // Practice fmv hourly block
                if (isset($practice_data[$contract->practice_id]['practice_info']["FMV_hourly"])) {
                    $practice_fmv_hourly = $practice_data[$contract->practice_id]['practice_info']["FMV_hourly"];
                } else {
                    $practice_fmv_hourly = $fmv_hourly;
                }

                // Contract min hours block
                if (isset($contract_totals["min_hours"])) {
                    $contract_min_hours = $contract_totals["min_hours"];
                } else {
                    $contract_min_hours = $min_hours;
                }

                // Contract potential pay block
                if (isset($contract_totals["potential_pay"])) {
                    $contract_potential_pay = $contract_totals["potential_pay"];
                } else {
                    $contract_potential_pay = $potential_pay;
                }

                // Contract amount paid for month block
                if (isset($contract_totals["amount_paid_for_month"])) {
                    $contract_amount_paid_for_month = $contract_totals["amount_paid_for_month"];
                } else {
                    $contract_amount_paid_for_month = $amount_paid_for_month;
                }

                // Contract amount to be paid for month block
                if (isset($contract_totals["amount_to_be_paid_for_month"])) {
                    $contract_amount_to_be_paid_for_month = $contract_totals["amount_to_be_paid_for_month"];
                } else {
                    $contract_amount_to_be_paid_for_month = $amount_to_be_paid_for_month;
                }

                // Contract fmv hourly block
                if (isset($contract_totals["FMV_hourly"])) {
                    $contract_fmv_hourly = $contract_totals["FMV_hourly"];
                } else {
                    $contract_fmv_hourly = $fmv_hourly;
                }

                // Contract fmv time study block
                if (isset($contract_totals["fmv_time_study"])) {
                    $contract_fmv_time_study = $contract_totals["fmv_time_study"];
                } else {
                    $contract_fmv_time_study = $fmv_time_study;
                }
            }

            if (isset($practice_data[$contract->practice_id]['practice_info']["colD"])) {
                if ($practice_data[$contract->practice_id]['practice_info']["colD"] != '-' && $colD_val != '-') {
                    $practice_colD = $practice_data[$contract->practice_id]['practice_info']["colD"] + $colD_val;
                } else if ($colD_val != '-') {
                    $practice_colD = $colD_val;
                } else {
                    $practice_colD = $practice_data[$contract->practice_id]['practice_info']["colD"];
                }
            } else {
                $practice_colD = $colD_val;
            }

            if (isset($practice_data[$contract->practice_id]['practice_info']["colE"])) {
                if ($practice_data[$contract->practice_id]['practice_info']["colE"] != '-' && $colE_val != '-') {
                    $practice_colE = $practice_data[$contract->practice_id]['practice_info']["colE"] + $colE_val;
                } else if ($colE_val != '-') {
                    $practice_colE = $colE_val;
                } else {
                    $practice_colE = $practice_data[$contract->practice_id]['practice_info']["colE"];
                }
            } else {
                $practice_colE = $colE_val;
            }

            if (isset($practice_data[$contract->practice_id]['practice_info']["colF"])) {
                if ($practice_data[$contract->practice_id]['practice_info']["colF"] != '-' && $colF_val != '-') {
                    $practice_colF = $practice_data[$contract->practice_id]['practice_info']["colF"] + $colF_val;
                } else if ($colF_val != '-') {
                    $practice_colF = $colF_val;
                } else {
                    $practice_colF = $practice_data[$contract->practice_id]['practice_info']["colF"];
                }
            } else {
                $practice_colF = $colF_val;
            }

            // Below code is commented you can  use below code to set the practices H,I,J,K values.
            /*
            if(isset($practice_data[$contract->practice_id]['practice_info']["colH"])){
                if($practice_data[$contract->practice_id]['practice_info']["colH"] != '-' && $colH_val != '-'){
                    if($colH_val > $practice_data[$contract->practice_id]['practice_info']["colH"]){
                        $practice_colH = $colH_val;
                    }else {
                        $practice_colH = $practice_data[$contract->practice_id]['practice_info']["colH"];
                    }
                } else {
                    $practice_colH = $practice_data[$contract->practice_id]['practice_info']["colH"] ;
                }
            } else {
                $practice_colH = $colH_val;
            }

            if(isset($practice_data[$contract->practice_id]['practice_info']["colI"])){
                if($practice_data[$contract->practice_id]['practice_info']["colI"] != '-' && $colI_val != '-'){
                    if($colI_val > $practice_data[$contract->practice_id]['practice_info']["colI"] ){
                        $practice_colI = $colI_val;
                    }else {
                        $practice_colI = $practice_data[$contract->practice_id]['practice_info']["colI"];
                    }
                } else {
                    $practice_colI = $practice_data[$contract->practice_id]['practice_info']["colI"];
                }
            } else {
                $practice_colI = $colI_val;
            }

            if(isset($practice_data[$contract->practice_id]['practice_info']["colJ"])){
                if($practice_data[$contract->practice_id]['practice_info']["colJ"] != '-' && $colJ_val != '-'){
                    if($colJ_val > $practice_data[$contract->practice_id]['practice_info']["colJ"] ){
                        $practice_colJ = $colJ_val;
                    }else {
                        $practice_colJ = $practice_data[$contract->practice_id]['practice_info']["colJ"];
                    }
                } else {
                    $practice_colJ = $practice_data[$contract->practice_id]['practice_info']["colJ"];
                }
            } else {
                $practice_colJ = $colJ_val;
            }

            if(isset($practice_data[$contract->practice_id]['practice_info']["colK"])){
                if($practice_data[$contract->practice_id]['practice_info']["colK"] != '-' && $total_dues != '-'){
                    if($total_dues > $practice_data[$contract->practice_id]['practice_info']["colK"]){
                        $practice_colK = $total_dues;
                    }else {
                        $practice_colK = $practice_data[$contract->practice_id]['practice_info']["colK"];
                    }
                } else {
                    $practice_colK = $practice_data[$contract->practice_id]['practice_info']["colK"];
                }
            } else {
                $practice_colK = $total_dues;
            }
            */

            if (isset($practice_data[$contract->practice_id]['practice_info']["colAD"])) {
                if ($practice_data[$contract->practice_id]['practice_info']["colAD"] != '-' && $colAD_val != '-') {
                    $practice_colAD = $practice_data[$contract->practice_id]['practice_info']["colAD"] + $colAD_val;
                } else if ($colAD_val != '-') {
                    $practice_colAD = $colAD_val;
                } else {
                    $practice_colAD = $practice_data[$contract->practice_id]['practice_info']["colAD"];
                }
            } else {
                $practice_colAD = $colAD_val;
            }

            if (isset($contract_totals["colD"])) {
                if ($contract_totals["colD"] != '-' && $colD_val != '-') {
                    $contract_colD = $contract_totals["colD"] + $colD_val;
                } else if ($colD_val != '-') {
                    $contract_colD = $colD_val;
                } else {
                    $contract_colD = $contract_totals["colD"];
                }
            } else {
                $contract_colD = $colD_val;
            }

            if (isset($contract_totals["colE"])) {
                if ($contract_totals["colE"] != '-' && $colE_val != '-') {
                    $contract_colE = $contract_totals["colE"] + $colE_val;
                } else if ($colE_val != '-') {
                    $contract_colE = $colE_val;
                } else {
                    $contract_colE = $contract_totals["colE"];
                }
            } else {
                $contract_colE = $colE_val;
            }

            if (isset($contract_totals["colF"])) {
                if ($contract_totals["colF"] != '-' && $colF_val != '-') {
                    $contract_colF = $contract_totals["colF"] + $colF_val;
                } else if ($colF_val != '-') {
                    $contract_colF = $colF_val;
                } else {
                    $contract_colF = $contract_totals["colF"];
                }
            } else {
                $contract_colF = $colF_val;
            }

            // Below code is commented you can  use below code to set the contracts H,I,J,K values.
            /*
            if(isset($contract_totals["colH"])){
                if($contract_totals["colH"] != '-' && $colH_val != '-'){
                    if($colH_val > $contract_totals["colH"]){
                        $contract_colH = $colH_val;
                    }else {
                        $contract_colH = $contract_totals["colH"];
                    }
                } else {
                    $contract_colH = $contract_totals["colH"];
                }
            } else {
                $contract_colH = $colH_val;
            }

            if(isset($contract_totals["colI"])){
                if($contract_totals["colI"] != '-' && $colI_val != '-'){
                    if($colI_val > $contract_totals["colI"]){
                        $contract_colI = $colI_val;
                    }else {
                        $contract_colI = $contract_totals["colI"];
                    }
                } else {
                    $contract_colI = $contract_totals["colI"];
                }
            } else {
                $contract_colI = $colI_val;
            }

            if(isset($contract_totals["colJ"])){
                if($contract_totals["colJ"] != '-' && $colJ_val != '-'){
                    if($colJ_val > $contract_totals["colJ"]){
                        $contract_colJ = $colJ_val;
                    }else {
                        $contract_colJ = $contract_totals["colJ"];
                    }
                } else {
                    $contract_colJ = $contract_totals["colJ"];
                }
            } else {
                $contract_colJ = $colJ_val;
            }

            if(isset($contract_totals["colK"])){
                if($contract_totals["colK"] != '-' && $total_dues != '-'){
                    if($total_dues > $contract_totals["colK"]){
                        $contract_colK = $total_dues;
                    }else {
                        $contract_colK = $contract_totals["colK"];
                    }
                } else {
                    $contract_colK = $contract_totals["colK"];
                }
            } else {
                $contract_colK = $total_dues;
            }
            */

            if (isset($contract_totals["colAD"])) {
                if ($contract_totals["colAD"] != '-' && $colAD_val != '-') {
                    $contract_colAD = $contract_totals["colAD"] + $colAD_val;
                } else if ($colAD_val != '-') {
                    $contract_colAD = $colAD_val;
                } else {
                    $contract_colAD = $contract_totals["colAD"];
                }
            } else {
                $contract_colAD = $colAD_val;
            }

            if (isset($practice_data[$contract->practice_id]['practice_info']["worked_hrs"])) {
                $practice_worked_hours = $practice_data[$contract->practice_id]['practice_info']["worked_hrs"] + $worked_hrs;
            } else {
                $practice_worked_hours = $worked_hrs;
            }

            if (isset($contract_totals["worked_hrs"])) {
                $contract_worked_hours = $contract_totals["worked_hrs"] + $worked_hrs;
            } else {
                $contract_worked_hours = $worked_hrs;
            }

            if ($contract->payment_type_id == PaymentType::HOURLY || $contract->payment_type_id == PaymentType::PER_UNIT) {
                if ($practice_worked_hours != 0) {
                    $practice_actual_hourly_rate = $practice_amount_paid_for_month / $practice_worked_hours;
                } else {
                    $practice_actual_hourly_rate = $actual_hourly_rate;
                }

            } else {
                $practice_actual_hourly_rate = $actual_hourly_rate;
            }

            if ($contract->payment_type_id == PaymentType::HOURLY || $contract->payment_type_id == PaymentType::PER_UNIT) {
                if ($practice_worked_hours != 0) {
                    $contract_actual_hourly_rate = $contract_amount_paid_for_month / $contract_worked_hours;
                } else {
                    $contract_actual_hourly_rate = $actual_hourly_rate;
                }

            } else {
                $contract_actual_hourly_rate = $actual_hourly_rate;
            }

            $practice_data[$contract->practice_id]['practice_info'] = [
                "practice_name" => $contract->practice_name,
                "isPerDiem" => isset($practice_data[$contract->practice_id]['practice_info']["isPerDiem"]) ? !$practice_data[$contract->practice_id]['practice_info']["isPerDiem"] ? $isPerDiem : $practice_data[$contract->practice_id]['practice_info']["isPerDiem"] : $isPerDiem,
                "isHourly" => isset($practice_data[$contract->practice_id]['practice_info']["isHourly"]) ? !$practice_data[$contract->practice_id]['practice_info']["isHourly"] ? $isHourly : $practice_data[$contract->practice_id]['practice_info']["isHourly"] : $isHourly,
                "isStipend" => isset($practice_data[$contract->practice_id]['practice_info']["isStipend"]) ? !$practice_data[$contract->practice_id]['practice_info']["isStipend"] ? $isStipend : $practice_data[$contract->practice_id]['practice_info']["isStipend"] : $isStipend,
                "isuncompensated" => isset($practice_data[$contract->practice_id]['practice_info']["isuncompensated"]) ? !$practice_data[$contract->practice_id]['practice_info']["isuncompensated"] ? $isuncompensated : $practice_data[$contract->practice_id]['practice_info']["isuncompensated"] : $isuncompensated,
                "isPsa" => isset($practice_data[$contract->practice_id]['practice_info']["isPsa"]) ? !$practice_data[$contract->practice_id]['practice_info']["isPsa"] ? $isStipend : $practice_data[$contract->practice_id]['practice_info']["isPsa"] : $isPsa,
                "isTimeStudy" => isset($practice_data[$contract->practice_id]['practice_info']["isTimeStudy"]) ? !$practice_data[$contract->practice_id]['practice_info']["isTimeStudy"] ? $isTimeStudy : $practice_data[$contract->practice_id]['practice_info']["isTimeStudy"] : $isTimeStudy,
                "isPerUnit" => isset($practice_data[$contract->practice_id]['practice_info']["isPerUnit"]) ? !$practice_data[$contract->practice_id]['practice_info']["isPerUnit"] ? $isPerUnit : $practice_data[$contract->practice_id]['practice_info']["isPerUnit"] : $isPerUnit,
                "practice_pmt_status" => $formula->payment_status ? 'Y' : 'N',
                "colD" => $practice_colD,
                "colE" => $practice_colE,
                "colF" => $practice_colF,
                "colG" => isset($practice_data[$contract->practice_id]['practice_info']["colG"]) ? $practice_data[$contract->practice_id]['practice_info']["colG"] + $total_days : $total_days,
                "colH" => isset($practice_data[$contract->practice_id]['practice_info']["colH"]) ? $practice_data[$contract->practice_id]['practice_info']["colH"] + $colH_val : $colH_val,
                "colI" => isset($practice_data[$contract->practice_id]['practice_info']["colI"]) ? $practice_data[$contract->practice_id]['practice_info']["colI"] + $colI_val : $colI_val,
                "colJ" => isset($practice_data[$contract->practice_id]['practice_info']["colJ"]) ? $practice_data[$contract->practice_id]['practice_info']["colJ"] + $colJ_val : $colJ_val,
                "colK" => isset($practice_data[$contract->practice_id]['practice_info']["colK"]) ? $practice_data[$contract->practice_id]['practice_info']["colK"] + $total_dues : $total_dues,
                "colAD" => $practice_colAD,
                "colAE" => isset($practice_data[$contract->practice_id]['practice_info']["colAE"]) ? $practice_data[$contract->practice_id]['practice_info']["colAE"] + $total_days : $total_days,
                "colAF" => isset($practice_data[$contract->practice_id]['practice_info']["colAF"]) ? $practice_data[$contract->practice_id]['practice_info']["colAF"] + $colAF_val : $colAF_val,
                "colAG" => isset($practice_data[$contract->practice_id]['practice_info']["colAG"]) ? $practice_data[$contract->practice_id]['practice_info']["colAG"] + $total_dues : $total_dues,
                "min_hours" => $practice_min_hours,
                "potential_pay" => $practice_potential_pay,
                "worked_hrs" => $practice_worked_hours,
                "amount_paid_for_month" => $practice_amount_paid_for_month,
                "amount_to_be_paid_for_month" => $practice_amount_to_be_paid_for_month,
                "actual_hourly_rate" => $practice_actual_hourly_rate,
                "FMV_hourly" => $practice_fmv_hourly,
                "expected_hours" => $expected_practice_total,
                "expected_payment" => $expected_practice_pmt_total,
                "stipend_worked_hrs" => isset($practice_data[$contract->practice_id]['practice_info']["stipend_worked_hrs"]) ? $practice_data[$contract->practice_id]['practice_info']["stipend_worked_hrs"] + $stipend_worked_hrs : $stipend_worked_hrs,
                "stipend_amount_paid_for_month" => $practice_stipend_amount_paid_for_month,
                "actual_stipend_rate" => isset($practice_data[$contract->practice_id]['practice_info']["actual_stipend_rate"]) ? $practice_data[$contract->practice_id]['practice_info']["actual_stipend_rate"] + $actual_stipend_rate : $actual_stipend_rate,
                "fmv_stipend" => $expected_practice_fmv_stipend_total,
                "fmv_time_study" => $practice_fmv_time_study,
                "fmv_per_unit" => $practice_fmv_per_unit
            ];
            $ex_pay = $ex_pay + $expected_payment;
            $contract_totals = [
                "colD" => $contract_colD,
                "colE" => $contract_colE,
                "colF" => $contract_colF,
                "colG" => isset($contract_totals["colG"]) ? $contract_totals["colG"] + $total_days : $total_days,
                "colH" => isset($contract_totals["colH"]) ? $contract_totals["colH"] + $colH_val : $colH_val,
                "colI" => isset($contract_totals["colI"]) ? $contract_totals["colI"] + $colI_val : $colI_val,
                "colJ" => isset($contract_totals["colJ"]) ? $contract_totals["colJ"] + $colJ_val : $colJ_val,
                "colK" => isset($contract_totals["colK"]) ? $contract_totals["colK"] + $total_dues : $total_dues,
                "colAD" => $contract_colAD,
                "colAE" => isset($contract_totals["colAE"]) ? $contract_totals["colAE"] + $total_days : $total_days,
                "colAF" => isset($contract_totals["colAF"]) ? $contract_totals["colAF"] + $colAF_val : $colAF_val,
                "colAG" => isset($contract_totals["colAG"]) ? $contract_totals["colAG"] + $total_dues : $total_dues,
                "min_hours" => $contract_min_hours,
                "potential_pay" => $contract_potential_pay,
                "worked_hrs" => $contract_worked_hours,
                "amount_paid_for_month" => $contract_amount_paid_for_month,
                "amount_to_be_paid_for_month" => $contract_amount_to_be_paid_for_month,
                "actual_hourly_rate" => $contract_actual_hourly_rate,
                "FMV_hourly" => $contract_fmv_hourly,
                "expected_hours" => $contract_expected_hours,
                "expected_payment" => $contract_expected_pmt_total,
                "stipend_worked_hrs" => isset($contract_totals["stipend_worked_hrs"]) ? $contract_totals["stipend_worked_hrs"] + $stipend_worked_hrs : $stipend_worked_hrs,
                "stipend_amount_paid_for_month" => $contract_stipend_amount_paid_for_month,
                "actual_stipend_rate" => isset($contract_totals["actual_stipend_rate"]) ? $contract_totals["actual_stipend_rate"] + $actual_stipend_rate : $actual_stipend_rate,
                "fmv_stipend" => $contract_fmv_stipend,
                "fmv_time_study" => $contract_fmv_time_study,
                "fmv_per_unit" => $contract_fmv_per_unit
            ];

            $processed_contract_ids[] = $contract->contract_id;
        }
        $result = [
            "practice_data" => $practice_data,
            "contract_totals" => $contract_totals,
            "perDiem" => $perDiem,
            "hourly" => $hourly,
            "stipend" => $stipend,
            "uncompensated" => $uncompensated,
            "psa" => $psa,
            "timeStudy" => $time_study,
            "perUnit" => $per_unit
        ];
        return $result;
    }

    protected static function applyFormula($contract)
    {
        $formula = new StdClass;
        $formula->days_on_call = 'N/A';
        $formula->payment_override = false;
        if (isset($contract)) {
            if ($contract->payment_type_id == PaymentType::STIPEND) {
                //print_r($contract);
                if (isset($contract->log_range)) {
                    $rate = ContractRate::getRate($contract->contract_id, $contract->applyFormula_startDate, ContractRate::FMV_RATE);
                    //$amount = $contract->rate * $contract->expected_hours * $contract->log_range;
                    $amount = $rate * $contract->expected_hours * $contract->log_range;
                } else
                    die;
                $formula->amount = round($amount);
                if (isset($contract->worked_hours) && $contract->worked_hours != 0)
                    $formula->actual_rate = round($amount / $contract->worked_hours);
                else
                    $formula->actual_rate = 0;
                /*if ($contract->contract_month > 5) {
                    $fmv = ($formula->actual_rate * 100.0) / $contract->rate;
                    $formula->payment_status = $fmv < 110;
                } else {
                    $formula->payment_status = true;
                    $formula->payment_override = true;
                }*/
                $contract->id = $contract->contract_id;
                $remaining = Agreement::getRemainingAmount($contract);
                if ($contract->contract_month > Contract::CO_MANAGEMENT_MIN_MONTHS) {
                    if ($remaining > 0) {
                        $formula->payment_status = true;
                    } else {
                        $formula->payment_status = false;
                    }
                } else {
                    if ($contract->min_hours <= $contract->worked_hours) {
                        if ($remaining > 0) {
                            $formula->payment_status = true;
                            $formula->payment_override = true;
                        } else {
                            $formula->payment_status = false;
                            $formula->payment_override = true;
                        }
                    } else {
                        $formula->payment_status = false;
                    }
                }
            } else {
                //$amount = $contract->worked_hours * $contract->rate;
                $rate = ContractRate::getRate($contract->contract_id, $contract->applyFormula_startDate, ContractRate::FMV_RATE);
                $amount = $contract->worked_hours * $rate;

                $formula->amount = round($amount);
                $formula->actual_rate = 'N/A';
                $formula->payment_status = ($contract->expected_hours * $contract->log_range) <= $contract->worked_hours;

                if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                    $formula->days_on_call = $contract->worked_hours / 24;
                }
            }
        }
        return $formula;
    }

    protected static function getCYTPMData($agreement, $contracts)
    {
        $colD_val = "-";
        $colE_val = "-";
        $colF_val = "-";
        $colH_val = 0.00;
        $colI_val = 0.00;
        $colJ_val = 0.00;
        $total_days = 0;
        $total_dues = 0.00;
        $min_hours = 0;
        $max_hours = 0;
        $annual_cap = 0;
        $prior_amount_paid = 0.00;
        $prior_worked_hours = 0;
        $physician_pmt_status = "";
        $potential_pay = 0.00;
        $worked_hrs = 0;
        $stipend_worked_hrs = 0;
        $amount_paid_for_month = 0.00;
        $stipend_amount_paid_for_month = 0.00;
        $amount_to_be_paid_for_month = 0.00;
        $actual_hourly_rate = 0.00;
        $actual_stipend_rate = 0.00;
        $expected_hours = 0.00;
        $expected_payment = 0.00;
        $fmv_hourly = 0.00;
        $fmv_stipend = 0.00;
        $practice_actual_hourly_rate = 0.00;
        $practice_actual_stipend_rate = 0.00;
        $practice_data = array();
        $contract_totals = array();
        $isPerDiem = false;
        $isHourly = false;
        $isStipend = false;
        $isPsa = false;
        $perDiem = false;
        $hourly = false;
        $stipend = false;
        $psa = false;
        $ex_pay = 0.00;
        $isuncompensated = false;
        $uncompensated = false;
        $colAE_val = 0.00;
        $colAG_val = 0.00;
        $fmv_time_study = 0.00;
        $fmv_per_unit = 0.00;
        $isTimeStudy = false;
        $isPerUnit = false;
        $timeStudy = false;
        $perUnit = false;
        $processed_contract_mont_ids = [];
        $processed_contract_ids = [];

        $contract_start_month = date('n', strtotime($contracts->agreement_start_date));

        foreach ($contracts->year_to_month as $index => $contracts) {
            foreach ($contracts as $index => $contract) {
                $is_shared_contract = false;
                $check_shared_contract = PhysicianContracts::where('contract_id', '=', $contract->contract_id)->whereNull('deleted_at')->get();
                if (count($check_shared_contract) > 1) {
                    $is_shared_contract = true;
                }
                unset($action_array); //  is gone
                $action_array = array(); // is here again

                $perDiem = $contract->payment_type_id == PaymentType::PER_DIEM ? true : $perDiem;
                $hourly = $contract->payment_type_id == PaymentType::HOURLY ? true : $hourly;
                $stipend = $contract->payment_type_id == PaymentType::STIPEND ? true : $stipend;
                $psa = $contract->payment_type_id == PaymentType::PSA ? true : $psa;
                $uncompensated = $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS ? true : $uncompensated;
                $timeStudy = $contract->payment_type_id == PaymentType::TIME_STUDY ? true : $timeStudy;
                $perUnit = $contract->payment_type_id == PaymentType::PER_UNIT ? true : $perUnit;

                // $month = ($contract->contract_month1 - 1) + $contract_start_month;
                // if ($month > 12) {
                // 	$month = $month % 12;
                // }
                // $month_string = number_to_month($month, "M"); //Old code to display month only on report.
                // $month_string = $agreement->start_date . '-' . $agreement->end_date;
                $month_string = str_replace('-', '/', $contract->start_date_check) . ' - ' . str_replace('-', '/', $contract->end_date_check);

                if (!isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['months'][$contract->contract_month1])) {
                    $colD_val = "-";
                    $colE_val = "-";
                    $colF_val = "-";
                    $colH_val = 0.00;
                    $colI_val = 0.00;
                    $colJ_val = 0.00;
                    $total_days = 0;
                    $total_dues = 0.00;
                    $min_hours = 0;
                    $max_hours = 0;
                    $annual_cap = 0;
                    $prior_amount_paid = 0.00;
                    $prior_worked_hours = 0;
                    $physician_pmt_status = "";
                    $potential_pay = 0.00;
                    $worked_hrs = 0;
                    $stipend_worked_hrs = 0;
                    $amount_paid_for_month = 0.00;
                    $stipend_amount_paid_for_month = 0.00;
                    $amount_to_be_paid_for_month = 0.00;
                    $actual_hourly_rate = 0.00;
                    $actual_stipend_rate = 0.00;
                    $expected_hours = 0.00;
                    $expected_payment = 0.00;
                    $fmv_hourly = 0.00;
                    $fmv_stipend = 0.00;
                    $isPerDiem = false;
                    $isHourly = false;
                    $isStipend = false;
                    $isPsa = false;
                    $perDiem_rate_type = 0;
                    $isuncompensated = false;
                    $colAE_val = 0.00;
                    $colAG_val = 0.00;
                    $fmv_time_study = 0.00;
                    $fmv_per_unit = 0.00;
                    $isTimeStudy = false;
                    $isPerUnit = false;
                }
                $contract->month_end_date = mysql_date(date($agreement->end_date));
                $amount_paid = Amount_paid::amountPaid($contract->contract_id, $contract->start_date_check, $contract->physician_id, $contract->practice_id);
                if (count($amount_paid) > 0) {
                    //if (isset($amount_paid->amountPaid)) {
                    //$contract->amount_paid = $amount_paid->amountPaid;
                    $contract->amount_paid = 0;
                    foreach ($amount_paid as $amount_paid) {
                        $contract->amount_paid += $amount_paid->amountPaid;
                    }
                } else {
                    $contract->amount_paid = 0;
                }
                $contract->applyFormula_startDate = $contract->start_date_check;
                $formula = self::applyFormula($contract);
                if ($formula->payment_override) {
                    $contract->has_override = true;
                }

                /*if($formula->payment_status){
                    $practice_data[$contract->practice_id]['practice_info']["practice_pmt_status"] = 'Y';
                }*/

                /*$weekday_rate = $contract->weekday_rate;
				$weekend_rate = $contract->weekend_rate;
				$holiday_rate = $contract->holiday_rate;
				$on_call_rate = $contract->on_call_rate;
				$called_back_rate = $contract->called_back_rate;
				$called_in_rate = $contract->called_in_rate;
				$rate = $contract->rate;*/
                $weekday_rate = ContractRate::getRate($contract->contract_id, $contract->start_date_check, ContractRate::WEEKDAY_RATE);
                $weekend_rate = ContractRate::getRate($contract->contract_id, $contract->start_date_check, ContractRate::WEEKEND_RATE);
                $holiday_rate = ContractRate::getRate($contract->contract_id, $contract->start_date_check, ContractRate::HOLIDAY_RATE);
                $on_call_rate = ContractRate::getRate($contract->contract_id, $contract->start_date_check, ContractRate::ON_CALL_RATE);
                $called_back_rate = ContractRate::getRate($contract->contract_id, $contract->start_date_check, ContractRate::CALLED_BACK_RATE);
                $called_in_rate = ContractRate::getRate($contract->contract_id, $contract->start_date_check, ContractRate::CALLED_IN_RATE);
                $uncompensated_rate = ContractRate::getRate($contract->contract_id, $contract->start_date_check, contractRate::ON_CALL_UNCOMPENSATED_RATE);
                $rate = ContractRate::getRate($contract->contract_id, $contract->start_date_check, ContractRate::FMV_RATE);
                $contract_rate_type = 0; /* for non on call contracts */
                if ($weekday_rate == 0 && $weekend_rate == 0 && $holiday_rate == 0 && ($on_call_rate > 0 || $called_back_rate > 0 || $contract->called_in_rate > 0 || ($contract->on_call_process == 1))) {
                    $contract_rate_type = 2; /* for on-call, called back and called in*/
                    $perDiem_rate_type = 2; /* for on-call, called back and called in*/
                } else if ($rate == 0) {
                    $contract_rate_type = 1; /* for weekday, weekend, holiday */
                    $perDiem_rate_type = 1; /* for weekday, weekend, holiday */
                }
                switch ($contract->payment_type_id) {
                    case 2:
                        $startcol = "L";
                        $endcol = "R";
                        $hourly = true;
                        //$contract->expected_payment = $contract->expected_hours * $contract->rate;
                        $contract->expected_payment = $contract->expected_hours * $rate;
                        $min_hours = $contract->min_hours;
                        $max_hours = $contract->max_hours;
                        $annual_cap = $contract->annual_cap;
                        $prior_worked_hours = $contract->prior_worked_hours;
                        $prior_amount_paid = $contract->prior_amount_paid;
                        $potential_pay = $contract->expected_hours * $rate;
                        $worked_hrs = $contract->worked_hours;
                        $amount_paid_for_month = $contract->amount_paid;

                        $contract_worked_hours_for_period = 0;
                        $contract_logs_for_period = PhysicianLog::select('physician_logs.*')
                            ->where("physician_logs.contract_id", "=", $contract->contract_id)
                            ->whereBetween("physician_logs.date", array($contract->start_date_check, $contract->end_date_check))
                            ->whereNull("physician_logs.deleted_at")
                            ->get();

                        if (count($contract_logs_for_period) > 0) {
                            $contract_worked_hours_for_period = $contract_logs_for_period->sum('duration');
                        }
                        $amount_to_be_paid_for_month = ($rate * $contract_worked_hours_for_period) - $contract->amount_paid;
                        $actual_hourly_rate = $contract->worked_hours != 0 ? $contract->amount_paid / $contract->worked_hours : 0;
                        $fmv_hourly = $rate;
                        $isHourly = true;
                        break;
                    case 3:
                        $startcol = "D";
                        $endcol = "K";
                        $perDiem = true;
                        $isPerDiem = true;
                        $physician_details = Physician::withTrashed()->findOrFail($contract->physician_id);
                        $physician_logs = DB::table('physician_logs')->select('physician_logs.duration', 'physician_logs.action_id', 'physician_logs.date', 'physician_logs.log_hours');
                        //added for soft delete
                        if ($physician_details->deleted_at != Null) {
                            $physician_logs = $physician_logs->join("physicians", function ($join) {
                                $join->on("physicians.id", "=", "physician_logs.physician_id")
                                    ->on("physicians.deleted_at", ">=", "physician_logs.deleted_at");
                            });
                        } else {
                            $physician_logs = $physician_logs->where('physician_logs.deleted_at', '=', Null);
                        }
                        $physician_logs = $physician_logs->where('physician_logs.physician_id', '=', $contract->physician_id)
                            ->where('physician_logs.contract_id', '=', $contract->contract_id)
                            ->where('physician_logs.practice_id', '=', $contract->practice_id)
                            ->whereBetween("physician_logs.date", [mysql_date($contract->start_date_check), mysql_date($contract->end_date_check)])
                            ->orderBy("physician_logs.date", "DESC")
                            ->get();

                        foreach ($physician_logs as $physician_log) {
                            /**
                             * Below line of change is addded for partial shift hours by akash.
                             * Creaed new array combination for taking log_hours alongwith the duration of a log for calculation for partial shift ON.
                             */

                            if (isset($action_array[$physician_log->action_id]) && count($action_array[$physician_log->action_id]) > 0) {
                                if (isset($action_array[$physician_log->action_id]['duration'])) {
                                    $action_array[$physician_log->action_id]['duration'] = $action_array[$physician_log->action_id]['duration'] + $physician_log->duration;
                                    $action_array[$physician_log->action_id]['log_hours'] = $action_array[$physician_log->action_id]['log_hours'] + $physician_log->log_hours;
                                } else {
                                    $action_array[$physician_log->action_id]['duration'] = $physician_log->duration;
                                    $action_array[$physician_log->action_id]['log_hours'] = $physician_log->log_hours;
                                }
                            } else {
                                $action_array[$physician_log->action_id]['duration'] = $physician_log->duration;
                                $action_array[$physician_log->action_id]['log_hours'] = $physician_log->log_hours;
                            }
                        }

                        /**
                         * Below code is added for newly created action_array for looping over it and performing the partial shift calculation as well.
                         */
                        foreach ($action_array as $action_id => $data_arr) {
                            $action_name = DB::table('actions')->select('name')
                                ->where('id', '=', $action_id)
                                ->first();
                            $action_sum = $data_arr['duration'];
                            $log_hours = $data_arr['log_hours'];
                            if (strlen(strstr(strtoupper($action_name->name), "WEEKDAY")) > 0 || $action_name->name == "On-Call") {
                                $colD_val = $action_sum;
                                $colH_val = $log_hours * ($contract_rate_type == 1 ? $weekday_rate : $on_call_rate);
                            }
                            if (strlen(strstr(strtoupper($action_name->name), "WEEKEND")) > 0 || $action_name->name == "Called-Back") {
                                $colE_val = $action_sum;
                                $colI_val = $log_hours * ($contract_rate_type == 1 ? $weekend_rate : $called_back_rate);
                            }
                            if (strlen(strstr(strtoupper($action_name->name), "HOLIDAY")) > 0 || $action_name->name == "Called-In") {
                                $colF_val = $action_sum;
                                $colJ_val = $log_hours * ($contract_rate_type == 1 ? $holiday_rate : $called_in_rate);
                            }
                        }

                        $total_days = ($colD_val != "-" ? $colD_val : 0) + ($colE_val != "-" ? $colE_val : 0) + ($colF_val != "-" ? $colF_val : 0);
                        $total_dues = ($colH_val != "-" ? $colH_val : 0) + ($colI_val != "-" ? $colI_val : 0) + ($colJ_val != "-" ? $colJ_val : 0);

                        break;
                    case 5:
                        $startcol = "AE";
                        $endcol = "AH";
                        $uncompensated = true;
                        $isuncompensated = true;
                        $physician_details = Physician::withTrashed()->findOrFail($contract->physician_id);
                        $physician_logs = DB::table('physician_logs')->select('physician_logs.duration', 'physician_logs.action_id', 'physician_logs.date', 'physician_logs.log_hours');
                        //added for soft delete
                        if ($physician_details->deleted_at != Null) {
                            $physician_logs = $physician_logs->join("physicians", function ($join) {
                                $join->on("physicians.id", "=", "physician_logs.physician_id")
                                    ->on("physicians.deleted_at", ">=", "physician_logs.deleted_at");
                            });
                        } else {
                            $physician_logs = $physician_logs->where('physician_logs.deleted_at', '=', Null);
                        }
                        $physician_logs = $physician_logs->where('physician_logs.physician_id', '=', $contract->physician_id)
                            ->where('physician_logs.contract_id', '=', $contract->contract_id)
                            ->where('physician_logs.practice_id', '=', $contract->practice_id)
                            ->whereBetween("physician_logs.date", [mysql_date($contract->start_date_check), mysql_date($contract->end_date_check)])
                            ->orderBy("physician_logs.date", "DESC")
                            ->get();

                        foreach ($physician_logs as $physician_log) {

                            /**
                             * Below line of change is addded for partial shift hours by akash.
                             * Creaed new array combination for taking log_hours alongwith the duration of a log for calculation for partial shift ON.
                             */

                            if (isset($action_array[$physician_log->action_id]) && count($action_array[$physician_log->action_id]) > 0) {
                                if (isset($action_array[$physician_log->action_id]['duration'])) {
                                    $action_array[$physician_log->action_id]['duration'] = $action_array[$physician_log->action_id]['duration'] + $physician_log->duration;
                                    $action_array[$physician_log->action_id]['log_hours'] = $action_array[$physician_log->action_id]['log_hours'] + $physician_log->log_hours;
                                } else {
                                    $action_array[$physician_log->action_id]['duration'] = $physician_log->duration;
                                    $action_array[$physician_log->action_id]['log_hours'] = $physician_log->log_hours;
                                }
                            } else {
                                $action_array[$physician_log->action_id]['duration'] = $physician_log->duration;
                                $action_array[$physician_log->action_id]['log_hours'] = $physician_log->log_hours;
                            }
                        }


                        /**
                         * Below code is added for newly created action_array for looping over it and performing the partial shift calculation as well.
                         */
                        foreach ($action_array as $action_id => $data_arr) {
                            $action_name = DB::table('actions')->select('name')
                                ->where('id', '=', $action_id)
                                ->first();
                            $action_sum = $data_arr['duration'];
                            $log_hours = $data_arr['log_hours'];
                            if ($action_name->name == "On-Call/Uncompensated") {
                                $colAE_val = $action_sum;

                                /**
                                 * Below is condition used for calculating payment based on payment ranges for the contract.
                                 */
                                $total_day = $log_hours;
                                $temp_day_remaining = $total_day;
                                $temp_calculated_payment = 0.00;

                                foreach ($uncompensated_rate as $range_val_arr) {
                                    $start_day = 0;
                                    $end_day = 0;
                                    $rate = 0.00;
                                    extract($range_val_arr->toArray()); // This line will convert the key into variable to create dynamic ranges from received data.
                                    if ($total_day >= $start_day) {
                                        if ($temp_day_remaining > 0) {
                                            $days_in_range = ($range_end_day - $range_start_day) + 1; // Calculating the number of days in a range.
                                            if ($temp_day_remaining < $days_in_range) {
                                                $temp_calculated_payment += $temp_day_remaining * $rate;
                                            } else {
                                                $temp_calculated_payment += $days_in_range * $rate;
                                            }
                                            $temp_day_remaining = $temp_day_remaining - $days_in_range;
                                        }
                                    } else if ($temp_day_remaining >= 0) {
                                        $temp_calculated_payment += $temp_day_remaining * $rate;
                                        $temp_day_remaining = 0;
                                    }
                                    // Log::Info('rem', array($temp_day_remaining));
                                    // Log::Info('test', array($temp_calculated_payment));
                                }
                                $colAG_val = $colAG_val + $temp_calculated_payment;
                            }
                        }

                        $total_days = ($colAE_val != "-" ? $colAE_val : 0);
                        $total_dues = ($colAG_val != "-" ? $colAG_val : 0);

                        break;
                    case PaymentType::PSA:
                        $startcol = "AB";
                        $endcol = "AD";
                        $psa = true;
                        $contract->expected_payment = $contract->expected_hours * $contract->rate;
                        $hourly_rate = $contract->worked_hours != 0 ? $contract->amount_paid / $contract->worked_hours : 0;
                        $physician_pmt_status = $contract->rate >= $hourly_rate && $contract->worked_hours != 0 ? 'Y' : 'N';
                        $expected_hours = $contract->expected_hours;
                        $expected_payment = $contract->expected_payment;
                        $stipend_worked_hrs = $contract->worked_hours;
                        $stipend_amount_paid_for_month = $contract->amount_paid;
                        $actual_stipend_rate = $contract->worked_hours > 0 ? $contract->amount_paid / $contract->worked_hours : $contract->amount_paid;
                        $fmv_stipend = $rate;
                        $isPsa = true;
                        break;
                    case 7:
                        $startcol = "AJ";
                        $endcol = "AP";
                        $timeStudy = true;
                        $contract->expected_payment = $contract->expected_hours * $rate;
                        $hourly_rate = $contract->worked_hours != 0 ? $contract->amount_paid / $contract->worked_hours : 0;
                        $physician_pmt_status = $rate >= $hourly_rate && $contract->worked_hours != 0 ? 'Y' : 'N';
                        $expected_hours = $contract->expected_hours;
                        $expected_payment = $contract->expected_payment;
                        $stipend_worked_hrs = $contract->worked_hours;
                        $stipend_amount_paid_for_month = $contract->amount_paid;
                        $actual_stipend_rate = $contract->worked_hours > 0 ? $contract->amount_paid / $contract->worked_hours : $contract->amount_paid;
                        $fmv_time_study = $rate;
                        $isTimeStudy = true;
                        break;
                    case 8:
                        $startcol = "AQ";
                        $endcol = "AX";
                        $perUnit = true;
                        $contract_worked_hours_for_period = 0;
                        $contract_logs_for_period = PhysicianLog::select('physician_logs.*')
                            ->where("physician_logs.contract_id", "=", $contract->contract_id)
                            ->whereBetween("physician_logs.date", array($contract->start_date_check, $contract->end_date_check))
                            ->whereNull("physician_logs.deleted_at")
                            ->get();

                        if (count($contract_logs_for_period) > 0) {
                            $contract_worked_hours_for_period = $contract_logs_for_period->sum('duration');
                        }
                        //$contract->expected_payment = $contract->expected_hours * $contract->rate;
                        $contract->expected_payment = $contract->expected_hours * $rate;
                        $min_hours = $contract->min_hours;
                        $max_hours = $contract->max_hours;
                        $annual_cap = $contract->annual_cap;
                        $prior_worked_hours = $contract->prior_worked_hours;
                        $prior_amount_paid = $contract->prior_amount_paid;
                        $potential_pay = $contract->expected_hours * $rate;
                        $worked_hrs = $contract->worked_hours;
                        $amount_paid_for_month = $contract->amount_paid;
                        $amount_to_be_paid_for_month = ($rate * $contract_worked_hours_for_period) - $contract->amount_paid;
                        $actual_hourly_rate = $contract->worked_hours != 0 ? $contract->amount_paid / $contract->worked_hours : 0;
                        $fmv_per_unit = $rate;
                        $isPerUnit = true;
                        break;
                    default:
                        $startcol = "S";
                        $endcol = "Y";
                        $stipend = true;
                        $contract->expected_payment = $contract->expected_hours * $rate;
                        $hourly_rate = $contract->worked_hours != 0 ? $contract->amount_paid / $contract->worked_hours : 0;
                        $physician_pmt_status = $rate >= $hourly_rate && $contract->worked_hours != 0 ? 'Y' : 'N';
                        $expected_hours = $contract->expected_hours;
                        $expected_payment = $contract->expected_payment;
                        $stipend_worked_hrs = $contract->worked_hours;
                        $stipend_amount_paid_for_month = $contract->amount_paid;
                        $actual_stipend_rate = $contract->worked_hours > 0 ? $contract->amount_paid / $contract->worked_hours : $contract->amount_paid;
                        $fmv_stipend = $rate;
                        $isStipend = true;
                        break;
                }

                $check_processed_contract = $contract->contract_id . "_" . preg_replace('/\s+/', '', $month_string);

                if (!isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id])) {
                    if (!in_array($check_processed_contract, $processed_contract_mont_ids)) {
                        $practice_expected_hours = isset($practice_data[$contract->practice_id]['practice_info']["expected_hours"]) ? $practice_data[$contract->practice_id]['practice_info']["expected_hours"] + $expected_hours : $expected_hours;
                        $practice_min_hours = isset($practice_data[$contract->practice_id]['practice_info']["min_hours"]) ? $practice_data[$contract->practice_id]['practice_info']["min_hours"] + $min_hours : $min_hours;
                        $practice_max_hours = isset($practice_data[$contract->practice_id]['practice_info']["max_hours"]) ? $practice_data[$contract->practice_id]['practice_info']["max_hours"] + $max_hours : $max_hours;
                        $practice_annual_cap = isset($practice_data[$contract->practice_id]['practice_info']["annual_cap"]) ? $practice_data[$contract->practice_id]['practice_info']["annual_cap"] + $annual_cap : $annual_cap;
                        $practice_prior_worked_hours = isset($practice_data[$contract->practice_id]['practice_info']["prior_worked_hours"]) ? $practice_data[$contract->practice_id]['practice_info']["prior_worked_hours"] + $prior_worked_hours : $prior_worked_hours; //prior_worked_hours
                        $practice_prior_amount_paid = isset($practice_data[$contract->practice_id]['practice_info']["prior_amount_paid"]) ? $practice_data[$contract->practice_id]['practice_info']["prior_amount_paid"] + $prior_amount_paid : $prior_amount_paid; //prior_amount_paid
                        $contract_min_hours = isset($contract_totals["min_hours"]) ? $contract_totals["min_hours"] + $min_hours : $min_hours;
                        $contract_max_hours = isset($contract_totals["max_hours"]) ? $contract_totals["max_hours"] + $max_hours : $max_hours;
                        $contract_annual_cap = isset($contract_totals["annual_cap"]) ? $contract_totals["annual_cap"] + $annual_cap : $annual_cap;
                        $contract_prior_worked_hours = isset($contract_totals["prior_worked_hours"]) ? $contract_totals["prior_worked_hours"] + $prior_worked_hours : $prior_worked_hours;//prior worked hours
                        $contract_prior_amount_paid = isset($contract_totals["prior_amount_paid"]) ? $contract_totals["prior_amount_paid"] + $prior_amount_paid : $prior_amount_paid;//prior amount paid
                    } else {
                        $practice_expected_hours = isset($practice_data[$contract->practice_id]['practice_info']["expected_hours"]) ? $practice_data[$contract->practice_id]['practice_info']["expected_hours"] : $expected_hours;
                        $practice_min_hours = isset($practice_data[$contract->practice_id]['practice_info']["min_hours"]) ? $practice_data[$contract->practice_id]['practice_info']["min_hours"] : $min_hours;
                        $practice_max_hours = isset($practice_data[$contract->practice_id]['practice_info']["max_hours"]) ? $practice_data[$contract->practice_id]['practice_info']["max_hours"] : $max_hours;
                        $practice_annual_cap = isset($practice_data[$contract->practice_id]['practice_info']["annual_cap"]) ? $practice_data[$contract->practice_id]['practice_info']["annual_cap"] : $annual_cap;
                        $practice_prior_worked_hours = isset($practice_data[$contract->practice_id]['practice_info']["prior_worked_hours"]) ? $practice_data[$contract->practice_id]['practice_info']["prior_worked_hours"] : $prior_worked_hours; //prior_worked_hours
                        $practice_prior_amount_paid = isset($practice_data[$contract->practice_id]['practice_info']["prior_amount_paid"]) ? $practice_data[$contract->practice_id]['practice_info']["prior_amount_paid"] : $prior_amount_paid; //prior_amount_paid
                        $contract_min_hours = isset($contract_totals["min_hours"]) ? $contract_totals["min_hours"] : $min_hours;
                        $contract_max_hours = isset($contract_totals["max_hours"]) ? $contract_totals["max_hours"] : $max_hours;
                        $contract_annual_cap = isset($contract_totals["annual_cap"]) ? $contract_totals["annual_cap"] : $annual_cap;
                        $contract_prior_worked_hours = isset($contract_totals["prior_worked_hours"]) ? $contract_totals["prior_worked_hours"] : $prior_worked_hours;//prior worked hours
                        $contract_prior_amount_paid = isset($contract_totals["prior_amount_paid"]) ? $contract_totals["prior_amount_paid"] : $prior_amount_paid;//prior amount paid
                    }
                } else {
                    if (!in_array($check_processed_contract, $processed_contract_mont_ids)) {
                        $practice_expected_hours = isset($practice_data[$contract->practice_id]['practice_info']["expected_hours"]) ? $practice_data[$contract->practice_id]['practice_info']["expected_hours"] : $expected_hours;
                        $practice_min_hours = isset($practice_data[$contract->practice_id]['practice_info']["min_hours"]) ? $practice_data[$contract->practice_id]['practice_info']["min_hours"] : $min_hours;
                        $practice_max_hours = isset($practice_data[$contract->practice_id]['practice_info']["max_hours"]) ? $practice_data[$contract->practice_id]['practice_info']["max_hours"] : $max_hours;
                        $practice_annual_cap = isset($practice_data[$contract->practice_id]['practice_info']["annual_cap"]) ? $practice_data[$contract->practice_id]['practice_info']["annual_cap"] : $annual_cap;
                        $practice_prior_worked_hours = isset($practice_data[$contract->practice_id]['practice_info']["prior_worked_hours"]) ? $practice_data[$contract->practice_id]['practice_info']["prior_worked_hours"] : $prior_worked_hours; //prior_worked_hours
                        $practice_prior_amount_paid = isset($practice_data[$contract->practice_id]['practice_info']["prior_amount_paid"]) ? $practice_data[$contract->practice_id]['practice_info']["prior_amount_paid"] : $prior_amount_paid; //prior_amount_paid
                        $contract_min_hours = isset($contract_totals["min_hours"]) ? $contract_totals["min_hours"] : $min_hours;
                        $contract_max_hours = isset($contract_totals["max_hours"]) ? $contract_totals["max_hours"] : $max_hours;
                        $contract_annual_cap = isset($contract_totals["annual_cap"]) ? $contract_totals["annual_cap"] : $annual_cap;
                        $contract_prior_worked_hours = isset($contract_totals["prior_worked_hours"]) ? $contract_totals["prior_worked_hours"] : $prior_worked_hours;//prior worked hours
                        $contract_prior_amount_paid = isset($contract_totals["prior_amount_paid"]) ? $contract_totals["prior_amount_paid"] : $prior_amount_paid;//prior amount paid
                    } else {
                        $practice_expected_hours = isset($practice_data[$contract->practice_id]['practice_info']["expected_hours"]) ? $practice_data[$contract->practice_id]['practice_info']["expected_hours"] : $expected_hours;
                        $practice_min_hours = isset($practice_data[$contract->practice_id]['practice_info']["min_hours"]) ? $practice_data[$contract->practice_id]['practice_info']["min_hours"] : $min_hours;
                        $practice_max_hours = isset($practice_data[$contract->practice_id]['practice_info']["max_hours"]) ? $practice_data[$contract->practice_id]['practice_info']["max_hours"] : $max_hours;
                        $practice_annual_cap = isset($practice_data[$contract->practice_id]['practice_info']["annual_cap"]) ? $practice_data[$contract->practice_id]['practice_info']["annual_cap"] : $annual_cap;
                        $practice_prior_worked_hours = isset($practice_data[$contract->practice_id]['practice_info']["prior_worked_hours"]) ? $practice_data[$contract->practice_id]['practice_info']["prior_worked_hours"] : $prior_worked_hours; //prior_worked_hours
                        $practice_prior_amount_paid = isset($practice_data[$contract->practice_id]['practice_info']["prior_amount_paid"]) ? $practice_data[$contract->practice_id]['practice_info']["prior_amount_paid"] : $prior_amount_paid; //prior_amount_paid
                        $contract_min_hours = isset($contract_totals["min_hours"]) ? $contract_totals["min_hours"] : $min_hours;
                        $contract_max_hours = isset($contract_totals["max_hours"]) ? $contract_totals["max_hours"] : $max_hours;
                        $contract_annual_cap = isset($contract_totals["annual_cap"]) ? $contract_totals["annual_cap"] : $annual_cap;
                        $contract_prior_worked_hours = isset($contract_totals["prior_worked_hours"]) ? $contract_totals["prior_worked_hours"] : $prior_worked_hours;//prior worked hours
                        $contract_prior_amount_paid = isset($contract_totals["prior_amount_paid"]) ? $contract_totals["prior_amount_paid"] : $prior_amount_paid;//prior amount paid
                    }
                }

                $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['months'][$contract->contract_month1] = ["physician_name" => $contract->physician_name,
                    "isPerDiem" => $isPerDiem,
                    "isHourly" => $isHourly,
                    "isStipend" => $isStipend,
                    "isPsa" => $isPsa,
                    "isuncompensated" => $isuncompensated,
                    "perDiem_rate_type" => $perDiem_rate_type,
                    "colD" => $colD_val,
                    "colE" => $colE_val,
                    "colF" => $colF_val,
                    "colG" => $total_days,
                    "colH" => $colH_val,
                    "colI" => $colI_val,
                    "colJ" => $colJ_val,
                    "colK" => $total_dues,
                    "colL" => ($is_shared_contract) ? 0.00 : $contract->amount_paid,
                    "colAE" => $colAE_val,
                    "colAF" => $total_days,
                    "colAG" => ($is_shared_contract) ? 0.00 : $colAG_val,
                    "colAH" => ($is_shared_contract) ? 0.00 : $total_dues,
                    "min_hours" => ($is_shared_contract) ? 0.00 : $min_hours,
                    "max_hours" => ($is_shared_contract) ? 0.00 : $max_hours,
                    "annual_cap" => ($is_shared_contract) ? 0.00 : $annual_cap,
                    "prior_worked_hours" => $prior_worked_hours,
                    "prior_amount_paid" => $prior_amount_paid,
                    "potential_pay" => ($is_shared_contract) ? 0.00 : $potential_pay,
                    "min_hours_ytm" => ($is_shared_contract) ? 0.00 : ($min_hours * $contract->contract_month1),
                    "worked_hrs" => $worked_hrs,
                    "amount_paid_for_month" => ($is_shared_contract) ? 0.00 : $amount_paid_for_month,
                    "amount_to_be_paid_for_month" => ($is_shared_contract) ? 0.00 : $amount_to_be_paid_for_month,
                    "actual_hourly_rate" => $actual_hourly_rate,
                    "FMV_hourly" => $fmv_hourly,
                    "PMT_status" => $physician_pmt_status,
                    "expected_hours" => ($is_shared_contract) ? 0.00 : $expected_hours,
                    "expected_payment" => ($is_shared_contract) ? 0.00 : $expected_payment,
                    "stipend_worked_hrs" => $stipend_worked_hrs,
                    "stipend_amount_paid_for_month" => ($is_shared_contract) ? 0.00 : $stipend_amount_paid_for_month,
                    "actual_stipend_rate" => $actual_stipend_rate,
                    "fmv_stipend" => $fmv_stipend,
                    "month_string" => $month_string,
                    "isTimeStudy" => $isTimeStudy,
                    "isPerUnit" => $isPerUnit,
                    "fmv_time_study" => $fmv_time_study,
                    "fmv_per_unit" => $fmv_per_unit
                ];

                if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals'])) {
                    if ($contract->payment_type_id == PaymentType::HOURLY) {
                        $actual_hourly_rate = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["worked_hrs"] + $worked_hrs != 0 ? ($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["amount_paid_for_month"] + $amount_paid_for_month) / ($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["worked_hrs"] + $worked_hrs) : 0;
                        $physician_pmt_status = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["physician_pmt_status"];
                    } elseif ($contract->payment_type_id != PaymentType::PER_DIEM) {
                        $actual_stipend_rate = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["stipend_worked_hrs"] + $stipend_worked_hrs > 0 ? ($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["stipend_amount_paid_for_month"] + $stipend_amount_paid_for_month) / ($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["stipend_worked_hrs"] + $stipend_worked_hrs) : ($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["stipend_amount_paid_for_month"] + $stipend_amount_paid_for_month);
                        $physician_pmt_status = $rate >= $actual_stipend_rate && $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["stipend_worked_hrs"] + $stipend_worked_hrs != 0 ? 'Y' : 'N';
                    }
                } elseif ($contract->payment_type_id != PaymentType::PER_DIEM && $contract->payment_type_id != PaymentType::HOURLY) {
                    $physician_pmt_status = $rate >= $actual_stipend_rate && $stipend_worked_hrs != 0 ? 'Y' : 'N';
                }

                if ($is_shared_contract) {
                    $physician_total_expected_hours = 0.00;
                    $physician_total_expected_hours_ytm = 0.00;
                    $physician_total_expected_pmt_ytm = 0.00;
                    $physician_total_stipend_amount_paid_for_month = 0.00;
                    $physician_min_hours = 0.00;
                    $physician_max_hours = 0.00;
                    $physician_annual_cap = 0.00;
                    $physician_potential_pay = 0.00;
                    $physician_min_hours_ytm = 0.00;
                    $physician_amount_paid_for_month = 0.00;
                    $physician_amount_to_be_paid_for_month = 0.00;
                    $physician_totals_amount_paid = 0.00;
                    $physician_colAG = 0.00;
                    $physician_colAH = 0.00;
                } else {
                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["expected_hours"])) {
                        $physician_total_expected_hours = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["expected_hours"];
                    } else {
                        $physician_total_expected_hours = $expected_hours;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["expected_hours_ytm"])) {
                        $physician_total_expected_hours_ytm = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["expected_hours_ytm"] + $expected_hours;
                    } else {
                        $physician_total_expected_hours_ytm = $expected_hours;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["expected_payment"])) {
                        $physician_total_expected_pmt_ytm = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["expected_payment"] + $expected_payment;
                    } else {
                        $physician_total_expected_pmt_ytm = $expected_payment;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["stipend_amount_paid_for_month"])) {
                        $physician_total_stipend_amount_paid_for_month = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["stipend_amount_paid_for_month"] + $stipend_amount_paid_for_month;
                    } else {
                        $physician_total_stipend_amount_paid_for_month = $stipend_amount_paid_for_month;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["min_hours"])) {
                        $physician_min_hours = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["min_hours"];
                    } else {
                        $physician_min_hours = $min_hours;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["max_hours"])) {
                        $physician_max_hours = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["max_hours"];
                    } else {
                        $physician_max_hours = $max_hours;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["annual_cap"])) {
                        $physician_annual_cap = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["annual_cap"];
                    } else {
                        $physician_annual_cap = $annual_cap;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["potential_pay"])) {
                        if ($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["potential_pay"] != $potential_pay) {
                            $physician_potential_pay = $potential_pay;
                        } else {
                            $physician_potential_pay = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["potential_pay"];
                        }
                    } else {
                        $physician_potential_pay = $potential_pay;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["min_hours_ytm"])) {
                        $physician_min_hours_ytm = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["min_hours_ytm"] + $min_hours;
                    } else {
                        $physician_min_hours_ytm = $min_hours;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["amount_paid_for_month"])) {
                        $physician_amount_paid_for_month = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["amount_paid_for_month"] + $amount_paid_for_month;
                    } else {
                        $physician_amount_paid_for_month = $amount_paid_for_month;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["amount_to_be_paid_for_month"])) {
                        $physician_amount_to_be_paid_for_month = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["amount_to_be_paid_for_month"] + $amount_to_be_paid_for_month;
                    } else {
                        $physician_amount_to_be_paid_for_month = $amount_to_be_paid_for_month;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colL"])) {
                        $physician_totals_amount_paid = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colL"] + $contract->amount_paid;
                    } else {
                        $physician_totals_amount_paid = $contract->amount_paid;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colAG"])) {
                        $physician_colAG = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colAG"] + $colAG_val;
                    } else {
                        $physician_colAG = $colAG_val;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colAH"])) {
                        $physician_colAH = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colAH"] + $total_dues;
                    } else {
                        $physician_colAH = $total_dues;
                    }
                }

                $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals'] = [
                    "physician_name" => $contract->physician_name,
                    "perDiem_rate_type" => $perDiem_rate_type,
                    "isPerDiem" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isPerDiem"]) ? !$practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isPerDiem"] ? $isPerDiem : $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isPerDiem"] : $isPerDiem,
                    "isHourly" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isHourly"]) ? !$practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isHourly"] ? $isHourly : $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isHourly"] : $isHourly,
                    "isStipend" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isStipend"]) ? !$practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isStipend"] ? $isStipend : $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isStipend"] : $isStipend,
                    "isPsa" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isPsa"]) ? !$practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isPsa"] ? $isPsa : $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isPsa"] : $isPsa,
                    "isuncompensated" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isuncompensated"]) ? !$practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isuncompensated"] ? $isuncompensated : $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isuncompensated"] : $isuncompensated,
                    "physician_pmt_status" => $physician_pmt_status,
                    "colD" => (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colD"]) && $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colD"] != '-' && $colD_val != '-') ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colD"] + $colD_val : $colD_val,
                    "colE" => (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colE"]) && $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colE"] != '-' && $colE_val != '-') ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colE"] + $colE_val : $colE_val,
                    "colF" => (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colF"]) && $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colF"] != '-' && $colF_val != '-') ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colF"] + $colF_val : $colF_val,
                    "colG" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colG"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colG"] + $total_days : $total_days,
                    "colH" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colH"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colH"] + $colH_val : $colH_val,
                    "colI" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colI"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colI"] + $colI_val : $colI_val,
                    "colJ" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colJ"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colJ"] + $colJ_val : $colJ_val,
                    "colK" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colK"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colK"] + $total_dues : $total_dues,
                    "colL" => $physician_totals_amount_paid,
                    "colAE" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colAE"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colAE"] + $colAE_val : $colAE_val,
                    "colAF" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colAF"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colAF"] + $total_days : $total_days,
                    "colAG" => $physician_colAG,
                    "colAH" => $physician_colAH,
                    "min_hours" => $physician_min_hours,
                    "max_hours" => $physician_max_hours,
                    "annual_cap" => $physician_annual_cap,
                    "prior_worked_hours" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["prior_worked_hours"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["prior_worked_hours"] : $prior_worked_hours,//prior_worked_hours
                    "prior_amount_paid" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["prior_amount_paid"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["prior_amount_paid"] : $prior_amount_paid,//prior_amount_paid
                    //"potential_pay" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["potential_pay"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["potential_pay"] : $potential_pay,
                    "potential_pay" => $physician_potential_pay,
                    "min_hours_ytm" => $physician_min_hours_ytm,
                    "worked_hrs" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["worked_hrs"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["worked_hrs"] + $worked_hrs : $worked_hrs,
                    "amount_paid_for_month" => $physician_amount_paid_for_month,
                    "amount_to_be_paid_for_month" => $physician_amount_to_be_paid_for_month,
                    "actual_hourly_rate" => $actual_hourly_rate,
                    "FMV_hourly" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["FMV_hourly"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["FMV_hourly"] + $fmv_hourly : $fmv_hourly,
                    "expected_hours" => $physician_total_expected_hours,
                    "expected_hours_ytm" => $physician_total_expected_hours_ytm,
                    "expected_payment" => $physician_total_expected_pmt_ytm,
                    "stipend_worked_hrs" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["stipend_worked_hrs"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["stipend_worked_hrs"] + $stipend_worked_hrs : $stipend_worked_hrs,
                    "stipend_amount_paid_for_month" => $physician_total_stipend_amount_paid_for_month,
                    "actual_stipend_rate" => $actual_stipend_rate,
                    "fmv_stipend" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["fmv_stipend"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["fmv_stipend"] + $fmv_stipend : $fmv_stipend,
                    "isTimeStudy" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isTimeStudy"]) ? !$practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isTimeStudy"] ? $isTimeStudy : $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isTimeStudy"] : $isTimeStudy,
                    "isPerUnit" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isPerUnit"]) ? !$practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isPerUnit"] ? $isPerUnit : $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isPerUnit"] : $isPerUnit,
                    "fmv_time_study" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["fmv_time_study"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["fmv_time_study"] + $fmv_time_study : $fmv_time_study,
                    "fmv_per_unit" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["fmv_per_unit"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["fmv_per_unit"] + $fmv_per_unit : $fmv_per_unit
                ];

                if (isset($practice_data[$contract->practice_id]['practice_info'])) {
                    if ($contract->payment_type_id == PaymentType::HOURLY || $contract->payment_type_id == PaymentType::PER_UNIT) {
                        $practice_actual_hourly_rate = $practice_data[$contract->practice_id]['practice_info']["worked_hrs"] + $worked_hrs != 0 ? ($practice_data[$contract->practice_id]['practice_info']["amount_paid_for_month"] + $amount_paid_for_month) / ($practice_data[$contract->practice_id]['practice_info']["worked_hrs"] + $worked_hrs) : 0;
                    } elseif ($contract->payment_type_id != PaymentType::PER_DIEM) {
                        $practice_actual_stipend_rate = $practice_data[$contract->practice_id]['practice_info']["stipend_worked_hrs"] + $stipend_worked_hrs > 0 ? ($practice_data[$contract->practice_id]['practice_info']["stipend_amount_paid_for_month"] + $stipend_amount_paid_for_month) / ($practice_data[$contract->practice_id]['practice_info']["stipend_worked_hrs"] + $stipend_worked_hrs) : ($practice_data[$contract->practice_id]['practice_info']["stipend_amount_paid_for_month"] + $stipend_amount_paid_for_month);
                        $practice_pmt_status = $rate >= $practice_actual_stipend_rate && $practice_data[$contract->practice_id]['practice_info']["stipend_worked_hrs"] + $stipend_worked_hrs != 0 ? 'Y' : 'N';
                    }
                } else {
                    $practice_actual_hourly_rate = $actual_hourly_rate;
                    $practice_actual_stipend_rate = $actual_stipend_rate;
                    $practice_pmt_status = $rate >= $actual_stipend_rate && $stipend_worked_hrs != 0 ? 'Y' : 'N';
                }

                if (!in_array($check_processed_contract, $processed_contract_mont_ids)) {
                    if (isset($practice_data[$contract->practice_id]['practice_info']["expected_payment"])) {
                        $practice_expected_payment = $practice_data[$contract->practice_id]['practice_info']["expected_payment"] + $expected_payment;
                    } else {
                        $practice_expected_payment = $expected_payment;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["expected_hours_ytm"])) {
                        $practice_expected_hour_ytm = $practice_data[$contract->practice_id]['practice_info']["expected_hours_ytm"] + $expected_hours;
                    } else {
                        $practice_expected_hour_ytm = $expected_hours;
                    }

                    if (isset($contract_totals["expected_hours_ytm"])) {
                        $contract_expected_hours_ytm = $contract_totals["expected_hours_ytm"] + $expected_hours;
                    } else {
                        $contract_expected_hours_ytm = $expected_hours;
                    }

                    if (isset($contract_totals["expected_payment"])) {
                        $ex_pay = $contract_totals["expected_payment"] + $expected_payment;
                    } else {
                        $ex_pay = $expected_payment;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["stipend_amount_paid_for_month"])) {
                        $practice_stipend_amount_paid_for_month = $practice_data[$contract->practice_id]['practice_info']["stipend_amount_paid_for_month"] + $stipend_amount_paid_for_month;
                    } else {
                        $practice_stipend_amount_paid_for_month = $stipend_amount_paid_for_month;
                    }

                    if (isset($contract_totals["stipend_amount_paid_for_month"])) {
                        $contract_stipend_amount_paid_for_month = $contract_totals["stipend_amount_paid_for_month"] + $stipend_amount_paid_for_month;
                    } else {
                        $contract_stipend_amount_paid_for_month = $stipend_amount_paid_for_month;
                    }
                } else {
                    if (isset($practice_data[$contract->practice_id]['practice_info']["expected_payment"])) {
                        $practice_expected_payment = $practice_data[$contract->practice_id]['practice_info']["expected_payment"];
                    } else {
                        $practice_expected_payment = $expected_payment;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["expected_payment"])) {
                        $practice_expected_hour_ytm = $practice_data[$contract->practice_id]['practice_info']["expected_hours_ytm"];
                    } else {
                        $practice_expected_hour_ytm = $expected_hours;
                    }

                    if (isset($contract_totals["expected_hours_ytm"])) {
                        $contract_expected_hours_ytm = $contract_totals["expected_hours_ytm"];
                    } else {
                        $contract_expected_hours_ytm = $expected_hours;
                    }

                    if (isset($contract_totals["expected_payment"])) {
                        $ex_pay = $contract_totals["expected_payment"];
                    } else {
                        $ex_pay = $expected_payment;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["stipend_amount_paid_for_month"])) {
                        $practice_stipend_amount_paid_for_month = $practice_data[$contract->practice_id]['practice_info']["stipend_amount_paid_for_month"];
                    } else {
                        $practice_stipend_amount_paid_for_month = $stipend_amount_paid_for_month;
                    }

                    if (isset($contract_totals["stipend_amount_paid_for_month"])) {
                        $contract_stipend_amount_paid_for_month = $contract_totals["stipend_amount_paid_for_month"];
                    } else {
                        $contract_stipend_amount_paid_for_month = $stipend_amount_paid_for_month;
                    }
                }

                if (!in_array($contract->contract_id, $processed_contract_ids)) {
                    if (isset($contract_totals["expected_hours"])) {
                        $contract_expected_hours = $contract_totals["expected_hours"] + $expected_hours;
                    } else {
                        $contract_expected_hours = $expected_hours;
                    }
                } else {
                    if (isset($contract_totals["expected_hours"])) {
                        $contract_expected_hours = $contract_totals["expected_hours"];
                    } else {
                        $contract_expected_hours = $expected_hours;
                    }
                }

                if (!in_array($check_processed_contract, $processed_contract_mont_ids)) {
                    if (isset($practice_data[$contract->practice_id]['practice_info']["potential_pay"])) {
                        $practice_potential_pay = $practice_data[$contract->practice_id]['practice_info']["potential_pay"] + $potential_pay;
                    } else {
                        $practice_potential_pay = $potential_pay;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["min_hours_ytm"])) {
                        $practice_min_hours_ytm = $practice_data[$contract->practice_id]['practice_info']["min_hours_ytm"] + $min_hours;
                    } else {
                        $practice_min_hours_ytm = $min_hours;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["worked_hrs"])) {
                        $practice_worked_hours = $practice_data[$contract->practice_id]['practice_info']["worked_hrs"] + $worked_hrs;
                    } else {
                        $practice_worked_hours = $worked_hrs;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["amount_paid_for_month"])) {
                        $practice_amount_paid_for_month = $practice_data[$contract->practice_id]['practice_info']["amount_paid_for_month"] + $amount_paid_for_month;
                    } else {
                        $practice_amount_paid_for_month = $amount_paid_for_month;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["amount_to_be_paid_for_month"])) {
                        $practice_amount_to_be_paid_for_month = $practice_data[$contract->practice_id]['practice_info']["amount_to_be_paid_for_month"] + $amount_to_be_paid_for_month;
                    } else {
                        $practice_amount_to_be_paid_for_month = $amount_to_be_paid_for_month;
                    }

                    if (isset($contract_totals["potential_pay"])) {
                        $contract_potential_pay = $contract_totals["potential_pay"] + $potential_pay;
                    } else {
                        $contract_potential_pay = $potential_pay;
                    }

                    if (isset($contract_totals["min_hours_ytm"])) {
                        $contract_min_hours_ytm = $contract_totals["min_hours_ytm"] + $min_hours;
                    } else {
                        $contract_min_hours_ytm = $min_hours;
                    }

                    if (isset($contract_totals["worked_hrs"])) {
                        $contract_worked_hours = $contract_totals["worked_hrs"] + $worked_hrs;
                    } else {
                        $contract_worked_hours = $worked_hrs;
                    }

                    if (isset($contract_totals["amount_paid_for_month"])) {
                        $contract_amount_paid_for_month = $contract_totals["amount_paid_for_month"] + $amount_paid_for_month;
                    } else {
                        $contract_amount_paid_for_month = $amount_paid_for_month;
                    }

                    if (isset($contract_totals["amount_to_be_paid_for_month"])) {
                        $contract_amount_to_be_paid_for_month = $contract_totals["amount_to_be_paid_for_month"] + $amount_to_be_paid_for_month;
                    } else {
                        $contract_amount_to_be_paid_for_month = $amount_to_be_paid_for_month;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["colL"])) {
                        $practice_amount_paid = $practice_data[$contract->practice_id]['practice_info']["colL"] + $contract->amount_paid;
                    } else {
                        $practice_amount_paid = $contract->amount_paid;
                    }

                    if (isset($contract_totals["colL"])) {
                        $contract_amount_paid = $contract_totals["colL"] + $contract->amount_paid;
                    } else {
                        $contract_amount_paid = $contract->amount_paid;
                    }
                } else {
                    if (isset($practice_data[$contract->practice_id]['practice_info']["potential_pay"])) {
                        $practice_potential_pay = $practice_data[$contract->practice_id]['practice_info']["potential_pay"];
                    } else {
                        $practice_potential_pay = $potential_pay;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["min_hours_ytm"])) {
                        $practice_min_hours_ytm = $practice_data[$contract->practice_id]['practice_info']["min_hours_ytm"];
                    } else {
                        $practice_min_hours_ytm = $min_hours;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["worked_hrs"])) {
                        $practice_worked_hours = $practice_data[$contract->practice_id]['practice_info']["worked_hrs"] + $worked_hrs;
                    } else {
                        $practice_worked_hours = $worked_hrs;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["amount_paid_for_month"])) {
                        $practice_amount_paid_for_month = $practice_data[$contract->practice_id]['practice_info']["amount_paid_for_month"];
                    } else {
                        $practice_amount_paid_for_month = $amount_paid_for_month;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["amount_to_be_paid_for_month"])) {
                        $practice_amount_to_be_paid_for_month = $practice_data[$contract->practice_id]['practice_info']["amount_to_be_paid_for_month"];
                    } else {
                        $practice_amount_to_be_paid_for_month = $amount_to_be_paid_for_month;
                    }

                    if (isset($contract_totals["potential_pay"])) {
                        $contract_potential_pay = $contract_totals["potential_pay"];
                    } else {
                        $contract_potential_pay = $potential_pay;
                    }

                    if (isset($contract_totals["min_hours_ytm"])) {
                        $contract_min_hours_ytm = $contract_totals["min_hours_ytm"];
                    } else {
                        $contract_min_hours_ytm = $min_hours;
                    }

                    if (isset($contract_totals["worked_hrs"])) {
                        $contract_worked_hours = $contract_totals["worked_hrs"] + $worked_hrs;
                    } else {
                        $contract_worked_hours = $worked_hrs;
                    }

                    if (isset($contract_totals["amount_paid_for_month"])) {
                        $contract_amount_paid_for_month = $contract_totals["amount_paid_for_month"];
                    } else {
                        $contract_amount_paid_for_month = $amount_paid_for_month;
                    }

                    if (isset($contract_totals["amount_to_be_paid_for_month"])) {
                        $contract_amount_to_be_paid_for_month = $contract_totals["amount_to_be_paid_for_month"];
                    } else {
                        $contract_amount_to_be_paid_for_month = $amount_to_be_paid_for_month;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["colL"])) {
                        $practice_amount_paid = $practice_data[$contract->practice_id]['practice_info']["colL"];
                    } else {
                        $practice_amount_paid = $contract->amount_paid;
                    }

                    if (isset($contract_totals["colL"])) {
                        $contract_amount_paid = $contract_totals["colL"];
                    } else {
                        $contract_amount_paid = $contract->amount_paid;
                    }
                }

                $practice_data[$contract->practice_id]['practice_info'] = [
                    "practice_name" => $contract->practice_name,
                    "isPerDiem" => isset($practice_data[$contract->practice_id]['practice_info']["isPerDiem"]) ? !$practice_data[$contract->practice_id]['practice_info']["isPerDiem"] ? $isPerDiem : $practice_data[$contract->practice_id]['practice_info']["isPerDiem"] : $isPerDiem,
                    "isHourly" => isset($practice_data[$contract->practice_id]['practice_info']["isHourly"]) ? !$practice_data[$contract->practice_id]['practice_info']["isHourly"] ? $isHourly : $practice_data[$contract->practice_id]['practice_info']["isHourly"] : $isHourly,
                    "isStipend" => isset($practice_data[$contract->practice_id]['practice_info']["isStipend"]) ? !$practice_data[$contract->practice_id]['practice_info']["isStipend"] ? $isStipend : $practice_data[$contract->practice_id]['practice_info']["isStipend"] : $isStipend,
                    "isPsa" => isset($practice_data[$contract->practice_id]['practice_info']["isPsa"]) ? !$practice_data[$contract->practice_id]['practice_info']["isPsa"] ? $isPsa : $practice_data[$contract->practice_id]['practice_info']["isPsa"] : $isPsa,
                    "isuncompensated" => isset($practice_data[$contract->practice_id]['practice_info']["isuncompensated"]) ? !$practice_data[$contract->practice_id]['practice_info']["isStipend"] ? $isStipend : $practice_data[$contract->practice_id]['practice_info']["isuncompensated"] : $isuncompensated,
                    "practice_pmt_status" => $practice_pmt_status,
                    "colD" => (isset($practice_data[$contract->practice_id]['practice_info']["colD"]) && $practice_data[$contract->practice_id]['practice_info']["colD"] != '-' && $colD_val != '-') ? $practice_data[$contract->practice_id]['practice_info']["colD"] + $colD_val : $colD_val,
                    "colE" => (isset($practice_data[$contract->practice_id]['practice_info']["colE"]) && $practice_data[$contract->practice_id]['practice_info']["colE"] != '-' && $colE_val != '-') ? $practice_data[$contract->practice_id]['practice_info']["colE"] + $colE_val : $colE_val,
                    "colF" => (isset($practice_data[$contract->practice_id]['practice_info']["colF"]) && $practice_data[$contract->practice_id]['practice_info']["colF"] != '-' && $colF_val != '-') ? $practice_data[$contract->practice_id]['practice_info']["colF"] + $colF_val : $colF_val,
                    "colG" => isset($practice_data[$contract->practice_id]['practice_info']["colG"]) ? $practice_data[$contract->practice_id]['practice_info']["colG"] + $total_days : $total_days,
                    "colH" => isset($practice_data[$contract->practice_id]['practice_info']["colH"]) ? $practice_data[$contract->practice_id]['practice_info']["colH"] + $colH_val : $colH_val,
                    "colI" => isset($practice_data[$contract->practice_id]['practice_info']["colI"]) ? $practice_data[$contract->practice_id]['practice_info']["colI"] + $colI_val : $colI_val,
                    "colJ" => isset($practice_data[$contract->practice_id]['practice_info']["colJ"]) ? $practice_data[$contract->practice_id]['practice_info']["colJ"] + $colJ_val : $colJ_val,
                    "colK" => isset($practice_data[$contract->practice_id]['practice_info']["colK"]) ? $practice_data[$contract->practice_id]['practice_info']["colK"] + $total_dues : $total_dues,
                    "colL" => $practice_amount_paid,
                    "colAE" => isset($practice_data[$contract->practice_id]['practice_info']["colAE"]) ? $practice_data[$contract->practice_id]['practice_info']["colAE"] + $colAE_val : $colAE_val,
                    "colAF" => isset($practice_data[$contract->practice_id]['practice_info']["colAF"]) ? $practice_data[$contract->practice_id]['practice_info']["colAF"] + $total_days : $total_days,
                    "colAG" => isset($practice_data[$contract->practice_id]['practice_info']["colAG"]) ? $practice_data[$contract->practice_id]['practice_info']["colAG"] + $colAG_val : $colAG_val,
                    "colAH" => isset($practice_data[$contract->practice_id]['practice_info']["colAH"]) ? $practice_data[$contract->practice_id]['practice_info']["colAH"] + $total_dues : $total_dues,
                    /*"min_hours" => isset($practice_data[$contract->practice_id]["min_hours"]) ? $practice_data[$contract->practice_id]["min_hours"] + $min_hours : $min_hours,
                    "max_hours" => isset($practice_data[$contract->practice_id]["max_hours"]) ? $practice_data[$contract->practice_id]["max_hours"] + $max_hours : $max_hours,
                    "annual_cap" => isset($practice_data[$contract->practice_id]["annual_cap"]) ? $practice_data[$contract->practice_id]["annual_cap"] + $annual_cap : $annual_cap,*/
                    "min_hours" => $practice_min_hours,
                    "max_hours" => $practice_max_hours,
                    "annual_cap" => $practice_annual_cap,
                    "prior_worked_hours" => $practice_prior_worked_hours,//prior_worked_hours
                    "prior_amount_paid" => $practice_prior_amount_paid,//prior_amount_paid
                    "potential_pay" => $practice_potential_pay,
                    "min_hours_ytm" => $practice_min_hours_ytm,
                    "worked_hrs" => $practice_worked_hours,
                    "amount_paid_for_month" => $practice_amount_paid_for_month,
                    "amount_to_be_paid_for_month" => $practice_amount_to_be_paid_for_month,
                    //"actual_hourly_rate" => isset($practice_data[$contract->practice_id]['practice_info']["actual_hourly_rate"]) ? $practice_data[$contract->practice_id]['practice_info']["actual_hourly_rate"] + $actual_hourly_rate : $actual_hourly_rate,
                    "actual_hourly_rate" => $practice_actual_hourly_rate,
                    "FMV_hourly" => isset($practice_data[$contract->practice_id]['practice_info']["FMV_hourly"]) ? $practice_data[$contract->practice_id]['practice_info']["FMV_hourly"] + $fmv_hourly : $fmv_hourly,
                    "expected_hours" => $practice_expected_hours, //
                    "expected_payment" => $practice_expected_payment, //
                    "expected_hours_ytm" => $practice_expected_hour_ytm, //
                    "stipend_worked_hrs" => isset($practice_data[$contract->practice_id]['practice_info']["stipend_worked_hrs"]) ? $practice_data[$contract->practice_id]['practice_info']["stipend_worked_hrs"] + $stipend_worked_hrs : $stipend_worked_hrs,
                    "stipend_amount_paid_for_month" => $practice_stipend_amount_paid_for_month,
                    //"actual_stipend_rate" => isset($practice_data[$contract->practice_id]['practice_info']["actual_stipend_rate"]) ? $practice_data[$contract->practice_id]['practice_info']["actual_stipend_rate"] + $actual_stipend_rate : $actual_stipend_rate ,
                    "actual_stipend_rate" => $practice_actual_stipend_rate,
                    "fmv_stipend" => isset($practice_data[$contract->practice_id]['practice_info']["fmv_stipend"]) ? $practice_data[$contract->practice_id]['practice_info']["fmv_stipend"] + $fmv_stipend : $fmv_stipend,
                    "isTimeStudy" => isset($practice_data[$contract->practice_id]['practice_info']["isTimeStudy"]) ? !$practice_data[$contract->practice_id]['practice_info']["isTimeStudy"] ? $isTimeStudy : $practice_data[$contract->practice_id]['practice_info']["isTimeStudy"] : $isTimeStudy,
                    "isPerUnit" => isset($practice_data[$contract->practice_id]['practice_info']["isPerUnit"]) ? !$practice_data[$contract->practice_id]['practice_info']["isPerUnit"] ? $isPerUnit : $practice_data[$contract->practice_id]['practice_info']["isPerUnit"] : $isPerUnit,
                    "fmv_time_study" => isset($practice_data[$contract->practice_id]['practice_info']["fmv_time_study"]) ? $practice_data[$contract->practice_id]['practice_info']["fmv_time_study"] + $fmv_time_study : $fmv_time_study,
                    "fmv_per_unit" => isset($practice_data[$contract->practice_id]['practice_info']["fmv_per_unit"]) ? $practice_data[$contract->practice_id]['practice_info']["fmv_per_unit"] + $fmv_per_unit : $fmv_per_unit
                ];

                if (isset($contract_totals['worked_hrs'])) {
                    if ($contract->payment_type_id == PaymentType::HOURLY || $contract->payment_type_id == PaymentType::PER_UNIT) {
                        $contract_actual_hourly_rate = $contract_totals["worked_hrs"] + $worked_hrs != 0 ? ($contract_totals["amount_paid_for_month"] + $amount_paid_for_month) / ($contract_totals["worked_hrs"] + $worked_hrs) : 0;
                    } elseif ($contract->payment_type_id != PaymentType::PER_DIEM) {
                        $contract_actual_stipend_rate = $contract_totals["stipend_worked_hrs"] + $stipend_worked_hrs > 0 ? ($contract_totals["stipend_amount_paid_for_month"] + $stipend_amount_paid_for_month) / ($contract_totals["stipend_worked_hrs"] + $stipend_worked_hrs) : ($contract_totals["stipend_amount_paid_for_month"] + $stipend_amount_paid_for_month);
                    }
                } else {
                    $contract_actual_hourly_rate = $practice_actual_hourly_rate;
                    $contract_actual_stipend_rate = $practice_actual_stipend_rate;
                }
//				$ex_pay = $ex_pay + $expected_payment;

                $contract_totals = [
                    "colD" => (isset($contract_totals["colD"]) && $contract_totals["colD"] != '-' && $colD_val != '-') ? $contract_totals["colD"] + $colD_val : $colD_val,
                    "colE" => (isset($contract_totals["colE"]) && $contract_totals["colE"] != '-' && $colE_val != '-') ? $contract_totals["colE"] + $colE_val : $colE_val,
                    "colF" => (isset($contract_totals["colF"]) && $contract_totals["colF"] != '-' && $colF_val != '-') ? $contract_totals["colF"] + $colF_val : $colF_val,
                    "colG" => isset($contract_totals["colG"]) ? $contract_totals["colG"] + $total_days : $total_days,
                    "colH" => isset($contract_totals["colH"]) ? $contract_totals["colH"] + $colH_val : $colH_val,
                    "colI" => isset($contract_totals["colI"]) ? $contract_totals["colI"] + $colI_val : $colI_val,
                    "colJ" => isset($contract_totals["colJ"]) ? $contract_totals["colJ"] + $colJ_val : $colJ_val,
                    "colK" => isset($contract_totals["colK"]) ? $contract_totals["colK"] + $total_dues : $total_dues,
                    "colL" => $contract_amount_paid,
                    "colAE" => isset($contract_totals["colAE"]) ? $contract_totals["colAE"] + $colAE_val : $colAE_val,
                    "colAF" => isset($contract_totals["colAF"]) ? $contract_totals["colAF"] + $total_days : $total_days,
                    "colAG" => isset($contract_totals["colAG"]) ? $contract_totals["colAG"] + $colAG_val : $colAG_val,
                    "colAH" => isset($contract_totals["colAH"]) ? $contract_totals["colAH"] + $total_dues : $total_dues,
                    /*"min_hours" => isset($contract_totals["min_hours"]) ? $contract_totals["min_hours"] + $min_hours : $min_hours,
					"max_hours" => isset($contract_totals["max_hours"]) ? $contract_totals["max_hours"] + $max_hours : $max_hours,
					"annual_cap" => isset($contract_totals["annual_cap"]) ? $contract_totals["annual_cap"] + $annual_cap : $annual_cap,*/
                    "min_hours" => $contract_min_hours,
                    "max_hours" => $contract_max_hours,
                    "annual_cap" => $contract_annual_cap,
                    "prior_worked_hours" => $contract_prior_worked_hours, //prior_worked_hours
                    "prior_amount_paid" => $contract_prior_amount_paid,//prior_amount_paid
                    "potential_pay" => $contract_potential_pay,
                    "min_hours_ytm" => $contract_min_hours_ytm,
                    "worked_hrs" => $contract_worked_hours,
                    "amount_paid_for_month" => $contract_amount_paid_for_month,
                    "amount_to_be_paid_for_month" => $contract_amount_to_be_paid_for_month,
                    //"actual_hourly_rate" => isset($contract_totals["actual_hourly_rate"]) ? $contract_totals["actual_hourly_rate"] + $actual_hourly_rate : $actual_hourly_rate,
                    "actual_hourly_rate" => $contract_actual_hourly_rate,
                    "FMV_hourly" => isset($contract_totals["FMV_hourly"]) ? $contract_totals["FMV_hourly"] + $fmv_hourly : $fmv_hourly,
                    "expected_hours" => $contract_expected_hours,
                    "expected_payment" => $ex_pay,
                    "expected_hours_ytm" => $contract_expected_hours_ytm,
                    "stipend_worked_hrs" => isset($contract_totals["stipend_worked_hrs"]) ? $contract_totals["stipend_worked_hrs"] + $stipend_worked_hrs : $stipend_worked_hrs,
                    "stipend_amount_paid_for_month" => $contract_stipend_amount_paid_for_month,
                    //"actual_stipend_rate" => isset($contract_totals["actual_stipend_rate"]) ? $contract_totals["actual_stipend_rate"] + $actual_stipend_rate : $actual_stipend_rate ,
                    "actual_stipend_rate" => $contract_actual_stipend_rate,
                    "fmv_stipend" => isset($contract_totals["fmv_stipend"]) ? $contract_totals["fmv_stipend"] + $fmv_stipend : $fmv_stipend,
                    "fmv_time_study" => isset($contract_totals["fmv_time_study"]) ? $contract_totals["fmv_time_study"] + $fmv_time_study : $fmv_time_study,
                    "fmv_per_unit" => isset($contract_totals["fmv_per_unit"]) ? $contract_totals["fmv_per_unit"] + $fmv_per_unit : $fmv_per_unit
                ];
                $processed_contract_mont_ids[] = $contract->contract_id . "_" . preg_replace('/\s+/', '', $month_string);
                $processed_contract_ids[] = $contract->contract_id;
            }
        }
        $result = [
            "practice_data" => $practice_data,
            "contract_totals" => $contract_totals,
            "perDiem" => $perDiem,
            "hourly" => $hourly,
            "stipend" => $stipend,
            "psa" => $psa,
            "uncompensated" => $uncompensated,
            "timeStudy" => $timeStudy,
            "perUnit" => $perUnit
        ];
        return $result;
    }

    protected static function getCYTDData($agreement, $contracts)
    {
        $colD_val = "-";
        $colE_val = "-";
        $colF_val = "-";
        $colH_val = 0.00;
        $colI_val = 0.00;
        $colJ_val = 0.00;
        $total_days = 0;
        $total_dues = 0.00;
        $min_hours = 0;
        $max_hours = 0;
        $annual_cap = 0;
        $prior_worked_hours = 0;
        $prior_amount_paid = 0.00;
        $physician_pmt_status = "";
        $potential_pay = 0.00;
        $worked_hrs = 0;
        $stipend_worked_hrs = 0;
        $amount_paid_for_month = 0.00;
        $stipend_amount_paid_for_month = 0.00;
        $amount_to_be_paid_for_month = 0.00;
        $actual_hourly_rate = 0.00;
        $actual_stipend_rate = 0.00;
        $practice_actual_hourly_rate = 0.00;
        $practice_actual_stipend_rate = 0.00;
        $expected_hours = 0.00;
        $expected_payment = 0.00;
        $fmv_hourly = 0.00;
        $fmv_stipend = 0.00;
        $practice_data = array();
        $contract_totals = array();
        $isPerDiem = false;
        $isHourly = false;
        $isStipend = false;
        $isPsa = false;
        $perDiem = false;
        $hourly = false;
        $stipend = false;
        $psa = false;
        $ex_pay = 0.00;
        $isuncompensated = false;
        $uncompensated = false;
        $colAE_val = 0.00;
        $colAG_val = 0.00;
        $fmv_time_study = 0.00;
        $fmv_per_unit = 0.00;
        $isTimeStudy = false;
        $isPerUnit = false;
        $timeStudy = false;
        $perUnit = false;
        $processed_contract_ids = [];
        $processed_contract_mont_ids = [];

        $contract_start_month = date('n', strtotime($contracts->agreement_start_date));

        foreach ($contracts->year_to_date as $index => $contracts) {
            foreach ($contracts as $index => $contract) {
                $is_shared_contract = false;
                $check_shared_contract = PhysicianContracts::where('contract_id', '=', $contract->contract_id)->whereNull('deleted_at')->get();
                if (count($check_shared_contract) > 1) {
                    $is_shared_contract = true;
                }

                unset($action_array); //  is gone
                $action_array = array(); // is here again

                $perDiem = $contract->payment_type_id == PaymentType::PER_DIEM ? true : $perDiem;
                $hourly = $contract->payment_type_id == PaymentType::HOURLY ? true : $hourly;
                $stipend = $contract->payment_type_id == PaymentType::STIPEND ? true : $stipend;
                $psa = $contract->payment_type_id == PaymentType::PSA ? true : $psa;
                $uncompensated = $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS ? true : $uncompensated;
                $timeStudy = $contract->payment_type_id == PaymentType::TIME_STUDY ? true : $timeStudy;
                $perUnit = $contract->payment_type_id == PaymentType::PER_UNIT ? true : $perUnit;

                $month = ($contract->contract_month1 - 1) + $contract_start_month;
                if ($month > 12) {
                    $month = $month % 12;
                }
                $month_string = str_replace('-', '/', $contract->start_date_check) . ' - ' . str_replace('-', '/', $contract->end_date_check);

                $colD_val = "-";
                $colE_val = "-";
                $colF_val = "-";
                $colH_val = 0.00;
                $colI_val = 0.00;
                $colJ_val = 0.00;
                $total_days = 0;
                $total_dues = 0.00;
                $min_hours = 0;
                $max_hours = 0;
                $annual_cap = 0;
                $prior_worked_hours = 0;
                $prior_amount_paid = 0.00;
                $physician_pmt_status = "";
                $potential_pay = 0.00;
                $worked_hrs = 0;
                $stipend_worked_hrs = 0;
                $amount_paid_for_month = 0.00;
                $stipend_amount_paid_for_month = 0.00;
                $amount_to_be_paid_for_month = 0.00;
                $actual_hourly_rate = 0.00;
                $actual_stipend_rate = 0.00;
                $expected_hours = 0.00;
                $expected_payment = 0.00;
                $fmv_hourly = 0.00;
                $fmv_stipend = 0.00;
                $colAE_val = 0.00;
                $colAG_val = 0.00;
                $fmv_time_study = 0.00;
                $fmv_per_unit = 0.00;

                if (!isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id])) {
                    $isPerDiem = false;
                    $isHourly = false;
                    $isStipend = false;
                    $isPsa = false;
                    $isuncompensated = false;
                    $perDiem_rate_type = 0;
                    $isTimeStudy = false;
                    $isPerUnit = false;
                }
                $contract->month_end_date = mysql_date(date($agreement->end_date));
                $amount_paid = Amount_paid::amountPaid($contract->contract_id, $contract->start_date_check, $contract->physician_id, $contract->practice_id);
                if (count($amount_paid) > 0) {
                    //if (isset($amount_paid->amountPaid)) {
                    //$contract->amount_paid = $amount_paid->amountPaid;
                    $contract->amount_paid = 0;
                    foreach ($amount_paid as $amount_paid) {
                        $contract->amount_paid += $amount_paid->amountPaid;
                    }
                } else {
                    $contract->amount_paid = 0;
                }
                $contract->applyFormula_startDate = $contract->start_date_check;
                $formula = self::applyFormula($contract);
                if ($formula->payment_override) {
                    $contract->has_override = true;
                }

                $weekday_rate = ContractRate::getRate($contract->contract_id, $contract->start_date_check, ContractRate::WEEKDAY_RATE);
                $weekend_rate = ContractRate::getRate($contract->contract_id, $contract->start_date_check, ContractRate::WEEKEND_RATE);
                $holiday_rate = ContractRate::getRate($contract->contract_id, $contract->start_date_check, ContractRate::HOLIDAY_RATE);
                $on_call_rate = ContractRate::getRate($contract->contract_id, $contract->start_date_check, ContractRate::ON_CALL_RATE);
                $called_back_rate = ContractRate::getRate($contract->contract_id, $contract->start_date_check, ContractRate::CALLED_BACK_RATE);
                $called_in_rate = ContractRate::getRate($contract->contract_id, $contract->start_date_check, ContractRate::CALLED_IN_RATE);
                $uncompensated_rate = ContractRate::getRate($contract->contract_id, $contract->start_date_check, contractRate::ON_CALL_UNCOMPENSATED_RATE);
                $rate = ContractRate::getRate($contract->contract_id, $contract->start_date_check, ContractRate::FMV_RATE);
                $contract_rate_type = 0; /* for non on call contracts */
                if ($weekday_rate == 0 && $weekend_rate == 0 && $holiday_rate == 0 && ($on_call_rate > 0 || $called_back_rate > 0 || $called_in_rate > 0 || $contract->on_call_process == 1)) {
                    $contract_rate_type = 2; /* for on-call, called back and called in*/
                    $perDiem_rate_type = 2; /* for on-call, called back and called in*/
                } else if ($rate == 0) {
                    $contract_rate_type = 1; /* for weekday, weekend, holiday */
                    $perDiem_rate_type = 1; /* for weekday, weekend, holiday */
                }
                switch ($contract->payment_type_id) {
                    case 2:
                        $startcol = "L";
                        $endcol = "R";
                        $hourly = true;
                        //$contract->expected_payment = $contract->expected_hours * $contract->rate;
                        $contract->expected_payment = $contract->expected_hours * $rate;
                        $min_hours = $contract->min_hours;
                        $max_hours = $contract->max_hours;
                        $annual_cap = $contract->annual_cap;
                        $prior_worked_hours = $contract->prior_worked_hours;//prior_worked_hours
                        $prior_amount_paid = $contract->prior_amount_paid; //prior_amount_paid
                        $potential_pay = $contract->expected_hours * $rate;
                        $worked_hrs = $contract->worked_hours;
                        $amount_paid_for_month = $contract->amount_paid;

                        $contract_worked_hours_for_period = 0;
                        $contract_logs_for_period = PhysicianLog::select('physician_logs.*')
                            ->where("physician_logs.contract_id", "=", $contract->contract_id)
                            ->whereBetween("physician_logs.date", array($contract->start_date_check, $contract->end_date_check))
                            ->whereNull("physician_logs.deleted_at")
                            ->get();

                        if (count($contract_logs_for_period) > 0) {
                            $contract_worked_hours_for_period = $contract_logs_for_period->sum('duration');
                        }

                        $amount_to_be_paid_for_month = ($rate * $contract_worked_hours_for_period) - $contract->amount_paid;
                        $actual_hourly_rate = $contract->worked_hours != 0 ? $contract->amount_paid / $contract->worked_hours : 0;
                        $fmv_hourly = $rate;
                        $isHourly = true;
                        break;
                    case 3:
                        $startcol = "D";
                        $endcol = "K";
                        $perDiem = true;
                        $isPerDiem = true;
                        $physician_details = Physician::withTrashed()->findOrFail($contract->physician_id);
                        $physician_logs = DB::table('physician_logs')->select('physician_logs.duration', 'physician_logs.action_id', 'physician_logs.date', 'physician_logs.log_hours');
                        //added for soft delete
                        if ($physician_details->deleted_at != Null) {
                            $physician_logs = $physician_logs->join("physicians", function ($join) {
                                $join->on("physicians.id", "=", "physician_logs.physician_id")
                                    ->on("physicians.deleted_at", ">=", "physician_logs.deleted_at");
                            });
                        } else {
                            $physician_logs = $physician_logs->where('physician_logs.deleted_at', '=', Null);
                        }
                        $physician_logs = $physician_logs->where('physician_logs.physician_id', '=', $contract->physician_id)
                            ->where('physician_logs.contract_id', '=', $contract->contract_id)
                            ->where('physician_logs.practice_id', '=', $contract->practice_id)
                            ->whereBetween("physician_logs.date", [mysql_date($contract->start_date_check), mysql_date($contract->end_date_check)])
                            ->orderBy("physician_logs.date", "DESC")
                            ->get();

                        foreach ($physician_logs as $physician_log) {

                            /**
                             * Below line of change is addded for partial shift hours by akash.
                             * Creaed new array combination for taking log_hours alongwith the duration of a log for calculation for partial shift ON.
                             */

                            if (isset($action_array[$physician_log->action_id]) && count($action_array[$physician_log->action_id]) > 0) {
                                if (isset($action_array[$physician_log->action_id]['duration'])) {
                                    $action_array[$physician_log->action_id]['duration'] = $action_array[$physician_log->action_id]['duration'] + $physician_log->duration;
                                    $action_array[$physician_log->action_id]['log_hours'] = $action_array[$physician_log->action_id]['log_hours'] + $physician_log->log_hours;
                                } else {
                                    $action_array[$physician_log->action_id]['duration'] = $physician_log->duration;
                                    $action_array[$physician_log->action_id]['log_hours'] = $physician_log->log_hours;
                                }
                            } else {
                                $action_array[$physician_log->action_id]['duration'] = $physician_log->duration;
                                $action_array[$physician_log->action_id]['log_hours'] = $physician_log->log_hours;
                            }
                        }


                        /**
                         * Below code is added for newly created action_array for looping over it and performing the partial shift calculation as well.
                         */
                        foreach ($action_array as $action_id => $data_arr) {
                            $action_name = DB::table('actions')->select('name')
                                ->where('id', '=', $action_id)
                                ->first();
                            $action_sum = $data_arr['duration'];
                            $log_hours = $data_arr['log_hours'];

                            if (strlen(strstr(strtoupper($action_name->name), "WEEKDAY")) > 0 || $action_name->name == "On-Call") {
                                $colD_val = $action_sum;
                                $colH_val = $log_hours * ($contract_rate_type == 1 ? $weekday_rate : $on_call_rate);
                                // Log::Info('from week', array($action_sum));
                            }
                            if (strlen(strstr(strtoupper($action_name->name), "WEEKEND")) > 0 || $action_name->name == "Called-Back") {
                                $colE_val = $action_sum;
                                $colI_val = $log_hours * ($contract_rate_type == 1 ? $weekend_rate : $called_back_rate);
                                // Log::Info('from weekend', array($action_sum));
                            }
                            if (strlen(strstr(strtoupper($action_name->name), "HOLIDAY")) > 0 || $action_name->name == "Called-In") {
                                $colF_val = $action_sum;
                                $colJ_val = $log_hours * ($contract_rate_type == 1 ? $holiday_rate : $called_in_rate);
                                // Log::Info('from holiday', array($action_sum));
                            }
                        }

                        $total_days = ($colD_val != "-" ? $colD_val : 0) + ($colE_val != "-" ? $colE_val : 0) + ($colF_val != "-" ? $colF_val : 0);
                        $total_dues = ($colH_val != "-" ? $colH_val : 0) + ($colI_val != "-" ? $colI_val : 0) + ($colJ_val != "-" ? $colJ_val : 0);

                        break;
                    case 5:
                        $startcol = "AE";
                        $endcol = "AH";
                        $uncompensated = true;
                        $isuncompensated = true;
                        $physician_details = Physician::withTrashed()->findOrFail($contract->physician_id);
                        $physician_logs = DB::table('physician_logs')->select('physician_logs.duration', 'physician_logs.action_id', 'physician_logs.date', 'physician_logs.log_hours');
                        //added for soft delete
                        if ($physician_details->deleted_at != Null) {
                            $physician_logs = $physician_logs->join("physicians", function ($join) {
                                $join->on("physicians.id", "=", "physician_logs.physician_id")
                                    ->on("physicians.deleted_at", ">=", "physician_logs.deleted_at");
                            });
                        } else {
                            $physician_logs = $physician_logs->where('physician_logs.deleted_at', '=', Null);
                        }
                        $physician_logs = $physician_logs->where('physician_logs.physician_id', '=', $contract->physician_id)
                            ->where('physician_logs.contract_id', '=', $contract->contract_id)
                            ->where('physician_logs.practice_id', '=', $contract->practice_id)
                            ->whereBetween("physician_logs.date", [mysql_date($contract->start_date_check), mysql_date($contract->end_date_check)])
                            ->orderBy("physician_logs.date", "DESC")
                            ->get();

                        foreach ($physician_logs as $physician_log) {

                            /**
                             * Below line of change is addded for partial shift hours by akash.
                             * Creaed new array combination for taking log_hours alongwith the duration of a log for calculation for partial shift ON.
                             */

                            if (isset($action_array[$physician_log->action_id]) && count($action_array[$physician_log->action_id]) > 0) {
                                if (isset($action_array[$physician_log->action_id]['duration'])) {
                                    $action_array[$physician_log->action_id]['duration'] = $action_array[$physician_log->action_id]['duration'] + $physician_log->duration;
                                    $action_array[$physician_log->action_id]['log_hours'] = $action_array[$physician_log->action_id]['log_hours'] + $physician_log->log_hours;
                                } else {
                                    $action_array[$physician_log->action_id]['duration'] = $physician_log->duration;
                                    $action_array[$physician_log->action_id]['log_hours'] = $physician_log->log_hours;
                                }
                            } else {
                                $action_array[$physician_log->action_id]['duration'] = $physician_log->duration;
                                $action_array[$physician_log->action_id]['log_hours'] = $physician_log->log_hours;
                            }
                        }


                        /**
                         * Below code is added for newly created action_array for looping over it and performing the partial shift calculation as well.
                         */
                        foreach ($action_array as $action_id => $data_arr) {
                            $action_name = DB::table('actions')->select('name')
                                ->where('id', '=', $action_id)
                                ->first();
                            $action_sum = $data_arr['duration'];
                            $log_hours = $data_arr['log_hours'];
                            if ($action_name->name == "On-Call/Uncompensated") {
                                $colAE_val = $action_sum;

                                /**
                                 * Below is condition used for calculating payment based on payment ranges for the contract.
                                 */
                                $total_day = $log_hours;
                                $temp_day_remaining = $total_day;
                                $temp_calculated_payment = 0.00;

                                foreach ($uncompensated_rate as $range_val_arr) {
                                    $start_day = 0;
                                    $end_day = 0;
                                    $rate = 0.00;
                                    extract($range_val_arr->toArray()); // This line will convert the key into variable to create dynamic ranges from received data.
                                    if ($total_day >= $start_day) {
                                        $days_in_range = ($range_end_day - $range_start_day) + 1; // Calculating the number of days in a range.
                                        if ($temp_day_remaining > 0) {
                                            if ($temp_day_remaining < $days_in_range) {
                                                $temp_calculated_payment += $temp_day_remaining * $rate;
                                            } else {
                                                $temp_calculated_payment += $days_in_range * $rate;
                                            }
                                            $temp_day_remaining = $temp_day_remaining - $days_in_range;
                                        }
                                    } else if ($temp_day_remaining >= 0) {
                                        $temp_calculated_payment += $temp_day_remaining * $rate;
                                        $temp_day_remaining = 0;
                                    }
                                    // Log::Info('rem', array($temp_day_remaining));
                                    // Log::Info('test', array($temp_calculated_payment));
                                }
                                $colAG_val = $colAG_val + $temp_calculated_payment;
                            }
                        }

                        $total_days = ($colAE_val != "-" ? $colAE_val : 0);
                        $total_dues = ($colAG_val != "-" ? $colAG_val : 0);

                        break;
                    case PaymentType::PSA;
                        $startcol = "AB";
                        $endcol = "AD";
                        $psa = true;
                        $contract->expected_payment = $contract->expected_hours * $contract->rate;
                        $hourly_rate = $contract->worked_hours != 0 ? $contract->amount_paid / $contract->worked_hours : 0;
                        $physician_pmt_status = $contract->rate >= $hourly_rate && $contract->worked_hours != 0 ? 'Y' : 'N';
                        $expected_hours = $contract->expected_hours;
                        $expected_payment = $contract->expected_payment;
                        $stipend_worked_hrs = $contract->worked_hours;
                        $stipend_amount_paid_for_month = $contract->amount_paid;
                        $actual_stipend_rate = $contract->worked_hours > 0 ? $contract->amount_paid / $contract->worked_hours : $contract->amount_paid;
                        $fmv_stipend = $rate;
                        $isPsa = true;
                        break;
                    case 7:
                        $startcol = "AI";
                        $endcol = "AO";
                        $timeStudy = true;
                        $contract->expected_payment = $contract->expected_hours * $rate;
                        $hourly_rate = $contract->worked_hours != 0 ? $contract->amount_paid / $contract->worked_hours : 0;
                        $physician_pmt_status = $rate >= $hourly_rate && $contract->worked_hours != 0 ? 'Y' : 'N';
                        $expected_hours = $contract->expected_hours;
                        $expected_payment = $contract->expected_payment;
                        $stipend_worked_hrs = $contract->worked_hours;
                        $stipend_amount_paid_for_month = $contract->amount_paid;
                        $actual_stipend_rate = $contract->worked_hours > 0 ? $contract->amount_paid / $contract->worked_hours : $contract->amount_paid;
                        $fmv_time_study = $rate;
                        $isTimeStudy = true;
                        break;
                    case 8:
                        $startcol = "AP";
                        $endcol = "AW";
                        $perUnit = true;
                        $contract_worked_hours_for_period = 0;
                        $contract_logs_for_period = PhysicianLog::select('physician_logs.*')
                            ->where("physician_logs.contract_id", "=", $contract->contract_id)
                            ->whereBetween("physician_logs.date", array($contract->start_date_check, $contract->end_date_check))
                            ->whereNull("physician_logs.deleted_at")
                            ->get();

                        if (count($contract_logs_for_period) > 0) {
                            $contract_worked_hours_for_period = $contract_logs_for_period->sum('duration');
                        }
                        //$contract->expected_payment = $contract->expected_hours * $contract->rate;
                        $contract->expected_payment = $contract->expected_hours * $rate;
                        $min_hours = $contract->min_hours;
                        $max_hours = $contract->max_hours;
                        $annual_cap = $contract->annual_cap;
                        $prior_worked_hours = $contract->prior_worked_hours;//prior_worked_hours
                        $prior_amount_paid = $contract->prior_amount_paid; //prior_amount_paid
                        $potential_pay = $contract->expected_hours * $rate;
                        $worked_hrs = $contract->worked_hours;
                        $amount_paid_for_month = $contract->amount_paid;
                        $amount_to_be_paid_for_month = ($rate * $contract_worked_hours_for_period) - $contract->amount_paid;
                        $actual_hourly_rate = $contract->worked_hours != 0 ? $contract->amount_paid / $contract->worked_hours : 0;
                        $fmv_per_unit = $rate;
                        $isPerUnit = true;
                        break;
                    default:
                        $startcol = "S";
                        $endcol = "Y";
                        $stipend = true;
                        $contract->expected_payment = $contract->expected_hours * $rate;
                        $hourly_rate = $contract->worked_hours != 0 ? $contract->amount_paid / $contract->worked_hours : 0;
                        $physician_pmt_status = $rate >= $hourly_rate && $contract->worked_hours != 0 ? 'Y' : 'N';
                        $expected_hours = $contract->expected_hours;
                        $expected_payment = $contract->expected_payment;
                        $stipend_worked_hrs = $contract->worked_hours;
                        $stipend_amount_paid_for_month = $contract->amount_paid;
                        $actual_stipend_rate = $contract->worked_hours > 0 ? $contract->amount_paid / $contract->worked_hours : $contract->amount_paid;
                        $fmv_stipend = $rate;
                        $isStipend = true;
                        break;
                }

                if (!isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]) && !in_array($contract->contract_id, $processed_contract_ids)) {
                    $practice_min_hours = isset($practice_data[$contract->practice_id]['practice_info']["min_hours"]) ? $practice_data[$contract->practice_id]['practice_info']["min_hours"] + $min_hours : $min_hours;
                    $practice_max_hours = isset($practice_data[$contract->practice_id]['practice_info']["max_hours"]) ? $practice_data[$contract->practice_id]['practice_info']["max_hours"] + $max_hours : $max_hours;
                    $practice_annual_cap = isset($practice_data[$contract->practice_id]['practice_info']["annual_cap"]) ? $practice_data[$contract->practice_id]['practice_info']["annual_cap"] + $annual_cap : $annual_cap;
                    $practice_prior_worked_hours = isset($practice_data[$contract->practice_id]['practice_info']["prior_worked_hours"]) ? $practice_data[$contract->practice_id]['practice_info']["prior_worked_hours"] + $prior_worked_hours : $prior_worked_hours;//prior_worked_hours
                    $practice_prior_amount_paid = isset($practice_data[$contract->practice_id]['practice_info']["prior_amount_paid"]) ? $practice_data[$contract->practice_id]['practice_info']["prior_amount_paid"] + $prior_amount_paid : $prior_amount_paid;//prior_amount_paid
                    $contract_min_hours = isset($contract_totals["min_hours"]) ? $contract_totals["min_hours"] + $min_hours : $min_hours;
                    $contract_max_hours = isset($contract_totals["max_hours"]) ? $contract_totals["max_hours"] + $max_hours : $max_hours;
                    $contract_annual_cap = isset($contract_totals["annual_cap"]) ? $contract_totals["annual_cap"] + $annual_cap : $annual_cap;
                    $contract_prior_worked_hours = isset($contract_totals["prior_worked_hours"]) ? $contract_totals["prior_worked_hours"] + $prior_worked_hours : $prior_worked_hours;//prior_worked_hours
                    $contract_prior_amount_paid = isset($contract_totals["prior_amount_paid"]) ? $contract_totals["prior_amount_paid"] + $prior_amount_paid : $prior_amount_paid;//prior_amount_paid
                    $practice_expected_hours = isset($practice_data[$contract->practice_id]['practice_info']["expected_hours"]) ? $practice_data[$contract->practice_id]['practice_info']["expected_hours"] + $expected_hours : $expected_hours;
//					$contract_expected_hours = isset($contract_totals["expected_hours"]) ? $contract_totals["expected_hours"] + $expected_hours : $expected_hours;
                } else {
                    $practice_min_hours = isset($practice_data[$contract->practice_id]['practice_info']["min_hours"]) ? $practice_data[$contract->practice_id]['practice_info']["min_hours"] : $min_hours;
                    $practice_max_hours = isset($practice_data[$contract->practice_id]['practice_info']["max_hours"]) ? $practice_data[$contract->practice_id]['practice_info']["max_hours"] : $max_hours;
                    $practice_annual_cap = isset($practice_data[$contract->practice_id]['practice_info']["annual_cap"]) ? $practice_data[$contract->practice_id]['practice_info']["annual_cap"] : $annual_cap;
                    $practice_prior_worked_hours = isset($practice_data[$contract->practice_id]['practice_info']["prior_worked_hours"]) ? $practice_data[$contract->practice_id]['practice_info']["prior_worked_hours"] : $prior_worked_hours; //prior_worked_hours
                    $practice_prior_amount_paid = isset($practice_data[$contract->practice_id]['practice_info']["prior_amount_paid"]) ? $practice_data[$contract->practice_id]['practice_info']["prior_amount_paid"] : $prior_amount_paid; //prior_amount_paid
                    $contract_min_hours = isset($contract_totals["min_hours"]) ? $contract_totals["min_hours"] : $min_hours;
                    $contract_max_hours = isset($contract_totals["max_hours"]) ? $contract_totals["max_hours"] : $max_hours;
                    $contract_annual_cap = isset($contract_totals["annual_cap"]) ? $contract_totals["annual_cap"] : $annual_cap;
                    $contract_prior_worked_hours = isset($contract_totals["prior_worked_hours"]) ? $contract_totals["prior_worked_hours"] : $prior_worked_hours;//prior_worked_hours
                    $contract_prior_amount_paid = isset($contract_totals["prior_amount_paid"]) ? $contract_totals["prior_amount_paid"] : $prior_amount_paid; //prior_amount_paid
//					$contract_expected_hours = isset($contract_totals["expected_hours"]) ? $contract_totals["expected_hours"] : $expected_hours;

                    if (!in_array($contract->contract_id, $processed_contract_ids)) {
                        $practice_expected_hours = isset($practice_data[$contract->practice_id]['practice_info']["expected_hours"]) ? $practice_data[$contract->practice_id]['practice_info']["expected_hours"] : 0.00;
                    }
                }
                if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals'])) {
                    if ($contract->payment_type_id == PaymentType::HOURLY) {
                        $actual_hourly_rate = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["worked_hrs"] + $worked_hrs != 0 ? ($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["amount_paid_for_month"] + $amount_paid_for_month) / ($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["worked_hrs"] + $worked_hrs) : 0;
                        $physician_pmt_status = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["physician_pmt_status"];
                    } elseif ($contract->payment_type_id != PaymentType::PER_DIEM) {
                        $actual_stipend_rate = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["stipend_worked_hrs"] + $stipend_worked_hrs > 0 ? ($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["stipend_amount_paid_for_month"] + $stipend_amount_paid_for_month) / ($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["stipend_worked_hrs"] + $stipend_worked_hrs) : ($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["stipend_amount_paid_for_month"] + $stipend_amount_paid_for_month);
                        $physician_pmt_status = $rate >= $actual_stipend_rate && $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["stipend_worked_hrs"] + $stipend_worked_hrs != 0 ? 'Y' : 'N';
                    }
                } elseif ($contract->payment_type_id != PaymentType::PER_DIEM && $contract->payment_type_id != PaymentType::HOURLY) {
                    $physician_pmt_status = $rate >= $actual_stipend_rate && $stipend_worked_hrs != 0 ? 'Y' : 'N';
                }

                if (!$is_shared_contract) {
                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["expected_hours"])) {
                        $physician_expected_hours = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["expected_hours"];
                    } else {
                        $physician_expected_hours = $expected_hours;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["expected_hours_ytm"])) {
                        $physician_expected_hours_ytm = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["expected_hours_ytm"] + $expected_hours;
                    } else {
                        $physician_expected_hours_ytm = $expected_hours;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["expected_payment"])) {
                        $physician_expected_payment = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["expected_payment"] + $expected_payment;
                    } else {
                        $physician_expected_payment = $expected_payment;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["stipend_amount_paid_for_month"])) {
                        $physician_stipend_amount_paid_for_month = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["stipend_amount_paid_for_month"] + $stipend_amount_paid_for_month;
                    } else {
                        $physician_stipend_amount_paid_for_month = $stipend_amount_paid_for_month;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["min_hours"])) {
                        $physician_min_hours = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["min_hours"];
                    } else {
                        $physician_min_hours = $min_hours;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["max_hours"])) {
                        $physician_max_hours = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["max_hours"];
                    } else {
                        $physician_max_hours = $max_hours;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["annual_cap"])) {
                        $physician_annual_cap = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["annual_cap"];
                    } else {
                        $physician_annual_cap = $annual_cap;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["potential_pay"])) {
                        if ($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["potential_pay"] != $potential_pay) {
                            $physician_potential_pay = $potential_pay;
                        } else {
                            $physician_potential_pay = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["potential_pay"];
                        }
                    } else {
                        $physician_potential_pay = $potential_pay;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["min_hours_ytm"])) {
                        $physician_min_hours_ytm = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["min_hours_ytm"] + $min_hours;
                    } else {
                        $physician_min_hours_ytm = $min_hours;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["amount_paid_for_month"])) {
                        $physician_amount_paid_for_month = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["amount_paid_for_month"] + $amount_paid_for_month;
                    } else {
                        $physician_amount_paid_for_month = $amount_paid_for_month;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["amount_to_be_paid_for_month"])) {
                        $physician_amount_to_be_paid_for_month = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["amount_to_be_paid_for_month"] + $amount_to_be_paid_for_month;
                    } else {
                        $physician_amount_to_be_paid_for_month = $amount_to_be_paid_for_month;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colD"])) {
                        if ($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colD"] != '-' && $colD_val != '-') {
                            $physician_colD = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colD"] + $colD_val;
                        } else if ($colD_val != '-') {
                            $physician_colD = $colD_val;
                        } else {
                            $physician_colD = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colD"];
                        }
                    } else {
                        $physician_colD = $colD_val;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colE"])) {
                        if ($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colE"] != '-' && $colE_val != '-') {
                            $physician_colE = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colE"] + $colE_val;
                        } else if ($colE_val != '-') {
                            $physician_colE = $colE_val;
                        } else {
                            $physician_colE = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colE"];
                        }
                    } else {
                        $physician_colE = $colE_val;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colF"])) {
                        if ($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colF"] != '-' && $colF_val != '-') {
                            $physician_colF = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colF"] + $colF_val;
                        } else if ($colF_val != '-') {
                            $physician_colF = $colF_val;
                        } else {
                            $physician_colF = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colF"];
                        }
                    } else {
                        $physician_colF = $colF_val;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colL"])) {
                        $physician_colL = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colL"] + $contract->amount_paid;
                    } else {
                        $physician_colL = $contract->amount_paid;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colAG"])) {
                        $physician_coLAG = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colAG"] + $colAG_val;
                    } else {
                        $physician_colAG = $colAG_val;
                    }

                    if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colAH"])) {
                        $physician_coLAH = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colAH"] + $total_dues;
                    } else {
                        $physician_colAH = $total_dues;
                    }
                } else {
                    $physician_expected_hours = 0.00;
                    $physician_expected_hours_ytm = 0.00;
                    $physician_expected_payment = 0.00;
                    $physician_stipend_amount_paid_for_month = 0.00;
                    $physician_min_hours = 0.00;
                    $physician_max_hours = 0.00;
                    $physician_annual_cap = 0.00;
                    $physician_potential_pay = 0.00;
                    $physician_min_hours_ytm = 0.00;
                    $physician_amount_paid_for_month = 0.00;
                    $physician_amount_to_be_paid_for_month = 0.00;
                    $physician_colD = 0.00;
                    $physician_colE = 0.00;
                    $physician_colF = 0.00;
                    $physician_colL = 0.00;
                    $physician_colAG = 0.00;
                    $physician_colAH = 0.00;

                    if ($contract->payment_type_id == 3) {
                        if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colD"])) {
                            if ($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colD"] != '-' && $colD_val != '-') {
                                $physician_colD = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colD"] + $colD_val;
                            } else if ($colD_val != '-') {
                                $physician_colD = $colD_val;
                            } else {
                                $physician_colD = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colD"];
                            }
                        }

                        if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colE"])) {
                            if ($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colE"] != '-' && $colE_val != '-') {
                                $physician_colE = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colE"] + $colE_val;
                            } else if ($colE_val != '-') {
                                $physician_colE = $colE_val;
                            } else {
                                $physician_colE = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colE"];
                            }
                        }

                        if (isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colF"])) {
                            if ($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colF"] != '-' && $colF_val != '-') {
                                $physician_colF = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colF"] + $colF_val;
                            } else if ($colF_val != '-') {
                                $physician_colF = $colF_val;
                            } else {
                                $physician_colF = $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colF"];
                            }
                        }
                    }
                }

                $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals'] = [
                    "physician_name" => $contract->physician_name,
                    "perDiem_rate_type" => $perDiem_rate_type,
                    "isPerDiem" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isPerDiem"]) ? !$practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isPerDiem"] ? $isPerDiem : $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isPerDiem"] : $isPerDiem,
                    "isHourly" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isHourly"]) ? !$practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isHourly"] ? $isHourly : $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isHourly"] : $isHourly,
                    "isStipend" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isStipend"]) ? !$practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isStipend"] ? $isStipend : $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isStipend"] : $isStipend,
                    "isPsa" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isPsa"]) ? !$practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isPsa"] ? $isPsa : $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isPsa"] : $isPsa,
                    "isuncompensated" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isStipend"]) ? !$practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isuncompensated"] ? $isuncompensated : $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isuncompensated"] : $isuncompensated,
                    "physician_pmt_status" => $physician_pmt_status,
                    "colD" => $physician_colD,
                    "colE" => $physician_colE,
                    "colF" => $physician_colF,
                    "colG" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colG"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colG"] + $total_days : $total_days,
                    "colH" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colH"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colH"] + $colH_val : $colH_val,
                    "colI" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colI"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colI"] + $colI_val : $colI_val,
                    "colJ" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colJ"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colJ"] + $colJ_val : $colJ_val,
                    "colK" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colK"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colK"] + $total_dues : $total_dues,
                    "colL" => $physician_colL,
                    "colAE" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colAE"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colAE"] + $colAE_val : $colAE_val,
                    "colAF" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colAF"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["colAF"] + $total_days : $total_days,
                    "colAG" => $physician_colAG,
                    "colAH" => $physician_colAH,
                    "min_hours" => $physician_min_hours,
                    "max_hours" => $physician_max_hours,
                    "annual_cap" => $physician_annual_cap,
                    "prior_worked_hours" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["prior_worked_hours"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["prior_worked_hours"] : $prior_worked_hours,//prior_worked_hours
                    "prior_amount_paid" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["prior_amount_paid"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["prior_amount_paid"] : $prior_amount_paid,//prior_amount_paid
                    //"potential_pay" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["potential_pay"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["potential_pay"] : $potential_pay,
                    "potential_pay" => $physician_potential_pay,
                    "min_hours_ytm" => $physician_min_hours_ytm,
                    "worked_hrs" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["worked_hrs"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["worked_hrs"] + $worked_hrs : $worked_hrs,
                    "amount_paid_for_month" => $physician_amount_paid_for_month,
                    "amount_to_be_paid_for_month" => $physician_amount_to_be_paid_for_month,
                    "actual_hourly_rate" => $actual_hourly_rate,
                    "FMV_hourly" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["FMV_hourly"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["FMV_hourly"] + $fmv_hourly : $fmv_hourly,
                    "expected_hours" => $physician_expected_hours,
                    "expected_hours_ytm" => $physician_expected_hours_ytm,
                    "expected_payment" => $physician_expected_payment,
                    "stipend_worked_hrs" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["stipend_worked_hrs"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["stipend_worked_hrs"] + $stipend_worked_hrs : $stipend_worked_hrs,
                    "stipend_amount_paid_for_month" => $physician_stipend_amount_paid_for_month,
                    "actual_stipend_rate" => $actual_stipend_rate,
                    "fmv_stipend" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["fmv_stipend"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["fmv_stipend"] + $fmv_stipend : $fmv_stipend,
                    "isTimeStudy" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isTimeStudy"]) ? !$practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isTimeStudy"] ? $isTimeStudy : $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isTimeStudy"] : $isTimeStudy,
                    "isPerUnit" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isPerUnit"]) ? !$practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isPerUnit"] ? $isPerUnit : $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["isPerUnit"] : $isPerUnit,
                    "fmv_time_study" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["fmv_time_study"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["fmv_time_study"] + $fmv_time_study : $fmv_time_study,
                    "fmv_per_unit" => isset($practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["fmv_per_unit"]) ? $practice_data[$contract->practice_id]["physician_data"][$contract->physician_id]['totals']["fmv_per_unit"] + $fmv_per_unit : $fmv_per_unit
                ];

                if (isset($practice_data[$contract->practice_id]['practice_info'])) {
                    if ($contract->payment_type_id == PaymentType::HOURLY || $contract->payment_type_id == PaymentType::PER_UNIT) {
                        $practice_actual_hourly_rate = $practice_data[$contract->practice_id]['practice_info']["worked_hrs"] + $worked_hrs != 0 ? ($practice_data[$contract->practice_id]['practice_info']["amount_paid_for_month"] + $amount_paid_for_month) / ($practice_data[$contract->practice_id]['practice_info']["worked_hrs"] + $worked_hrs) : 0;
                    } elseif ($contract->payment_type_id != PaymentType::PER_DIEM) {
                        $practice_actual_stipend_rate = $practice_data[$contract->practice_id]['practice_info']["stipend_worked_hrs"] + $stipend_worked_hrs > 0 ? ($practice_data[$contract->practice_id]['practice_info']["stipend_amount_paid_for_month"] + $stipend_amount_paid_for_month) / ($practice_data[$contract->practice_id]['practice_info']["stipend_worked_hrs"] + $stipend_worked_hrs) : ($practice_data[$contract->practice_id]['practice_info']["stipend_amount_paid_for_month"] + $stipend_amount_paid_for_month);
                        $practice_pmt_status = $rate >= $practice_actual_stipend_rate && $practice_data[$contract->practice_id]['practice_info']["stipend_worked_hrs"] + $stipend_worked_hrs != 0 ? 'Y' : 'N';
                    }
                } else {
                    $practice_actual_hourly_rate = $actual_hourly_rate;
                    $practice_actual_stipend_rate = $actual_stipend_rate;
                    $practice_pmt_status = $rate >= $actual_stipend_rate && $stipend_worked_hrs != 0 ? 'Y' : 'N';
                }

                if (!in_array($contract->contract_id, $processed_contract_ids)) {
                    if (isset($contract_totals["expected_hours"])) {
                        $contract_expected_hours = $contract_totals["expected_hours"] + $expected_hours;
                    } else {
                        $contract_expected_hours = $expected_hours;
                    }
                } else {
                    if (isset($contract_totals["expected_hours"])) {
                        $contract_expected_hours = $contract_totals["expected_hours"];
                    } else {
                        $contract_expected_hours = $expected_hours;
                    }
                }

                $check_processed_contract = $contract->contract_id . "_" . preg_replace('/\s+/', '', $month_string);
                if (!in_array($check_processed_contract, $processed_contract_mont_ids)) {
                    if (isset($practice_data[$contract->practice_id]['practice_info']["expected_payment"])) {
                        $practice_expected_payment = $practice_data[$contract->practice_id]['practice_info']["expected_payment"] + $expected_payment;
                    } else {
                        $practice_expected_payment = $expected_payment;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["expected_hours_ytm"])) {
                        $practice_expected_hours_ytm = $practice_data[$contract->practice_id]['practice_info']["expected_hours_ytm"] + $expected_hours;
                    } else {
                        $practice_expected_hours_ytm = $expected_hours;
                    }

                    if (isset($contract_totals["expected_payment"])) {
                        $ex_pay = $contract_totals["expected_payment"] + $expected_payment;
                    } else {
                        $ex_pay = $expected_payment;
                    }

                    if (isset($contract_totals["expected_hours_ytm"])) {
                        $contract_expected_hours_ytm = $contract_totals["expected_hours_ytm"] + $expected_hours;
                    } else {
                        $contract_expected_hours_ytm = $expected_hours;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["stipend_amount_paid_for_month"])) {
                        $practice_stipend_amount_paid_for_month = $practice_data[$contract->practice_id]['practice_info']["stipend_amount_paid_for_month"] + $stipend_amount_paid_for_month;
                    } else {
                        $practice_stipend_amount_paid_for_month = $stipend_amount_paid_for_month;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["amount_paid_for_month"])) {
                        $practice_amount_paid_for_month = $practice_data[$contract->practice_id]['practice_info']["amount_paid_for_month"] + $amount_paid_for_month;
                    } else {
                        $practice_amount_paid_for_month = $amount_paid_for_month;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["amount_to_be_paid_for_month"])) {
                        $practice_amount_to_be_paid_for_month = $practice_data[$contract->practice_id]['practice_info']["amount_to_be_paid_for_month"] + $amount_to_be_paid_for_month;
                    } else {
                        $practice_amount_to_be_paid_for_month = $amount_to_be_paid_for_month;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["potential_pay"])) {
                        $practice_potential_pay = $practice_data[$contract->practice_id]['practice_info']["potential_pay"] + $potential_pay;
                    } else {
                        $practice_potential_pay = $potential_pay;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["min_hours_ytm"])) {
                        $practice_min_hours_ytm = $practice_data[$contract->practice_id]['practice_info']["min_hours_ytm"] + $min_hours;
                    } else {
                        $practice_min_hours_ytm = $min_hours;
                    }

                    if (isset($contract_totals["stipend_amount_paid_for_month"])) {
                        $contract_stipend_amount_paid_for_month = $contract_totals["stipend_amount_paid_for_month"] + $stipend_amount_paid_for_month;
                    } else {
                        $contract_stipend_amount_paid_for_month = $stipend_amount_paid_for_month;
                    }

                    if (isset($contract_totals["amount_paid_for_month"])) {
                        $contract_amount_paid_for_month = $contract_totals["amount_paid_for_month"] + $amount_paid_for_month;
                    } else {
                        $contract_amount_paid_for_month = $amount_paid_for_month;
                    }

                    if (isset($contract_totals["amount_to_be_paid_for_month"])) {
                        $contract_amount_to_be_paid_for_month = $contract_totals["amount_to_be_paid_for_month"] + $amount_to_be_paid_for_month;
                    } else {
                        $contract_amount_to_be_paid_for_month = $amount_to_be_paid_for_month;
                    }

                    if (isset($contract_totals["potential_pay"])) {
                        $contract_potential_pay = $contract_totals["potential_pay"] + $potential_pay;
                    } else {
                        $contract_potential_pay = $potential_pay;
                    }

                    if (isset($contract_totals["min_hours_ytm"])) {
                        $contract_min_hours_ytm = $contract_totals["min_hours_ytm"] + $min_hours;
                    } else {
                        $contract_min_hours_ytm = $min_hours;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["colL"])) {
                        $practice_colL = $practice_data[$contract->practice_id]['practice_info']["colL"] + $contract->amount_paid;
                    } else {
                        $practice_colL = $contract->amount_paid;
                    }

                    if (isset($contract_totals["colL"])) {
                        $contract_colL = $contract_totals["colL"] + $contract->amount_paid;
                    } else {
                        $contract_colL = $contract->amount_paid;
                    }
                } else {
                    if (isset($practice_data[$contract->practice_id]['practice_info']["expected_payment"])) {
                        $practice_expected_payment = $practice_data[$contract->practice_id]['practice_info']["expected_payment"];
                    } else {
                        $practice_expected_payment = $expected_payment;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["expected_hours_ytm"])) {
                        $practice_expected_hours_ytm = $practice_data[$contract->practice_id]['practice_info']["expected_hours_ytm"];
                    } else {
                        $practice_expected_hours_ytm = $expected_hours;
                    }

                    if (isset($contract_totals["expected_payment"])) {
                        $ex_pay = $contract_totals["expected_payment"];
                    } else {
                        $ex_pay = $expected_payment;
                    }

                    if (isset($contract_totals["expected_hours_ytm"])) {
                        $contract_expected_hours_ytm = $contract_totals["expected_hours_ytm"];
                    } else {
                        $contract_expected_hours_ytm = $expected_hours;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["stipend_amount_paid_for_month"])) {
                        $practice_stipend_amount_paid_for_month = $practice_data[$contract->practice_id]['practice_info']["stipend_amount_paid_for_month"];
                    } else {
                        $practice_stipend_amount_paid_for_month = $stipend_amount_paid_for_month;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["amount_paid_for_month"])) {
                        $practice_amount_paid_for_month = $practice_data[$contract->practice_id]['practice_info']["amount_paid_for_month"];
                    } else {
                        $practice_amount_paid_for_month = $amount_paid_for_month;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["amount_to_be_paid_for_month"])) {
                        $practice_amount_to_be_paid_for_month = $practice_data[$contract->practice_id]['practice_info']["amount_to_be_paid_for_month"];
                    } else {
                        $practice_amount_to_be_paid_for_month = $amount_to_be_paid_for_month;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["potential_pay"])) {
                        $practice_potential_pay = $practice_data[$contract->practice_id]['practice_info']["potential_pay"];
                    } else {
                        $practice_potential_pay = $potential_pay;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["min_hours_ytm"])) {
                        $practice_min_hours_ytm = $practice_data[$contract->practice_id]['practice_info']["min_hours_ytm"];
                    } else {
                        $practice_min_hours_ytm = $min_hours;
                    }

                    if (isset($contract_totals["stipend_amount_paid_for_month"])) {
                        $contract_stipend_amount_paid_for_month = $contract_totals["stipend_amount_paid_for_month"];
                    } else {
                        $contract_stipend_amount_paid_for_month = $stipend_amount_paid_for_month;
                    }

                    if (isset($contract_totals["amount_paid_for_month"])) {
                        $contract_amount_paid_for_month = $contract_totals["amount_paid_for_month"];
                    } else {
                        $contract_amount_paid_for_month = $amount_paid_for_month;
                    }

                    if (isset($contract_totals["amount_to_be_paid_for_month"])) {
                        $contract_amount_to_be_paid_for_month = $contract_totals["amount_to_be_paid_for_month"];
                    } else {
                        $contract_amount_to_be_paid_for_month = $amount_to_be_paid_for_month;
                    }

                    if (isset($contract_totals["potential_pay"])) {
                        $contract_potential_pay = $contract_totals["potential_pay"];
                    } else {
                        $contract_potential_pay = $potential_pay;
                    }

                    if (isset($contract_totals["min_hours_ytm"])) {
                        $contract_min_hours_ytm = $contract_totals["min_hours_ytm"];
                    } else {
                        $contract_min_hours_ytm = $min_hours;
                    }

                    if (isset($practice_data[$contract->practice_id]['practice_info']["colL"])) {
                        $practice_colL = $practice_data[$contract->practice_id]['practice_info']["colL"];
                    } else {
                        $practice_colL = $contract->amount_paid;
                    }

                    if (isset($contract_totals["colL"])) {
                        $contract_colL = $contract_totals["colL"];
                    } else {
                        $contract_colL = $contract->amount_paid;
                    }
                }

                if (isset($practice_data[$contract->practice_id]['practice_info']["colD"])) {
                    if ($practice_data[$contract->practice_id]['practice_info']["colD"] != '-' && $colD_val != '-') {
                        $practice_colD = $practice_data[$contract->practice_id]['practice_info']["colD"] + $colD_val;
                    } else if ($colD_val != '-') {
                        $practice_colD = $colD_val;
                    } else {
                        $practice_colD = $practice_data[$contract->practice_id]['practice_info']["colD"];
                    }
                } else {
                    $practice_colD = $colD_val;
                }

                if (isset($practice_data[$contract->practice_id]['practice_info']["colE"])) {
                    if ($practice_data[$contract->practice_id]['practice_info']["colE"] != '-' && $colE_val != '-') {
                        $practice_colE = $practice_data[$contract->practice_id]['practice_info']["colE"] + $colE_val;
                    } else if ($colE_val != '-') {
                        $practice_colE = $colE_val;
                    } else {
                        $practice_colE = $practice_data[$contract->practice_id]['practice_info']["colE"];
                    }
                } else {
                    $practice_colE = $colE_val;
                }

                if (isset($practice_data[$contract->practice_id]['practice_info']["colF"])) {
                    if ($practice_data[$contract->practice_id]['practice_info']["colF"] != '-' && $colF_val != '-') {
                        $practice_colF = $practice_data[$contract->practice_id]['practice_info']["colF"] + $colF_val;
                    } else if ($colF_val != '-') {
                        $practice_colF = $colF_val;
                    } else {
                        $practice_colF = $practice_data[$contract->practice_id]['practice_info']["colF"];
                    }
                } else {
                    $practice_colF = $colF_val;
                }

                $practice_data[$contract->practice_id]['practice_info'] = [
                    "practice_name" => $contract->practice_name,
                    "isPerDiem" => isset($practice_data[$contract->practice_id]['practice_info']["isPerDiem"]) ? !$practice_data[$contract->practice_id]['practice_info']["isPerDiem"] ? $isPerDiem : $practice_data[$contract->practice_id]['practice_info']["isPerDiem"] : $isPerDiem,
                    "isHourly" => isset($practice_data[$contract->practice_id]['practice_info']["isHourly"]) ? !$practice_data[$contract->practice_id]['practice_info']["isHourly"] ? $isHourly : $practice_data[$contract->practice_id]['practice_info']["isHourly"] : $isHourly,
                    "isStipend" => isset($practice_data[$contract->practice_id]['practice_info']["isStipend"]) ? !$practice_data[$contract->practice_id]['practice_info']["isStipend"] ? $isStipend : $practice_data[$contract->practice_id]['practice_info']["isStipend"] : $isStipend,
                    "isPsa" => isset($practice_data[$contract->practice_id]['practice_info']["isPsa"]) ? !$practice_data[$contract->practice_id]['practice_info']["isPsa"] ? $isPsa : $practice_data[$contract->practice_id]['practice_info']["isPsa"] : $isPsa,
                    "isuncompensated" => isset($practice_data[$contract->practice_id]['practice_info']["isuncompensated"]) ? !$practice_data[$contract->practice_id]['practice_info']["isuncompensated"] ? $isuncompensated : $practice_data[$contract->practice_id]['practice_info']["isuncompensated"] : $isuncompensated,
                    "practice_pmt_status" => $practice_pmt_status,
                    "colD" => $practice_colD,
                    "colE" => $practice_colE,
                    "colF" => $practice_colF,
                    "colG" => isset($practice_data[$contract->practice_id]['practice_info']["colG"]) ? $practice_data[$contract->practice_id]['practice_info']["colG"] + $total_days : $total_days,
                    "colH" => isset($practice_data[$contract->practice_id]['practice_info']["colH"]) ? $practice_data[$contract->practice_id]['practice_info']["colH"] + $colH_val : $colH_val,
                    "colI" => isset($practice_data[$contract->practice_id]['practice_info']["colI"]) ? $practice_data[$contract->practice_id]['practice_info']["colI"] + $colI_val : $colI_val,
                    "colJ" => isset($practice_data[$contract->practice_id]['practice_info']["colJ"]) ? $practice_data[$contract->practice_id]['practice_info']["colJ"] + $colJ_val : $colJ_val,
                    "colK" => isset($practice_data[$contract->practice_id]['practice_info']["colK"]) ? $practice_data[$contract->practice_id]['practice_info']["colK"] + $total_dues : $total_dues,
                    "colL" => $practice_colL,
                    "colAE" => isset($practice_data[$contract->practice_id]['practice_info']["colAE"]) ? $practice_data[$contract->practice_id]['practice_info']["colAE"] + $colAE_val : $colAE_val,
                    "colAF" => isset($practice_data[$contract->practice_id]['practice_info']["colAF"]) ? $practice_data[$contract->practice_id]['practice_info']["colAF"] + $total_days : $total_days,
                    "colAG" => isset($practice_data[$contract->practice_id]['practice_info']["colAG"]) ? $practice_data[$contract->practice_id]['practice_info']["colAG"] + $colAG_val : $colAG_val,
                    "colAH" => isset($practice_data[$contract->practice_id]['practice_info']["colAH"]) ? $practice_data[$contract->practice_id]['practice_info']["colAH"] + $total_dues : $total_dues,
                    /*"min_hours" => isset($practice_data[$contract->practice_id]["min_hours"]) ? $practice_data[$contract->practice_id]["min_hours"] + $min_hours : $min_hours,
                    "max_hours" => isset($practice_data[$contract->practice_id]["max_hours"]) ? $practice_data[$contract->practice_id]["max_hours"] + $max_hours : $max_hours,
                    "annual_cap" => isset($practice_data[$contract->practice_id]["annual_cap"]) ? $practice_data[$contract->practice_id]["annual_cap"] + $annual_cap : $annual_cap,*/
                    "min_hours" => $practice_min_hours,
                    "max_hours" => $practice_max_hours,
                    "annual_cap" => $practice_annual_cap,
                    "prior_worked_hours" => $practice_prior_worked_hours,//prior_worked_hours
                    "prior_amount_paid" => $practice_prior_amount_paid, //prior_amount_paid
                    "potential_pay" => $practice_potential_pay,
                    "min_hours_ytm" => $practice_min_hours_ytm,
                    "worked_hrs" => isset($practice_data[$contract->practice_id]['practice_info']["worked_hrs"]) ? $practice_data[$contract->practice_id]['practice_info']["worked_hrs"] + $worked_hrs : $worked_hrs,
                    "amount_paid_for_month" => $practice_amount_paid_for_month,
                    "amount_to_be_paid_for_month" => $practice_amount_to_be_paid_for_month,
                    //"actual_hourly_rate" => isset($practice_data[$contract->practice_id]['practice_info']["actual_hourly_rate"]) ? $practice_data[$contract->practice_id]['practice_info']["actual_hourly_rate"] + $actual_hourly_rate : $actual_hourly_rate,
                    "actual_hourly_rate" => $practice_actual_hourly_rate,
                    "FMV_hourly" => isset($practice_data[$contract->practice_id]['practice_info']["FMV_hourly"]) ? $practice_data[$contract->practice_id]['practice_info']["FMV_hourly"] + $fmv_hourly : $fmv_hourly,
                    "expected_hours" => $practice_expected_hours,
                    "expected_payment" => $practice_expected_payment,
                    "expected_hours_ytm" => $practice_expected_hours_ytm,
                    "stipend_worked_hrs" => isset($practice_data[$contract->practice_id]['practice_info']["stipend_worked_hrs"]) ? $practice_data[$contract->practice_id]['practice_info']["stipend_worked_hrs"] + $stipend_worked_hrs : $stipend_worked_hrs,
                    "stipend_amount_paid_for_month" => $practice_stipend_amount_paid_for_month,
                    //"actual_stipend_rate" => isset($practice_data[$contract->practice_id]['practice_info']["actual_stipend_rate"]) ? $practice_data[$contract->practice_id]['practice_info']["actual_stipend_rate"] + $actual_stipend_rate : $actual_stipend_rate ,
                    "actual_stipend_rate" => $practice_actual_stipend_rate,
                    "fmv_stipend" => isset($practice_data[$contract->practice_id]['practice_info']["fmv_stipend"]) ? $practice_data[$contract->practice_id]['practice_info']["fmv_stipend"] + $fmv_stipend : $fmv_stipend,
                    "isTimeStudy" => isset($practice_data[$contract->practice_id]['practice_info']["isTimeStudy"]) ? !$practice_data[$contract->practice_id]['practice_info']["isTimeStudy"] ? $isTimeStudy : $practice_data[$contract->practice_id]['practice_info']["isTimeStudy"] : $isTimeStudy,
                    "isPerUnit" => isset($practice_data[$contract->practice_id]['practice_info']["isPerUnit"]) ? !$practice_data[$contract->practice_id]['practice_info']["isPerUnit"] ? $isPerUnit : $practice_data[$contract->practice_id]['practice_info']["isPerUnit"] : $isPerUnit,
                    "fmv_time_study" => isset($practice_data[$contract->practice_id]['practice_info']["fmv_time_study"]) ? $practice_data[$contract->practice_id]['practice_info']["fmv_time_study"] + $fmv_time_study : $fmv_time_study,
                    "fmv_per_unit" => isset($practice_data[$contract->practice_id]['practice_info']["fmv_per_unit"]) ? $practice_data[$contract->practice_id]['practice_info']["fmv_per_unit"] + $fmv_per_unit : $fmv_per_unit
                ];

                if (isset($contract_totals['worked_hrs'])) {
                    if ($contract->payment_type_id == PaymentType::HOURLY) {
                        $contract_actual_hourly_rate = $contract_totals["worked_hrs"] + $worked_hrs != 0 ? ($contract_totals["amount_paid_for_month"] + $amount_paid_for_month) / ($contract_totals["worked_hrs"] + $worked_hrs) : 0;
                    } elseif ($contract->payment_type_id != PaymentType::PER_DIEM) {
                        $contract_actual_stipend_rate = $contract_totals["stipend_worked_hrs"] + $stipend_worked_hrs > 0 ? ($contract_totals["stipend_amount_paid_for_month"] + $stipend_amount_paid_for_month) / ($contract_totals["stipend_worked_hrs"] + $stipend_worked_hrs) : ($contract_totals["stipend_amount_paid_for_month"] + $stipend_amount_paid_for_month);
                    }
                } else {
                    $contract_actual_hourly_rate = $practice_actual_hourly_rate;
                    $contract_actual_stipend_rate = $practice_actual_stipend_rate;
                }

                if (isset($contract_totals["colD"])) {
                    if ($contract_totals["colD"] != '-' && $colD_val != '-') {
                        $contract_colD = $contract_totals["colD"] + $colD_val;
                    } else if ($colD_val != '-') {
                        $contract_colD = $colD_val;
                    } else {
                        $contract_colD = $contract_totals["colD"];
                    }
                } else {
                    $contract_colD = $colD_val;
                }

                if (isset($contract_totals["colE"])) {
                    if ($contract_totals["colE"] != '-' && $colE_val != '-') {
                        $contract_colE = $contract_totals["colE"] + $colE_val;
                    } else if ($colE_val != '-') {
                        $contract_colE = $colE_val;
                    } else {
                        $contract_colE = $contract_totals["colE"];
                    }
                } else {
                    $contract_colE = $colE_val;
                }

                if (isset($contract_totals["colF"])) {
                    if ($contract_totals["colF"] != '-' && $colF_val != '-') {
                        $contract_colF = $contract_totals["colF"] + $colF_val;
                    } else if ($colF_val != '-') {
                        $contract_colF = $colF_val;
                    } else {
                        $contract_colF = $contract_totals["colF"];
                    }
                } else {
                    $contract_colF = $colF_val;
                }

//				$ex_pay = $ex_pay + $expected_payment;
                $contract_totals = [
                    "colD" => $contract_colD,
                    "colE" => $contract_colE,
                    "colF" => $contract_colF,
                    "colG" => isset($contract_totals["colG"]) ? $contract_totals["colG"] + $total_days : $total_days,
                    "colH" => isset($contract_totals["colH"]) ? $contract_totals["colH"] + $colH_val : $colH_val,
                    "colI" => isset($contract_totals["colI"]) ? $contract_totals["colI"] + $colI_val : $colI_val,
                    "colJ" => isset($contract_totals["colJ"]) ? $contract_totals["colJ"] + $colJ_val : $colJ_val,
                    "colK" => isset($contract_totals["colK"]) ? $contract_totals["colK"] + $total_dues : $total_dues,
                    "colL" => $contract_colL,
                    "colAE" => isset($contract_totals["colAE"]) ? $contract_totals["colAE"] + $colAE_val : $colAE_val,
                    "colAF" => isset($contract_totals["colAF"]) ? $contract_totals["colAF"] + $total_days : $total_days,
                    "colAG" => isset($contract_totals["colAG"]) ? $contract_totals["colAG"] + $colAG_val : $colAG_val,
                    "colAH" => isset($contract_totals["colAH"]) ? $contract_totals["colAH"] + $total_dues : $total_dues,
                    /*"min_hours" => isset($contract_totals["min_hours"]) ? $contract_totals["min_hours"] + $min_hours : $min_hours,
                    "max_hours" => isset($contract_totals["max_hours"]) ? $contract_totals["max_hours"] + $max_hours : $max_hours,
                    "annual_cap" => isset($contract_totals["annual_cap"]) ? $contract_totals["annual_cap"] + $annual_cap : $annual_cap,*/
                    "min_hours" => $contract_min_hours,
                    "max_hours" => $contract_max_hours,
                    "annual_cap" => $contract_annual_cap,
                    "prior_worked_hours" => $contract_prior_worked_hours, //prior_worked_hours
                    "prior_amount_paid" => $contract_prior_amount_paid,//prior_amount_paid
                    "potential_pay" => $contract_potential_pay,
                    "min_hours_ytm" => $contract_min_hours_ytm,
                    "worked_hrs" => isset($contract_totals["worked_hrs"]) ? $contract_totals["worked_hrs"] + $worked_hrs : $worked_hrs,
                    "amount_paid_for_month" => $contract_amount_paid_for_month,
                    "amount_to_be_paid_for_month" => $contract_amount_to_be_paid_for_month,
                    //"actual_hourly_rate" => isset($contract_totals["actual_hourly_rate"]) ? $contract_totals["actual_hourly_rate"] + $actual_hourly_rate : $actual_hourly_rate,
                    "actual_hourly_rate" => $contract_actual_hourly_rate,
                    "FMV_hourly" => isset($contract_totals["FMV_hourly"]) ? $contract_totals["FMV_hourly"] + $fmv_hourly : $fmv_hourly,
                    "expected_hours" => $contract_expected_hours,
                    "expected_payment" => $ex_pay,
                    "expected_hours_ytm" => $contract_expected_hours_ytm,
                    "stipend_worked_hrs" => isset($contract_totals["stipend_worked_hrs"]) ? $contract_totals["stipend_worked_hrs"] + $stipend_worked_hrs : $stipend_worked_hrs,
                    "stipend_amount_paid_for_month" => $contract_stipend_amount_paid_for_month,
                    //"actual_stipend_rate" => isset($contract_totals["actual_stipend_rate"]) ? $contract_totals["actual_stipend_rate"] + $actual_stipend_rate : $actual_stipend_rate ,
                    "actual_stipend_rate" => $contract_actual_stipend_rate,
                    "fmv_stipend" => isset($contract_totals["fmv_stipend"]) ? $contract_totals["fmv_stipend"] + $fmv_stipend : $fmv_stipend,
                    "fmv_time_study" => isset($contract_totals["fmv_time_study"]) ? $contract_totals["fmv_time_study"] + $fmv_time_study : $fmv_time_study,
                    "fmv_per_unit" => isset($contract_totals["fmv_per_unit"]) ? $contract_totals["fmv_per_unit"] + $fmv_per_unit : $fmv_per_unit
                ];
                $processed_contract_mont_ids[] = $contract->contract_id . "_" . preg_replace('/\s+/', '', $month_string);
                $processed_contract_ids[] = $contract->contract_id;
            }
        }
        $result = [
            "practice_data" => $practice_data,
            "contract_totals" => $contract_totals,
            "perDiem" => $perDiem,
            "hourly" => $hourly,
            "stipend" => $stipend,
            "uncompensated" => $uncompensated,
            "psa" => $psa,
            "timeStudy" => $timeStudy,
            "perUnit" => $perUnit
        ];
        return $result;
    }

    public static function getPaymentStatusReportData($hospital, $agreement_ids, $physician_ids)
    {
        $months = [];
        $months_start = [];
        $months_end = [];

        // if (count($agreement_ids) == 0 || count($physician_ids) == 0) { // Old condition changed by akash
        if ($agreement_ids == null || $physician_ids == null) { // Newly added condition because null was coming in the variable.
            return Redirect::back()->with([
                'error' => Lang::get('breakdowns.selection_error')
            ]);
        }

        foreach ($agreement_ids as $agreement_id) {
            $months[] = Request::input("agreement_{$agreement_id}_start_month");
            $months[] = Request::input("agreement_{$agreement_id}_start_month");
            $months_split[] = Request::input("start_{$agreement_id}_start_month");
            $months_split[] = Request::input("end_{$agreement_id}_start_month");
            $months_start[] = Request::input("start_{$agreement_id}_start_month");
            $months_end[] = Request::input("end_{$agreement_id}_start_month");
        }
        $physician_logs = new PhysicianLog();

        $timestamp = Request::input("current_timestamp");
        $timeZone = Request::input("current_zoneName");
        //	log::info("timestamp",array($timestamp));

        if ($timestamp != null && $timeZone != null && $timeZone != '' && $timestamp != '') {
            if (!strtotime($timestamp)) {
                $zone = new DateTime(strtotime($timestamp));
            } else {
                $zone = new DateTime(false);
            }
            $zone->setTimezone(new DateTimeZone($timeZone));
            $localtimeZone = $zone->format('m/d/Y h:i A T');
        } else {
            $localtimeZone = '';
        }


        $data = $physician_logs->logReportData($hospital, $agreement_ids, $physician_ids, $months_start, $months_end, Request::input('contract_type'), $localtimeZone, 'status');

        $agreement_ids = implode(',', $agreement_ids);
        $physician_ids = implode(',', $physician_ids);
        $months = implode(',', $months);
        $months_split = implode(',', $months_split);

        //Allow all users to select multiple months for log report
        Artisan::call('reports:paymentstatus', [
            'hospital' => $hospital->id,
            'contract_type' => Request::input('contract_type'),
            'physicians' => $physician_ids,
            'agreements' => $agreement_ids,
            'months' => $months_split,
            "report_data" => $data
        ]);

        if (!PaymentStatusReport::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(PaymentStatusReport::$message)
            ]);
        }

        return Redirect::back()->with([
            'success' => Lang::get(PaymentStatusReport::$message),
            'report_id' => PaymentStatusReport::$report_id,
            'report_filename' => PaymentStatusReport::$report_filename
        ]);
    }

    public function file()
    {
        return $this->morphOne(File::class, 'fileable');
    }

    public function hospital()
    {
        return $this->belongsTo('App\Hospital');
    }
}
