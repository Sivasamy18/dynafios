@extends('layouts/email')
@section('body')
    <table width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #c8c8c8; padding: 40px 20px;">
        <tr>
            <td style="border-bottom: 1px solid #000; padding: 0 0 40px 0; ">
                <h1 style="color: #000; font-size:18px;">The Dynafios APP Provider Check Request</h1>

                <p style="font-size: 12px">
                    Please find below your pre-defined check request for your provider contracts in the Dynafios APP;
                    This is an easy way to see all your monthly payment details.
                </p>

                <p style="font-size: 12px">
                    In addition to this email, you can locate all of your physician check requests through
                    your Dynafios APP dashboard.
                </p>
            </td>
        </tr>
        <tr>
            <td>
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr valign="top">
                        <td>
                            <img src="{{ asset('assets/img/email/invoice.png') }}" alt="Hospital Logo"/>
                        </td>
                        <td style="text-align: right; font-size: 14px;">
                            <b>ACCOUNTS PAYABLE</b><br>
                            Check Request
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="font-size: 14px;">
                            <b>{{ $report_data->hospital_name }}</b><br>
                            {{ $report_data->hospital_address1 }}<br>
                            {{ $report_data->hospital_address2 }}
                        </td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr style="font-size: 14px;">
                        <td>&nbsp;</td>
                        <td style="text-align: right;">{{ "Run Date: {$report_data->run_date}" }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 20px 0 0 0">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr style="color: #fff; background: #1758dd; text-align: left;">
                        <th style="padding: 4px 0; font-size: 14px; width: 25%; text-align:left;">Practice (Manager)
                        </th>
                        <th style="padding: 4px 0; font-size: 14px; width: 15%; text-align:left;">Date</th>
                        <th style="padding: 4px 0; font-size: 14px; width: 25%; text-align:left;">Physician</th>
                        <th style="padding: 4px 0; font-size: 14px; width: 10%; text-align:right;">Hours</th>
                        <th style="padding: 4px 0; font-size: 14px; width: 10%; text-align:right;">Payment</th>
                    </tr>
                    @foreach (array_reverse($report_data->practices) as $practice)
                    <tr style="background: #f2f2f2;">
                        <td style="font-size: 14px">{{ $practice->name }}</td>
                        <td style="font-size: 14px">{{ $practice->date_range }}</td>
                        <td style="font-size: 14px">{{ $practice->contract_name }}</td>
                        <td style="font-size: 14px; text-align: right;">{{ formatNumber($practice->worked_hours) }}</td>
                        <td style="font-size: 14px; text-align: right;">{{ formatCurrency($practice->amount) }}</td>
                    </tr>
                    @foreach (array_reverse($practice->physicians) as $physician)
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>{{ $physician->name }}</td>
                        <td style="font-size: 14px; text-align: right;">{{ formatNumber($physician->worked_hours) }}
                        </td>
                        <td style="font-size: 14px; text-align: right;">{{ formatCurrency($physician->amount) }}</td>
                    </tr>
                    @endforeach
                    @endforeach
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table style="width: 100%">
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr style="font-size: 14px;">
                        <td>{{ $report_data->stipend }}</td>
                        <td style="text-align: right">{{ formatCurrency($report_data->grand_total) }}</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Approved By: ________________________________</td>
                        <td style="text-align: right">Date: _______________</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 10px 20px; font-size: 12px; border-top: 1px solid #000;">
                <a style="color: #0e53e0; text-decoration: none" href="{{ route('tickets.index')}}">Help Center</a>
                <span style="color: #0e53e0">|</span>
                <a style="color: #0e53e0; text-decoration: none" href="{{ route('dashboard.index') }}">The Dynafios APP
                    Dashboard</a>
            </td>
        </tr>
        <tr>
            <td style="color: #3b3b3b; padding: 0 20px; font-size: 12px;">
                <p>
                    This email was sent by an automated system, please do not reply to this email address.
                    If you would like to contact us, please log in to your account and submit a ticket through
                    the "Help Center" or you can send an email to <a href="mailto:support@dynafiosapp.com">support@dynafiosapp.com</a>.
                </p>
            </td>
        </tr>
        <tr>
            <td style="color: #3b3b3b; padding: 0 20px; font-size: 12px;">
                <p>Copyright &copy; {{ date('Y') }} The Dynafios APP All rights reserved.
            </td>
        </tr>
    </table>
@endsection