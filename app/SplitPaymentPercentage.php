<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Request;
use Redirect;
use Lang;
use Log;

class SplitPaymentPercentage extends Model
{
    protected $table = 'split_payment_percentage';

    public static function postPaymentManagement($id, $pid = 0, $physician_id)
    {
        $practice = Practice::findOrFail($pid);
        $hospital_id = $practice->hospital_id;
        $contract_id = $id;

        SplitPaymentPercentage::where("contract_id", '=', $contract_id)
            ->where("hospital_id", '=', $hospital_id)
            ->where("is_active", '=', true)
            ->update(["is_active" => false]);

        if (Request::input('split_payment_count') > 0) {
            for ($i = 1; $i <= Request::input('split_payment_count'); $i++) {
                if (Request::input("payment_" . $i) != '') {
                    $payment_percentage = Request::input("payment_" . $i);
                    $payment_note_1 = Request::input("payment_note_" . $i . "_1");
                    $payment_note_2 = Request::input("payment_note_" . $i . "_2");
                    $payment_note_3 = Request::input("payment_note_" . $i . "_3");
                    $payment_note_4 = Request::input("payment_note_" . $i . "_4");

                    $SplitPaymentPercentage = new SplitPaymentPercentage();
                    $SplitPaymentPercentage->contract_id = $contract_id;
                    $SplitPaymentPercentage->hospital_id = $hospital_id;
                    $SplitPaymentPercentage->payment_percentage_index = $i;
                    $SplitPaymentPercentage->payment_percentage = $payment_percentage;
                    $SplitPaymentPercentage->payment_note_1 = $payment_note_1;
                    $SplitPaymentPercentage->payment_note_2 = $payment_note_2;
                    $SplitPaymentPercentage->payment_note_3 = $payment_note_3;
                    $SplitPaymentPercentage->payment_note_4 = $payment_note_4;
                    $SplitPaymentPercentage->start_date = now();
                    $SplitPaymentPercentage->is_active = true;
                    $SplitPaymentPercentage->save();
                }
            }
        }
        return Redirect::route('contracts.paymentmanagement', [$contract_id, $practice->id, $physician_id])->with([
            'success' => Lang::get('contracts.split_payment_success')
        ]);
    }

    public static function getSplitPayment($contract_id, $hospital_id, $amountpaidID)
    {
        $results = self::select('split_payment_percentage.payment_note_1', 'split_payment_percentage.payment_note_2',
            'split_payment_percentage.payment_note_3', 'split_payment_percentage.payment_note_4', 'split_payment_percentage.payment_percentage',
            'amount_paid_with_percentage.amount')
            ->join('amount_paid', 'amount_paid.contract_id', '=', 'split_payment_percentage.contract_id')
            ->join('amount_paid_with_percentage', function ($join) {
                $join->on('amount_paid_with_percentage.amount_paid_id', '=', 'amount_paid.id')
                    ->On('amount_paid_with_percentage.index', '=', 'split_payment_percentage.payment_percentage_index');
            })
            ->where('split_payment_percentage.contract_id', '=', $contract_id)
            ->where('split_payment_percentage.hospital_id', '=', $hospital_id)
            ->where('split_payment_percentage.is_active', '=', true)
            ->where('amount_paid_with_percentage.amount_paid_id', '=', $amountpaidID)
            ->groupBy('split_payment_percentage.id')
            ->get();
        return $results;
    }

    public static function updateSplitPayment()
    {
        $hospitals = Hospital::select('hospitals.id')
            ->whereNull("hospitals.deleted_at")
            ->distinct()
            ->get();

        if (count($hospitals) > 0) {
            foreach ($hospitals as $hospital) {
                $contracts = Contract::select('contracts.*')
                    ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                    ->where('agreements.hospital_id', '=', $hospital->id)
                    ->distinct()
                    ->get();

                foreach ($contracts as $contract) {
                    SplitPaymentPercentage::where("contract_id", '=', $contract->id)
                        ->where("hospital_id", '=', $hospital->id)
                        ->where("is_active", '=', true)
                        ->update(["is_active" => false]);

                    $SplitPaymentPercentage = new SplitPaymentPercentage();
                    $SplitPaymentPercentage->contract_id = $contract->id;
                    $SplitPaymentPercentage->hospital_id = $hospital->id;
                    $SplitPaymentPercentage->payment_percentage_index = 1;
                    $SplitPaymentPercentage->payment_percentage = 100.00;
                    $SplitPaymentPercentage->payment_note_1 = "";
                    $SplitPaymentPercentage->payment_note_2 = "";
                    $SplitPaymentPercentage->payment_note_3 = "";
                    $SplitPaymentPercentage->payment_note_4 = "";
                    $SplitPaymentPercentage->start_date = now();
                    $SplitPaymentPercentage->is_active = true;
                    $SplitPaymentPercentage->save();
                }
            }
        } else {
            log::info('Hospitals not found.');
        }
        return 1;
    }
}
