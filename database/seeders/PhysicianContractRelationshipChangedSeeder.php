<?php

namespace Database\Seeders;

use App\Contract;
use App\PhysicianContracts;
use Illuminate\Database\Seeder;

class PhysicianContractRelationshipChangedSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (PhysicianContracts::count() == 0) {
            Contract::select('contracts.*')
                ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
                ->where('contracts.physician_id', '!=', 0)
                ->withTrashed()
                ->distinct()
                ->orderBy('id', 'asc')
                ->chunk(100, function ($contracts) {
                    if (count($contracts) > 0) {
                        foreach ($contracts as $contract) {
                            $check_exist = PhysicianContracts::where('contract_id', '=', $contract->id)
                                ->where('physician_id', '=', $contract->physician_id)
                                ->where('practice_id', '=', $contract->practice_id)
                                ->get();

                            if (count($check_exist) == 0) {
                                $physician_contracts = new PhysicianContracts();
                                $physician_contracts->physician_id = $contract->physician_id;
                                $physician_contracts->contract_id = $contract->id;
                                $physician_contracts->practice_id = $contract->practice_id;
                                $physician_contracts->created_by = 0;
                                $physician_contracts->updated_by = 0;
                                $physician_contracts->created_at = $contract->created_at;
                                $physician_contracts->updated_at = $contract->updated_at;
                                $physician_contracts->deleted_at = $contract->deleted_at;
                                $physician_contracts->save();
                            }
                        }
                    }
                });
        }
    }
}
