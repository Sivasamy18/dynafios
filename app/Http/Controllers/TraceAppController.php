<?php

namespace App\Http\Controllers;

use App\Physician;
use App\Contract;
use App\PaymentType;
use App\PhysicianLog;
use App\Action;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;

class TraceAppController extends BaseController
{
    public function postLogin()
    {
        $email = Request::input('email');
        $password = Request::input('password');
        $physician = Physician::where('email', '=', $email)->first();

        if (!$physician || !Hash::check($password, $physician->password)) {
            return Response::json([
                'error' => Lang::get('trace_app.login_error')
            ]);
        }

        $data = [
            'calendar' => $this->getCalendar(),
            'contracts' => $this->getContracts($physician),
            'rlogs' => $this->getRecentLogs($physician),
            'tlogs' => $this->getRecentTracks($physician),
            'physician' => [
                'id' => $physician->id,
                'npi' => $physician->npi,
                'email' => $physician->email,
                'first_name' => $physician->first_name,
                'last_name' => $physician->last_name,
                'password' => $physician->password,
                'phone' => $physician->phone,
                'specialty' => $physician->specialty->name,
                'practice' => $physician->practices->first()->name,
                'hospital' => $physician->practices->first()->hospital->name,
                'logs' => $physician->logs()->count(),
                'lastlogin' => format_date($physician->updated_at, 'M d Y, h:i A')
            ]
        ];

        return Response::json($data);
    }

    public function postChangePassword()
    {
        $id = Request::input('id');
        $current = Request::input('current');
        $password = Request::input('newpass');

        $physician = Physician::find(Request::input('id'));

        if (strlen($password) < 6 || strlen($password) > 20) {
            return Response::json(['resp' => Lang::get('trace_app.invalid_password_length')]);
        }

        if (!$physician || !Hash::check($current, $physician->password)) {
            return Response::json(['resp' => Lang::get('trace_app.invalid_password')]);
        }

        $physician->password = Hash::make($password);
        $physician->setPasswordText($password);

        if (!$physician->save()) {
            return Response::json(['resp' => Lang::get('trace_app.password_error')]);
        }

        return Response::json(['resp' => Lang::get('trace_app.password_success')]);
    }

    public function postSaveLog()
    {
        $email = Request::input('email');
        $password = Request::input('password');
        $contract_id = Request::input('cid');
        $customAction = Request::input('otheract') == 'true';

        $physician = Physician::where('email', '=', $email)
            ->where('password', '=', $password)
            ->first();
        $contract = Contract::find($contract_id);

        if (!$physician || !$contract) {
            return Response::json([
                'error' => Lang::get('trace_app.save_error')
            ]);
        }

        $data = [
        ];

        //if ($contract->contract_type_id == 4) {
        if ($contract->payment_type_id == PaymentType::PER_DIEM) {
            $dates = json_decode(Request::input('date'));
            $failed = 0;
            $saved = 0;

            foreach ($dates as $date) {
                $date = explode('-', $date);
                $date = date('Y') . '-' . $date[0] . '-' . $date[1];

                if (date('Y-m-d', strtotime($date)) > date('Y-m-d')) {
                    $failed++;
                    continue;
                }

                $log = new PhysicianLog;
                $log->contract_id = $contract->id;
                $log->physician_id = $physician->id;
                $log->date = $date;
                $log->duration = 24;
                $log->action_id = Request::input('action');

                if ($log->save()) {
                    $saved++;
                } else {
                    $failed++;
                }
            }

            if (sizeof($dates) == 1 && $saved == 1) {
                $data['passed'] = "Log saved successfully.";
            } else if (sizeof($dates) == 1 && $saved == 0) {
                $data['error'] = "An error occurred while saving this log.";
            } else if (sizeof($dates) > 1 && $failed == 0) {
                $data['passed'] = "Successfully saved {$saved} logs.";
            } elseif (sizeof($dates) > 1 && $failed > 0 && $saved > 0) {
                $data['passed'] = "Successfully saved {$saved} log(s).\nFailed to save {$failed} log(s).";
            } else if (sizeof($dates) > 1 && $saved == 0) {
                $data['error'] = "The logs has not been saved.";
            }

            $data['saved'] = $saved;
            $data['failed'] = $failed;
        } else {
            $date = explode('-', Request::input('date'));

            if (count($date) < 3) {
                $date = date('Y') . "-{$date[0]}-{$date[1]}";
            } else {
                $date = "{$date[2]}-{$date[0]}-{$date[1]}";
            }

            if (date('Y-m-d', strtotime($date)) > date('Y-m-d')) {
                return Response::json(['error' => 'Invalid date.']);
            }

            $log = new PhysicianLog();
            $log->contract_id = $contract->id;
            $log->physician_id = $physician->id;
            $log->date = $date;
            $log->duration = Request::input('duration');

            if ($customAction) {
                $action = new Action();
                $action->name = Request::input('action');
                $action->contract_type_id = $contract->contract_type_id;
                $action->payment_type_id = $contract->payment_type_id;
                $action->action_type_id = Request::input('actiontype') == 'activities' ? 3 : 4;
                $action->save();

                $physician->actions()->attach($action->id);
                $log->action_id = $action->id;
            } else {
                $log->action_id = Request::input('action');
            }

            if (!$log->save()) {
                $data['error'] = "An error occurred while saving this log.";
            } else {
                $data['passed'] = "Log saved successfully.";
            }
        }

        $data['rlogs'] = $this->getRecentLogs($physician);
        $data['tlogs'] = $this->getRecentTracks($physician);

        return Response::json($data);
    }

    public function postDeleteLog()
    {
        $email = Request::input('email');
        $password = Request::input('password');
        $log_id = Request::input('log_id');

        $physician = Physician::where('email', '=', $email)
            ->where('password', '=', $password)
            ->first();
        $log = PhysicianLog::find($log_id);

        if (!$physician || !$log) {
            return Response::json([
                'error' => Lang::get('trace_app.delete_error')
            ]);
        }

        if (!$log->delete()) {
            return Response::json([
                'error' => Lang::get('trace_app.delete_error')
            ]);
        }

        return Response::json([
            'passed' => Lang::get('trace_app.delete_success'),
            'recent_logs' => $this->getRecentLogs($physician),
            'recent_tracks' => $this->getRecentTracks($physician)
        ]);
    }

    private function getContracts($physician)
    {
        $contracts = $physician->contracts()
            ->select('contracts.*')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->whereRaw('agreements.start_date <= NOW()')
            ->whereRaw('agreements.end_date >= NOW()')
            ->get();

        if (count($contracts) == 0)
            return [];

        foreach ($contracts as $contract) {
            if ($contract->actions()->count() == 0)
                continue;

            $results[] = [
                'name' => $this->getContractName($contract),
                'details' => $this->getDetails($contract),
                'duties' => $this->getDuties($contract),
                'activities' => $this->getActivities($contract)
            ];
        }

        return $results;
    }

    private function getContractName($contract)
    {
        return contract_name($contract);
    }

    private function getDetails($contract)
    {
        return [
            'attributes' => [
                'id' => $contract->id,
                'physician_id' => $contract->physician_id,
                'contracttype_id' => $contract->contract_type_id,
                'paymenttype_id' => $contract->payment_type_id,
                'contractname_id' => $contract->contract_name_id,
                'start_date' => format_date($contract->agreement->start_date, 'M d Y'),
                'end_date' => format_date($contract->agreement->end_date, 'M d Y'),
                'min_hours' => "{$contract->min_hours}",
                'max_hours' => "{$contract->max_hours}",
                'exphrs' => "{$contract->expected_hours}",
                'rate' => "{$contract->rate}",
                'description' => $contract->description,
                'archived' => $contract->archived,
                'created_at' => $contract->created_at,
                'updated_at' => $contract->updated_at
            ]
        ];
    }

    private function getDuties($contract)
    {
        $results = [];

        foreach ($contract->actions as $action) {
            if ($action->action_type_id == 2 || $action->action_type_id == 4) {
                $results[] = $this->getActionData($action);
            }
        }

        return $results;
    }

    private function getActivities($contract)
    {
        $results = [];

        foreach ($contract->actions as $action) {
            if ($action->action_type_id == 2 || $action->action_type_id == 4)
                continue;

            $results[] = $this->getActionData($action);
        }

        return $results;
    }

    private function getActionData($action)
    {
        return [
            'attributes' => [
                'id' => $action->id,
                'name' => $action->name,
                'actiontype_id' => $action->action_type_id,
                'contracttype_id' => $action->contract_type_id,
                'paymenttype_id' => $action->payment_type_id
            ]
        ];
    }

    private function getCalendar()
    {
        $month = date('n', strtotime('-1 month'));
        $days = intval(date('t', strtotime('-1 month')));
        return [$month => $days];
    }

    private function getRecentLogs($physician)
    {
        $results = DB::table('physician_logs')->select(
            'physician_logs.id as plog_id',
            'physician_logs.date',
            'physician_logs.duration',
            'physician_logs.created_at',
            'actions.name',
            'action_types.name as actiontype',
            'contract_types.name as cname')
            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('actions', 'actions.id', '=', 'physician_logs.action_id')
            ->join('action_types', 'action_types.id', '=', 'actions.action_type_id')
            ->where('physician_logs.physician_id', '=', $physician->id)
            ->whereRaw('agreements.start_date <= NOW()')
            ->whereRaw('agreements.end_date >= NOW()')
            ->whereRaw('physician_logs.date >= DATE(NOW() - INTERVAL 90 DAY)')
            ->orderBy('physician_logs.id', 'desc')
            ->get();

        foreach ($results as $result) {
            $result->duration = "{$result->duration}";
            $result->created_at = format_date($result->created_at, 'M j, Y g:ia');
            $result->date = format_date($result->date, 'M j, Y');
        }

        return $results ? $results : 0;
    }

    private function getRecentTracks($physician)
    {
        $results = DB::table('physician_logs')->select(
            DB::raw('SUM(physician_logs.duration) as workedhrs'),
            'physician_logs.contract_id',
            'contract_types.name as cname',
            DB::raw('round(contracts.expected_hours, 2) as exphrs')
        )
            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('actions', 'actions.id', '=', 'physician_logs.action_id')
            ->join('action_types', 'action_types.id', '=', 'actions.action_type_id')
            ->where('physician_logs.physician_id', '=', $physician->id)
            ->whereRaw('contracts.archived = false')
            ->whereRaw('YEAR(physician_logs.date) = YEAR(NOW())')
            ->whereRaw('MONTH(physician_logs.date) = MONTH(NOW())')
            ->whereRaw('agreements.start_date <= NOW()')
            ->whereRaw('agreements.end_date >= NOW()')
            ->groupBy('contracts.id')
            ->get();

        return $results ? $results : 0;
    }
}
