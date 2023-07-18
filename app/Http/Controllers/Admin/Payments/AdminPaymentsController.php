<?php

namespace App\Http\Controllers\Admin\Payments;

use App\Contract;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AmountPaidPhysician;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class AdminPaymentsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'dynafios-staff']);
    }

    public function index(Request $request)
    {
        $query = AmountPaidPhysician::with('physician', 'contract.contractName')
            ->orderBy('start_date', 'desc');

        if ($request->filled('start_date')) {
            $query->where('start_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('end_date', '<=', $request->end_date);
        }

        if ($request->filled('contract_name')) {
            $query->whereHas('contract.contractName', function($q) use($request) {
                $q->where('name', 'LIKE', '%' . $request->contract_name . '%');
            });
        }

        if ($request->filled('physician_name')) {
            $query->whereHas('physician', function($q) use($request) {
                $q->where('first_name', 'LIKE', '%' . $request->physician_name . '%');
            })->orWhereHas('physician', function($q) use($request) {
                $q->where('last_name', 'LIKE', '%' . $request->physician_name . '%');
            });
        }

        if ($request->filled('amount')) {
            $query->where('amt_paid', 'LIKE', '%' . $request->amount . '%');
        }

        $payments = $query->paginate(100)->appends([
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'contract_name' => $request->contract_name,
        ]);


        return view('admin.payments.index', compact('payments'));
    }

    public function deletePayment(Request $request, AmountPaidPhysician $payment): \Illuminate\Http\JsonResponse
    {
        $amountPaidRemoved = false;
        $completed = false;
        try {
            DB::beginTransaction();

            if($payment->amountPaid) {
                $amountPaidRemoved = $payment->amountPaid->delete();
            }

            if($amountPaidRemoved) {
               $completed = $payment->delete();
            }

            if($completed){
                DB::commit();
            } else {
                DB::rollBack();
                return Response::json([
                    'status' => 400,
                    'message' => 'Unable to find all required models, database transaction aborted.'
                ]);
            }

            return Response::json([
                'status' => 200,
                'message' => 'Payment deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return Response::json([
                'status' => 400,
                'message' => 'Error deleting payment.'
            ]);
        }
    }
}
