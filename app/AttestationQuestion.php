<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\State;
use App\customClasses\PaymentFrequencyFactoryClass;
use Auth;
use Request;
use Redirect;
use Lang;
use View;
use Response;
use App\Services\EmailQueueService;
use App\customClasses\EmailSetup;
use function App\Start\is_physician;

class AttestationQuestion extends Model
{
    protected $table = "attestation_questions";

    public static function createAttestationQuestion()
    {
        $state = Request::input('state');
        $attestation = Request::input('attestation');
        $question_type = Request::input('question_type', 1);
        $attestation_type = Request::input('attestation_type', 1);

        $question = $_POST['editor'];
        $question   =  htmlentities($question, ENT_HTML5  , 'UTF-8');

        $question_count = self::select('*')
            ->where('state_id', '=', $state)
            ->where('attestation_type', '=', $attestation_type)
            ->where('attestation_id', '=', $attestation)
            ->count();

        $check_exist = AttestationQuestion::select('*')
            ->where('state_id', '=', $state)
            ->where('attestation_type', '=', $attestation_type)
            ->where('attestation_id', '=', $attestation)
            ->where('question', '=', $question)
            ->count();

        if ($check_exist > 0) {
            return Redirect::route('question.edit')->with([
                'error' => Lang::get('attestations.attestation_exist_error')
            ]);
        }

        if ($question != "" && $question != null) {
            $attestation_question = new AttestationQuestion();
            $attestation_question->state_id = $state;
            $attestation_question->attestation_type = $attestation_type;
            $attestation_question->attestation_id = $attestation;
            $attestation_question->question = $question;
            $attestation_question->question_type = $question_type;
            $attestation_question->sort_order = $question_count + 1;
            $attestation_question->created_by = Auth::user()->id;
            $attestation_question->updated_by = Auth::user()->id;
            $attestation_question->save();

            return Redirect::route('attestations.index')->with([
                'success' => Lang::get('attestations.create_success')
            ]);
        } else {
            return Redirect::route('attestations.create')->with([
                'error' => Lang::get('attestations.enter_question_error')
            ]);
        }
    }

    public static function getAttestationQuestions($state_id, $attestation_id, $attestation_type)
    {
        $question_list = self::select('*')
            ->where('state_id', '=', $state_id)
            ->where('attestation_id', '=', $attestation_id)
            ->where('attestation_type', '=', $attestation_type)
            ->where('is_active', '=', true)
            ->get();

        foreach( $question_list as $question)
        {      
            $question->attributes['question'] = html_entity_decode($question->attributes['question'],ENT_HTML5);
        } 

        return $question_list;
    }

    public static function getEditAttestationQuestion($state_id, $attestation_id, $question_id)
    {
        $attestation = Request::input('attestation', 0);

        $question = AttestationQuestion::select('attestation_questions.*', 'states.id as state_id', 'states.name as state_name', 'attestations.id as attestation_id', 'attestations.name as attestation_name', 'attestation_question_types.id as question_type_id', 'attestation_question_types.type as question_type')
            ->join('states', 'states.id', '=', 'attestation_questions.state_id')
            ->join('attestations', 'attestations.id', '=', 'attestation_questions.attestation_id')
            ->join('attestation_question_types', 'attestation_question_types.id', '=', 'attestation_questions.question_type')
            ->where('attestation_questions.state_id', '=', $state_id)
            ->where('attestation_questions.attestation_id', '=', $attestation_id)
            ->where('attestation_questions.id', '=', $question_id)
            ->where('attestation_questions.is_active', '=', true)
            ->first();
            
        $question->attributes['question'] =  html_entity_decode($question->attributes['question'],ENT_HTML5);   

        if ($attestation == 0) {
            if ($question->attestation_id == 5) {
                $question_type_id = 0;
            } else {
                $question_type_id = 3;
            }
        } else {
            if ($attestation == 5) {
                $question_type_id = 0;
            } else {
                $question_type_id = 3;
            }
        }

        $data = [
            'question' => $question,
            'states' => options(State::orderBy('name')->get(), 'id', 'name'),
            'attestations' => options(Attestation::where('is_active', '=', true)->get(), 'id', 'name'),
            'question_types' => options(DB::table("attestation_question_types")->where('is_active', '=', true)->whereNotIn('id', [$question_type_id])->get(), 'id', 'type'),
            'attestation_types' => options(DB::table('attestation_types')->where('is_active', '=', true)->get(), 'id', 'name')
        ];

        return $data;
    }

    public static function updateAttestationQuestion($state, $attestation, $question_id, $question, $question_type, $attestation_type)
    {
        $check_exist = self::select('*')
            ->where('id', '=', $question_id)
            ->where('is_active', '=', true)
            ->first();

        if ($check_exist) {
            self::where('id', '=', $question_id)
                ->where('is_active', '=', true)
                ->update(['state_id' => $state, 'attestation_type' => $attestation_type, 'attestation_id' => $attestation, 'question' => $question, 'question_type' => $question_type, 'updated_by' => Auth::user()->id]);
        }
    }

    public static function postAttestationQuestionsAnswer($physician_id, $contract_id, $date_selector, $questions_answer_annually, $questions_answer_monthly, $dates_range)
    {
        set_time_limit(0);
        $dates_range_arr = array_reverse($dates_range);
        unset($dates_range_arr[count($dates_range_arr) - 1]);

        $temp_range_start_date = [];
        if ($date_selector != "All") {
            $temp_date_selector_arr = explode(" - ", $date_selector);
            $temp_range_start_date[] = str_replace("-", "/", $temp_date_selector_arr[0]);
        } else {
            foreach ($dates_range_arr as $date_range) {
                $temp_date_selector_arr = explode(" - ", $date_range);
                $temp_range_start_date[] = str_replace("-", "/", $temp_date_selector_arr[0]);
            }
        }

        $contract = Contract::findOrFail($contract_id);
        $agreement = Agreement::select('agreements.*')
            ->where('agreements.id', '=', $contract->agreement_id)
            ->first();
        $agreement_start_date = $agreement->start_date;
        $agreement_month_end_date = date("Y-m-t", strtotime($agreement->start_date));
        $log_date = '00-0000';
        $physician = Physician::findOrFail($physician_id);
        $is_answer_no = [];

        foreach ($temp_range_start_date as $range_start_date) {
            if ($log_date != date('m-Y', strtotime($range_start_date))) {
                $log_date = date('m-Y', strtotime($range_start_date));

                if ($contract->state_attestations_annually && $questions_answer_annually) {
                    if ((date('Y-m-d', strtotime($range_start_date)) >= $agreement_start_date && date('Y-m-d', strtotime($range_start_date)) <= $agreement_month_end_date) || (date('m', strtotime($range_start_date)) == 1)) {
                        $check_exist_annually = DB::table('attestation_questions_answer_header')->select('*')
                            ->where('physician_id', '=', $physician_id)
                            ->where('contract_id', '=', $contract_id)
                            ->where('attestation_type', '=', 2)
                            ->whereBetween('submitted_for_date', [date('Y-m-01', strtotime($range_start_date)), date('Y-m-t', strtotime($range_start_date))])
                            ->get();

                        if (count($check_exist_annually) == 0) {
                            $data_annually = array(
                                'physician_id' => $physician_id,
                                'contract_id' => $contract_id,
                                'submitted_for_date' => date('Y-m-01', strtotime($range_start_date)),
                                'attestation_type' => 2,
                                'created_by' => Auth::user()->id,
                                'updated_by' => Auth::user()->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            );

                            $annually_attestation_questions_answer_header_id = DB::table('attestation_questions_answer_header')->insertGetId($data_annually);

                            foreach ($questions_answer_annually as $question_answer_annually) {
                                $annually_question = new PhysicianAttestationAnswer();
                                $annually_question->attestation_questions_answer_header_id = $annually_attestation_questions_answer_header_id;
                                $annually_question->question_id = $question_answer_annually->question_id;
                                $annually_question->question_answer = $question_answer_annually->answer;
                                $annually_question->created_by = Auth::user()->id;
                                $annually_question->updated_by = Auth::user()->id;
                                $annually_question->save();

                                if ($question_answer_annually->answer == "No") {
                                    if (!array_key_exists(format_date($range_start_date, 'F Y'), $is_answer_no)) {
                                        array_push($is_answer_no, format_date($range_start_date, 'F Y'));
                                    }
                                }
                            }
                        }
                    }
                }

                if ($contract->state_attestations_monthly && $questions_answer_monthly) {
                    $check_exist_monthly = DB::table('attestation_questions_answer_header')->select('*')
                        ->where('physician_id', '=', $physician_id)
                        ->where('contract_id', '=', $contract_id)
                        ->where('attestation_type', '=', 1)
                        ->whereBetween('submitted_for_date', [date('Y-m-01', strtotime($range_start_date)), date('Y-m-t', strtotime($range_start_date))])
                        ->get();

                    if (count($check_exist_monthly) == 0) {
                        $data_monthly = array(
                            'physician_id' => $physician_id,
                            'contract_id' => $contract_id,
                            'submitted_for_date' => date('Y-m-01', strtotime($range_start_date)),
                            'attestation_type' => 1,
                            'created_by' => Auth::user()->id,
                            'updated_by' => Auth::user()->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        );

                        $monthly_attestation_questions_answer_header_id = DB::table('attestation_questions_answer_header')->insertGetId($data_monthly);

                        foreach ($questions_answer_monthly as $question_answer_monthly) {
                            $monthly_question = new PhysicianAttestationAnswer();
                            $monthly_question->attestation_questions_answer_header_id = $monthly_attestation_questions_answer_header_id;
                            $monthly_question->question_id = $question_answer_monthly->question_id;
                            $monthly_question->question_answer = $question_answer_monthly->answer;
                            $monthly_question->created_by = Auth::user()->id;
                            $monthly_question->updated_by = Auth::user()->id;
                            $monthly_question->save();

                            if ($question_answer_monthly->answer == "No") {
                                if (!array_key_exists(format_date($range_start_date, 'F Y'), $is_answer_no)) {
                                    array_push($is_answer_no, format_date($range_start_date, 'F Y'));
                                }
                            }
                        }
                    }
                }
            }
        }

        if (count($is_answer_no) > 0) {
            $recipients = [$contract->receipient1, $contract->receipient2, $contract->receipient3];
            $is_answer_no = array_unique($is_answer_no, SORT_REGULAR);

            foreach ($recipients as $recipient) {
                if ($recipient != "" && $recipient != null) {
                    $email_data = [
                        'name' => $physician->first_name . " " . $physician->last_name,
                        'email' => $recipient,
                        'type' => EmailSetup::ATTESTATION_ALERT,
                        'with' => [
                            'name' => $physician->first_name . " " . $physician->last_name,
                            'email' => $recipient,
                            'months' => implode(", ", $is_answer_no)
                        ]
                    ];

                    EmailQueueService::sendEmail($email_data);
                }
            }
        }

        return 1;
    }

    public static function sigantureApprovePage($physician_id, $contract_id, $date_selector)
    {
        $date_check = date('Y-m') . "-01";
        $token = "dashboard";
        $signature = DB::table('signature')
            ->select("signature.*")
            ->where("physician_id", "=", $physician_id)
            ->orderBy("created_at", "desc")
            ->first();

        $contract = Contract::findOrFail($contract_id);
        $physician_obj = Physician::findOrFail($physician_id)->contracts()->where('contracts.id', '=', $contract->id)->first();
        $practice_id = $physician_obj->pivot->practice_id;
        $data['practice_id'] = $practice_id;
        //drop column practice_id from table 'physicians' changes by 1254
        $physician = Physician::findOrFail($physician_id);
        $physician->practice_id = $practice_id;
        $data['physician'] = $physician;
        //end drop column practice_id from table 'physicians' changes by 1254

        $data['contract_id'] = $contract_id;
        $data['date_selector'] = $date_selector;
        $data['user_type'] = Auth::user()->group_id;
        $contract = Contract::findOrFail($contract_id);
        //drop column practice_id from table 'physicians' changes by 1254 : codereview
        $data['hospital_id'] = PhysicianPractices::where('physician_id', '=', $physician_id)
            ->where('practice_id', '=', $practice_id)
            ->whereRaw("start_date <= now()")
            ->whereRaw("end_date >= now()")
            ->whereNull("deleted_at")
            ->orderBy("start_date", "desc")
            ->first()->hospital_id;

        if (!$signature && $signature == null) {
            if (is_physician()) {
                return View::make("physicians.signatureApprove_edit")->with($data);
            } else {
                return Redirect::back()->with(['error' => Lang::get('physician_interface.no_signature_physician')]);
            }
        } else {
            $data['signature_id'] = $signature->signature_id;
            $data['signature'] = $signature->signature_path;
            $data['payment_type_id'] = $contract->payment_type_id;

            return View::make("physicians.signatureApprove")->with($data);
        }
    }

    public static function getAttestations($physician_id)
    {
        $data = [];
        $states = Practice::select('practices.state_id as state_id')
            ->join('physician_practices', 'physician_practices.practice_id', '=', 'practices.id')
            ->where('physician_practices.physician_id', '=', $physician_id)
            ->distinct()
            ->get();

        if (count($states) > 0) {
            $attestations = Attestation::select('attestations.*')
                ->join('attestation_questions', 'attestation_questions.attestation_id', '=', 'attestations.id')
                ->whereIn('attestation_questions.state_id', $states->toArray())
                ->where('attestations.is_active', '=', true)
                ->where('attestation_questions.is_active', '=', true)
                ->distinct()
                ->get();

            if (count($attestations) > 0) {
                foreach ($attestations as $attestation) {
                    $questions = self::select('*')
                        ->where('state_id', '=', $states->toArray())
                        ->where('attestation_id', '=', $attestation->id)
                        ->where('is_active', '=', true)
                        ->distinct()
                        ->get();

                    if (count($questions) > 0) {
                        $data[] = [
                            'attestation_id' => $attestation->id,
                            'attestation_name' => $attestation->name,
                            'questions' => $questions
                        ];
                    }
                }
            }
        }

        return $data;
    }

    public static function getPhysicianAttestations($physician_id, $contract_id, $date_selector)
    {
        $contract = Contract::findOrFail($contract_id);
        $approve_logs = AttestationQuestion::getApproveLogs($contract);
        $date_selectors = [];
        $renumbered = [];
        $dates_range_arr = [];
        $results = [];
        $annually_attestations = [];
        $monthly_attestations = [];
        if ($date_selector == "All") {
            if (count($approve_logs) > 0) {
                foreach ($approve_logs as $log) {
                    array_push($date_selectors, $log['date_selector']);
                }
                $date_selectors = array_unique($date_selectors);
                $dates_range_arr = array_reverse($date_selectors);
                $renumbered = array_merge($dates_range_arr, array());
                json_encode($renumbered);
            }
        } else {
            $renumbered[] = $date_selector;
        }

        $agreement = Agreement::select('agreements.*')
            ->where('agreements.id', '=', $contract->agreement_id)
            ->first();
        $agreement_start_date = $agreement->start_date;
        $agreement_month_end_date = date("Y-m-t", strtotime($agreement->start_date));
        $temp_range_start_date = '';
        $check_annually = true;
        $check_monthly = true;
        $log_date = '00-0000';

        foreach ($renumbered as $date_range) {
            $temp_date_selector_arr = explode(" - ", $date_range);
            $temp_range_start_date = str_replace("-", "/", $temp_date_selector_arr[0]);

            if ($log_date != date('m-Y', strtotime($temp_range_start_date))) {
                $log_date = date('m-Y', strtotime($temp_range_start_date));

                // Annually
                if ($contract->state_attestations_annually) {
                    if ((date('Y-m-d', strtotime($temp_range_start_date)) >= $agreement_start_date && date('Y-m-d', strtotime($temp_range_start_date)) <= $agreement_month_end_date) || (date('m', strtotime($temp_range_start_date)) == 1)) {
                        $check_exist_annually = DB::table('attestation_questions_answer_header')->select('*')
                            ->where('physician_id', '=', $physician_id)
                            ->where('contract_id', '=', $contract_id)
                            ->where('attestation_type', '=', 2)
                            ->whereBetween('submitted_for_date', [date('Y-m-01', strtotime($temp_range_start_date)), date('Y-m-t', strtotime($temp_range_start_date))])
                            ->get();

                        if (count($check_exist_annually) == 0) {
                            $check_annually = false;
                        }
                    }
                }

                // Monthly
                if ($contract->state_attestations_monthly) {
                    $check_exist_monthly = DB::table('attestation_questions_answer_header')->select('*')
                        ->where('physician_id', '=', $physician_id)
                        ->where('contract_id', '=', $contract_id)
                        ->where('attestation_type', '=', 1)
                        ->whereBetween('submitted_for_date', [date('Y-m-01', strtotime($temp_range_start_date)), date('Y-m-t', strtotime($temp_range_start_date))])
                        ->get();

                    if (count($check_exist_monthly) == 0) {
                        $check_monthly = false;
                    }
                }
            }
        }

        if (!$check_monthly) {    // Attestation is not exist for selected month
            $monthly_attestations = self::getAnnuallyMonthlyAttestations($contract->id, 1, $physician_id);
        }

        if (!$check_annually) {   // Attestation is not exist for selected year
            $annually_attestations = self::getAnnuallyMonthlyAttestations($contract->id, 2, $physician_id);
        }

        $results = [
            "annually_attestation_flag" => $check_annually,
            "annually_attestations" => $annually_attestations,
            "monthly_attestation_flag" => $check_monthly,
            "monthly_attestations" => $monthly_attestations
        ];

        return $results;
    }

    public static function getApproveLogs($contract)
    {
        $agreement_data = Agreement::getAgreementData($contract->agreement);
        $start_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_data->start_date))); // . " -1 month"

        $payment_type_factory = new PaymentFrequencyFactoryClass();
        $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
        $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
        $prior_month_end_date = $res_pay_frequency['prior_date'];
        $date_range_res = $res_pay_frequency['date_range_with_start_end_date'];

        if ($contract->payment_type_id == PaymentType::PER_DIEM) {
            $changedActionNamesList = OnCallActivity::where("contract_id", "=", $contract->id)->pluck("name", "action_id")->toArray();
        } else {
            $changedActionNamesList = [0 => 0];
        }
        $recent_logs = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.created_at", ">=", $start_date)
            //->where("physician_logs.created_at", "<=", $prior_month_end_date)
            ->where("physician_logs.date", "<=", $prior_month_end_date)
            ->where("physician_logs.signature", "=", 0)
            ->where("physician_logs.approval_date", "=", "0000-00-00")
            ->orderBy("date", "desc")
            ->get();

        $results = [];
        $duration_data = "";

        foreach ($recent_logs as $log) {
            $approval = DB::table('log_approval_history')->select('*')
                ->where('log_id', '=', $log->id)->orderBy('created_at', 'desc')->get();

            if (count($approval) < 1) {
                foreach ($date_range_res as $date_range_obj) {
                    if (strtotime($log->date) >= strtotime($date_range_obj['start_date']) && strtotime($log->date) <= strtotime($date_range_obj['end_date'])) {
                        $date_selector = format_date($date_range_obj['start_date'], "m/d/Y") . " - " . format_date($date_range_obj['end_date'], "m/d/Y");
                    }
                }

                $results[] = [
                    "date_selector" => $date_selector,
                ];
            }
        }

        if (count($results) > 0) {
            return $results;
        }

        return [];
    }

    public static function getAnnuallyMonthlyAttestations($contract_id, $attestation_type, $physician_id)
    {
        $contract = Contract::findOrFail($contract_id);
        $data = [];
        $attestation_id = 0;
        if ($contract->supervision_type == 1) {
            $attestation_id = 2;
        } else if ($contract->supervision_type == 2) {
            $attestation_id = 1;
        }

        $states = Practice::select('practices.state_id as state_id')
            ->join('physician_practices', 'physician_practices.practice_id', '=', 'practices.id')
            ->where('physician_practices.physician_id', '=', $physician_id)
            ->distinct()
            ->get();

        if (count($states) > 0) {
            $attestations = Attestation::select('attestations.*')
                ->join('attestation_questions', 'attestation_questions.attestation_id', '=', 'attestations.id')
                ->whereIn('attestation_questions.state_id', $states->toArray())
                ->where('attestations.is_active', '=', true)
                ->where('attestation_questions.is_active', '=', true)
                ->where('attestation_questions.attestation_type', '=', $attestation_type)
                ->whereNotIn('attestations.id', [$attestation_id])
                ->distinct()
                ->get();

            if (count($attestations) > 0) {
                foreach ($attestations as $attestation) {
                    $questions = self::select('*')
                        ->where('state_id', '=', $states->toArray())
                        ->where('attestation_id', '=', $attestation->id)
                        ->where('is_active', '=', true)
                        ->where('attestation_type', '=', $attestation_type)
                        ->distinct()
                        ->get();

                    if (count($questions) > 0) {
                        $data[] = [
                            'attestation_id' => $attestation->id,
                            'attestation_name' => $attestation->name,
                            'questions' => $questions
                        ];
                    }
                }
            }
        }

        return $data;
    }

    public static function saveAttestations($physician_id, $contract_id, $date_selector, $questions_answer_annually, $questions_answer_monthly, $dates_range)
    {
        set_time_limit(0);

        $temp_range_start_date = [];
        if ($date_selector != "All") {
            $temp_date_selector_arr = explode(" - ", $date_selector);
            $temp_range_start_date[] = str_replace("-", "/", $temp_date_selector_arr[0]);
        } else {
            $dates_range_arr = array_reverse($dates_range);
            unset($dates_range_arr[count($dates_range_arr) - 1]);

            foreach ($dates_range_arr as $date_range) {
                $temp_date_selector_arr = explode(" - ", $date_range);
                $temp_range_start_date[] = str_replace("-", "/", $temp_date_selector_arr[0]);
            }
        }

        $contract = Contract::findOrFail($contract_id);
        $agreement = Agreement::select('agreements.*')
            ->where('agreements.id', '=', $contract->agreement_id)
            ->first();
        $agreement_start_date = $agreement->start_date;
        $agreement_month_end_date = date("Y-m-t", strtotime($agreement->start_date));
        $log_date = '00-0000';
        $physician = Physician::findOrFail($physician_id);
        $is_answer_no = [];

        foreach ($temp_range_start_date as $range_start_date) {
            if ($log_date != date('m-Y', strtotime($range_start_date))) {
                $log_date = date('m-Y', strtotime($range_start_date));

                if ($contract->state_attestations_annually && $questions_answer_annually) {
                    if ((date('Y-m-d', strtotime($range_start_date)) >= $agreement_start_date && date('Y-m-d', strtotime($range_start_date)) <= $agreement_month_end_date) || (date('m', strtotime($range_start_date)) == 1)) {
                        $check_exist_annually = DB::table('attestation_questions_answer_header')->select('*')
                            ->where('physician_id', '=', $physician_id)
                            ->where('contract_id', '=', $contract_id)
                            ->where('attestation_type', '=', 2)
                            ->whereBetween('submitted_for_date', [date('Y-m-01', strtotime($range_start_date)), date('Y-m-t', strtotime($range_start_date))])
                            ->get();

                        if (count($check_exist_annually) == 0) {
                            $data_annually = array(
                                'physician_id' => $physician_id,
                                'contract_id' => $contract_id,
                                'submitted_for_date' => date('Y-m-01', strtotime($range_start_date)),
                                'attestation_type' => 2,
                                'created_by' => $physician_id,
                                'updated_by' => $physician_id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            );

                            $annually_attestation_questions_answer_header_id = DB::table('attestation_questions_answer_header')->insertGetId($data_annually);

                            foreach ($questions_answer_annually as $question_answer_annually) {
                                $annually_question = new PhysicianAttestationAnswer();
                                $annually_question->attestation_questions_answer_header_id = $annually_attestation_questions_answer_header_id;
                                $annually_question->question_id = $question_answer_annually['question_id'];
                                $annually_question->question_answer = $question_answer_annually['answer'];
                                $annually_question->created_by = $physician_id;
                                $annually_question->updated_by = $physician_id;
                                $annually_question->save();

                                if ($question_answer_annually['answer'] == "No") {
                                    if (!array_key_exists(format_date($range_start_date, 'M Y'), $is_answer_no)) {
                                        array_push($is_answer_no, format_date($range_start_date, 'M Y'));
                                    }
                                }
                            }
                        }
                    }
                }

                if ($contract->state_attestations_monthly && $questions_answer_monthly) {
                    $check_exist_monthly = DB::table('attestation_questions_answer_header')->select('*')
                        ->where('physician_id', '=', $physician_id)
                        ->where('contract_id', '=', $contract_id)
                        ->where('attestation_type', '=', 1)
                        ->whereBetween('submitted_for_date', [date('Y-m-01', strtotime($range_start_date)), date('Y-m-t', strtotime($range_start_date))])
                        ->get();

                    if (count($check_exist_monthly) == 0) {
                        $data_monthly = array(
                            'physician_id' => $physician_id,
                            'contract_id' => $contract_id,
                            'submitted_for_date' => date('Y-m-01', strtotime($range_start_date)),
                            'attestation_type' => 1,
                            'created_by' => $physician_id,
                            'updated_by' => $physician_id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        );

                        $monthly_attestation_questions_answer_header_id = DB::table('attestation_questions_answer_header')->insertGetId($data_monthly);

                        foreach ($questions_answer_monthly as $question_answer_monthly) {
                            $monthly_question = new PhysicianAttestationAnswer();
                            $monthly_question->attestation_questions_answer_header_id = $monthly_attestation_questions_answer_header_id;
                            $monthly_question->question_id = $question_answer_monthly['question_id'];
                            $monthly_question->question_answer = $question_answer_monthly['answer'];
                            $monthly_question->created_by = $physician_id;
                            $monthly_question->updated_by = $physician_id;
                            $monthly_question->save();

                            if ($question_answer_monthly['answer'] == "No") {
                                if (!array_key_exists(format_date($range_start_date, 'M Y'), $is_answer_no)) {
                                    array_push($is_answer_no, format_date($range_start_date, 'M Y'));
                                }
                            }
                        }
                    }
                }
            }
        }

        if (count($is_answer_no) > 0) {
            $recipients = [$contract->receipient1, $contract->receipient2, $contract->receipient3];
            $is_answer_no = array_unique($is_answer_no, SORT_REGULAR);

            foreach ($recipients as $recipient) {
                if ($recipient != "" && $recipient != null) {
                    $email_data = [
                        'name' => $physician->first_name . " " . $physician->last_name,
                        'email' => $recipient,
                        'type' => EmailSetup::ATTESTATION_ALERT,
                        'with' => [
                            'name' => $physician->first_name . " " . $physician->last_name,
                            'email' => $recipient,
                            'months' => implode(", ", $is_answer_no)
                        ]
                    ];

                    EmailQueueService::sendEmail($email_data);
                }
            }
        }

        return 1;
    }

    public static function attestationReportData($hospital, $agreement_ids, $physician_ids, $contract_type, $attestation_type)
    {
        $results = [];

        if ($contract_type == 20) {
            foreach ($agreement_ids as $agreement_id) {
                $agreement = Agreement::FindOrFail($agreement_id);
                $months_start = Request::input("start_{$agreement_id}_start_month");
                $months_end = Request::input("end_{$agreement_id}_start_month");

                $attestations = Attestation::select('attestations.*')
                    ->where('attestations.is_active', '=', true)
                    ->distinct()
                    ->get();

                $headers = DB::table('attestation_questions_answer_header')->select('attestation_questions_answer_header.*', 'contract_names.name as contract_name', DB::raw("CONCAT(physicians.first_name, ' ' ,physicians.last_name ) AS physician_name"))
                    ->join('contracts', 'contracts.id', '=', 'attestation_questions_answer_header.contract_id')
                    ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
                    ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
                    ->join('physicians', 'physicians.id', '=', 'physician_contracts.physician_id')
                    ->where('contracts.agreement_id', '=', $agreement_id)
                    ->where('contracts.contract_type_id', '=', $contract_type)
                    ->whereIn('physician_contracts.physician_id', $physician_ids)
                    ->where('attestation_questions_answer_header.attestation_type', $attestation_type)
                    ->whereBetween('attestation_questions_answer_header.submitted_for_date', [date('Y-m-1', strtotime($months_start)), date('Y-m-t', strtotime($months_end))])
                    ->distinct()
                    ->get();

                $contracts_arr = [];

                foreach ($headers as $headers) {
                    $questions_arr = [];
                    foreach ($attestations as $attestation) {
                        $questions = AttestationQuestion::select('attestation_questions.id as question_id', 'attestation_questions.question as question', 'attestation_questions_answer.question_answer as answer')
                            ->join('attestation_questions_answer', 'attestation_questions_answer.question_id', '=', 'attestation_questions.id')
                            ->join('attestation_questions_answer_header', 'attestation_questions_answer_header.id', '=', 'attestation_questions_answer.attestation_questions_answer_header_id')
                            ->where('attestation_questions.attestation_id', '=', $attestation->id)
                            ->where('attestation_questions_answer.attestation_questions_answer_header_id', '=', $headers->id)
                            ->distinct()
                            ->get();

                        if (count($questions) > 0) {
                            $questions_arr[] = [
                                "attestation_name" => $attestation->name,
                                "questions" => $questions
                            ];
                        }
                    }

                    $contracts_arr [] = [
                        'contract_name' => $headers->contract_name,
                        'physician_name' => $headers->physician_name,
                        'submitted_for_date' => format_date($headers->submitted_for_date, 'M Y'),
                        'attestations' => $questions_arr
                    ];
                }

                if ($contracts_arr) {
                    $results[] = [
                        'attestation_type' => $attestation_type == 2 ? "Annually Report" : "Monthly Report",
                        "agreement_id" => $agreement->id,
                        "agreement_name" => $agreement->name,
                        "contracts" => $contracts_arr
                    ];
                }
            }
        }
        return $results;
    }
}
