@extends('layouts/email')
@section('body')
<table width="100%" cellpadding="0">
    <tr>
        <td>
            <p>This is an automated message to notify the DYNAFIOS development team that an unhandled exception has been caught.</p>
        </td>
    </tr>
    <tr><td><strong>Exception Details:</strong></td></tr>
    <tr>
        <td>
            <table width="100%" cellpadding="0" style="border: 1px solid #d9d9d9">
                <tr valign="top">
                    <td width="100"><strong>User:</strong></td>
                    <td>{!! "{$user_email} ({$user_first_name} {$user_last_name})" !!}</td>
                </tr>
                <tr valign="top">
                    <td width="100"><strong>URL:</strong></td>
                    <td><a href="{{ $path }}">{{ $path }}</a></td>
                </tr>
                <tr valign="top">
                    <td width="100"><strong>HTTP Error:</strong></td>
                    <td>{{ $error_code }}</a></td>
                </tr>
                <tr valign="top">
                    <td width="100"><strong>User input:</strong></td>
                    <td>{!! $input !!}</td>
                </tr>
                <tr valign="top">
                    <td width="100"><strong>Error Message:</strong></td>
                    <td>{!! $error_message !!}</td>
                </tr>
                <tr valign="top">
                    <td width="100"><strong>Exception:</strong></td>
                    <td>{!! $exception_class !!}</td>
                </tr>
                <tr valign="top">
                    <td width="100"><strong>Stack Trace:</strong></td>
                    <td><pre style="width: 500px; height: 200px; overflow-y: scroll;margin:0; padding:0;">{{ $exception_stack_trace }}</pre></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
@endsection