<?php

namespace App\Policies;

use App\Hospital;
use Illuminate\Support\Facades\Auth;
use App\Agreement;
use App\Contract;
use App\Physician;
use App\Practice;
use App\User;
use App\Group;
use Illuminate\Auth\Access\HandlesAuthorization;
use function App\Start\is_super_hospital_user;
use function App\Start\is_super_user;
use function App\Start\is_hospital_user_healthSystem_user;


class ContractPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param \App\User $user
     * @param \App\Models\Contract $contract
     * @return \Illuminate\Auth\Access\Response|bool
     */

    private function hasUserAccess(Contract $contract, User $user)
    {
        $id = Auth::user();
        $user = User::find($id);
        if ($user->hasRole('super_user')) {
            return true;
        }
        $userStatus = false;
        $contractApprovers = $contract->getContractApprovers($contract);
        if ($contractApprovers->contains('user_id', $user->id)) {
            $userStatus = true;
        }
        return $userStatus;
    }

    public function view(User $user, Contract $contract)
    {
	    return $this->hasUserAccess($contract, $user);
    }

    public function viewContract(User $user, Contract $contract, Practice $practice = NULL)
    {
	    return $this->hasUserAccess($contract, $user);
    }

    public function viewAgreementsContract(User $user, Agreement $agreement, Contract $contract)
    {
        return  $this->hasUserAccess($contract, $user);
    }

    public function viewAuditHistory(User $user, Contract $contract)
    {
	    return $this->hasUserAccess($contract, $user);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param \App\User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function create(User $user, Physician $physician, Practice $practice)
    {
	    return $physician->hospitals()->get();
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param \App\User $user
     * @param \App\Models\Contract $contract
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Contract $contract)
    {
        if (!is_super_user() && !is_super_hospital_user() && !is_hospital_user_healthSystem_user()) {
            return false;
        }
	
	    return $this->hasUserAccess($contract, $user);
    }

    public function viewArchive(User $user, Contract $contract)
    {
        if (!is_super_user() && !is_super_hospital_user() && !is_hospital_user_healthSystem_user()) {
            return false;
        }
	    return $this->hasUserAccess($contract, $user);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param \App\User $user
     * @param \App\Models\Contract $contract
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Contract $contract, Practice $practice)
    {
        if (!is_super_user() && !is_super_hospital_user() && !is_hospital_user_healthSystem_user()) {
	        return false;
        }
	    return $this->hasUserAccess($contract, $user);
    }

    public function displayContract(User $user, Contract $contract, Practice $practice)
    {
        if (!is_super_user() && !is_super_hospital_user() && !is_hospital_user_healthSystem_user()) {
            return false;
        }
	    return $this->hasUserAccess($contract, $user);
    }


}
