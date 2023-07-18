<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\PaymentStatusDashboard;
use App\Hospital;
use App\Agreement;
use App\Contract;
use App\customClasses\PaymentFrequencyFactoryClass;
use App\PhysicianContracts;
use Illuminate\Support\Facades\Log;

class UpdatePaymentStatusDashboardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $hospitals = Hospital::where('archived','=',0)->whereNull('deleted_at')->distinct()->get();

        if($hospitals){
            foreach($hospitals as $hospital){
                $agreements = Agreement::where('hospital_id', '=', $hospital->id)->get();
                if($agreements){
                    foreach($agreements as $agreement){
                        $contracts = Contract::where('agreement_id',$agreement->id)->withTrashed()  // ->get();
                        ->chunk(50, function ($contracts) use ($agreement){
                            foreach($contracts as $contract){
                                try{
                                    // Get the frequency type for the agreement and then get the periods for that frequency type.
                                    $payment_frequency_type_factory = new PaymentFrequencyFactoryClass();
                                    $payment_frequency_type_obj = $payment_frequency_type_factory->getPaymentFactoryClass($agreement->payment_frequency_type);
                                    $res_pay_frequency = $payment_frequency_type_obj->calculateDateRange($agreement);
                                    $period_range = $res_pay_frequency['date_range_with_start_end_date'];

                                    foreach($period_range as $period){
                                        $period_min = $period['start_date'];
                                        $period_max = $period['end_date'];

                                        $physician_contracts = PhysicianContracts::select('physician_id', 'practice_id')
                                            ->where('contract_id', '=', $contract->id)->get();

                                        foreach($physician_contracts as $physician_contract){
                                            PaymentStatusDashboard::UpdatePaymentStatusDashboard($physician_contract->physician_id, $physician_contract->practice_id, $contract->id, $contract->contract_name_id, $agreement->hospital_id, $contract->agreement_id, $period_min);
                                        }
                                    }
                                }catch(\Exception $e){
                                    Log::error('Seed ERROR! '.$e->getMessage());
                                }
                            }
                        });
                    }
                }
            }
        }
    }
}
