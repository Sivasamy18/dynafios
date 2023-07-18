<?php

namespace App\Http\Controllers\Admin;

use App\Contract;
use App\Http\Controllers\Controller;
use App\Models\MailTracker;
use Illuminate\Http\Request;
use App\Models\AmountPaidPhysician;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class AdminDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'dynafios-staff']);
    }

    public function index(Request $request)
    {
        return view('admin.dashboard.index');
    }
}
