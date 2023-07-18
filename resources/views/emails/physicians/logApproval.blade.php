@extends('layouts/email')
@section('body')

    <p>Dear Dr. {{ $logArray[0]['physician_first_name'] }} {{ $logArray[0]['physician_last_name'] }},</p>
    <p>
        Thank you for approving the following logs; they have been successfully submitted to the Hospital.
    </p>

    <table>
        <tr>
            <th style="border:1px solid black;border-collapse: collapse;padding: 7px;">Contract Name</th>
            <th style="border:1px solid black;border-collapse: collapse;padding: 7px;">Date</th>
            <th style="border:1px solid black;border-collapse: collapse;padding: 7px;">Activity</th>
            <th style="border:1px solid black;border-collapse: collapse;padding: 7px;">Duration</th>
            <th style="border:1px solid black;border-collapse: collapse;padding: 7px;">Details</th>
        </tr>
        @foreach ($logArray as $log)
        <tr>
            <td style="border:1px solid black;border-collapse: collapse;padding: 7px;">{{ $log['contract_name'] }}</td>
            <td style="border:1px solid black;border-collapse: collapse;padding: 7px;">{{ $log['log_date'] }}</td>
            <td style="border:1px solid black;border-collapse: collapse;padding: 7px;">{{ $log['action'] }}</td>
            <td style="border:1px solid black;border-collapse: collapse;padding: 7px;">{{ $log['duration'] }}</td>
            <td style="border:1px solid black;border-collapse: collapse;padding: 7px;">{{ $log['details'] }}</td>
        </tr>
        @endforeach
    </table>

    <p>
        <small>Thanks,<br/>The Dynafios APP Support Team<br/>{{ HTML::mailto('support@dynafiosapp.com') }}</small>
    </p>

    @endsection