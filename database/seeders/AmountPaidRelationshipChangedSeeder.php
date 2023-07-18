<?php

namespace Database\Seeders;

use App\Contract;
use App\PhysicianContracts;
use Eloquent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AmountPaidRelationshipChangedSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (DB::table('amount_paid_physicians')->count() == 0) {
            DB::table('amount_paid')
                ->orderBy('physician_id', 'asc')
                ->chunk(100, function ($amountsPaid) {
                    foreach ($amountsPaid as $amountPaid) {
                        $check_exist = DB::table('amount_paid_physicians')
                            ->where('amt_paid_id', '=', $amountPaid->id)
                            ->get();

                        if (count($check_exist) == 0) {
                            DB::table('amount_paid_physicians')->insert([
                                'amt_paid_id' => $amountPaid->id,
                                'amt_paid' => $amountPaid->amountPaid,
                                'physician_id' => $amountPaid->physician_id,
                                'contract_id' => $amountPaid->contract_id,
                                'start_date' => $amountPaid->start_date,
                                'end_date' => $amountPaid->end_date,
                                'created_by' => 0,
                                'updated_by' => 0,
                                'created_at' => $amountPaid->created_at,
                                'updated_at' => $amountPaid->updated_at
                            ]);
                        }
                    }
                });
        }
    }
}
