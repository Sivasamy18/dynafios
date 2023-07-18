@extends('layouts/email')
@section('body')

    <p>Dear Dr. {{ $logArray[0]['name'] }},</p>
    <p>
        Your time logs associated with {{ $logArray[0]['contract_name'] }} have been rejected. All entries have been
        unapproved, please edit logs & reapprove.
    </p>

	<p>Rejected By - {{ $logArray[0]['rejected_user_name'] }}</p>

    <table>
        <tr>
            <th style="border:1px solid black;border-collapse: collapse;padding: 7px;">Contract Name</th>
            <th style="border:1px solid black;border-collapse: collapse;padding: 7px;">Date</th>
            <th style="border:1px solid black;border-collapse: collapse;padding: 7px;">Activity</th>
            <th style="border:1px solid black;border-collapse: collapse;padding: 7px;">Duration</th>
            <th style="border:1px solid black;border-collapse: collapse;padding: 7px;">Details</th>
			<th style="border:1px solid black;border-collapse: collapse;padding: 7px;">Reason</th>
        </tr>
        @foreach ($logArray as $log)
        <tr>
            <td style="border:1px solid black;border-collapse: collapse;padding: 7px;">{{ $log['contract_name'] }}</td>
            <td style="border:1px solid black;border-collapse: collapse;padding: 7px;">{{ $log['log_date'] }}</td>
            <td style="border:1px solid black;border-collapse: collapse;padding: 7px;">{{ $log['action'] }}</td>
            <td style="border:1px solid black;border-collapse: collapse;padding: 7px;">{{ $log['duration'] }}</td>
            <td style="border:1px solid black;border-collapse: collapse;padding: 7px;">{{ $log['details'] }}</td>
			<td style="border:1px solid black;border-collapse: collapse;padding: 7px;">{{ $log['reason'] }}</td>
        </tr>
        @endforeach
    </table>

    <p>
        <small>Thanks,<br/>The Dynafios APP Support Team<br/>{{ HTML::mailto('support@dynafiosapp.com') }}</small>
    </p>

    @endsection