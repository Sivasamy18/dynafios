<?php

namespace App;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use StdClass;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use DateTime;
use App\PaymentType;
use App\SplitPaymentPercentage;

class Amount_paid extends Model
{

    public $timestamps = true;
    protected $table = 'amount_paid';
    protected $fillable = array('physician_id', 'amountPaid', 'start_date', 'end_date', 'contract_id');

    public static function amountPaid($contract_id, $startDate, $physician_id, $practice_id)
    {
        return $amount = DB::table('amount_paid')
            ->where('start_date', '<=', mysql_date(date($startDate)))
            ->where('end_date', '>=', mysql_date(date($startDate)))
//			->where('physician_id', '=', $physician_id)
            ->where('contract_id', '=', $contract_id)
            ->where('practice_id', '=', $practice_id)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    public static function penddingPaymentForPhysician($physician_id)
    {
        $agreements = Contract::where('physician_id', '=', $physician_id)->distinct()->pluck('agreement_id')->toArray();
        foreach ($agreements as $agreement) {
            $contracts = Contract::where('physician_id', '=', $physician_id)->where('agreement_id', '=', $agreement)->get();
            $agreement_data = Agreement::getAgreementData($agreement);
            //$agreement_month = $agreement_data->months[$agreement_data->current_month];
            $start_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_data->start_date))); // . " -1 month"
            //$prior_month_end_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_month->end_date) . " -1 month"));
            $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));
            foreach ($contracts as $contract) {
                $approved_months = PhysicianLog::getApprovedLogsMonths($contract, $start_date, $prior_month_end_date);
                foreach ($approved_months as $approved_month) {
                    $check = self::whereMonth('start_date', '=', $approved_month->month)
                        ->whereYear('start_date', '=', $approved_month->year)
                        ->where('physician_id', '=', $physician_id)
                        ->where('contract_id', '=', $contract->id)->get();
                    if (count($check) == 0) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public static function penddingPaymentForContract($contract_id)
    {
        $contract = Contract::findOrFail($contract_id);
        $agreement = Agreement::findOrFail($contract->agreement_id);
        $agreement_data = Agreement::getAgreementData($agreement);
        //$agreement_month = $agreement_data->months[$agreement_data->current_month];
        $start_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_data->start_date))); // . " -1 month"
        //$prior_month_end_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_month->end_date) . " -1 month"));
        $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));
        $approved_months = PhysicianLog::getApprovedLogsMonths($contract, $start_date, $prior_month_end_date);
        foreach ($approved_months as $approved_month) {
            $check = self::whereMonth('start_date', '=', $approved_month->month)
                ->whereYear('start_date', '=', $approved_month->year)
                ->where('contract_id', '=', $contract_id)->get();
            if (count($check) == 0) {
                return true;
            }
        }
        return false;
    }

    public function submitPayments($data, $contract_ids, $practice_ids, $start_date, $end_date, $selected_date, $prev_amounts = array(), $final_payment, $localtimeZone)
    {
        return $this->submitPayment($data, $contract_ids, $practice_ids, $start_date, $end_date, $selected_date, $prev_amounts, $final_payment, $localtimeZone);
    }

    private function submitPayment($data, $contract_ids, $practice_ids, $start_date, $end_date, $selected_date, $prev_amounts, $final_payment, $localtimeZone)
    {
        $printNewInInvoice = Request::has('printNew') ? Request::input('printNew') : array();
        $printOnInvoice = array();
        $payed_contracts = array();
        $payed_practices = array();
        /*updating already paid amount with updated values*/
        // if(count($prev_amounts) > 0) { //Original condition
        if ($prev_amounts != null) {
            foreach ($prev_amounts as $prev_amount) {
                $amount_paid = Amount_paid::findOrFail($prev_amount['id']);
                if ($amount_paid) {
                    if ($amount_paid->amountPaid != $prev_amount['amount']) {
                        DB::table('amount_paid')->where('id', $prev_amount['id'])->update(
                            array('amountPaid' => $prev_amount['amount'], 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => Auth::user()->id));
                    }
                    if ($prev_amount['print'] == "true") {
                        //Log::info('print prev', array($prev_amount['print']));
                        $printOnInvoice[$amount_paid->contract_id][$amount_paid->practice_id][] = $prev_amount['id'];
                        $payed_contracts[] = $amount_paid->contract_id;
                        $payed_practices[] = $amount_paid->practice_id;
                    }
                }
            }
        }

        $i = 0;
        $a = "";
        if ($contract_ids != null) {
            if (count($contract_ids) > 0) {
                foreach ($contract_ids as $key => $value) {
                    $practice_id = $practice_ids[$i];
                    $contract_id = $contract_ids[$i];
                    $contract = Contract::findOrFail($contract_id);
                    $practice = Practice::findOrFail($practice_id);
                    $a .= "asd";

                    /*
                     * Write inovice number code here...
                     */

                    $invoice_data = self::where('start_date', '=', $start_date)
                        ->where('end_date', '=', $end_date)
                        ->where('contract_id', '=', $contract_id)
                        ->where('practice_id', '=', $practice_id)
                        ->first();

                    if ($invoice_data) {
                        if ($invoice_data->invoice_no != "") {
                            $invoice_no = $invoice_data->invoice_no;
                        } else {

                            $last_invoice_no = HospitalInvoice::orderBy("last_invoice_no", "desc")
                                ->value('last_invoice_no');

                            $new_invoice_no = $last_invoice_no + 1;
                            $invoice_no = $contract_id . '-' . date('m', strtotime($start_date)) . substr(date('Y', strtotime($start_date)), -2) . '-' . $new_invoice_no;
                        }
                    } else {

                        $last_invoice_no = HospitalInvoice::orderBy("last_invoice_no", "desc")
                            ->value('last_invoice_no');

                        $new_invoice_no = $last_invoice_no + 1;
                        $invoice_no = $contract_id . '-' . date('m', strtotime($start_date)) . substr(date('Y', strtotime($start_date)), -2) . '-' . $new_invoice_no;
                    }


                    //if (!$datas) {
                    //	if (!isset($datas->id) || $datas->id == "") {
                    $new_amount_paid_id = DB::table('amount_paid')->insertGetId(
                        array('physician_id' => 0, 'amountPaid' => $data[$i],
                            'start_date' => $start_date, 'end_date' => $end_date,
                            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
                            'contract_id' => $contract_id, 'practice_id' => $practice_id,
                            'final_payment' => $final_payment[$i] == "true" ? 1 : 0,
                            'invoice_no' => $invoice_no,
                            'created_by' => Auth::user()->id,
                            'updated_by' => Auth::user()->id)
                    );

                    if (!$new_amount_paid_id) {
                        return 1;
                    } else {
                        /* START Split Payment Management */

                        $physician_contracts = DB::table('physician_contracts')
                            ->where('contract_id', '=', $contract_id)
                            ->where('practice_id', '=', $practice_id)
                            ->whereNull("deleted_at",)
                            ->get();

                        if (count($physician_contracts) > 0) {
                            foreach ($physician_contracts as $physician_contract_obj) {
                                $amount_paid_to_physician = array(
                                    'amt_paid_id' => $new_amount_paid_id,
                                    'amt_paid' => $data[$i],
                                    'physician_id' => $physician_contract_obj->physician_id,
                                    'contract_id' => $contract_id,
                                    'start_date' => $start_date,
                                    'end_date' => $end_date,
                                    'created_by' => Auth::user()->id,
                                    'updated_by' => Auth::user()->id,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                );

                                DB::table('amount_paid_physicians')->insert($amount_paid_to_physician);
                            }
                        }

                        $split_payment_percentage = DB::table('split_payment_percentage')
                            ->where('contract_id', '=', $contract_id)
                            ->where('hospital_id', '=', $practice->hospital_id)
                            ->where("is_active", '=', true)
                            ->get();

                        if ($split_payment_percentage) {
                            $amount_paid_amount = DB::table('amount_paid')
                                ->select('amountPaid')
                                ->where('start_date', "=", $start_date)
                                ->where('end_date', "=", $end_date)
//								->where('physician_id', "=", $value)
                                ->where('contract_id', '=', $contract_id)
                                ->where('practice_id', '=', $practice_id)
                                ->orderBy('id', 'desc')->first();

                            foreach ($split_payment_percentage as $split_payment) {
                                $split_amount = (($amount_paid_amount->amountPaid * $split_payment->payment_percentage) / 100);
                                // log::info('split_amount', array($split_amount));

                                $amount_paid_with_percentage = array(
                                    'amount_paid_id' => $new_amount_paid_id,
                                    "amount" => $split_amount,
                                    "index" => $split_payment->payment_percentage_index
                                );
                                DB::table('amount_paid_with_percentage')->insert($amount_paid_with_percentage);
                            }
                        }

                        /* END Split Payment Management */

                        //Log::info("input::", array(Input::get()));
                        if ($data[$i] > 0) {
                            if ($printNewInInvoice[$i] == "true") {
                                $payed_contracts[] = $contract_id;
                                $payed_practices[] = $practice_id;
                                //Log::info('print new',array($printNewInInvoice[$i]));
                                $printOnInvoice[$contract_id][$practice_id][] = $new_amount_paid_id;
                            }
                            if ($contract->is_lawson_interfaced) {

                                foreach ($physician_contracts as $contracts_phy_obj) {
                                    $amount_paid_id_physician = DB::table('amount_paid_physicians')->where('start_date', "=", $start_date)->where('end_date', "=", $end_date)->where('physician_id', '=', $contracts_phy_obj->physician_id)->where('contract_id', '=', $contract_id)->orderBy('id', 'desc')->first();
                                    DB::table('amount_paid')->where('id', $amount_paid_id_physician->amt_paid_id)->update(
                                        array('is_interfaced' => true));

                                    $hospital_interface_lawson_id = HospitalInterfaceLawson::where('hospital_id', '=', $practice->hospital_id)->first();
                                    $physician_interface_lawson_apcinvoice_id = PhysicianInterfaceLawsonApcinvoice::where('physician_id', '=', $amount_paid_id_physician->physician_id)->first();
                                    $contract_interface_lawson_apcdistrib_id = ContractInterfaceLawsonApcdistrib::where('contract_id', '=', $amount_paid_id_physician->contract_id)->first();

                                    $interface_tank_lawson = new InterfaceTankLawson();
                                    $interface_tank_lawson->hospital_id = $practice->hospital_id;
                                    $interface_tank_lawson->amount_paid_id = $amount_paid_id_physician->amt_paid_id;
                                    $interface_tank_lawson->hospital_interface_lawson_id = $hospital_interface_lawson_id->id;
                                    $interface_tank_lawson->physician_interface_lawson_apcinvoice_id = $physician_interface_lawson_apcinvoice_id->id;
                                    $interface_tank_lawson->contract_interface_lawson_apcdistrib_id = $contract_interface_lawson_apcdistrib_id->id;
                                    $interface_tank_lawson->created_by = Auth::user()->id;
                                    $interface_tank_lawson->updated_by = Auth::user()->id;
                                    if (!$interface_tank_lawson->save()) {
                                        return 1;
                                    }
                                }
                            }
                        }
                    }

                    $physician_logs = PhysicianLog::where('contract_id', '=', $contract_id)
                        ->where('date', ">=", $start_date)
                        ->where('date', "<=", $end_date)
                        ->get();

                    foreach ($physician_logs as $log) {
                        $approve_log = LogApproval::where('log_id', '=', $log->id)
                            ->orderBy('approval_managers_level', 'DESC')
                            ->first();

                        // Checking for the approval process ON in log_approval table.
                        if ($approve_log) {
                            $check_date = $approve_log->approval_date;
                        } else {
                            // Executin this condition when approval process is OFF
                            $log_obj = PhysicianLog::where('id', '=', $log->id)->first();
                            $check_date = $log_obj->updated_at;
                        }
                        $amount_paid_id = DB::table('amount_paid')->where('start_date', "=", $start_date)->where('end_date', "=", $end_date)->where('contract_id', '=', $contract_id)->where('practice_id', '=', $practice_id)->orderBy('id', 'asc')->first();
                        $datediff = strtotime($amount_paid_id->created_at) - strtotime($check_date);
                        $total_days = round($datediff / (60 * 60 * 24));
                        $physician_log = PhysicianLog::find($log->id);
                        if ((int)$total_days < 0) {
                            $total_days = 0;
                        }
                        $physician_log->time_to_payment = (int)$total_days;
                        $physician_log->save();
                    }
                    $i++;
                }
            }
        }

        if (count($payed_contracts) > 0) {
            $agreement_ids = [];
            $months_start = [];
            $months_end = [];
            $contract = Contract::findOrFail($payed_contracts[0]);
            if ($contract) {
                $agreement_ids[] = $contract->agreement_id;
                $agreement = Agreement::findOrFail($contract->agreement_id);
//				if($agreement->approval_process == 1) {
                $hospital = Hospital::findOrFail($agreement->hospital_id);
                $recipient = explode(',', $agreement->invoice_receipient);
                $months_start[] = $selected_date;
                $months_end[] = $selected_date;
                $months[] = $selected_date;
                $months[] = $selected_date;

                if ($hospital->approve_all_invoices) {
                    $data = $this->invoiceReportData($hospital, $agreement_ids, $payed_practices, $start_date, $end_date, $contract->contract_type_id, false, $payed_contracts, $printOnInvoice);
                } else {
                    $data = $this->invoiceReportData($hospital, $agreement_ids, $payed_practices, $months_start, $months_end, $contract->contract_type_id, false, $payed_contracts, $printOnInvoice);
                }
                $agreement_ids = implode(',', $agreement_ids);
                if ($practice_ids != null) {
                    if (count($practice_ids) > 0) {
                        $practice_ids = implode(',', array_unique($practice_ids));
                    }
                }
                if ($hospital->approve_all_invoices) {
                    $months[] = date('m', strtotime($start_date));
                    $months = implode(',', $months);
                } else {
                    $months = implode(',', $months);
                }
                $invoice_notes = InvoiceNote::getInvoiceNotes($hospital->id, InvoiceNote::HOSPITAL, $hospital->id, 0); /*hospital invoice notes*/
                $result = [
                    'hospital' => $hospital,
                    'payment_type' => -1,
                    'contract_type' => -1,
                    'practices' => $practice_ids,
                    'agreements' => $agreement_ids,
                    'recipient' => array_filter($recipient),
                    'months' => $months,
                    'data' => $data,
                    'start_date' => $start_date,
                    'invoice_notes' => $invoice_notes,
                    'is_lawson_interfaced' => false,
                    'localtimeZone' => $localtimeZone
                ];
//                log::debug('$result submit pmt', array($result));
                return $result;
            } else {
                $result = [];
                return $result;
            }
        } else {
            $result = [];
            return $result;
        }

    }

    public function invoiceReportData($hospital, $agreement_ids, $practice_ids, $months_start, $months_end, $contract_type, $check = false, $payedcontracts = array(), $printOnInvoice = array())
    {
        return $this->reportDataForInvoice($hospital, $agreement_ids, $practice_ids, $months_start, $months_end, $contract_type, $check, $payedcontracts, $printOnInvoice);
    }

    private function reportDataForInvoice($hospital, $agreement_ids, $practice_ids, $months_start, $months_end, $contract_type, $check, $payedcontracts, $printOnInvoice)
    {
        $result = [];
        $practice_data = [];
        $prevPractice = 0;
        $i = 0;
        $j = 0;
//		$invoice_no = HospitalInvoice::where("hospital_id","=",$hospital->id)
//			->whereMonth('created_at', '=', date('m'))
//			->whereYear('created_at', '=', date('Y'))
//			// ->orderBy("created_at","desc")
//			->orderBy("last_invoice_no","desc")
//			->value('last_invoice_no');

        $invoice_no = HospitalInvoice::orderBy("last_invoice_no", "desc")
            ->value('last_invoice_no');

        $invoice_no_period = '';
        $agreement_obj = new Agreement();
        foreach ($agreement_ids as $agreement_id) {
            $practice_data = [];
            $agreement = Agreement::findOrFail($agreement_id);
            foreach ($practice_ids as $practice_id) {
                $practiceName = Practice::withTrashed()->findOrFail($practice_id);
                $agreement_data_start = $agreement_obj->getAgreementData($agreement);
                $term = $agreement_data_start->term;
                $agreement_data_end = $agreement_obj->getAgreementData($agreement);

                if ($hospital->approve_all_invoices) {
                    if (is_array($months_start) && is_array($months_end)) {
                        $start_month_data = $months_start[$i];
                        $startmonth = strtotime(format_date($months_start[$i]));
                        $end_month_data = $months_end[$i];
                        $endmonth = strtotime(format_date($months_end[$i]));
                    } else {
                        $start_month_data = $months_start;
                        $startmonth = strtotime(format_date($months_start));
                        $end_month_data = $months_end;
                        $endmonth = strtotime(format_date($months_end));
                    }
                } else {
                    $start_month_data = $agreement_data_start->months[$months_start[$i]];
                    $startmonth = strtotime(format_date($start_month_data->start_date));
                    $end_month_data = $agreement_data_end->months[$months_end[$i]];
                    $endmonth = strtotime(format_date($end_month_data->end_date));
                }

                $checkPayment = true; //if payment submitted then true and its true for payment submiition and invoice day mail
                if ($check) {
                    if ($hospital->approve_all_invoices) {
                        // check payment submitted or not
                        $checkPayment = $this->checkPaymentSubmit($hospital, $agreement_id, $practice_id, format_date($start_month_data), format_date($end_month_data), $contract_type);
                        //Log::info("checkPayment", array($checkPayment));
                    } else {
                        // check payment submitted or not
                        $checkPayment = $this->checkPaymentSubmit($hospital, $agreement_id, $practice_id, format_date($start_month_data->start_date), format_date($end_month_data->end_date), $contract_type);
                        //Log::info("checkPayment", array($checkPayment));
                    }
                }
                $contracts = $this->getContractforInvoice($startmonth, $endmonth, $agreement, $practice_id, $contract_type);
                if (count($contracts->period) > 0 && $checkPayment) {
                    $contract_data = [];
                    foreach ($contracts->period as $index => $contract) {

                        if ((count($payedcontracts) == 0 || in_array($contract->contract_id, $payedcontracts)) && (count($printOnInvoice) == 0 || array_key_exists($contract->contract_id, $printOnInvoice))) {
                            /****************Below condition is commented for custome invoice one to many issue */
                            // $practices = PhysicianPracticeHistory::where("physician_id", "=", $contract->physician_id)->orderBy("start_date")->get();
                            /****************Below line added for custome invoice one to many issue */
                            $contracts_phy_arr = DB::table('physician_contracts')
                                ->where('contract_id', '=', $contract->contract_id)
                                ->where('practice_id', '=', $practice_id)
                                ->whereNull("deleted_at",)
                                ->pluck('physician_id')->toArray();

                            $practices = PhysicianPracticeHistory::whereIn("physician_id", $contracts_phy_arr)->where('practice_id', '=', $practice_id)->groupBy('practice_id')->orderBy("start_date")->get();
                            foreach ($practices as $practice) {
                                $practice_invoice_notes = InvoiceNote::getInvoiceNotes($practice->practice_id, InvoiceNote::PRACTICE, $hospital->id, 0);
                                if (strtotime($practice->end_date) >= $startmonth && (strtotime($practice->start_date) <= $startmonth || (strtotime($practice->start_date) > $startmonth && strtotime($practice->start_date) <= $endmonth))) {
                                    if (count($printOnInvoice) > 0) {
                                        if (array_key_exists($contract->contract_id, $printOnInvoice)) {
                                            if (array_key_exists($practice->practice_id, $printOnInvoice[$contract->contract_id])) {
                                                foreach ($printOnInvoice[$contract->contract_id][$practice->practice_id] as $amountpaidID) {
                                                    $logs = $this->getApprovedLogs($contract, $agreement_id, $practice, date('m/d/Y', $startmonth), date('m/d/Y', $endmonth), $contracts_phy_arr[0], $term, $amountpaidID);
                                                    if (count($logs) > 0) {
                                                        // Below code is for getting invoice number
                                                        $amt_paid_detail = self::where('start_date', '=', mysql_date(date('m/d/Y', $startmonth)))
                                                            ->where('end_date', '=', mysql_date(date('m/d/Y', $endmonth)))
//                                                            ->where('physician_id', '=', $contract->physician_id)
                                                            ->where('contract_id', '=', $contract->contract_id)
                                                            ->where('practice_id', '=', $practice_id)
                                                            ->first();

                                                        if ($amt_paid_detail) {
                                                            if ($amt_paid_detail->invoice_no != "") {
                                                                $invoice_no_period = $amt_paid_detail->invoice_no;
                                                            } else {
//                                                              $invoice_no_period = $contract->physician_id .'_'. $contract->contract_id .'_'. $hospital->id .'_'. date('m') .'_'. date('Y');

                                                                $last_invoice_no = HospitalInvoice::orderBy("last_invoice_no", "desc")
                                                                    ->value('last_invoice_no');

                                                                $new_invoice_no = $last_invoice_no + 1;
                                                                $invoice_no_period = $contract->contract_id . '-' . date('m', $startmonth) . date('Y', $startmonth) . '-' . $new_invoice_no;

                                                                $amt_paid_detail = self::where('start_date', '=', mysql_date(date('m/d/Y', $startmonth)))
                                                                    ->where('end_date', '=', mysql_date(date('m/d/Y', $endmonth)))
//                                                                    ->where('physician_id', '=', $contract->physician_id)
                                                                    ->where('contract_id', '=', $contract->contract_id)
                                                                    ->where('practice_id', '=', $practice_id)
                                                                    ->update(array('invoice_no' => $invoice_no_period));
                                                            }
                                                        } else {
//                                                            Log::Debug('Outside amount paid', array($contract->contract_id, $practice_id));
//                                                          $invoice_no_period = $contract->physician_id .'_'. $contract->contract_id .'_'. $hospital->id .'_'. date('m') .'_'. date('Y');

                                                            $last_invoice_no = HospitalInvoice::orderBy("last_invoice_no", "desc")
                                                                ->value('last_invoice_no');

                                                            $new_invoice_no = $last_invoice_no + 1;
                                                            $invoice_no_period = $contract->contract_id . '-' . date('m', $startmonth) . date('Y', $startmonth) . '-' . $new_invoice_no;
                                                        }

                                                        if ($practice_id == $practice->practice_id) {
                                                            $contract_data[] = $logs;
                                                        } else {
                                                            $changePracticeName = Practice::findOrFail($practice->practice_id);
                                                            if ($prevPractice != $practice->practice_id) {
                                                                $contract_data_new = [];
                                                            }
                                                            $contract_data_new[] = $logs;
                                                            $prevPractice = $practice->practice_id;
                                                            $practice_data[$practice->practice_id] = [
                                                                "name" => $changePracticeName->name,
                                                                "practice_invoice_notes" => $practice_invoice_notes,
                                                                "contract_data" => $contract_data_new
                                                            ];
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    } else {
                                        $amount_paid_ids = DB::table('amount_paid')
                                            ->where('start_date', '<=', mysql_date(date('m/d/Y', $startmonth)))
                                            ->where('end_date', '>=', mysql_date(date('m/d/Y', $endmonth)))
//                                            ->where('physician_id', '=', $contract->physician_id)
                                            ->where('contract_id', '=', $contract->contract_id)
                                            ->where('practice_id', '=', $practice->practice_id)
                                            ->pluck('id')
                                            ->toArray();

                                        if (count($amount_paid_ids) > 0) {
                                            foreach ($amount_paid_ids as $amount_paid_id) {
                                                $logs = $this->getApprovedLogs($contract, $agreement_id, $practice, date('m/d/Y', $startmonth), date('m/d/Y', $endmonth), $contracts_phy_arr[0], $term, $amount_paid_id);


                                                if (count($logs) > 0) {

                                                    // Below code is for getting invoice number
                                                    $amt_paid_detail = self::where('start_date', '=', mysql_date(date('m/d/Y', $startmonth)))
                                                        ->where('end_date', '=', mysql_date(date('m/d/Y', $endmonth)))
//                                                        ->where('physician_id', '=', $contract->physician_id)
                                                        ->where('contract_id', '=', $contract->contract_id)
                                                        ->where('practice_id', '=', $practice_id)
                                                        ->first();

                                                    if ($amt_paid_detail) {
//                                                        Log::Debug('Inside amount paid', array($contract->contract_id,$practice_id));
                                                        if ($amt_paid_detail->invoice_no != "") {
                                                            $invoice_no_period = $amt_paid_detail->invoice_no;
                                                        } else {
//                                                              $invoice_no_period = $contract->physician_id .'_'. $contract->contract_id .'_'. $hospital->id .'_'. date('m') .'_'. date('Y');

                                                            $last_invoice_no = HospitalInvoice::orderBy("last_invoice_no", "desc")
                                                                ->value('last_invoice_no');

                                                            $new_invoice_no = $last_invoice_no + 1;
                                                            $invoice_no_period = $contract->contract_id . '-' . date('m', $startmonth) . date('Y', $startmonth) . '-' . $new_invoice_no;

                                                            $amt_paid_detail = self::where('start_date', '=', mysql_date(date('m/d/Y', $startmonth)))
                                                                ->where('end_date', '=', mysql_date(date('m/d/Y', $endmonth)))
//                                                                ->where('physician_id', '=', $contract->physician_id)
                                                                ->where('contract_id', '=', $contract->contract_id)
                                                                ->where('practice_id', '=', $practice_id)
                                                                ->update(array('invoice_no' => $invoice_no_period));
                                                        }
                                                    } else {
//                                                        Log::Debug('Outside amount paid',array($contract->contract_id,$practice_id));
//                                                          $invoice_no_period = $contract->physician_id .'_'. $contract->contract_id .'_'. $hospital->id .'_'. date('m') .'_'. date('Y');

                                                        $last_invoice_no = HospitalInvoice::orderBy("last_invoice_no", "desc")
                                                            ->value('last_invoice_no');

                                                        $new_invoice_no = $last_invoice_no + 1;
                                                        $invoice_no_period = $contract->contract_id . '-' . date('m', $startmonth) . date('Y', $startmonth) . '-' . $new_invoice_no;
                                                    }

                                                    if ($practice_id == $practice->practice_id) {
                                                        $contract_data[] = $logs;
                                                    } else {
                                                        $changePracticeName = Practice::findOrFail($practice->practice_id);
                                                        if ($prevPractice != $practice->practice_id) {
                                                            $contract_data_new = [];
                                                        }
                                                        $contract_data_new[] = $logs;
                                                        $prevPractice = $practice->practice_id;
                                                        $practice_data[$practice->practice_id] = [
                                                            "name" => $changePracticeName->name,
                                                            "practice_invoice_notes" => $practice_invoice_notes,
                                                            "contract_data" => $contract_data_new
                                                        ];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    //$practice_data[$practice_id]=$contract_data;
                    if (count($contract_data) > 0) {
                        $practice_data[$practice_id] = [
                            "name" => $practiceName->name,
                            "practice_invoice_notes" => $practice_invoice_notes,
                            "contract_data" => $contract_data
                        ];
                    }
                } else {        // Monthly stipend without log generate invoice
                    $contracts = $this->getContractforInvoiceMonthlyStipendWithoutLog($startmonth, $endmonth, $agreement, $practice_id, $contract_type);
                    if (count($contracts->period) > 0 && $checkPayment) {
                        $contract_data = [];
                        foreach ($contracts->period as $index => $contract) {
                            if ((count($payedcontracts) == 0 || in_array($contract->contract_id, $payedcontracts)) && (count($printOnInvoice) == 0 || array_key_exists($contract->contract_id, $printOnInvoice))) {
                                $contracts_phy_arr = DB::table('physician_contracts')
                                    ->where('contract_id', '=', $contract->contract_id)
                                    ->where('practice_id', '=', $practice_id)
                                    ->whereNull("deleted_at",)
                                    ->pluck('physician_id')->toArray();
                                $practices = PhysicianPracticeHistory::whereIn("physician_id", "=", $contracts_phy_arr)->where('practice_id', '=', $practice_id)->orderBy("start_date")->get();
                                foreach ($practices as $practice) {
                                    $practice_invoice_notes = InvoiceNote::getInvoiceNotes($practice->practice_id, InvoiceNote::PRACTICE, $hospital->id, 0);
                                    if (strtotime($practice->end_date) >= $startmonth && (strtotime($practice->start_date) <= $startmonth || (strtotime($practice->start_date) > $startmonth && strtotime($practice->start_date) <= $endmonth))) {
                                        if (count($printOnInvoice) > 0) {
                                            if (array_key_exists($contract->contract_id, $printOnInvoice)) {
                                                // Log::info('contracts', array($contracts));
                                                if (array_key_exists($practice->practice_id, $printOnInvoice[$contract->contract_id])) {
                                                    foreach ($printOnInvoice[$contract->contract_id][$practice->practice_id] as $amountpaidID) {
                                                        $logs = $this->getMonthlyStipendData($contract, $agreement_id, $practice, date('m/d/Y', $startmonth), date('m/d/Y', $endmonth), $contracts_phy_arr[0], $term, $amountpaidID);
                                                        // Log::info("logs in", array($logs));
                                                        if (count($logs) > 0) {
                                                            if ($practice_id == $practice->practice_id) {
                                                                $contract_data[] = $logs;
                                                            } else {
                                                                $changePracticeName = Practice::findOrFail($practice->practice_id);
                                                                if ($prevPractice != $practice->practice_id) {
                                                                    $contract_data_new = [];
                                                                }
                                                                $contract_data_new[] = $logs;
                                                                $prevPractice = $practice->practice_id;
                                                                $practice_data[$practice->practice_id] = [
                                                                    "name" => $changePracticeName->name,
                                                                    "practice_invoice_notes" => $practice_invoice_notes,
                                                                    "contract_data" => $contract_data_new
                                                                ];
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        //$practice_data[$practice_id]=$contract_data;
                        if (count($contract_data) > 0) {
                            $practice_data[$practice_id] = [
                                "name" => $practiceName->name,
                                "practice_invoice_notes" => $practice_invoice_notes,
                                "contract_data" => $contract_data
                            ];
                        }
                    }
                }
            }
            $i++;

            if (count($practice_data) > 0) {
                if ($hospital->approve_all_invoices) {
                    $result[] = [
                        "agreement_data" => [
                            "period" => format_date($start_month_data) . "-" . format_date($end_month_data),
                            "name" => $agreement->name,
                            "invoice_no" => $invoice_no + 1,
                            "invoice_no_period" => $invoice_no_period
                        ],
                        "practices" => $practice_data
                    ];
                } else {
                    $result[] = [
                        "agreement_data" => [
                            "period" => format_date($start_month_data->start_date) . "-" . format_date($end_month_data->end_date),
                            "name" => $agreement->name,
                            "invoice_no" => $invoice_no + 1,
                            "invoice_no_period" => $invoice_no_period
                        ],
                        "practices" => $practice_data
                    ];
                    //$result[] = $practice_data;
                }
            } else {
            }
        }
        return $result;
    }

    private function checkPaymentSubmit($hospital, $agreement_ids, $practice_ids, $month_start, $months_end, $contract_type)
    {
        $time = strtotime($month_start);
        $month = date("m", $time);
        $year = date("Y", $time);
        $amount_paid = $this->select('amount_paid.*')
            ->join("contracts", "contracts.id", "=", "amount_paid.contract_id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->where('amount_paid.start_date', '>=', mysql_date($month_start))
            ->whereMonth('amount_paid.start_date', '=', $month)
            ->whereYear('amount_paid.start_date', '=', $year)
            //->where('amount_paid.practice_id','=',$practice_ids)
            ->where('agreements.id', '=', $agreement_ids)
            ->get();
        if (count($amount_paid) > 0) {
            return true;
        }
        return false;
    }

    private function getContractforInvoice($start_date, $end_date, $agreement, $practice_id, $contract_type_id)
    {
        $start_date = mysql_date(date('m/d/Y', $start_date));
        $end_date = mysql_date(date('m/d/Y', $end_date));

        $contract_month = months($agreement->start_date, 'now');
        $contract_term = months($agreement->start_date, $agreement->end_date);
        $log_range = months($start_date, $end_date);

        if ($contract_month > $contract_term) {
            $contract_month = $contract_term;
        }

        $period_query = PhysicianLog::select(
            DB::raw("practices.name as practice_name"),
            DB::raw("physicians.id as physician_id"),
            DB::raw("physicians.email as physician_email"),
            DB::raw("physicians.first_name as physician_first_name"),
            DB::raw("physicians.last_name as physician_last_name"),
            DB::raw("concat(physicians.last_name, ', ', physicians.first_name) as physician_name"),
            DB::raw("specialties.name as specialty_name"),
            DB::raw("contracts.id as contract_id"),
            DB::raw("contracts.contract_name_id as contract_name_id"),
            DB::raw("contracts.payment_type_id as payment_type_id"),
            DB::raw("contracts.contract_type_id as contract_type_id"),
            DB::raw("contract_types.name as contract_name"),
            DB::raw("contracts.min_hours as min_hours"),
            DB::raw("contracts.max_hours as max_hours"),
            DB::raw("contracts.expected_hours as expected_hours"),
            DB::raw("contracts.rate as rate"),
            DB::raw("contracts.wrvu_payments as wrvu_payments"),
            DB::raw("contracts.weekday_rate as weekday_rate"),
            DB::raw("contracts.weekend_rate as weekend_rate"),
            DB::raw("contracts.holiday_rate as holiday_rate"),
            DB::raw("contracts.on_call_rate as on_call_rate"),
            DB::raw("contracts.called_back_rate as called_back_rate"),
            DB::raw("contracts.called_in_rate as called_in_rate"),
            DB::raw("contracts.is_lawson_interfaced as is_lawson_interfaced"),
            DB::raw("contracts.default_to_agreement as contract_default_to_agreement"),
            DB::raw("agreements.name as agreement_name"),
            DB::raw("agreements.approval_process as approval_process"),
            DB::raw("sum(physician_logs.duration) as worked_hours"),
            DB::raw("sum(physician_logs.log_hours) as log_hours"),
            DB::raw("'{$contract_month}' as contract_month"),
            DB::raw("'{$contract_term}' as contract_term"),
            DB::raw("'{$log_range}' as log_range")
        )
            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
            ->join('specialties', 'specialties.id', '=', 'physicians.specialty_id')
            ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
            ->join('practices', 'practices.id', '=', 'physician_practices.practice_id')
            ->where('contracts.agreement_id', '=', $agreement->id)
            ->where('practices.id', $practice_id)
            ->whereBetween('physician_logs.date', [$start_date, $end_date])
            ->where('physician_logs.approval_date', '!=', '0000-00-00')
            ->where('physician_logs.signature', '!=', 0)
            ->groupBy('physicians.id', 'agreements.id', 'contracts.id')
            ->orderBy('contracts.id', 'asc')
            ->orderBy('contract_types.name', 'asc')
            ->orderBy('practices.name', 'asc')
            ->orderBy('physicians.last_name', 'asc')
            ->orderBy('physicians.first_name', 'asc');

        if ($contract_type_id != -1) {
            $period_query->where('contracts.contract_type_id', '=', $contract_type_id);
        }

        $results = new StdClass;
        $results->period = $period_query->get();

        foreach ($results->period as $result) {
            if ($result->contract_name_id)
                $result->contract_name = ContractName::findOrFail($result->contract_name_id)->name;
        }

        return $results;
    }

    private function getApprovedLogs($contract, $agreementId, $practice, $startmonth, $endmonth, $physician_id, $term, $amountpaidID = 0)
    {
        $practiceName = Practice::withTrashed()->findOrFail($practice->practice_id);

        $is_shared_contract = false;
        $contracts_phy_count = DB::table('physician_contracts')
            ->where('contract_id', '=', $contract->contract_id)
            ->where('practice_id', '=', $practice->practice_id)
            ->whereNull("deleted_at",)
            ->pluck('physician_id')->count();

        if ($contracts_phy_count > 1) {
            $is_shared_contract = true;
        }

        if ($amountpaidID > 0) {
            $amount = Amount_paid::findOrFail($amountpaidID);
        } else {
            $amount = DB::table('amount_paid')
                ->where('start_date', '<=', mysql_date($startmonth))
                ->where('end_date', '>=', mysql_date($startmonth))
//				->where('physician_id', '=', $contract->physician_id)
                ->where('contract_id', '=', $contract->contract_id)
                ->where('practice_id', '=', $practice->practice_id)
                ->first();
        }
        if ($amount) {
            $amountPaid = $amount->amountPaid;
        } else {
            $amountPaid = 0;
        }
        $sum_worked_hours = 0;
        $total_calculated_payment = 0;
        $prev_worked_hours = 0;
        $prevrate = 0;
        // log::info('hospital_id', array($practiceName));
        //get on call rates for uncompensated type
        if ($contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
            $contract_rates = ContractRate::where("contract_id", "=", $contract->contract_id)->where("status", "=", "1")->get();
        } else {
            $contract_rates = ContractRate::where("contract_id", "=", $contract->contract_id)->get();
        }

        $rehab_categories = [];
        $rehab_max_hours_per_week = 0;
        $rehab_max_hours_per_month = 0;
        $rehab_admin_hours = 0;
        if ($contract->payment_type_id == PaymentType::REHAB) {
            $rehab_categories = ActionCategories::whereIn("id", [9, 10, 11, 12])->pluck("id", "name")->toArray();
            if (count($rehab_categories) > 0) {
                foreach ($rehab_categories as $key => $id) {
                    $action_list = Action::where('category_id', '=', $id)->get();
                    $rehab_categories[$key] = $action_list;
                }
            }

            $rehab_week_count = DB::table('rehab_days_week_calculation')->where("start_date", '=', mysql_date($startmonth))->value("week_count");
            $rehab_max_hours_per_week = DB::table('rehab_max_hours_per_week')
                ->where("contract_id", '=', $contract->contract_id)
                ->where("start_date", '=', mysql_date($startmonth))
                ->where("end_date", '=', mysql_date($endmonth))
                ->value("max_hours_per_week");

            $rehab_max_hours_per_month = $rehab_week_count * $rehab_max_hours_per_week;

            $rehab_admin_hours = DB::table('rehab_admin_hours')
                ->where("contract_id", '=', $contract->contract_id)
                ->where("start_date", '=', mysql_date($startmonth))
                ->where("end_date", '=', mysql_date($endmonth))
                ->value("admin_hours");
        }
        // log::info("contract rates",array($contract_rates));
        if ($amountPaid != 0) {
            $physician_invoice_notes = InvoiceNote::getInvoiceNotes($contract->physician_id, InvoiceNote::PHYSICIAN, $practiceName->hospital_id, $practiceName->id);
            $contract_invoice_notes = InvoiceNote::getInvoiceNotes($contract->contract_id, InvoiceNote::CONTRACT, $practiceName->hospital_id, 0);
            $split_payment = SplitPaymentPercentage::getSplitPayment($contract->contract_id, $practiceName->hospital_id, $amountpaidID > 0 ? $amountpaidID : $amount->id);
            $contract_data = [
                "physician_name" => "{$contract->physician_name}",
                "physician_email" => $contract->physician_email,
                "physician_first_name" => $contract->physician_first_name,
                "physician_last_name" => $contract->physician_last_name,
                "physician_id" => $contract->physician_id,
                "practice_id" => $practice->practice_id,
                "practice_name" => $practiceName->name,
                "contract_id" => $contract->contract_id,
                "payment_type_id" => $contract->payment_type_id,
                "date_range" => "{$startmonth} - {$endmonth}",
                "expected_hours" => $contract->expected_hours,
                "max_hours" => $contract->max_hours * $term,
                "worked_hours" => $contract->worked_hours,
                "contract_name" => $contract->contract_name,
                "contract_name_id" => $contract->contract_name_id,
                "agreement_name" => $contract->agreement_name,
                "agreement_id" => $agreementId,
                "physician" => $contract->physician_id,
                "practice_start_date" => $practice->start_date,
                "practice_end_date" => $practice->end_date,
                "sum_worked_hour" => 0,
                "total_calculated_payment" => 0,
                "rate" => $contract->rate,
                "on_call_rates" => $contract_rates,
                "amount_paid" => $amountPaid,
                "amount_paid_id" => $amount->id,
                "invoice_number" => $amount->invoice_no,
                "physician_invoice_notes" => $physician_invoice_notes,
                "contract_invoice_notes" => $contract_invoice_notes,
                "split_payment" => $split_payment,
                "is_lawson_interfaced" => $contract->is_lawson_interfaced,
                "rehab_category_action_list" => $rehab_categories,
                "rehab_max_hours_per_week" => $rehab_max_hours_per_week,
                "rehab_max_hours_per_month" => $rehab_max_hours_per_month,
                "rehab_admin_hours" => $rehab_admin_hours,
                "breakdown" => [],
                "is_shared_contract" => $is_shared_contract
            ];
            // log::info("contract_data",array($contract_data));
            //added for get changed names
            //if($contract->contract_type_id == ContractType::ON_CALL)
            if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                $changedActionNamesList = OnCallActivity::where("contract_id", "=", $contract->contract_id)->pluck("name", "action_id")->toArray();
            } else {
                $changedActionNamesList = [0 => 0];
            }

            $logs = PhysicianLog::select(
                DB::raw("physician_logs.id as log_id"),
                DB::raw("physician_logs.action_id as action_id"),
                DB::raw("actions.name as action"),
                DB::raw("actions.action_type_id as action_type_id"),
                DB::raw("physician_logs.date as date"),
                DB::raw("physician_logs.approval_date as approval_date"),
                DB::raw("physician_logs.updated_at as updated_at"),
                DB::raw("physician_logs.signature as signature"),
                DB::raw("physician_logs.duration as worked_hours"),
                DB::raw("physician_logs.log_hours as log_hours"),
                DB::raw("physician_logs.details as notes"),
                DB::raw("physician_logs.signatureTimeZone as signatureTimeZone")
            )
                ->join("actions", "actions.id", "=", "physician_logs.action_id")
                ->where("physician_logs.contract_id", "=", $contract->contract_id)
                ->where("physician_logs.practice_id", "=", $practice->practice_id)
//                ->where("physician_logs.physician_id", "=", $contract->physician_id)
                ->whereBetween("physician_logs.date", [mysql_date($startmonth), mysql_date($endmonth)]);

            if ($is_shared_contract && $contract->payment_type_id == PaymentType::REHAB) {

            } else {
                $logs = $logs->where("physician_logs.physician_id", "=", $contract->physician_id);
            }

            $logs = $logs->orderBy("physician_logs.action_id", "asc")
                ->get();

            $action_id = 0;
            $worked_hours = 0;
            $worked_duration = 0;
            $level_signature_approver = array();

            if ($contract->approval_process == '1') {
                //Log::info('process point yes');
                if ($contract->contract_default_to_agreement == '1') {
                    $contract_id = 0;//when we are fetching all approval managers same as agreement
                } else {
                    $contract_id = $contract->contract_id;// when we are fetching approval managers for the specific contract
                }
                $approval_manager_info = ApprovalManagerInfo::where('agreement_id', '=', $agreementId)
                    ->where('contract_id', "=", $contract_id)
                    ->where('is_deleted', '=', '0')
                    ->orderBy('level')->pluck('user_id', 'level')->toArray();

            } else {
                //Log::info('process point No');
                $approval_manager_info = array();
            }
            //Log::info('approval manager info',array($approval_manager_info));

            foreach ($logs as $log) {
                if ($log->action_type_id == 3) $log->action = "Custom: Activity";
                if ($log->action_type_id == 4) $log->action = "Custom: Mgmt Duty";
                if ($log->approval_date != "0000-00-00") {
                    $approve_date = date('m/d/Y', strtotime($log->approval_date));
                } else if ($log->approval_date == "0000-00-00" && $log->signature > 0) {
                    $approve_date = date('m/d/Y', strtotime($log->updated_at));
                } else {
                    $approve_date = "";
                }

                $Physician_approve_date = "";
//				$CM_approve_date = "NA";
//				$FM_approve_date = "NA";
                $Physician_approve_signature = "";
//				$CM_approve_signature = "NA";
//				$FM_approve_signature = "NA";
                $Physician_signature_date = "NA";
//				$CM_signature_date = "NA";
//				$FM_signature_date = "NA";
                $level = array();

                if ($approve_date != "") {
                    $approval_dates = LogApproval::where("log_id", "=", $log->log_id)->get();
                    // Log::info('$approval_dates',array($approval_dates));
                    if (count($approval_dates) > 1) {
                        foreach ($approval_dates as $date_approve) {
                            if ($date_approve->role == LogApproval::physician || $date_approve->approval_managers_level == 0) {
                                $Physician_approve_date = format_date($date_approve->approval_date);
                                $Physician_signature = Signature::where("signature_id", "=", $date_approve->signature_id)->first();
                                if ($Physician_signature) {
                                    $Physician_approve_signature = $Physician_signature->signature_path;
                                    //$Physician_signature_date = format_date($date_approve->updated_at, 'm/d/Y \a\t h:i:s A');
                                    $Physician_signature_date = $date_approve->signatureTimeZone != '' ? $date_approve->signatureTimeZone : format_date($date_approve->updated_at, 'm/d/Y \a\t h:i A');
                                }
                            } else {
                                $level_approve_date = format_date($date_approve->approval_date);
                                //  Log::info('date approve----',array($date_approve->approval_managers_level));
                                if (array_key_exists($date_approve->approval_managers_level, $approval_manager_info)) {
                                    if ($approval_manager_info[$date_approve->approval_managers_level] == $date_approve->user_id) {
                                        $signature_id = $date_approve->signature_id;
                                        $user_id = $date_approve->user_id;
                                        $level_signature_approver[$date_approve->user_id] = $signature_id;
                                    } else {
                                        //	Log::info('approval_manager_info$date_approve->approval_managers_level',array($approval_manager_info[$date_approve->approval_managers_level]));
                                        if (array_key_exists($approval_manager_info[$date_approve->approval_managers_level], $level_signature_approver)) {
                                            $signature_id = $level_signature_approver[$approval_manager_info[$date_approve->approval_managers_level]];
                                            $user_id = $approval_manager_info[$date_approve->approval_managers_level];
                                        } else {
                                            $signature_id = $date_approve->signature_id;
                                            $user_id = $date_approve->user_id;
                                        }

                                    }
                                } else {
                                    $signature_id = $date_approve->signature_id;
                                    $user_id = $date_approve->user_id;
                                }
                                $level_signature = UserSignature::where("signature_id", "=", $signature_id)->first();
                                // $role = ApprovalManagerType::where("approval_manager_type_id","=",($date_approve->role)-1)->first();
                                $user = User::withTrashed()->where('id', '=', $user_id)->first();
                                $level_approve_signature = '';
                                $level_signature_date = '';
                                if ($level_signature) {
                                    $level_approve_signature = $level_signature->signature_path;
                                    //$CM_signature_date = format_date($date_approve->updated_at, 'm/d/Y \a\t h:i:s A');
                                    $level_signature_date = $date_approve->signatureTimeZone != '' ? $date_approve->signatureTimeZone : format_date($date_approve->updated_at, 'm/d/Y \a\t h:i A');
                                }
                                $level[$date_approve->approval_managers_level] = [
                                    // "type" => $role->manager_type,
                                    "type" => $user->title,
                                    "name" => $user->first_name . " " . $user->last_name,
                                    "approve_date" => $level_approve_date,
                                    "signature" => $level_approve_signature,
                                    "sign_date" => $level_signature_date
                                ];
                            }
                        }
                    } else {
                        $Physician_signature = Signature::where("signature_id", "=", $log->signature)->first();
                        $Physician_approve_date = format_date($approve_date);
                        if ($Physician_signature) {
                            $Physician_approve_signature = $Physician_signature->signature_path;
                            //$Physician_signature_date = format_date($log->updated_at, 'm/d/Y \a\t h:i:s A');
                            $Physician_signature_date = $log->signatureTimeZone != '' ? $log->signatureTimeZone : format_date($log->updated_at, 'm/d/Y \a\t h:i A');
                        }
                    }

                    //$rate = $contract->rate;
                    $rate = ContractRate::getRate($contract->contract_id, $log->date, ContractRate::FMV_RATE);
                    if ($contract->payment_type_id == PaymentType::PSA) {
                        if ($contract->wrvu_payments) {
                            $rate = Contract::getPsaRate($contract->contract_id, $log->worked_hours);
                        }
                    }
                    //if ($contract->contract_type_id == ContractType::ON_CALL) {
                    if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                        if (strlen(strstr(strtoupper($log->action), "WEEKDAY")) > 0) {
                            //$rate = $contract->weekday_rate;
                            $rate = ContractRate::getRate($contract->contract_id, $log->date, ContractRate::WEEKDAY_RATE);
                        } elseif (strlen(strstr(strtoupper($log->action), "WEEKEND")) > 0) {
                            //$rate = $contract->weekend_rate;
                            $rate = ContractRate::getRate($contract->contract_id, $log->date, ContractRate::WEEKEND_RATE);
                        } elseif (strlen(strstr(strtoupper($log->action), "HOLIDAY")) > 0) {
                            //$rate = $contract->holiday_rate;
                            $rate = ContractRate::getRate($contract->contract_id, $log->date, ContractRate::HOLIDAY_RATE);
                        } elseif ($log->action == 'On-Call') {
                            //$rate = $contract->on_call_rate;
                            $rate = ContractRate::getRate($contract->contract_id, $log->date, ContractRate::ON_CALL_RATE);
                        } elseif ($log->action == 'Called-Back') {
                            //$rate = $contract->called_back_rate;
                            $rate = ContractRate::getRate($contract->contract_id, $log->date, ContractRate::CALLED_BACK_RATE);
                        } elseif ($log->action == 'Called-In') {
                            //$rate = $contract->called_in_rate;
                            $rate = ContractRate::getRate($contract->contract_id, $log->date, ContractRate::CALLED_IN_RATE);
                        }
                    } elseif ($contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                        $contract_obj = Contract::where('id', '=', $contract->contract_id)->first();
                        //$contract = Contract::where('id', '=', $contract->contract_id)->first();
                        $uncompensated_action = Action::getActions($contract_obj);
                        foreach ($uncompensated_action as $uncompensated) {
                            $log->action = $uncompensated["display_name"];
                        }
                    }
                    if ($action_id == $log->action_id) {
                        $worked_hours = $worked_hours + $log->log_hours;
                        $worked_duration = $worked_duration + $log->worked_hours;
                        $prev_worked_hours = $worked_hours;
                        $prevrate = $rate;
                    } else {
                        if ($action_id != 0) {
                            $total_calculated_payment = $total_calculated_payment + ($prev_worked_hours * $prevrate);
                        }
                        $worked_hours = $log->log_hours;
                        $worked_duration = $log->worked_hours;
                        $action_id = $log->action_id;
                        $prev_worked_hours = $worked_hours;
                        $prevrate = $rate;

                        $action_logs = PhysicianLog::select(
                            DB::raw("physician_logs.id as log_id"),
                            DB::raw("physician_logs.action_id as action_id"),
                            DB::raw("actions.name as action"),
                            DB::raw("actions.action_type_id as action_type_id"),
                            DB::raw("physician_logs.date as date"),
                            DB::raw("physician_logs.approval_date as approval_date"),
                            DB::raw("physician_logs.updated_at as updated_at"),
                            DB::raw("physician_logs.signature as signature"),
                            DB::raw("SUM(physician_logs.duration) as worked_hours"),
                            DB::raw("SUM(physician_logs.log_hours) as log_hours"),
                            DB::raw("physician_logs.details as notes"),
                            DB::raw("physician_logs.signatureTimeZone as signatureTimeZone")
                        )
                            ->join("actions", "actions.id", "=", "physician_logs.action_id")
                            ->where("physician_logs.action_id", "=", $log->action_id)
                            ->where("physician_logs.contract_id", "=", $contract->contract_id)
                            ->where("physician_logs.practice_id", "=", $practice->practice_id)
                            ->where("physician_logs.approval_date", "!=", "0000-00-00")
                            ->whereBetween("physician_logs.date", [mysql_date($startmonth), mysql_date($endmonth)])
                            ->groupBY("physician_logs.date")
                            ->orderBy("physician_logs.date", "asc")
                            ->get();
                    }

                    if (count($level) < 6) {
                        for ($m = count($level) + 1; $m < 7; $m++) {
                            $level[$m] = [
                                "type" => "NA",
                                "name" => "NA",
                                "approve_date" => "NA",
                                "signature" => "NA",
                                "sign_date" => "NA"
                            ];
                        }
                    }

                    $contract_data["breakdown"][$action_id] = [
                        "action" => array_key_exists($log->action_id, $changedActionNamesList) ? $changedActionNamesList[$log->action_id] : $log->action,
                        "category_name" => $log->category_name,
                        "action_id" => $log->action_id,
                        "date" => format_date($log->date),
                        "Physician_approve_date" => $Physician_approve_date,
//						"CM_approve_date" => $CM_approve_date,
//						"FM_approve_date" => $FM_approve_date,
                        "worked_hours" => $worked_duration,
                        "Physician_approve_signature" => $Physician_approve_signature,
//						"CM_approve_signature" => $CM_approve_signature,
//						"FM_approve_signature" => $FM_approve_signature,
                        "Physician_signature_date" => $Physician_signature_date,
//						"CM_signature_date" => $CM_signature_date,
//						"FM_signature_date" => $FM_signature_date,
                        "levels" => $level,
                        "rate" => $rate,
                        "calculated_payment" => $worked_hours * $rate,
                        "action_logs" => $action_logs
                    ];
                    $sum_worked_hours = $sum_worked_hours + $log->worked_hours;
                }
            }
            $contract_data["sum_worked_hour"] = $sum_worked_hours;
            $contract_data["total_calculated_payment"] = $total_calculated_payment = $total_calculated_payment + ($prev_worked_hours * $prevrate);
            if (count($contract_data["breakdown"]) > 0) {
//                log::debug('$contract_data["breakdown"]::', array($contract_data["breakdown"]));
                return $contract_data;
            } else {
                return array();
            }
        } else {
            return array();
        }
    }

    private function getContractforInvoiceMonthlyStipendWithoutLog($start_date, $end_date, $agreement, $practice_id, $contract_type_id)
    {
        $start_date = mysql_date(date('m/d/Y', $start_date));
        $end_date = mysql_date(date('m/d/Y', $end_date));

        $contract_month = months($agreement->start_date, 'now');
        $contract_term = months($agreement->start_date, $agreement->end_date);
        $log_range = months($start_date, $end_date);

        if ($contract_month > $contract_term) {
            $contract_month = $contract_term;
        }

        $period_query = Contract::select(
            DB::raw("practices.name as practice_name"),
            DB::raw("physicians.id as physician_id"),
            DB::raw("concat(physicians.last_name, ', ', physicians.first_name) as physician_name"),
            DB::raw("specialties.name as specialty_name"),
            DB::raw("contracts.id as contract_id"),
            DB::raw("contracts.contract_name_id as contract_name_id"),
            DB::raw("contracts.payment_type_id as payment_type_id"),
            DB::raw("contracts.contract_type_id as contract_type_id"),
            DB::raw("contract_types.name as contract_name"),
            DB::raw("contracts.min_hours as min_hours"),
            DB::raw("contracts.max_hours as max_hours"),
            DB::raw("contracts.expected_hours as expected_hours"),
            DB::raw("contracts.rate as rate"),
            DB::raw("contracts.wrvu_payments as wrvu_payments"),
            DB::raw("contracts.weekday_rate as weekday_rate"),
            DB::raw("contracts.weekend_rate as weekend_rate"),
            DB::raw("contracts.holiday_rate as holiday_rate"),
            DB::raw("contracts.on_call_rate as on_call_rate"),
            DB::raw("contracts.called_back_rate as called_back_rate"),
            DB::raw("contracts.called_in_rate as called_in_rate"),
            DB::raw("contracts.is_lawson_interfaced as is_lawson_interfaced"),
            DB::raw("contracts.default_to_agreement as contract_default_to_agreement"),
            DB::raw("agreements.name as agreement_name"),
            DB::raw("agreements.approval_process as approval_process"),
            DB::raw("'{$contract_month}' as contract_month"),
            DB::raw("'{$contract_term}' as contract_term"),
            DB::raw("'{$log_range}' as log_range")
        )
            ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('physicians', 'physicians.id', '=', 'contracts.physician_id')
            ->join('specialties', 'specialties.id', '=', 'physicians.specialty_id')
            ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
            ->join('practices', 'practices.id', '=', 'physician_practices.practice_id')
            ->join('payment_types', 'payment_types.id', '=', 'contracts.payment_type_id')
            ->where('contracts.agreement_id', '=', $agreement->id)
            ->where('practices.id', $practice_id)
            ->where('contracts.payment_type_id', '=', PaymentType::MONTHLY_STIPEND)
            ->groupBy('physicians.id', 'agreements.id', 'contracts.id')
            ->orderBy('contract_types.name', 'asc')
            ->orderBy('practices.name', 'asc')
            ->orderBy('physicians.last_name', 'asc')
            ->orderBy('physicians.first_name', 'asc');

        if ($contract_type_id != -1) {
            $period_query->where('contracts.contract_type_id', '=', $contract_type_id);
        }

        $results = new StdClass;
        $results->period = $period_query->get();

        foreach ($results->period as $result) {
            if ($result->contract_name_id)
                $result->contract_name = ContractName::findOrFail($result->contract_name_id)->name;
        }

        return $results;
    }

    private function getMonthlyStipendData($contract, $agreementId, $practice, $startmonth, $endmonth, $physician_id, $term, $amountpaidID = 0)
    {
        $practiceName = Practice::withTrashed()->findOrFail($practice->practice_id);
        $amount = DB::table('amount_paid')
            ->where('start_date', '<=', mysql_date($startmonth))
            ->where('end_date', '>=', mysql_date($startmonth))
//			->where('physician_id', '=', $contract->physician_id)
            ->where('contract_id', '=', $contract->contract_id)
            ->where('practice_id', '=', $practice->practice_id)
            ->orderBy('id', 'desc')
            ->first();
        // Log::info('amount', array($amount));
        if ($amount) {
            $amountPaid = $amount->amountPaid;
        } else {
            $amountPaid = 0;
        }

        $sum_worked_hours = 0;
        $total_calculated_payment = 0;
        $prev_worked_hours = 0;
        $prevrate = 0;

        if ($amountPaid != 0) {
            $physician_invoice_notes = InvoiceNote::getInvoiceNotes($physician_id, InvoiceNote::PHYSICIAN, $practiceName->hospital_id, $practiceName->id);
            $contract_invoice_notes = InvoiceNote::getInvoiceNotes($contract->contract_id, InvoiceNote::CONTRACT, $practiceName->hospital_id, 0);
            $contract_data = [
                "physician_name" => "{$contract->physician_name}",
                "physician_id" => $physician_id,
                "practice_id" => $practice->practice_id,
                "practice_name" => $practiceName->name,
                "contract_id" => $contract->contract_id,
                "date_range" => "{$startmonth} - {$endmonth}",
                "expected_hours" => $contract->expected_hours,
                "max_hours" => $contract->max_hours * $term,
                "worked_hours" => $contract->worked_hours,
                "contract_name" => $contract->contract_name,
                "contract_name_id" => $contract->contract_name_id,
                "agreement_name" => $contract->agreement_name,
                "payment_type_id" => $contract->payment_type_id,
                "agreement_id" => $agreementId,
                "physician" => $physician_id,
                "practice_start_date" => $practice->start_date,
                "practice_end_date" => $practice->end_date,
                "sum_worked_hour" => 0,
                "total_calculated_payment" => 0,
                "rate" => $contract->rate,
                "amount_paid" => $amountPaid,
                "physician_invoice_notes" => $physician_invoice_notes,
                "contract_invoice_notes" => $contract_invoice_notes,
                "is_lawson_interfaced" => $contract->is_lawson_interfaced,
                "breakdown" => []
            ];

            // levels
            for ($i = 1; $i < 7; $i++) {
                $level[$i] = [
                    "type" => "NA",
                    "name" => "NA",
                    "approve_date" => "NA",
                    "signature" => "NA",
                    "sign_date" => "NA"
                ];
            }

            $contract_data["breakdown"]["1001"] = [
                "action" => "NA",
                "action_id" => "",
                "date" => "NA",
                "Physician_approve_date" => "NA",
                "worked_hours" => 0.0,
                "Physician_approve_signature" => "NA",
                "Physician_signature_date" => "NA",
                "levels" => $level,
                "rate" => $contract->rate,
                "calculated_payment" => $contract->rate * $contract->worked_hours == null ? 0.00 : $contract->worked_hours
            ];
            $sum_worked_hours = $sum_worked_hours;
            $contract_data["sum_worked_hour"] = $sum_worked_hours;
            $contract_data["total_calculated_payment"] = $total_calculated_payment = $total_calculated_payment + ($prev_worked_hours * $prevrate);
            // log::info('contract_data', array($contract_data));
            if (count($contract_data["breakdown"]) > 0) {
                return $contract_data;
            } else {
                return array();
            }
        } else {
            return array();
        }
    }

    public function changeDate($start_date, $end_date, $id)
    {
        return $this->changeDates($start_date, $end_date, $id);
    }

    private function changeDates($start_date, $end_date, $id)
    {
        $contract_ids = Contract::where('agreement_id', '=', $id)->pluck('id')->toArray();
        if (count($contract_ids) > 0) {
            $month_start_date = mysql_date(date('Y', strtotime($start_date)) . '-' . date('m', strtotime($start_date)) . '-01');
            $amount_paid = $this->select('start_date')
                ->where('start_date', '>=', $month_start_date)
                ->whereIn('contract_id', $contract_ids)
                ->distinct()
                ->orderBy('start_date')
                ->get();
            foreach ($amount_paid as $paid) {
                $date_new = date('Y', strtotime($paid->start_date)) . '-' . date('m', strtotime($paid->start_date)) . '-' . date('d', strtotime($start_date));
                $new_start_date = with(new DateTime($date_new))->setTime(0, 0, 0);
                $new_end_date = with(clone $new_start_date)->modify('+1 month')->modify('-1 day')->setTime(23, 59, 59);
                if ($new_end_date > new DateTime($end_date)) {
                    $new_end_date = new DateTime($end_date);
                }
                DB::table('amount_paid')
                    ->where('start_date', '=', $paid->start_date)
                    ->whereIn('contract_id', $contract_ids)
                    ->update(
                        array('start_date' => $new_start_date->format('Y-m-d'), 'end_date' => $new_end_date->format('Y-m-d'), 'updated_at' => date('Y-m-d')));
            }
            return $amount_paid;
        }
        return true;

    }

    public function checkPayment($hospital, $agreement_ids, $practice_ids, $months_start, $months_end, $contract_type)
    {
        return $this->checkPaymentSubmit($hospital, $agreement_ids, $practice_ids, $months_start, $months_end, $contract_type);
    }
}
