@extends('layouts/email')
@section('body')
    <p><b>Re-Submit Logs</b></p>
    @if($type==='physician')
    <p>Dear {{ $name }},</p>
    @else
    <p>Dear {{ $name }},</p>
    @endif

    <p>
        <!-- Your logs have been rejected by the {{$manager}} . Please review and re-submit the logs. -->
        Your logs have been rejected. Please review and re-submit the logs.
    </p>
    <p>
        Click <a href="https://dynafiosapp.com">here</a> or cut and paste the following URL into your browser’s navigation bar: https://dynafiosapp.com
        to re-submit any logs and/or days on call.
    </p>
	
	<p>Rejection User : {{ $manager_name }},</p>
	
	<table>
        <tr>
            <th style="border:1px solid black;border-collapse: collapse;padding: 7px;">Contract Name</th>
            <th style="border:1px solid black;border-collapse: collapse;padding: 7px;">Date</th> 
            <th style="border:1px solid black;border-collapse: collapse;padding: 7px;">Activity</th>
            <th style="border:1px solid black;border-collapse: collapse;padding: 7px;">Duration</th>
            <th style="border:1px solid black;border-collapse: collapse;padding: 7px;">Details</th>
			<th style="border:1px solid black;border-collapse: collapse;padding: 7px;">Reason</th>
        </tr>
    @foreach ($log_details as $log_detail)
        <tr>
            <td style="border:1px solid black;border-collapse: collapse;padding: 7px;">{{ $log_detail['contract_name'] }}</td>
            <td style="border:1px solid black;border-collapse: collapse;padding: 7px;">{{ $log_detail['log_date'] }}</td> 
            <td style="border:1px solid black;border-collapse: collapse;padding: 7px;">{{ $log_detail['activity'] }}</td>
            <td style="border:1px solid black;border-collapse: collapse;padding: 7px;">{{ $log_detail['duration'] }}</td>
            <td style="border:1px solid black;border-collapse: collapse;padding: 7px;">{{ $log_detail['details'] }}</td>
			<td style="border:1px solid black;border-collapse: collapse;padding: 7px;">{{ $log_detail['reason'] }}</td>
        </tr>
    @endforeach
    </table>
	
    <p>
        <small>Thanks,<br/>The Dynafios APP Support Team</small>
    </p>
    @endsection