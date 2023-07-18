@extends('layouts/email')
@section('body')

    <p>Dear {{ $approver_name }},</p>
    <p>
        You have contracts ready for approval for the following provider(s):
    </p>
	
	<table>
        <tr>
            <th style="border:1px solid black;border-collapse: collapse;padding: 7px;">Provider Name</th>
            <th style="border:1px solid black;border-collapse: collapse;padding: 7px;">Contract Name</th> 
            <th style="border:1px solid black;border-collapse: collapse;padding: 7px;">Period(s)</th>
        </tr>
    @foreach ($approver_pending_logs as $approver_pending_log)
        <tr>
            <td style="border:1px solid black;border-collapse: collapse;padding: 7px;">{{ $approver_pending_log['physician_name'] }}</td>
            <td style="border:1px solid black;border-collapse: collapse;padding: 7px;">{{ $approver_pending_log['contract_name'] }}</td> 
            <td style="border:1px solid black;border-collapse: collapse;padding: 7px;">{{ $approver_pending_log['period'] }}</td>
        </tr>
    @endforeach
    </table>
	
	<p>
		Click <a href="https://dynafiosapp.com/getLogsForApproval">here</a> or cut and paste the following URL into your browser’s navigation bar: https://dynafiosapp.com/getLogsForApproval
        to go to the DYNAFIOS dashboard to review and approve logs.
    </p>

    <p>
        <small>Thanks,<br/>The DYNAFIOS Support Team<br/>{{ HTML::mailto('support@dynafiosapp.com') }}</small>
    </p>

@endsection