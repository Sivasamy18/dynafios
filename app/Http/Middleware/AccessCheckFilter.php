<?php namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\RedirectResponse;
use App\Physician;
use App\Group;
use App\User;
use App\Agreement;
use App\Hospital;
use Illuminate\Support\Facades\DB;
use function App\Start\is_super_user;
use function App\Start\is_super_hospital_user;
use function App\Start\is_hospital_owner;
use Illuminate\Support\Facades\Auth;


class AccessCheckFilter
{
    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * Create a new filter instance.
     *
     * @param Guard $auth
     * @return void
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next, $role, $permission = null)
    {
        $getUrlRout_string = $request->route()->getActionName();
        $getUrlRout_arr = explode('Controllers', $getUrlRout_string);
        $getUrlRout = substr($getUrlRout_arr[1], 1);
        if ($request->route("id") !== null) {
            $id = $request->route("id");
        } else {
            $id = 0;
        }
        //Start-For all url to verify group name
        $group_parameter_array = explode("|", $role);
        $groupParamArray = [];
        foreach ($group_parameter_array as $pkey => $pval) {
            $group_id = $pval;
            $id = Auth::user();
            $user = User::find($id);
            if ($user->hasRole('super_user')) {
                return $next($request);
            }
        }

        if (!$this->hasRoleCustom($group_parameter_array)) {
            return view("errors.401");
        }

        if (Auth::user()->group_id == GROUP::HOSPITAL_ADMIN) {
            if ($getUrlRout == 'ContractsController@interfaceDetails'
                || $getUrlRout == 'ContractsController@getUnapproveLogs'
                || $getUrlRout == 'ContractsController@getCopyContract') {
                return view("errors.401");
            }
        }
        if ($getUrlRout == 'ContractsController@show') {
            if ((Auth::user()->group_id == GROUP::SUPER_HOSPITAL_USER)
                || (Auth::user()->group_id == GROUP::HOSPITAL_ADMIN)
                || (Auth::user()->group_id == GROUP::PRACTICE_MANAGER)
                || (Auth::user()->group_id == GROUP::HEALTH_SYSTEM_USER)) {
                return view("errors.401");
            }
        }

        return $next($request);
    }

    function hasRoleCustom($groupArray)
    {
        if (isset($groupArray) && count($groupArray) > 0) {
            if (in_array(Auth::user()->group_id, $groupArray)) {
                return true;
            } else {
                return false;
            }
        }
    }
}
