@extends('layouts/email')
@section('body')
    <p><strong>FAILURE</strong></p>
    <p>
        The following files failed to interface to Lawson for {{ $hosp_name }}:
    </p>
    <p>
        @foreach ($filenames as $filename)
        <tr>
            <td style="border:1px solid black;border-collapse: collapse;padding: 7px;">{{ $filename }}</td>
        </tr>
        @endforeach
    </p>
    <p>
        See attached for a log of the FTP or SFTP session.
    </p>
@endsection