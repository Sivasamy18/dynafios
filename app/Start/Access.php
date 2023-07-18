<?php

namespace App\Start;

use App\Group;
use App\ApprovalManagerInfo;
use App\Hospital;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

function is_owner($id)
{
    if(auth()->user()) {
        return Auth::user()->id == $id;
    }
    else {
        return false;
    }
}

function is_hospital_owner($id)
{
    $user = Auth::user();
    $user = User::find($id);
    if ($user->hasAnyRole(['Practice Manager', 'super_user','Hospital User','System Administrator'])) {
        $count = DB::table('hospital_user')
            ->where('hospital_user.hospital_id', '=', $id)
            ->where('hospital_user.user_id', '=', $user->id)
            ->count('id');
        return $count > 0;
    } else if ($user->hasRole('Practice Manager')) {
        return false;
    } else if ($user->hasRole('super_user')) {
        return true;
    }

    return false;
}

function is_practice_owner($id)
{
    $user = Auth::user();
    $user = User::find($id);
    if ($user->hasAnyRole(['Hospital User','System Administrator'])) {
        $count = DB::table('practices')
            ->join('hospitals', 'hospitals.id', '=', 'practices.hospital_id')
            ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
            ->where('practices.id', '=', $id)
            ->where('hospital_user.user_id', '=', $user->id)
            ->count('practices.id');
        return $count > 0;
    } else if ($user->hadRole('Practice Manager')) {
        $count = DB::table('practices')
            ->join('practice_user', 'practice_user.practice_id', '=', 'practices.id')
            ->where('practices.id', '=', $id)
            ->where('practice_user.user_id', '=', $user->id)
            ->count('practices.id');
        return $count > 0;
    } else if ($user->hasRole('super_user')) {
        return true;
    }

    return false;
}

function is_physician_owner($id)
{
    $user = Auth::user();
    $user = User::find($id);
    if ($user->hasAnyRole(['Hospital User','System Administrator'])) {
        $count = DB::table('physicians')
            ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
            ->join('practices', 'practices.id', '=', 'physician_practices.practice_id')
            ->join('hospitals', 'hospitals.id', '=', 'practices.hospital_id')
            ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
            ->where('physicians.id', '=', $id)
            ->where('hospital_user.user_id', '=', $user->id)
            ->count('physicians.id');
        return $count > 0;
    } else if ($user->hasRole('Practice Manager')) {
        $count = DB::table('physicians')
            ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
            ->join('practices', 'practices.id', '=', 'physician_practices.practice_id')
            ->join('practice_user', 'practice_user.practice_id', '=', 'practices.id')
            ->where('physicians.id', '=', $id)
            ->where('practice_user.user_id', '=', $user->id)
            ->count('physicians.id');
        return $count > 0;
    } else if ($user->hasRole('super_user')) {
        return true;
    } else if ($user->hasRole('Physician')) {
        return true;
    }

    return false;
}

function is_user_owner($id)
{
    $user = Auth::user();
    $user = User::find($id);
    if ($user->id == $id) {
        return true;
    } else if ($user->hasRole('super_user')) {
        return true;
    } else if ($user->hasRole('Hospital User')) {

    } else if ($user->hasRole('Practice Manager')) {

    }

    return false;
}

function is_group($group_id)
{
    if(auth()->user()) {
        return Auth::user()->group_id == $group_id;
    }
    else {
        return false;
    }
}
function role_check($role)
{
    if(auth()->user()) {
        $id=Auth::user()->id;
        $user = User::find($id);
        return $user->hasRole($role);
  } else {
      return false;
  }
}

function is_super_user()
{
    return role_check('super_user');
}

function is_hospital_admin()
{
    return role_check('Hospital User');
}

function is_super_hospital_user()
{
    return is_group(Group::SUPER_HOSPITAL_USER);
}

function is_practice_manager()
{
    return role_check('Practice Manager');
}

function is_hospital_cfo()
{
    return is_group(Group::HOSPITAL_CFO);
}

function is_physician()
{
    return role_check('Physician');

}

function is_contract_manager()
{
    $id = Auth::user()->id;
    $user = User::find($id);
    if ($user->hasRole('Physician')) {
        //new approval level check 28AUG2018
        $count = ApprovalManagerInfo::where("user_id", "=", $user->id)->where("type_id", "=", 1)->where("is_deleted", "=", '0')->count('user_id');
        return $count > 0;
    }
    return false;
}

function is_financial_manager()
{
    $id = Auth::user()->id;
    $user = User::find($id);

    if ($user->hasRole('Physician')) {
        //new approval level check 28AUG2018
        $count = ApprovalManagerInfo::where("user_id", "=", $user->id)->where("type_id", "=", 2)->where("is_deleted", "=", '0')->count('user_id');
        return $count > 0;
    }

    return false;
}

function is_approval_manager()
{
    $id = Auth::user()->id;
    $user = User::find($id);
    if ($user->hasRole('Physician')) {
        #check if user is an approver
        $count = ApprovalManagerInfo::where("user_id", "=", $user->id)->where("is_deleted", "=", '0')->count('user_id');

        if ($count == 0) {
            #check if he is a proxy approver for another user who is an approver
            $today = date('Y-m-d');
            $proxy_check = DB::table("proxy_approver_details")
                ->select("proxy_approver_details.user_id")
                ->where("proxy_approver_details.proxy_approver_id", "=", $user->id)
                ->where("proxy_approver_details.start_date", "<=", $today)
                ->where("proxy_approver_details.end_date", ">=", $today)
                ->get();

            if (count($proxy_check) > 0) {
                #check if the user for whom this user is proxy approver is an approver for anything or not
                foreach ($proxy_check as $proxy_check) {
                    $proxy_check_id[] = $proxy_check->user_id;
                }
                $count = ApprovalManagerInfo::whereIn("user_id", $proxy_check_id)->where("is_deleted", "=", '0')->count('user_id');
            }
        }

        return $count > 0;
    }

    return false;
}

function is_health_system_user()
{
    return role_check('Health System User');
}

function is_health_system_region_user()
{
    return is_group(Group::HEALTH_SYSTEM_REGION_USER);
}

function has_invoice_access()
{
    $id = Auth::user()->id;
    $user = User::find($id);
    $invoice_dashboard_display = 1;
    if ($user->hasAnyRole(['Hospital User','System Administrator'])){
        $invoice_dashboard_display = Hospital::get_status_invoice_dashboard_display(Auth::user()->id);
        return $invoice_dashboard_display;
    }
    return $invoice_dashboard_display;

}


function has_invoice_dashboard_access()
{
    $id = Auth::user()->id;
    $user = User::find($id);
    $invoice_dashboard_display = 0;
    if ($user->hasAnyRole(['Hospital User','System Administrator'])) {
        $hospital_user_obj = DB::table("hospital_user")
            ->join("hospitals", "hospital_user.hospital_id", "=", "hospitals.id")
            ->where('hospital_user.user_id', '=', $user->id)
            ->where('hospital_user.is_invoice_dashboard_display', '=', 1)
            ->whereNull('hospitals.deleted_at')
            ->where('hospitals.archived', '=', 0)
            ->pluck('hospital_user.hospital_id')->toArray();

        foreach ($hospital_user_obj as $hospital_id) {
            $result = Hospital::findOrFail($hospital_id);
            /*            if($result){
                            $pending_payment_contract += $result['pending_payment_count'];
                        }*/
            if ($result['invoice_dashboard_on_off'] == 1) {
                $invoice_dashboard_display = 1;
            }
        }
    }
    return $invoice_dashboard_display;

}

function is_hospital_user_healthSystem_user()
{
    $id = Auth::user()->id;
    $user = User::find($id);
    if ($user->hasAnyRole(['Practice Manager','Hospital User','System Administrator']) ) {
        $count = DB::table('health_system_users')->where('health_system_users.user_id', '=', $id)->whereNull('deleted_at')->count('id');
        return $count > 0;
    }
}

function is_hospital_user_healthSystem_region_user()
{
    $id = Auth::user()->id;
    $user = User::find($id);

    if ($user->hasAnyRole(['Practice Manager','Hospital User','System Administrator']) ) {
        $count = DB::table('health_system_region_users')->where('health_system_region_users.user_id', '=', $id)->whereNull('deleted_at')->count('id');
        return $count > 0;
    }
}

function create_an_agreement(){
    $user = Auth::user();
    return $user->hasPermissionTo('agreement.create'); 
} 
function create_a_contract(){
    $user = Auth::user();
    return $user->hasPermissionTo('contract.create'); 
} 
function create_a_user(){
    $user = Auth::user();
    return $user->hasPermissionTo('user.create'); 
} 
function create_a_provider(){
    $user = Auth::user();
    return $user->hasPermissionTo('provider.create'); 
} 
function create_a_practiceManager(){
    $user = Auth::user();
} 
function create_a_newPractice(){
    $user = Auth::user();
    return $user->hasPermissionTo(' practice.create'); 
}
function change_contract_dates(){
    $user = Auth::user();
    return $user->hasPermissionTo('contract.update'); 
} 
function change_contract_rate(){
    $user = Auth::user();
    return $user->hasPermissionTo('contract.update'); 
}  
function change_invoice_recepients(){
    $user = Auth::user();
    return $user->hasPermissionTo('amount_paid.update'); 
}
function copy_a_contract(){
    $user = Auth::user();
    return $user->hasPermissionTo('contract.read'); 
}
function run_hospitalReport(){ 
    $user = Auth::user();
    $user->hasAnyPermission(['hospital.create', 'hospital.read', 'hospital.delete', 'hospital.update']);
}
function run_payment_statusReport(){ 
    $user = Auth::user();
    $user->hasAnyPermission(['amount_paid.create', 'amount_paid.read', 'amount_paid.update', 'amount_paid.delete']);
}
function submit_payment(){ 
    $user = Auth::user();
    $user->hasAnyPermission(['amount_paid.create', 'amount_paid.read', 'amount_paid.delete','amount_paid.update']);
}
function run_active_contractsReport(){ 
    $user = Auth::user();
    $user->hasAnyPermission(['contract.create' , 'contract.read' , 'contract.update', 'contract.delete']);
}
function run_payment_summaryReport(){  
    $user = Auth::user();
    $user->hasAnyPermission(['amount_paid.create', 'amount_paid.read', 'amount_paid.update','amount_paid.delete']);
}