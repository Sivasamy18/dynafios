<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailTracker;
use Illuminate\Http\Request;

class MailTrackerController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'dynafios-staff']);
    }

    public function index(Request $request)
    {
        $query = MailTracker::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', $request->end_date);
        }

        if ($request->filled('sent_to')) {
            $query->where('sent_to', 'LIKE', '%' . $request->sent_to . '%');
        }

        $mailTrackers = $query->paginate(100)->appends([
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);


        return view('admin.emails.index', compact('mailTrackers'));
    }
}
