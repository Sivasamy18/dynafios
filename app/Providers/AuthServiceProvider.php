<?php

namespace App\Providers;

use App\Policies\ContractsPolicy;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
        Hospital::class => HospitalPolicy::class,
        User::class => UserPolicy::class,
        Agreement::class => AgreementPolicy::class,
        Physician::class => PhysicianPolicy::class,
        Practice::class => PracticePolicy::class,
        Contract::class => ContractPolicy::class,
    ];

    /**
     * Register any application authentication / authorization services.
     *
     * @param \Illuminate\Contracts\Auth\Access\Gate $gate
     * @return void
     */
    // public function boot(GateContract $gate)
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}