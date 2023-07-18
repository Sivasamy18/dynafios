<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mail;
use Lang;
use Auth;
use App\Services\EmailQueueService;
use App\customClasses\EmailSetup;

class ProxyApprovalDetails extends Model
{

    use SoftDeletes;

    protected $table = 'proxy_approver_details';
    protected $softDelete = true;
    protected $dates = ['deleted_at'];


    public static function find_proxy_aaprovers($user_id)
    {
        //this function returns aaray of all proxy approver user ids including that user id
        $today = date('Y-m-d');
        $proxy_check_id = array();
        $proxy_check = DB::table("proxy_approver_details")
            ->select("proxy_approver_details.user_id")
            ->where("proxy_approver_details.proxy_approver_id", "=", $user_id)
            ->where("proxy_approver_details.start_date", "<=", $today)
            ->where("proxy_approver_details.end_date", ">=", $today)
            ->whereNull("proxy_approver_details.deleted_at")
            ->get();
        foreach ($proxy_check as $proxy_check) {
            $proxy_check_id[] = $proxy_check->user_id;
        }
        //Log::info('in find proxy approver ---- ',array($proxy_check_id));

        array_push($proxy_check_id, $user_id);
        //Log::info('in find proxy approver +++++++ ',array($proxy_check_id));
        return $proxy_check_id;
    }

    public static function find_only_proxy_approvers($user_id)
    {
        //this function returns aaray of all proxy approver user ids including that user id
        $today = date('Y-m-d');
        $proxy_check_id = array();
        $proxy_check = DB::table("proxy_approver_details")
            ->select("proxy_approver_details.user_id")
            ->where("proxy_approver_details.proxy_approver_id", "=", $user_id)
            ->where("proxy_approver_details.start_date", "<=", $today)
            ->where("proxy_approver_details.end_date", ">=", $today)
            ->whereNull("proxy_approver_details.deleted_at")
            ->get();
        foreach ($proxy_check as $proxy_check) {
            $proxy_check_id[] = $proxy_check->user_id;
        }
        //Log::info('in find proxy approver ---- ',array($proxy_check_id));

        //array_push($proxy_check_id,$user_id);
        //Log::info('in find proxy approver +++++++ ',array($proxy_check_id));
        return $proxy_check_id;
    }

    //function for find approver managers
    public static function check_for_manager_id($agreement_id, $contract_id, $role, $user_id = 0)
    {
        if ($user_id == 0) {
            $user_id = Auth::user()->id;
        }
        $proxy_approvers = self::find_only_proxy_approvers($user_id);
        if (count($proxy_approvers) > 0) {
            $manager_ids = ApprovalManagerInfo::where("agreement_id", "=", $agreement_id)
                ->where("contract_id", "=", $contract_id)
//                    ->where("type_id","=",$role)
                ->where("is_deleted", "=", "0")
                ->whereIn("user_id", $proxy_approvers)
                ->pluck("user_id");

            if (count($manager_ids) > 0) {
                $checked_for_user_id = $manager_ids[0];
            } else {
                $checked_for_user_id = $user_id;
            }
        } else {
            $checked_for_user_id = $user_id;
        }

        return $checked_for_user_id;
    }

    public static function find_proxy_aaprover_users($user_id)
    {
        //this function returns aaray of all proxy approver user ids including that user id
        $today = date('Y-m-d');
        $proxy_check_id = array();
        $proxy_check = DB::table("proxy_approver_details")
            ->select("proxy_approver_details.proxy_approver_id")
            ->where("proxy_approver_details.user_id", "=", $user_id)
            ->where("proxy_approver_details.start_date", "<=", $today)
            ->where("proxy_approver_details.end_date", ">=", $today)
            ->whereNull("proxy_approver_details.deleted_at")
            ->get();
        foreach ($proxy_check as $proxy_check) {
            $proxy_check_id[] = $proxy_check->proxy_approver_id;
        }
        //Log::info('in find proxy approver ---- ',array($proxy_check_id));

        array_push($proxy_check_id, $user_id);
        //Log::info('in find proxy approver +++++++ ',array($proxy_check_id));
        return $proxy_check_id;
    }

    public static function find_only_proxy_aaprover_users($user_id)
    {
        //this function returns aaray of all proxy approver user ids including that user id
        $today = mysql_date(date('Y-m-d'));
        $proxy_check_id = array();
        $proxy_check = self::where("proxy_approver_details.user_id", "=", $user_id)
            ->where("proxy_approver_details.start_date", "<=", $today)
            ->where("proxy_approver_details.end_date", ">=", $today)
            ->whereNull("proxy_approver_details.deleted_at")
            ->first();

        return $proxy_check;
    }


    public static function save_proxy_aaprover_user($userid, $proxy_user_id, $start_date, $end_date, $created_by)
    {
        $userdata = $user = User::findOrFail($userid);

        if ($proxy_user_id > 0) {
            $proxy_userdata = $user = User::findOrFail($proxy_user_id);
            if ($start_date <= $end_date) {
                $check_for_proxy_approver = self::find_only_proxy_aaprover_users($userid);
                if ($check_for_proxy_approver) {
                    $check_for_proxy_approver->delete();
                }

                $proxy_approver = new ProxyApprovalDetails;
                $proxy_approver->user_id = $userid;
                $proxy_approver->proxy_approver_id = $proxy_user_id;
                $proxy_approver->start_date = $start_date;
                $proxy_approver->end_date = $end_date;
                $proxy_approver->created_by_user_id = $created_by;
                if (!$proxy_approver->save()) {
                    $result["response"] = "error";
                    $result["msg"] = Lang::get('proxy_user.proxy_approver_save_error');
                } else {
                    $data = [];
                    $data['email'] = $proxy_userdata->email;
                    $data['name'] = ucfirst($userdata->first_name) . ' ' . ucfirst($userdata->last_name);
                    $data['proxy_user_name'] = ucfirst($proxy_userdata->first_name) . ' ' . ucfirst($proxy_userdata->last_name);
                    $data['start_date'] = $start_date;
                    $data['end_date'] = $end_date;
                    $data['type'] = EmailSetup::PROXY_APPROVER_ASSIGNMENT;
                    $data['with'] = [
                        'proxy_user_name' => ucfirst($proxy_userdata->first_name) . ' ' . ucfirst($proxy_userdata->last_name),
                        'name' => ucfirst($userdata->first_name) . ' ' . ucfirst($userdata->last_name),
                        'start_date' => $start_date,
                        'end_date' => $end_date
                    ];

                    EmailQueueService::sendEmail($data);

                    $result["response"] = "success";
                    $result["msg"] = Lang::get('proxy_user.create_proxy_approver_success');
                }
            } else {
                $result["response"] = "error";
                $result["msg"] = Lang::get('proxy_user.start_date_end_date_error');
            }
        } else {
            $result["response"] = "error";
            $result["msg"] = Lang::get('proxy_user.approver_selection_error');
        }

        return $result;
    }

    public static function delete_proxy_user($userid)
    {


        $check_for_proxy_approver = self::find_only_proxy_aaprover_users($userid);
        if ($check_for_proxy_approver) {
            $check_for_proxy_approver->delete();
            return true;
        } else {
            return false;
        }

    }


}
