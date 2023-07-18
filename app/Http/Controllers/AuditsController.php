<?php

namespace App\Http\Controllers;

use OwenIt\Auditing\Models\Audit;

class AuditsController extends Controller
{
    public function index()
    {
        $audits = Audit::paginate(100);
        return view('audits.index', compact('audits'));
    }
}
