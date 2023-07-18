<?php

namespace App;

use App\customClasses\PaymentFrequencyFactoryClass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Validations\PaymentTypeValidation;
use Request;
use Redirect;
use Lang;
use Log;
use DateTime;

class PaymentType extends Model
{
    const STIPEND = 1;
    const HOURLY = 2;
    const PER_DIEM = 3;
    const PSA = 4;
    const PER_DIEM_WITH_UNCOMPENSATED_DAYS = 5;
    const MONTHLY_STIPEND = 6;
    const TIME_STUDY = 7;
    const PER_UNIT = 8;
    const REHAB = 9;

    protected $table = 'payment_types';

    public static function createPaymentType()
    {
        $validation = new PaymentTypeValidation();
        if (!$validation->validateCreate(Request::input())) {
            return Redirect::back()
                ->withErrors($validation->messages())
                ->withInput();
        }

        $paymenttType = new PaymentType();
        $paymenttType->name = Request::input('name');
        $paymenttType->description = Request::input('description');

        if (!$paymenttType->save()) {
            return Redirect::back()->with(['error' => Lang::get('payment-types.create_error')]);
        }

        return Redirect::route('payment_types.index')->with([
            'success' => Lang::get('payment-types.create_success')
        ]);
    }

    public static function editPaymentType($id)
    {
        $paymentType = PaymentType::findOrFail($id);

        $validation = new PaymentTypeValidation();
        if (!$validation->validateEdit(Request::input())) {
            return Redirect::back()
                ->withErrors($validation->messages())
                ->withInput();
        }

        $paymentType->name = Request::input('name');
        $paymentType->description = Request::input('description');

        if (!$paymentType->save()) {
            return Redirect::back()
                ->with(['error' => Lang::get('payment-types.edit_error')])
                ->withInput();
        }

        return Redirect::route('payment_types.index')->with([
            'success' => Lang::get('payment-types.edit_success')
        ]);
    }

    public static function deletePaymentType($id)
    {
        $paymentType = PaymentType::findOrFail($id);

        $count = $paymentType->actions()->count() +
            $paymentType->contracts()->count() +
            $paymentType->contractNames()->count();

        if ($count > 0) {
            return Redirect::back()->with(['error' => Lang::get('payment-types.delete_error')]);
        } else {
            $paymentType->delete();
        }

        return Redirect::route('payment_types.index')->with([
            'success' => Lang::get('payment-types.delete_success')
        ]);
    }

    public function actions()
    {
        return $this->hasMany('App\Action');
    }

    public function contracts()
    {
        return $this->hasMany('App\Contract');
    }

    public function contractNames()
    {
        return $this->hasMany('App\ContractName');
    }

    public static function getManagerOptions($userId, $type, $hospital_id, $physician_id, $physicians)
    {
        $default = ['0' => 'All'];
        if ($physician_id != 0) {
            $physician_ids[] = $physician_id;
        } else {
            $physician_ids = array_keys($physicians);
        }
        $payment_types = Hospital::fetch_payment_stats_for_hospital_users($userId, $type);
        $payment_type_list = array();
        foreach ($payment_types as $payment_types) {
            $check_payment_type_exists = Contract::where("payment_type_id", "=", $payment_types['payment_type_id'])
               
                ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
                ->whereIn("physician_contracts.physician_id", $physician_ids)->get();
            if (count($check_payment_type_exists) > 0) {
                $payment_type_id = $payment_types['payment_type_id'];
                $payment_type_name = $payment_types['payment_type_name'];
                if (!in_array($payment_type_id, $payment_type_list)) {
                    $payment_type_list[$payment_type_id] = $payment_type_name;
                }
            }
        }
        return $default + $payment_type_list;
    }

    public static function getPaymentTypeForPerformanceDashboard($user_id, $selected_hospital, $hospital, $selected_agreement, $agreements, $practices, $selected_practice, $physician_id, $physicians)
    {
        $default = ['0' => 'All'];
        
        $physician_ids = array_keys($physicians);
        $practice_ids = array_keys($practices);
        $agreement_ids = array_keys($agreements);

        $payment_types = Hospital::fetch_payment_stats_for_hospital_users_performance_dashboard($user_id);
        $payment_type_list = array();
        foreach ($payment_types as $payment_types) {
            $payment_type_existance = Contract::
            join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->join("physician_practices", "physician_practices.physician_id", "=", "contracts.physician_id")
                ->whereNotIn("contracts.payment_type_id", [3, 5])
                ->whereIn("agreements.id", $agreement_ids)
                ->where('agreements.archived', '=', false)
                ->whereIn("physician_practices.practice_id", $practice_ids)
                ->whereIn("contracts.physician_id", $physician_ids)
                ->where("payment_type_id", "=", $payment_types['payment_type_id'])
                ->get();
            if (count($payment_type_existance) > 0) {
                $payment_type_id = $payment_types['payment_type_id'];
                $payment_type_name = $payment_types['payment_type_name'];
                if (!in_array($payment_type_id, $payment_type_list)) {
                    $payment_type_list[$payment_type_id] = $payment_type_name;
                }
            }
        }
        return $payment_type_list;

    }

    public static function getName($id)
    {
        return self::where("id", "=", $id)
            ->value('name');
    }


    public static function fetch_contract_effectiveness_for_health_system_users($user_id, $group_id, $region, $facility, $selected_start_date, $selected_end_date)
    {
        $query = self::select(DB::raw("distinct(payment_types.id),payment_types.name"))
            ->join("contracts", "contracts.payment_type_id", "=", "payment_types.id")
            ->where("payment_types.id", "<>", self::PER_DIEM)
            ->where("payment_types.id", "<>", self::PSA)
            ->orderByRaw("FIELD(payment_types.id , '2') DESC");
        $contract_payment_types = $query->get();

        $return_data = array();
        foreach ($contract_payment_types as $contract_payment_type) {
            $contracts = self::getContractsByPaymentType($contract_payment_type->id, $user_id, $group_id, $region, $facility, true, $selected_start_date, $selected_end_date);
            $total_expected_payment = 0;
            $total_spend_amount = 0;
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

                $payment_type_factory = new PaymentFrequencyFactoryClass();
                $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($contract->agreement->payment_frequency_type);
                $res_pay_frequency = $payment_type_obj->calculateDateRange($contract->agreement, $selected_start_date, $derived_end_date);
                $payment_frequency_range = $res_pay_frequency['date_range_with_start_end_date'];

                $months = count($payment_frequency_range);
                $startEndDatesForYear['year_start_date'] = $payment_frequency_range[0]['start_date'];
                $startEndDatesForYear['year_end_date'] = $payment_frequency_range[count($payment_frequency_range) - 1]['end_date'];

                $total_expected_payment_contract = ContractRate::findTotalExpectedPayment($contract->id, with(new DateTime($startEndDatesForYear['year_start_date'])), $months);
                $total_expected_payment = $total_expected_payment + $total_expected_payment_contract;
                $contract_ids[] = $contract->id;
                $total_spend = Amount_paid::select(DB::raw("sum(amountPaid) as paid"))
                    ->where("contract_id", "=", $contract->id)
                    ->where("start_date", ">=", mysql_date($startEndDatesForYear['year_start_date']))->first();

                $year_start_date_formatted = $startEndDatesForYear['year_start_date'];
                $contract_total_spend_amount = strtotime($year_start_date_formatted) == strtotime($contract->prior_start_date) ? $total_spend->paid + $contract->prior_amount_paid : $total_spend->paid;
                $total_spend_amount = $total_spend_amount + $contract_total_spend_amount;

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
                    "payment_type_id" => $contract_payment_type->id,
                    "payment_type_name" => $contract_payment_type->name,
                    "active_contract_count" => count($contracts),
                    "contract_effectiveness" => $contract_effectiveness > 0 ? $contract_effectiveness > 100 ? 100 : number_format($contract_effectiveness, 2) : 0
                ];
            }
        }
        return $return_data;
    }

    public static function getContractsByPaymentType($payment_type, $user_id, $group_id, $region, $facility, $exclude_perDiem = false, $selected_start_date, $selected_end_date)
    {
        $contract_type = 0;/*common functio for contract and payment type so all contract type is 0*/
         $contract_list = Contract::getContractsForRegionAndHealthSystemUsers($contract_type, $payment_type, $user_id, $group_id, $region, $facility, $exclude_perDiem, $selected_start_date, $selected_end_date);
        return $contract_list;
    }

    public static function getContractSpendEffectivness($payment_type, $user_id, $group_id, $region, $facility, $start_date, $end_date)
    {
        $contracts = self::getContractsByPaymentType($payment_type, $user_id, $group_id, $region, $facility, true, $start_date, $end_date);
        $sorted_array = array();
        $data = array();
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

            $total_expected_payment = ContractRate::findTotalExpectedPayment($contract->id, with(new DateTime($startEndDatesForYear['year_start_date']))->setTime(0, 0, 0), $months);

            if ($contract->payment_type_id == PaymentType::HOURLY) {
                $max_expected_payment = ContractRate::findAnnualHourlySpend($contract->id, with(new DateTime($startEndDatesForYear['year_start_date']))->setTime(0, 0, 0), ContractRate::FMV_RATE);
            } else {
                $max_expected_payment = ContractRate::findTotalExpectedPayment($contract->id, with(new DateTime($startEndDatesForYear['year_start_date']))->setTime(0, 0, 0), 12);
            }

            $contract_id = $contract->id;
            $total_spend = Amount_paid::select(DB::raw("sum(amountPaid) as paid"))
                ->where('contract_id', '=', $contract->id)
                ->where("start_date", ">=", mysql_date($startEndDatesForYear['year_start_date']))->first();
            /*changes for adding contract prior amount paid values */
            $year_start_date_formatted = date_format(with(new DateTime($startEndDatesForYear['year_start_date']))->setTime(0, 0, 0), "Y-m-d");
            $contract_total_spend_amount = strtotime($year_start_date_formatted) == strtotime($contract->prior_start_date) ? $total_spend->paid + $contract->prior_amount_paid : $total_spend->paid;
            $total_spend_amount = $contract_total_spend_amount;

            if ($total_expected_payment > 0) {
                $contract_effectiveness = ($total_spend_amount / $total_expected_payment) * 100;
            } elseif ($total_spend_amount > 0) {
                $contract_effectiveness = 100;
            } else {
                $contract_effectiveness = 0;
            }
            $data[] = [
                "contract_effectiveness" => number_format($contract_effectiveness > 0 ? $contract_effectiveness > 100 ? 100 : number_format($contract_effectiveness, 2) : 0, 2),
                "contract_name" => $contract->contract_name,
                "hospital_name" => $contract->hospital_name,
                "physician_name" => $contract->physician_name,
                "expected_spend" => $total_expected_payment != null ? $total_expected_payment : 0,
                "actual_spend" => $total_spend_amount != null ? $total_spend_amount : 0,
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

    public static function getAllSystemPaymentTypes($user_id, $group_id, $region, $facility, $contract_type)
    {
        $query = self::select(DB::raw("distinct(payment_types.id),payment_types.name"))
            ->join("contracts", "contracts.payment_type_id", "=", "payment_types.id")
            ->where("payment_types.id", "<>", self::PER_DIEM);
        $contract_payment_types = $query->get();
        $contract_type = 0;
        $exclude_perDiem = false;
        $return_data = array();
        foreach ($contract_payment_types as $payment_type) {
            $contract_list = Contract::getContractsForHealthSystemUsers($contract_type, $payment_type->id, $user_id, $group_id, $region, $facility, $exclude_perDiem);

            if (count($contract_list) > 0) {
                $return_data[$payment_type->id] = $payment_type->name;
            }
        }
        return $return_data;
    }

    private static function prefixDefaultOptions($results)
    {

        $defaults = [
            "-1" => "All Payment Types"
        ];

        return $results;
    }

    /*Function to fetch payment types of health system having active contracts in it*/

    public function contractTemplates()
    {
        return $this->hasMany('App\ContractTemplate');
    }


}
