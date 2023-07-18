<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\customClasses\PaymentFrequencyFactoryClass;
use DateTime;

class ContractType extends Model
{
    const CO_MANAGEMENT = 1;
    const MEDICAL_DIRECTORSHIP = 2;
    const MEDICAL_EDUCATION = 3;
    const ON_CALL = 4;
    const MONTH_TO_MONTH = 5;

    protected $table = 'contract_types';

    public function actions()
    {
        return $this->hasMany('App\Action');
    }

    public function contracts()
    {
        return $this->hasMany('App\Contract');
    }

    public function contractTemplates()
    {
        return $this->hasMany('App\ContractTemplate');
    }

    public function contractNames()
    {
        return $this->hasMany('App\ContractName');
    }

    public static function getHospitalOptions($hospitalId, $addDefault = false)
    {
        /*below where clause  of is_Deleted is added for soft delete
      Code modified_on: 11/04/2016
      */

        $results = DB::table("contract_types")
            ->select("contract_types.*")
            ->join("contracts", "contracts.contract_type_id", "=", "contract_types.id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->whereNull("contracts.deleted_at")
            ->where("agreements.is_deleted", "=", false)
            ->where("agreements.hospital_id", "=", $hospitalId)
            ->whereNull("agreements.deleted_at")
            ->where("agreements.start_date", '<=', mysql_date(now()))
            ->where("agreements.end_date", '>=', mysql_date(now()))
            ->groupBy("contract_types.id")
            ->pluck("name", "id")->toArray();
        if ($addDefault) {
            $defaults = [
                "-1" => "All"
            ];

            return $defaults + $results;
        } else {
            return self::prefixDefaultOptions($results);
        }
    }

    public static function getPracticeOptions($practiceId)
    {
        $results = DB::table("practices")
            ->select("contract_types.*")
            ->join("physician_practice_history", "physician_practice_history.practice_id", "=", "practices.id")
            ->join("physician_practices", function ($join) {
                $join->on("physician_practices.practice_id", "=", "practices.id")
                    ->on("physician_practices.hospital_id", "=", "practices.hospital_id");
            })
            ->join("physician_contracts", function ($join) {
                $join->on("physician_contracts.practice_id", "=", "practices.id");
            })
            ->join("contracts", "contracts.id", "=", "physician_contracts.contract_id")
            ->join("contract_types", "contract_types.id", "=", "contracts.contract_type_id")
            ->whereNull("physician_practices.deleted_at")
            ->where("practices.id", "=", $practiceId)
            ->pluck("name", "id")->toArray();

        return self::prefixDefaultOptions($results);
    }

    public static function getPhysicianOptions($physicianId, $practice_id)
    {
        $results = DB::table("physicians")
            ->select("contract_types.*")
            ->join("physician_contracts", "physician_contracts.physician_id", "=", "physicians.id")
            ->join("contracts", "contracts.id", "=", "physician_contracts.physician_id")
            ->join("contract_types", "contract_types.id", "=", "contracts.contract_type_id")
            ->where("physicians.id", "=", $physicianId);
        if ($practice_id > 0) {
            $results = $results->where("physician_contracts.practice_id", "=", $practice_id);
        }
        $results = $results->where("contracts.end_date", "=", "0000-00-00 00:00:00")
            ->pluck("name", "id")->toArray();
        return self::prefixDefaultOptions($results);
    }

    public static function getManagerOptions($userId, $type, $hospital_id, $physician_id, $physicians)
    {
        $default = ['0' => 'All'];
        if ($physician_id != 0) {
            $physician_ids[] = $physician_id;
        } else {
            $physician_ids = array_keys($physicians);
        }
        $contract_types = Hospital::fetch_contract_stats_for_hospital_users($userId, $type);
        $contract_type_list = array();
        foreach ($contract_types as $contract_types) {
            $contract_type_existance = Contract::where("contract_type_id", "=", $contract_types['contract_type_id'])
    
                ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
                ->whereIn("physician_contracts.physician_id", $physician_ids)->get();
            if (count($contract_type_existance) > 0) {
                $contract_type_id = $contract_types['contract_type_id'];
                $contract_type_name = $contract_types['contract_type_name'];
                
                if (!in_array($contract_type_id, $contract_type_list)) {
                    $contract_type_list[$contract_type_id] = $contract_type_name;
                }
            }
        }

        return $default + $contract_type_list;
    }

    public static function getContractTypesForPerformanceDashboard($user_id, $selected_hospital, $hospital, $selected_agreement, $agreements, $practices, $selected_practice, $physician_id, $physicians, $payment_types, $payment_type)
    {
        $physician_ids = array_keys($physicians);
        $practice_ids = array_keys($practices);
        $agreement_ids = array_keys($agreements);
        $contract_types = Hospital::fetch_contract_stats_for_hospital_users_performance_dashboard($user_id);
        $contract_type_list = array();
        foreach ($contract_types as $contract_types) {
            $contract_type_existance = Contract::
            join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->join("physician_practices", "physician_practices.physician_id", "=", "contracts.physician_id")
                ->where("contract_type_id", "=", $contract_types['contract_type_id'])
                ->whereIn("agreements.id", $agreement_ids)
                ->where('agreements.archived', '=', false)
                ->whereIn("physician_practices.practice_id", $practice_ids)
                ->whereIn("contracts.physician_id", $physician_ids)
                ->where("payment_type_id", "=", $payment_type)
                ->whereNotIn("payment_type_id", [3, 5])
                ->get();
            if (count($contract_type_existance) > 0) {
                $contract_type_id = $contract_types['contract_type_id'];
                $contract_type_name = $contract_types['contract_type_name'];

                if (!in_array($contract_type_id, $contract_type_list)) {
                    $contract_type_list[$contract_type_id] = $contract_type_name;
                }
            }
        }
        return $contract_type_list;
    }

    private static function prefixDefaultOptions($results)
    {

        $defaults = [
            "-1" => "All Contract Types"
        ];

        return $results;
    }

    public static function options()
    {
        return self::all()
            ->pluck("name", "id");
    }

    public static function psaOptions()
    {
        return self::where('id', '=', 13)
            ->pluck("name", "id");
    }

    public static function getName($id)
    {
        return self::where("id", "=", $id)
            ->pluck('name');
    }

    public static function getContractsByType($contract_type, $user_id, $group_id, $region, $facility, $exclude_perDiem = false)
    {
        $payment_type = 0;
        $contract_list = Contract::getContractsForHealthSystemUsers($contract_type, $payment_type, $user_id, $group_id, $region, $facility, $exclude_perDiem);
        return $contract_list;
    }

    public static function fetch_contract_stats_for_health_system_users($user_id, $group_id, $region, $facility, $selected_start_date, $selected_end_date, $with_physician = 0)
    {
        $query = self::select(DB::raw("distinct(contract_types.id),contract_types.name"))
            ->join("contracts", "contracts.contract_type_id", "=", "contract_types.id");
        $contract_types = $query->get();

        $sorted_array = array();
        $return_data = array();
        foreach ($contract_types as $contract_type) {
            $contract_count = self::getContractsByTypeForRegionAndHealthSystem($contract_type->id, $user_id, $group_id, $region, $facility, false, $selected_start_date, $selected_end_date, $with_physician);

            $contract_spend = self::totalSpend($contract_count, $contract_type);

            if (count($contract_count) > 0) {
                $return_data[] = [
                    "active_contract_count" => count($contract_count),
                    "contract_type_id" => $contract_type->id,
                    "contract_type_name" => $contract_type->name,
                    "total_spend" => $contract_spend
                ];
            }
        }
        arsort($return_data);
        $sorted_array = array_values($return_data);

        return $sorted_array;
    }

    public static function totalSpend($contracts, $contract_type)
    {
        $paid = 0;
        foreach ($contracts as $contract) {
            $total_amount_paid = Amount_paid::select(DB::raw("SUM(amountPaid) as paid"))
                ->where("contract_id", "=", $contract->id)->first();
            $total_amount_paid = (($contract->payment_type_id == PaymentType::HOURLY) && ($contract->prior_start_date != '0000-00-00')) ? $total_amount_paid->paid + $contract->prior_amount_paid : $total_amount_paid->paid;
            $paid = formatNumber($paid + $total_amount_paid);
        }
        return $paid;
    }

    public static function fetch_contract_spendYTD_for_health_system_users($user_id, $group_id, $region, $facility, $selected_start_date, $selected_end_date)
    {
        $query = self::select(DB::raw("distinct(contract_types.id),contract_types.name"))
            ->join("contracts", "contracts.contract_type_id", "=", "contract_types.id");
        $contract_types = $query->get();

        $sorted_array = array();
        $return_data = array();
        foreach ($contract_types as $contract_type) {
            $contract_count = self::getContractsByTypeForRegionAndHealthSystem($contract_type->id, $user_id, $group_id, $region, $facility, true, $selected_start_date, $selected_end_date, 1);

            $contract_spend = Hospital::totalPaid($contract_count, $contract_type);

            if ($contract_spend > 0) {
                $return_data[] = [
                    "total_spend" => $contract_spend,
                    "contract_type_id" => $contract_type->id,
                    "contract_type_name" => $contract_type->name,
                    "active_contract_count" => count($contract_count)
                ];
            }
        }

        arsort($return_data);
        $sorted_array = array_values($return_data);
        return $sorted_array;
    }

    public static function getContractSpendYTD($contract_type, $user_id, $group_id, $region, $facility, $total, $selected_start_date, $selected_end_date)
    {
        $contracts = self::getContractsByTypeForRegionAndHealthSystem($contract_type, $user_id, $group_id, $region, $facility, true, $selected_start_date, $selected_end_date);
        $sorted_array = array();
        $data = array();
        foreach ($contracts as $contract) {
            $percentage = 0;
            $total_amount_paid = Amount_paid::select(DB::raw("SUM(amount_paid.amountPaid) as paid"))
                ->join('amount_paid_physicians', 'amount_paid_physicians.amt_paid_id', 'amount_paid.id')
                ->where("amount_paid.contract_id", "=", $contract->id)
                ->where('amount_paid_physicians.physician_id', '=', $contract->physicians_id)
                ->where("amount_paid.start_date", ">=", mysql_date($contract->agreement_start_date))->first();

            if ($total > 0) {
                $percentage = ($total_amount_paid->paid / $total) * 100;
            } elseif ($total_amount_paid->paid > 0) {
                $percentage = 100;
            } else {
                $percentage = 0;
            }
            $contract->percentage = number_format($percentage, 2);
            $data[] = [
                "percentage" => number_format($percentage, 2),
                "contract_name" => $contract->contract_name,
                "hospital_name" => $contract->hospital_name,
                "physician_name" => $contract->physician_name,
                "amount" => $total_amount_paid->paid != null ? $total_amount_paid->paid : 0,
                "agreement_start_date" => format_date($contract->agreement_start_date),
                "agreement_end_date" => format_date($contract->agreement_end_date),
                "manual_contract_end_date" => format_date($contract->manual_contract_end_date),
                "agreement_valid_upto_date" => format_date($contract->agreement_valid_upto_date)
            ];
        }
        arsort($data);
        $sorted_array = array_values($data);
        return $sorted_array;
    }

    public static function fetch_contract_effectiveness_for_health_system_users($user_id, $group_id, $region, $facility, $selected_start_date, $selected_end_date)
    {
        $query = self::select(DB::raw("distinct(contract_types.id),contract_types.name"))
            ->join("contracts", "contracts.contract_type_id", "=", "contract_types.id");
        $contract_types = $query->get();

        $return_data = array();
        $last_date_of_prev_month = date('m/d/Y', strtotime('last day of previous month'));
        $now = new DateTime('now');
        foreach ($contract_types as $contract_type) {
            $color = '#d3d3d3';
            $contracts = self::getContractsByTypeForRegionAndHealthSystem($contract_type->id, $user_id, $group_id, $region, $facility, true, $selected_start_date, $selected_end_date, 1);

            $total_expected_hrs = 0;
            $total_worked_hrs = 0;
            $contract_ids = array();

            $selected_start_date = date("Y-m-d", strtotime($selected_start_date));
            $selected_end_date = date("Y-m-d", strtotime($selected_end_date));
            $derived_end_date = '';
            foreach ($contracts as $contract) {

                if ($selected_end_date < $contract->manual_contract_end_date) {
                    $derived_end_date = $selected_end_date;
                } else {
                    $derived_end_date = $contract->manual_contract_end_date;
                }

                // Below changes are done based on payment frequency of agreement by akash.
                $payment_type_factory = new PaymentFrequencyFactoryClass();
                $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($contract->agreement->payment_frequency_type);
                $res_pay_frequency = $payment_type_obj->calculateDateRange($contract->agreement, $selected_start_date, $derived_end_date);
                $payment_frequency_range = $res_pay_frequency['date_range_with_start_end_date'];

                $months = count($payment_frequency_range);
                $startEndDatesForYear['year_start_date'] = $payment_frequency_range[0]['start_date'];
                $startEndDatesForYear['year_end_date'] = $payment_frequency_range[count($payment_frequency_range) - 1]['end_date'];
                // payment frequency changes ends here.

                $total_expected_hrs = $total_expected_hrs + ($months * $contract->expected_hours);
                $contract_ids[] = $contract->id;
                $total_worked = PhysicianLog::select(DB::raw("sum(duration) as hours"))
                    ->whereBetween('date', [mysql_date($startEndDatesForYear['year_start_date']), mysql_date($startEndDatesForYear['year_end_date'])])
                    ->where('contract_id', '=', $contract->id)->first();

                $year_start_date_formatted = $startEndDatesForYear['year_start_date'];
                $contract_total_worked_hours = strtotime($year_start_date_formatted) == strtotime($contract->prior_start_date) ? $total_worked->hours + $contract->prior_worked_hours : $total_worked->hours;
                $total_worked_hrs = $total_worked_hrs + $contract_total_worked_hours;
            }

            if (count($contract_ids) > 0) {
                if ($total_expected_hrs > 0) {
                    $contract_effectiveness = ($total_worked_hrs / $total_expected_hrs) * 100;
                } elseif ($total_worked_hrs > 0) {
                    $contract_effectiveness = 100;
                } else {
                    $contract_effectiveness = 0;
                }
            } else {
                $contract_effectiveness = 0;
            }
            if ($contract_effectiveness > 80) {
                $color = '#109618';
            } elseif ($contract_effectiveness > 50) {
                $color = '#f90';
            } else {
                $color = '#dc3912';
            }
            if (count($contracts) > 0) {
                $return_data[] = [
                    "contract_type_id" => $contract_type->id,
                    "contract_type_name" => $contract_type->name,
                    "active_contract_count" => count($contracts),
                    "contract_effectiveness" => $contract_effectiveness > 0 ? $contract_effectiveness > 100 ? 100 : number_format($contract_effectiveness, 2) : 0,
                    "style_color" => $color
                ];
            }
        }
        return $return_data;
    }

    public static function getContractsTypeEffectivness($contract_type, $user_id, $group_id, $region, $facility, $selected_start_date, $selected_end_date)
    {
        
        $contracts = self::getContractsByTypeForRegionAndHealthSystem($contract_type, $user_id, $group_id, $region, $facility, true, $selected_start_date, $selected_end_date);
        $sorted_array = array();
        $data = array();
        $last_date_of_prev_month = date('m/d/Y', strtotime('last day of previous month'));
        $now = new DateTime('now');
        $derived_end_date = '';
        $selected_start_date = date("Y-m-d", strtotime($selected_start_date));
        $selected_end_date = date("Y-m-d", strtotime($selected_end_date));
        foreach ($contracts as $contract) {

            if ($selected_end_date < $contract->manual_contract_end_date) {
                $derived_end_date = $selected_end_date;
            } else {
                $derived_end_date = $contract->manual_contract_end_date;
            }

            // Below changes are done based on payment frequency of agreement by akash.
            $payment_type_factory = new PaymentFrequencyFactoryClass();
            $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($contract->agreement->payment_frequency_type);
            $res_pay_frequency = $payment_type_obj->calculateDateRange($contract->agreement, $selected_start_date, $derived_end_date);
            $payment_frequency_range = $res_pay_frequency['date_range_with_start_end_date'];

            $months = count($payment_frequency_range);
            $startEndDatesForYear['year_start_date'] = $payment_frequency_range[0]['start_date'];
            $startEndDatesForYear['year_end_date'] = $payment_frequency_range[count($payment_frequency_range) - 1]['end_date'];
            // payment frequency changes ends here.

            $total_expected_hrs = $months * $contract->expected_hours;
            if ($contract->payment_type_id == PaymentType::HOURLY) {
                $max_expected_hrs = $contract->annual_cap;
            } else {
                $max_expected_hrs = 12 * $contract->expected_hours;
            }
            $contract_id = $contract->id;
            $total_worked = PhysicianLog::select(DB::raw("sum(duration) as hours"))
                ->whereBetween('date', [mysql_date($startEndDatesForYear['year_start_date']), mysql_date($startEndDatesForYear['year_end_date'])])
                ->where('physician_id', '=', $contract->physicians_id)
                ->where('contract_id', '=', $contract->id)->first();

            /*changes for adding contract prior worked hours values */
            $year_start_date_formatted = date_format(with(new DateTime($startEndDatesForYear['year_start_date']))->setTime(0, 0, 0), "Y-m-d");
            $contract_total_worked_hours = strtotime($year_start_date_formatted) == strtotime($contract->prior_start_date) ? $total_worked->hours + $contract->prior_worked_hours : $total_worked->hours;
            $total_worked_hrs = $contract_total_worked_hours;

            if ($total_expected_hrs > 0) {
                $contract_effectiveness = ($total_worked_hrs / $total_expected_hrs) * 100;
            } elseif ($total_worked_hrs > 0) {
                $contract_effectiveness = 100;
            } else {
                $contract_effectiveness = 0;
            }
            $data[] = [
                "contract_effectiveness" => number_format($contract_effectiveness > 0 ? $contract_effectiveness > 100 ? 100 : number_format($contract_effectiveness, 2) : 0, 2),
                "contract_name" => $contract->contract_name,
                "hospital_name" => $contract->hospital_name,
                "physician_name" => $contract->physician_name,
                "expected_hrs" => $total_expected_hrs != null ? $total_expected_hrs : 0,
                "worked_hrs" => $total_worked_hrs != null ? $total_worked_hrs : 0,
                "max_expected_hrs" => $max_expected_hrs != null ? $max_expected_hrs : 0,
                "agreement_start_date" => format_date($contract->agreement_start_date),
                "agreement_end_date" => format_date($contract->agreement_end_date),
                "manual_contract_end_date" => format_date($contract->manual_contract_end_date),
                "agreement_valid_upto_date" => format_date($contract->agreement_valid_upto_date)
            ];
        }
        arsort($data);
        $sorted_array = array_values($data);
        return $sorted_array;
    }

    public static function fetch_contract_spend_to_actual_for_health_system_users($user_id, $group_id, $region, $facility, $selected_start_date, $selected_end_date)
    {
        $query = self::select(DB::raw("distinct(contract_types.id),contract_types.name"))
            ->join("contracts", "contracts.contract_type_id", "=", "contract_types.id")
            ->orderByRaw("FIELD(contracts.contract_type_id , '2') DESC");;
        $contract_types = $query->get();

        $return_data = array();
        foreach ($contract_types as $contract_type) {
            $contracts = self::getContractsByTypeForRegionAndHealthSystem($contract_type->id, $user_id, $group_id, $region, $facility, true, $selected_start_date, $selected_end_date, 1);
            $total_expected_payment = 0;
            $total_spend_amount = 0;
            $contract_ids = array();
            $derived_end_date = '';
            $startEndDatesForYear = [];
            $selected_start_date = date("Y-m-d", strtotime($selected_start_date));
            $selected_end_date = date("Y-m-d", strtotime($selected_end_date));
            foreach ($contracts as $contract) {

                if ($selected_end_date < $contract->manual_contract_end_date) {
                    $derived_end_date = $selected_end_date;
                } else {
                    $derived_end_date = $contract->manual_contract_end_date;
                }

                // Below changes are done based on payment frequency of agreement by akash.
                $payment_type_factory = new PaymentFrequencyFactoryClass();
                $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($contract->agreement->payment_frequency_type);
                $res_pay_frequency = $payment_type_obj->calculateDateRange($contract->agreement, $selected_start_date, $derived_end_date);
                $payment_frequency_range = $res_pay_frequency['date_range_with_start_end_date'];

                $months = count($payment_frequency_range);
                $startEndDatesForYear['year_start_date'] = $payment_frequency_range[0]['start_date'];
                $startEndDatesForYear['year_end_date'] = $payment_frequency_range[count($payment_frequency_range) - 1]['end_date'];
                // payment frequency changes ends here.

                $total_expected_payment_contract = ContractRate::findTotalExpectedPayment($contract->id, with(new DateTime($startEndDatesForYear['year_start_date'])), $months);
                $total_expected_payment += $total_expected_payment_contract;
                $contract_ids[] = $contract->id;
                $total_spend = Amount_paid::select(DB::raw("sum(amountPaid) as paid"))
                    ->where("contract_id", "=", $contract->id)
                    ->where("start_date", ">=", mysql_date($startEndDatesForYear['year_start_date']))->first();

                $year_start_date_formatted = $startEndDatesForYear['year_start_date'];
                $contract_total_spend_amount = strtotime($year_start_date_formatted) == strtotime($contract->prior_start_date) ? $total_spend->paid + $contract->prior_amount_paid : $total_spend->paid;
                $total_spend_amount += $contract_total_spend_amount;
            }

            if (count($contract_ids) > 0) {
                if ($total_expected_payment > 0) {
                    $contract_effectiveness = ($total_spend_amount / $total_expected_payment) * 100;
                } elseif ($total_spend_amount > 0) {
                    $contract_effectiveness = 100;
                } else {
                    $contract_effectiveness = 0;
                }
            } else {
                $contract_effectiveness = 0;
            }
            if (count($contracts) > 0) {
                $return_data[] = [
                    "contract_type_id" => $contract_type->id,
                    "contract_type_name" => $contract_type->name,
                    "active_contract_count" => count($contracts),
                    "total_spend_amount" => $total_spend_amount,
                    "actual_spend_YTD" => '$' . formatNumber($total_spend_amount),
                    "contract_effectiveness" => $contract_effectiveness > 0 ? $contract_effectiveness > 100 ? 100 : number_format($contract_effectiveness, 2) : 0
                ];
            }
        }
        return $return_data;
    }

    public static function getContractSpendToActual($contract_type, $user_id, $group_id, $region, $facility, $totalSpend, $start_date, $end_date)
    {
        $contracts = self::getContractsByTypeForRegionAndHealthSystem($contract_type, $user_id, $group_id, $region, $facility, true, $start_date, $end_date);
        $sorted_array = array();
        $data = array();
        $last_date_of_prev_month = date('m/d/Y', strtotime('last day of previous month'));
        $now = new DateTime('now');
        $selected_start_date = date("Y-m-d", strtotime($start_date));
        $selected_end_date = date("Y-m-d", strtotime($end_date));
        $derived_end_date = '';
        foreach ($contracts as $contract) {

            if ($selected_end_date < $contract->manual_contract_end_date) {
                $derived_end_date = $selected_end_date;
            } else {
                $derived_end_date = $contract->manual_contract_end_date;
            }

            // Below changes are done based on payment frequency of agreement by akash.
            $payment_type_factory = new PaymentFrequencyFactoryClass();
            $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($contract->agreement->payment_frequency_type);
            $res_pay_frequency = $payment_type_obj->calculateDateRange($contract->agreement, $selected_start_date, $derived_end_date);
            $payment_frequency_range = $res_pay_frequency['date_range_with_start_end_date'];

            $months = count($payment_frequency_range);
            $startEndDatesForYear['year_start_date'] = $payment_frequency_range[0]['start_date'];
            $startEndDatesForYear['year_end_date'] = $payment_frequency_range[count($payment_frequency_range) - 1]['end_date'];
            // payment frequency changes ends here.

            $total_spend_YTD = Amount_paid::select(DB::raw("SUM(amountPaid) as paid"))
                ->where("contract_id", "=", $contract->id)
                ->where("start_date", ">=", mysql_date($startEndDatesForYear['year_start_date']))->first();

            /*changes for adding contract prior amount paid values */
            $year_start_date_formatted = date_format(with(new DateTime($startEndDatesForYear['year_start_date']))->setTime(0, 0, 0), "Y-m-d");
            $contract_total_spend_amount = strtotime($year_start_date_formatted) == strtotime($contract->prior_start_date) ? $total_spend_YTD->paid + $contract->prior_amount_paid : $total_spend_YTD->paid;
            $total_spend_YTD_amount = $contract_total_spend_amount;

            $total_expected_payment = ContractRate::findTotalExpectedPayment($contract->id, with(new DateTime($startEndDatesForYear['year_start_date']))->setTime(0, 0, 0), $months);
            if ($contract->payment_type_id == PaymentType::HOURLY) {
                $max_expected_payment = ContractRate::findAnnualHourlySpend($contract->id, with(new DateTime($startEndDatesForYear['year_start_date']))->setTime(0, 0, 0), ContractRate::FMV_RATE);
            } else {
                $max_expected_payment = ContractRate::findTotalExpectedPayment($contract->id, with(new DateTime($startEndDatesForYear['year_start_date']))->setTime(0, 0, 0), 12);
            }
            if ($totalSpend > 0) {
                $contract_effectiveness = ($total_spend_YTD_amount / $totalSpend) * 100;
            } elseif ($total_spend_YTD_amount > 0) {
                $contract_effectiveness = 100;
            } else {
                $contract_effectiveness = 0;
            }
            $data[] = [
                "contract_effectiveness" => number_format($contract_effectiveness > 0 ? $contract_effectiveness > 100 ? 100 : number_format($contract_effectiveness, 2) : 0, 2),
                "contract_name" => $contract->contract_name,
                "hospital_name" => $contract->hospital_name,
                "physician_name" => $contract->physician_name,
                "expected_spend_YTD" => $total_expected_payment != null ? $total_expected_payment : 0,
                "actual_spend_YTD" => $total_spend_YTD_amount != null ? $total_spend_YTD_amount : 0,
                "max_expected_payment" => $max_expected_payment != null ? $max_expected_payment : 0,
                "agreement_start_date" => format_date($contract->agreement_start_date),
                "agreement_end_date" => format_date($contract->agreement_end_date),
                "manual_contract_end_date" => format_date($contract->manual_contract_end_date),
                "agreement_valid_upto_date" => format_date($contract->agreement_valid_upto_date)
            ];
        }
        arsort($data);
        $sorted_array = array_values($data);
        return $sorted_array;
    }

    public static function fetch_contract_type_alerts_for_health_system_users($user_id, $group_id, $region, $facility, $selected_start_date, $selected_end_date)
    {
        $query = self::select(DB::raw("distinct(contract_types.id),contract_types.name"))
            ->join("contracts", "contracts.contract_type_id", "=", "contract_types.id");
        $contract_types = $query->get();

        $return_data = array();
        foreach ($contract_types as $contract_type) {
            $contracts = self::getContractsByTypeForRegionAndHealthSystem($contract_type->id, $user_id, $group_id, $region, $facility, false, $selected_start_date, $selected_end_date, 1);
            $alerts = 0;

            foreach ($contracts as $contract) {
                $logs = PhysicianLog::where("contract_id", '=', $contract->id)->get();
                if (count($logs) > 0) {
                    $paid = Amount_paid::where("contract_id", '=', $contract->id)->get();
                    if (count($paid) == 0) {
                        $alerts = $alerts + 1;
                    }
                } else {
                    $alerts = $alerts + 1;
                }
            }
            if ($alerts > 0) {
                $return_data[] = [
                    "contract_type_id" => $contract_type->id,
                    "contract_type_name" => $contract_type->name,
                    "remaining" => count($contracts) - $alerts,
                    "alerts" => $alerts
                ];
            }
        }
        return $return_data;
    }

    public static function getContractAlerts($contract_type, $user_id, $group_id, $region, $facility, $payment, $selected_start_date, $selected_end_date)
    {
        $contracts = self::getContractsByTypeForRegionAndHealthSystem($contract_type, $user_id, $group_id, $region, $facility, false, $selected_start_date, $selected_end_date);
        $data = array();
        foreach ($contracts as $contract) {
            $alerts = 0;
            $logs = PhysicianLog::where("contract_id", '=', $contract->id)->get();
            if (count($logs) > 0) {
                $paid = Amount_paid::where("contract_id", '=', $contract->id)->get();
                if (count($paid) == 0) {
                    $alerts = $alerts + 1;
                }
            } else {
                $alerts = $alerts + 1;
            }
            if ($alerts > 0 && $payment == 0) {
                $data[] = [
                    "contract_name" => $contract->contract_name,
                    "hospital_name" => $contract->hospital_name,
                    "physician_name" => $contract->physician_name,
                    "agreement_start_date" => format_date($contract->agreement_start_date),
                    "agreement_end_date" => format_date($contract->agreement_end_date),
                    "manual_contract_end_date" => format_date($contract->manual_contract_end_date),
                    "agreement_valid_upto_date" => format_date($contract->agreement_valid_upto_date)
                ];
            } elseif ($alerts == 0 && $payment == 1) {
                $data[] = [
                    "contract_name" => $contract->contract_name,
                    "hospital_name" => $contract->hospital_name,
                    "physician_name" => $contract->physician_name,
                    "agreement_start_date" => format_date($contract->agreement_start_date),
                    "agreement_end_date" => format_date($contract->agreement_end_date),
                    "manual_contract_end_date" => format_date($contract->manual_contract_end_date),
                    "agreement_valid_upto_date" => format_date($contract->agreement_valid_upto_date)
                ];
            }
        }
        return $data;
    }

    public static function getContractTypesByRegionChart($user_id, $group, $start_date, $end_date)
    {
        $data = array();
        $system = HealthSystemUsers::where('user_id', '=', Auth::user()->id)->first();
        $regions = HealthSystemRegion::where('health_system_id', '=', $system->health_system_id)->get();
        foreach ($regions as $region) {
            $region_data = ContractType::fetch_contract_stats_for_health_system_users(Auth::user()->id, $group, $region->id, 0, $start_date, $end_date, 1);
            $data[] = [
                "region_id" => $region->id,
                "region_name" => $region->region_name,
                "region_data" => $region_data
            ];
        }
        return $data;
    }

    public static function getContractCountsByFacility($user_id, $region_id, $group, $start_date, $end_date)
    {
        $data = array();
        $tempdata = array();
        $sorted_data = array();
        $rows = array();
        $ids = array();
        if ($region_id == 0) {
            if ($group == Group::HEALTH_SYSTEM_USER) {
                $system = HealthSystemUsers::where('user_id', '=', Auth::user()->id)->first();
                $regions = HealthSystemRegion::where('health_system_id', '=', $system->health_system_id)->pluck('id');
            } else {
                $regions = HealthSystemRegionUsers::where('user_id', '=', Auth::user()->id)->pluck('health_system_region_id');
            }
        } else {
            $regions = [$region_id];
        }
        $facilities = RegionHospitals::select('hospitals.name as name', 'hospitals.id as hospital_id', 'region_hospitals.region_id as region_id')->join('hospitals', 'region_hospitals.hospital_id', '=', 'hospitals.id')
            ->whereIn('region_id', $regions)
            ->orderBy('hospitals.name', 'ASC')
            ->get();
        foreach ($facilities as $facility) {
            $active_contract_count = 0;
            $facility_data = ContractType::fetch_contract_stats_for_health_system_users(Auth::user()->id, $group, $facility->region_id, $facility->hospital_id, $start_date, $end_date, 1);
            foreach ($facility_data as $facility_counts) {
                $active_contract_count = $active_contract_count + $facility_counts['active_contract_count'];
            }
            $tempdata[] = [
                "active_contract_count" => $active_contract_count,
                "hospital_name" => $facility->name,
                "id" => $facility->hospital_id
            ];
        }
        arsort($tempdata);
        $sorted_data = array_values($tempdata);

        foreach ($sorted_data as $item) {
            $rows[] = [$item['hospital_name'], $item['active_contract_count']];
            $ids[] = [$item['id']];
        }
        $data["rows"] = $rows;
        $data["ids"] = $ids;
        return $data;
    }

    /*Function to fetch contract types of health system having active contracts in it*/
    public static function getAllSystemContractTypes($user_id, $group_id, $region, $facility, $payment_type)
    {
        $query = self::select(DB::raw("distinct(contract_types.id),contract_types.name"))
            ->join("contracts", "contracts.contract_type_id", "=", "contract_types.id");
        $contract_types = $query->get();
        $exclude_perDiem = true;
        $return_data = array();
        foreach ($contract_types as $contract_type) {
            $contract_list = Contract::getContractsForHealthSystemUsers($contract_type->id, $payment_type, $user_id, $group_id, $region, $facility, $exclude_perDiem);

            if (count($contract_list) > 0) {
                $return_data[$contract_type->id] = $contract_type->name;
            }
        }
        return $return_data;
    }


    public static function getContractsType($contract_type, $user_id, $facility)
    {
        $contract_list = Contract::getContractsType($contract_type, $user_id, $facility);
        return $contract_list;
    }

    public static function getContractTypesByPhysician($physicianId, $hospital_id)
    {
        $results = DB::table("physicians")
            ->select("contract_types.*")
            ->join("physician_contracts", "physician_contracts.physician_id", "=", "physicians.id")
            ->join("contracts", "contracts.id", "=", "physician_contracts.contract_id")
            ->join("contract_types", "contract_types.id", "=", "contracts.contract_type_id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
            ->where("physicians.id", "=", $physicianId)
            ->whereNull("contracts.deleted_at");

        if ($hospital_id > 0) {
            $results = $results->where("hospitals.id", "=", $hospital_id);
        }
        $results = $results->where("contracts.end_date", "=", "0000-00-00 00:00:00")
            ->pluck("name", "id")->toArray();
        return self::prefixDefaultOptions($results);
    }

    public static function getAverageDurationOfPaymentApprovalPopUp($user_id, $facility, $contract_type_id)
    {
        $contract_list = DB::table("contracts")->select("contracts.*", DB::raw('CONCAT(physicians.first_name, " ", physicians.last_name) AS physician_name'),
            "contract_names.name as contract_name", "agreements.id as agreement_id", "agreements.approval_process as approval_process", "agreements.start_date as agreement_start_date",
            "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date", "hospitals.name as hospital_name")
            ->join('physicians', 'physicians.id', '=', 'contracts.physician_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
            ->join("hospital_user", "hospital_user.hospital_id", "=", "hospitals.id")
            ->where('hospital_user.user_id', '=', $user_id)
            ->where("contracts.contract_type_id", "=", $contract_type_id)
            ->whereRaw("agreements.is_deleted=0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("agreements.end_date >= now()");

        if ($facility != 0) {
            $contract_list = $contract_list->where("hospitals.id", "=", $facility);
        }
        $contract_list = $contract_list->whereNull('contracts.deleted_at')->distinct()->get();
        $return_data = array();
        foreach ($contract_list as $contract) {
            $startEndDatesForYear = Agreement::getAgreementStartDateForYear($contract->agreement_id);
            $month = "00";
            $year = "0000";
            $total_days = 0;
            $approval_date = '0000-00-00';
            $count = 0;

            // Below code is added for updating the time_to_approve columns in physician_logs table.
            $agreement_data = Agreement::getAgreementData($contract->agreement_id);
            $payment_type_factory = new PaymentFrequencyFactoryClass();
            $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
            $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);

            $period_dates = $res_pay_frequency['date_range_with_start_end_date'];

            $log_count = 0;
            foreach ($period_dates as $dates_obj) {
                if (strtotime($dates_obj['start_date']) >= strtotime($startEndDatesForYear['year_start_date']->format('Y-m-d')) && strtotime($dates_obj['end_date']) <= strtotime($startEndDatesForYear['year_end_date']->format('Y-m-d'))) {
                    $query = PhysicianLog::select("physician_logs.*")
                        ->whereNull('physician_logs.deleted_at')
                        ->where('physician_logs.contract_id', '=', $contract->id)
                        ->whereBetween('physician_logs.date', [mysql_date($dates_obj['start_date']), mysql_date($dates_obj['end_date'])])
                        ->orderBy('physician_logs.date', 'asc');
                    $logs = $query->distinct()->first();

                    if ($logs) {

                        $time_arr = [$logs->time_to_approve_by_physician, $logs->time_to_approve_by_level_1, $logs->time_to_approve_by_level_2, $logs->time_to_approve_by_level_3, $logs->time_to_approve_by_level_4, $logs->time_to_approve_by_level_5, $logs->time_to_approve_by_level_6];
                        $days_sume = array_sum($time_arr);

                        $log_count = $log_count + 1;
                        $total_days += $days_sume;
                    }
                }
            }

            $return_data[] = [
                "hospital_name" => $contract->hospital_name,
                "physician_name" => $contract->physician_name,
                "contract_name" => $contract->contract_name,
                "agreement_start_date" => $startEndDatesForYear['year_start_date']->format('m/d/Y'),
                "agreement_end_date" => $startEndDatesForYear['year_end_date']->format('m/d/Y'),
                "rate" => number_format($log_count > 0 ? ($total_days / $log_count) : 0, 2),
            ];
        }

        return $return_data;
    }

    public static function getAverageDurationOfProviderApprovalPopUp($user_id, $facility, $contract_type_id)
    {
        $contract_list = DB::table("contracts")->select("contracts.*", DB::raw('CONCAT(physicians.first_name, " ", physicians.last_name) AS physician_name'),
            "contract_names.name as contract_name", "agreements.id as agreement_id", "agreements.approval_process as approval_process", "agreements.start_date as agreement_start_date",
            "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date", "hospitals.name as hospital_name")
            ->join('physicians', 'physicians.id', '=', 'contracts.physician_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
            ->join("hospital_user", "hospital_user.hospital_id", "=", "hospitals.id")
            ->where('hospital_user.user_id', '=', $user_id)
            ->where("contracts.contract_type_id", "=", $contract_type_id)
            ->whereRaw("agreements.is_deleted=0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("agreements.end_date >= now()");

        if ($facility != 0) {
            $contract_list = $contract_list->where("hospitals.id", "=", $facility);
        }
        $contract_list = $contract_list->whereNull('contracts.deleted_at')->distinct()->get();

        $return_data = array();
        foreach ($contract_list as $contract) {
            $startEndDatesForYear = Agreement::getAgreementStartDateForYear($contract->agreement_id);
            $total_days = 0;
            $total_logs = 0;

            $query = PhysicianLog::select("physician_logs.*")
                ->whereNull('physician_logs.deleted_at')
                ->where('physician_logs.contract_id', '=', $contract->id)
                ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))])
                ->orderBy('physician_logs.date', 'asc');
            $logs = $query->avg('physician_logs.time_to_approve_by_physician');

            

            if (!empty($logs)) {
                $total_days = $logs;
            }

                $return_data[] = [
                "hospital_name" => $contract->hospital_name,
                "physician_name" => $contract->physician_name,
                "contract_name" => $contract->contract_name,
                "agreement_start_date" => $startEndDatesForYear['year_start_date']->format("m/d/Y"),
                "agreement_end_date" => $startEndDatesForYear['year_end_date']->format("m/d/Y"),
                "rate" => number_format($total_days, 2),
            ];
        }

        return $return_data;
    }

    public static function getContractTypesForPerformansDashboard($user_id, $region, $facility, $practice_type, $group_id)
    {
        $contract_types = self::select(DB::raw("distinct(contract_types.id),contract_types.name"))
            ->join("contracts", "contracts.contract_type_id", "=", "contract_types.id")
            ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
            ->join("practices", "practices.id", "=", "physician_contracts.practice_id")
            ->join("hospitals", "hospitals.id", "=", "practices.hospital_id")
            ->join("hospital_user", "hospital_user.hospital_id", "=", "hospitals.id");

        if ($group_id == Group::HEALTH_SYSTEM_USER) {
            $contract_types = $contract_types->join("region_hospitals", "region_hospitals.hospital_id", "=", "hospitals.id");
            $contract_types = $contract_types->whereNull("region_hospitals.deleted_at");
        }

        if ($region != 0) {
            $contract_types = $contract_types->where('region_hospitals.region_id', '=', $region);
        }

        if ($facility != 0) {
            $contract_types = $contract_types->where("hospitals.id", "=", $facility);
        } else {
            $hospitals = Hospital::select('hospitals.id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->get();

            $hospital_list = array();
            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('performance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital->id;
                }
            }

            $contract_types = $contract_types->whereIn("hospitals.id", $hospital_list);
        }

        if ($practice_type != 0) {
            if ($practice_type == 2) {
                $contract_types = $contract_types->whereBetween('practices.practice_type_id', array(1, 3));
            } elseif ($practice_type == 3) {
                $contract_types = $contract_types->whereBetween('practices.practice_type_id', array(4, 6));
            } else {
                $contract_types = $contract_types->whereBetween('practices.practice_type_id', array(1, 6));
            }
        }

        $contract_types = $contract_types
            ->where('hospital_user.user_id', '=', $user_id)
            ->whereNull("contracts.deleted_at")
            ->whereNotIn('contracts.payment_type_id', array(3, 5, 8))
            ->orderBy('name', 'asc')
            ->pluck('name', 'id')->toArray();

        return $contract_types;
    }

    public static function getManagementContractTypeChart($user_id, $region, $facility, $practice_type, $contract_type, $group_id)
    {
        $return_data = array();

        $contracts = Contract::select('contracts.id', 'contracts.agreement_id', 'physician_contracts.physician_id as physician_id')
            ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
            ->join("practices", "practices.id", "=", "physician_contracts.practice_id")
            ->join("hospitals", "hospitals.id", "=", "practices.hospital_id")
            ->join("hospital_user", "hospital_user.hospital_id", "=", "hospitals.id");

        if ($group_id == Group::HEALTH_SYSTEM_USER) {
            $contracts = $contracts->join("region_hospitals", "region_hospitals.hospital_id", "=", "hospitals.id");
            $contracts = $contracts->whereNull("region_hospitals.deleted_at");
        }

        if ($region != 0) {
            $contracts = $contracts->where('region_hospitals.region_id', '=', $region);
        }

        if ($facility != 0) {
            $contracts = $contracts->where("hospitals.id", "=", $facility);
        } else {
            $hospitals = Hospital::select('hospitals.id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->get();

            $hospital_list = array();
            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('performance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital->id;
                }
            }

            $contracts = $contracts->whereIn("hospitals.id", $hospital_list);
        }

        if ($practice_type != 0) {
            if ($practice_type == 2) {
                $contracts = $contracts->whereBetween('practices.practice_type_id', array(1, 3));
            } elseif ($practice_type == 3) {
                $contracts = $contracts->whereBetween('practices.practice_type_id', array(4, 6));
            } else {
                $contracts = $contracts->whereBetween('practices.practice_type_id', array(1, 6));
            }
        }

        $contracts = $contracts
            ->where('hospital_user.user_id', '=', $user_id)
            ->where("contracts.contract_type_id", "=", $contract_type)
            ->whereNotIn("contracts.payment_type_id", array(3, 5, 8))
            ->whereNull("contracts.deleted_at")
            ->distinct()->get();

        foreach ($contracts as $contract) {
            $startEndDatesForYear = Agreement::getAgreementStartDateForYear($contract->agreement_id);

            $total_durations = PhysicianLog::
            select(DB::raw("action_categories.id as category_id, action_categories.name as category_name, SUM(physician_logs.duration) as total_duration"))
                ->join("actions", "actions.id", "=", "physician_logs.action_id")
                ->join("action_categories", "action_categories.id", "=", "actions.category_id")
                ->where("physician_logs.contract_id", "=", $contract->id)
                ->where('physician_logs.physician_id', '=', $contract->physician_id)
                ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))])
                ->whereNull("physician_logs.deleted_at")
                ->orderBy('action_categories.id', 'asc')
                ->distinct()->get();

            if (count($total_durations) > 0) {
                foreach ($total_durations as $logs_duration) {
                    if ($logs_duration->category_id) {
                        $collection = collect($return_data);
                        $check_exist = $collection->contains('category_id', $logs_duration->category_id);

                        if ($check_exist) {
                            $data = collect($return_data)->where('category_id', $logs_duration->category_id)->all();
                            foreach ($data as $data) {
                                $total_duration = $data["total_duration"] + $logs_duration->total_duration;

                                $category_id = $data["category_id"];

                                foreach ($return_data as $key => $value) {
                                    if ($value["category_id"] == $category_id) {
                                        unset($return_data[$key]);
                                    }
                                }

                                $return_data[] = [
                                    "category_id" => $logs_duration->category_id,
                                    "category_name" => $logs_duration->category_name,
                                    "total_duration" => formatNumber($total_duration) . ""
                                ];
                            }
                        } else {
                            $return_data[] = [
                                "category_id" => $logs_duration->category_id,
                                "category_name" => $logs_duration->category_name,
                                "total_duration" => formatNumber($logs_duration->total_duration) . ""
                            ];
                        }
                    }
                }
            }
        }
        $data = collect($return_data)->sortBy('category_id')->toArray();

        return $data;
    }

    public static function getActualToExpectedTimeContractTypeChart($user_id, $region, $facility, $practice_type, $contract_type, $group_id)
    {
        $return_data = array();

        $contracts = Contract::select('contracts.id', 'contracts.agreement_id', 'contracts.expected_hours', 'physician_contracts.physician_id as physician_id')
            ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
            ->join("practices", "practices.id", "=", "physician_contracts.practice_id")
            ->join("hospitals", "hospitals.id", "=", "practices.hospital_id")
            ->join("hospital_user", "hospital_user.hospital_id", "=", "hospitals.id");

        if ($group_id == Group::HEALTH_SYSTEM_USER) {
            $contracts = $contracts->join("region_hospitals", "region_hospitals.hospital_id", "=", "hospitals.id");
            $contracts = $contracts->whereNull("region_hospitals.deleted_at");
        }

        if ($region != 0) {
            $contracts = $contracts->where('region_hospitals.region_id', '=', $region);
        }

        if ($facility != 0) {
            $contracts = $contracts->where("hospitals.id", "=", $facility);
        } else {
            $hospitals = Hospital::select('hospitals.id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->get();

            $hospital_list = array();
            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('performance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital->id;
                }
            }

            $contracts = $contracts->whereIn("hospitals.id", $hospital_list);
        }

        if ($practice_type != 0) {
            if ($practice_type == 2) {
                $contracts = $contracts->whereBetween('practices.practice_type_id', array(1, 3));
            } elseif ($practice_type == 3) {
                $contracts = $contracts->whereBetween('practices.practice_type_id', array(4, 6));
            } else {
                $contracts = $contracts->whereBetween('practices.practice_type_id', array(1, 6));
            }
        }

        $contracts = $contracts
            ->where('hospital_user.user_id', '=', $user_id)
            ->where("contracts.contract_type_id", "=", $contract_type)
            ->whereNotIn("contracts.payment_type_id", array(3, 5, 8))
            ->whereNull("contracts.deleted_at")
            ->distinct()->get();

        $total_expected_hours = 0;
        $total_actual_hours = 0;
        $total_remaining_hours = 0;
        $contract_ids = [];

        foreach ($contracts as $contract) {
            $startEndDatesForYear = Agreement::getAgreementStartDateForYear($contract->agreement_id);

            $expected_hours = $contract->expected_hours;

            $start_date = $startEndDatesForYear['year_start_date']->format('Y-m-d');
            $end_date = date("Y-n-j", strtotime("last day of previous month"));

            $start_date = strtotime($start_date);
            $end_date = strtotime($end_date);

            $year_start_date = date('Y', $start_date);
            $year_end_date = date('Y', $end_date);

            $month_start_date = date('m', $start_date);
            $month_end_date = date('m', $end_date);

            $months_diff = (($year_end_date - $year_start_date) * 12) + ($month_end_date - $month_start_date);
            $prior_periods = $months_diff + 1;

            $total_expected_hours = $prior_periods * $expected_hours;

            $total_durations = PhysicianLog::select(DB::raw("SUM(physician_logs.duration) as total_durations"))
                ->join("log_approval", "log_approval.log_id", "=", "physician_logs.id")
                ->whereNull("physician_logs.deleted_at")
                ->where('physician_logs.contract_id', '=', $contract->id)
                ->where("log_approval.approval_managers_level", ">", 0)
                ->where("log_approval.approval_status", "=", "1")
                ->where('physician_logs.physician_id', '=', $contract->physician_id)
                ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))])
                ->first();

            if ($total_durations->total_durations) {
                $total_durations = $total_durations->total_durations;
                $actual_hours = $total_durations;
                $remaining_hours = $total_durations > $total_expected_hours ? 0 : $total_expected_hours - $total_durations;

                $total_actual_hours += $actual_hours;
                if (!in_array($contract->id, $contract_ids)) {
                    array_push($contract_ids, $contract->id);
                    $total_remaining_hours += $remaining_hours;
                }
            }
        }

        if (count($contracts) > 0) {
            if ($total_actual_hours > 0 || $total_remaining_hours > 0) {
                $return_data[] = [
                    "type_id" => '1001',
                    "type_name" => 'Actual Hours',
                    "total_hours" => formatNumber($total_actual_hours) . ""
                ];

                $return_data[] = [
                    "type_id" => '1002',
                    "type_name" => 'Remaining Expected',
                    "total_hours" => formatNumber($total_remaining_hours) . ""
                ];
            }
        }

        return $return_data;
    }

    public static function getManagementDutyPopUp($user_id, $region, $facility, $practice_type, $contract_type, $specialty, $provider, $group_id, $category_id)
    {
        $return_data = array();

        $contracts = Contract::
        select(DB::raw("hospitals.id as hospitals_id, hospitals.name as hospital_name, agreements.id as agreement_id, contracts.id as contract_id, contract_names.name as contract_name, 
            physicians.id as physicians_id, CONCAT(physicians.first_name,' ',physicians.last_name) as physician_name"))
            ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
            ->join("physicians", "physicians.id", "=", "physician_contracts.physician_id")
            ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
            ->join("hospital_user", "hospital_user.hospital_id", "=", "hospitals.id")
            ->join("practices", "practices.id", "=", "physician_contracts.practice_id");

        if ($group_id == Group::HEALTH_SYSTEM_USER) {
            $contracts = $contracts->join("region_hospitals", "region_hospitals.hospital_id", "=", "hospitals.id");
            $contracts = $contracts->whereNull("region_hospitals.deleted_at");
        }

        if ($region != 0) {
            $contracts = $contracts->where('region_hospitals.region_id', '=', $region);
        }

        if ($facility != 0) {
            $contracts = $contracts->where("hospitals.id", "=", $facility);
        } else {
            $hospitals = Hospital::select('hospitals.id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->get();

            $hospital_list = array();
            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('performance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital->id;
                }
            }

            $contracts = $contracts->whereIn("hospitals.id", $hospital_list);
        }

        if ($contract_type != 0) {
            $contracts = $contracts->where("contracts.contract_type_id", "=", $contract_type);
        }

        if ($specialty != 0) {
            $contracts = $contracts->where("physicians.specialty_id", "=", $specialty);
        }

        if ($provider != 0) {
            $contracts = $contracts->where("physicians.id", "=", $provider);
        }

        if ($practice_type != 0) {
            if ($practice_type == 2) {
                $contracts = $contracts->whereBetween('practices.practice_type_id', array(1, 3));
            } elseif ($practice_type == 3) {
                $contracts = $contracts->whereBetween('practices.practice_type_id', array(4, 6));
            } else {
                $contracts = $contracts->whereBetween('practices.practice_type_id', array(1, 6));
            }
        }

        $contracts = $contracts->where('hospital_user.user_id', '=', $user_id)
            ->whereNotIn("contracts.payment_type_id", array(3, 5, 8))
            ->whereNull("contracts.deleted_at")
            ->distinct()
            ->get();

        foreach ($contracts as $contract) {
            $startEndDatesForYear = Agreement::getAgreementStartDateForYear($contract->agreement_id);

            $logs = PhysicianLog::
            select(DB::raw("physician_logs.date as log_date, physician_logs.duration as log_duration, actions.name as action_name, physician_logs.details as log_details"))
                ->join("actions", "actions.id", "=", "physician_logs.action_id")
                ->join("action_categories", "action_categories.id", "=", "actions.category_id")
                ->where("physician_logs.contract_id", "=", $contract->contract_id)
                ->where("physician_logs.physician_id", "=", $contract->physicians_id)
                ->where("action_categories.id", "=", $category_id)
                ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))])
                ->whereNull("physician_logs.deleted_at")
                ->distinct()
                ->get();

            foreach ($logs as $log) {
                $return_data[] = [
                    "organization" => $contract->hospital_name,
                    "contract_name" => $contract->contract_name,
                    "physician_name" => $contract->physician_name,
                    "log_date" => date("m-d-Y", strtotime($log->log_date)),
                    "duration" => formatNumber($log->log_duration),
                    "action" => $log->action_name,
                    "details" => (strlen($log->log_details) > 0) ? $log->log_details : "-"
                ];
            }
        }
        return $return_data;
    }

    public static function getActualToExpectedPopUp($user_id, $region, $facility, $practice_type, $contract_type, $specialty, $provider, $group_id)
    {
        $return_data = array();

        $contracts = Contract::
        select(DB::raw("hospitals.id as hospitals_id, hospitals.name as hospital_name, agreements.id as agreement_id, contracts.id as contract_id, contract_names.name as contract_name, 
            contracts.expected_hours as expected_hours, physicians.id as physicians_id, CONCAT(physicians.first_name,' ',physicians.last_name) as physician_name, practices.name as practice_name"))
            ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
            ->join("physicians", "physicians.id", "=", "physician_contracts.physician_id")
            ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
            ->join("hospital_user", "hospital_user.hospital_id", "=", "hospitals.id")
            ->join("practices", "practices.id", "=", "physician_contracts.practice_id");

        if ($group_id == Group::HEALTH_SYSTEM_USER) {
            $contracts = $contracts->join("region_hospitals", "region_hospitals.hospital_id", "=", "hospitals.id");
            $contracts = $contracts->whereNull("region_hospitals.deleted_at");
        }

        if ($region != 0) {
            $contracts = $contracts->where('region_hospitals.region_id', '=', $region);
        }

        if ($facility != 0) {
            $contracts = $contracts->where("hospitals.id", "=", $facility);
        } else {
            $hospitals = Hospital::select('hospitals.id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->get();

            $hospital_list = array();
            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('performance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital->id;
                }
            }

            $contracts = $contracts->whereIn("hospitals.id", $hospital_list);
        }

        if ($contract_type != 0) {
            $contracts = $contracts->where("contracts.contract_type_id", "=", $contract_type);
        }

        if ($specialty != 0) {
            $contracts = $contracts->where("physicians.specialty_id", "=", $specialty);
        }

        if ($provider != 0) {
            $contracts = $contracts->where("physicians.id", "=", $provider);
        }

        if ($practice_type != 0) {
            if ($practice_type == 2) {
                $contracts = $contracts->whereBetween('practices.practice_type_id', array(1, 3));
            } elseif ($practice_type == 3) {
                $contracts = $contracts->whereBetween('practices.practice_type_id', array(4, 6));
            } else {
                $contracts = $contracts->whereBetween('practices.practice_type_id', array(1, 6));
            }
        }

        $contracts = $contracts->where('hospital_user.user_id', '=', $user_id)
            ->whereNotIn("contracts.payment_type_id", array(3, 5, 8))
            ->whereNull("contracts.deleted_at")
            ->distinct()
            ->get();

        foreach ($contracts as $contract) {

            $startEndDatesForYear = Agreement::getAgreementStartDateForYear($contract->agreement_id);

            $expected_hours = $contract->expected_hours;

            $start_date = $startEndDatesForYear['year_start_date']->format('Y-m-d');
            $end_date = date("Y-n-j", strtotime("last day of previous month"));

            $start_date = strtotime($start_date);
            $end_date = strtotime($end_date);

            $year_start_date = date('Y', $start_date);
            $year_end_date = date('Y', $end_date);

            $month_start_date = date('m', $start_date);
            $month_end_date = date('m', $end_date);

            $months_diff = (($year_end_date - $year_start_date) * 12) + ($month_end_date - $month_start_date);
            $prior_periods = $months_diff + 1;

            $total_expected_hours = $prior_periods * $expected_hours;

            $total_durations = PhysicianLog::select(DB::raw("SUM(physician_logs.duration) as total_durations"))
                ->join("log_approval", "log_approval.log_id", "=", "physician_logs.id")
                ->whereNull("physician_logs.deleted_at")
                ->where('physician_logs.contract_id', '=', $contract->contract_id)
                ->where("physician_logs.physician_id", "=", $contract->physicians_id)
                ->where("log_approval.approval_managers_level", ">", 0)
                ->where("log_approval.approval_status", "=", "1")
                ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))])
                ->first();

            if ($total_durations->total_durations) {
                $total_durations = $total_durations->total_durations;
                $actual_hours = $total_durations;
                $remaining_hours = $total_durations > $total_expected_hours ? 0 : $total_expected_hours - $total_durations;
                $physicians_count = DB::table('physician_contracts')->where('physician_contracts.contract_id', '=', $contract->contract_id)->whereNull("physician_contracts.deleted_at")->count();

                $return_data[] = [
                    "organization" => $contract->hospital_name,
                    "contract_name" => $contract->contract_name,
                    "physician_name" => $contract->physician_name,
                    "practice_name" => $physicians_count > 1 ? $contract->practice_name : " - ",
                    "period" => $prior_periods,
                    "hours_entered" => formatNumber($actual_hours),
                    "hours_remaining" => formatNumber($remaining_hours)
                ];
            }
        }

        return $return_data;
    }

    public static function getContractTypesForComplianceReports($user_id, $hospital_id)
    {
        $defaults = [
            "0" => "All"
        ];

        $results = DB::table("contract_types")
            ->select("contract_types.*")
            ->join("contracts", "contracts.contract_type_id", "=", "contract_types.id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
            ->join("hospital_user", "hospital_user.hospital_id", "=", "hospitals.id")
            ->whereRaw("agreements.is_deleted = 0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("agreements.end_date >= now()");

        if ($hospital_id != 0) {
            $results = $results->where("hospitals.id", "=", $hospital_id);
        } else {
            $hospitals = Hospital::select('hospitals.id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->get();

            $hospital_list = array();
            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('compliance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital->id;
                }
            }

            $results = $results->where("hospitals.id", $hospital_list);
        }
        $results = $results->where("hospital_user.user_id", "=", $user_id)
            ->distinct('contract_types.id')
            ->groupBy("contract_types.id")
            ->pluck("name", "id")->toArray();

        return $defaults + $results;
    }

    public static function getContractsByTypeForRegionAndHealthSystem($contract_type, $user_id, $group_id, $region, $facility, $exclude_perDiem = false, $selected_start_date, $selected_end_date, $with_physician = 0)
    {
        $payment_type = 0;
        $contract_list = Contract::getContractsForRegionAndHealthSystemUsers($contract_type, $payment_type, $user_id, $group_id, $region, $facility, $exclude_perDiem, $selected_start_date, $selected_end_date, $with_physician);
        return $contract_list;
    }
}
