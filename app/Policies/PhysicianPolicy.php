<?php

namespace App\Policies;

use App\Group;
use App\Hospital;
use Auth;
use App\Contract;
use App\physician;
use App\Practice;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\App;
use function App\Start\is_physician_owner;
use function App\Start\is_practice_manager;
use function App\Start\is_practice_owner;
use function App\Policies\ContractPolicy\create;

class PhysicianPolicy
{
    use HandlesAuthorization;

    protected $contractPolicy;

    public function __construct(ContractPolicy $contractPolicy)
    {
        $this->contractPolicy = $contractPolicy;
    }

    public function viewContractByPhysician(User $user, Physician $physician, Practice $practice = null)
    {
	    return $this->hasUserAccess($physician, $practice);
    }

    private function hasUserAccess($physician, $practice = null)
    {
	    $returnValue = false;
        if ((Auth::user()->group_id === GROUP::SUPER_USER)) {
            return true;
        }
	    if (!is_physician_owner($physician->id) && !is_practice_manager()) {
		    $returnValue = false;
	    }else{
		    $returnValue = true;
	    }
        return $returnValue;
    }

    public function createContract(User $user, Physician $physician, Practice $practice)
    {
        return  $this->hasUserAccess($physician, $practice);
    }

    public function hasRole(...$roles)
    {
        $group_param_array = explode("|", $roles[0]);
        $currentUserId = Auth::user()->id;
        $currentUser = User::find($currentUserId);
        $userGroup_id = $currentUser->group_id;
        foreach ($group_param_array as $role) {
            if ($userGroup_id === $role) {
                return true;
            }
        }
        return false;
    }

}
