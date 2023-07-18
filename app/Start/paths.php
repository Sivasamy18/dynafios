<?php

namespace App\Start;

use Illuminate\Support\Facades\Auth;

function admin_report_path($report = null)
{
    return storage_path() . "/reports/admin" . ($report ? ("/" . $report->filename) : '');
}

function hospital_report_path($hospital, $report = null)
{
    return storage_path() . "/reports/hospitals/" . $hospital->npi . ($report ? ("/" . $report->filename) : '');
}

function practice_report_path($practice, $report = null)
{
    return storage_path() . "/reports/practices/" . $practice->npi . ($report ? ("/" . $report->filename) : '');
}

function physician_report_path($physician, $report = null)
{
    return storage_path() . "/reports/physicians/" . $physician->npi . ($report ? ("/" . $report->filename) : '');
}

function contract_document_path($contract_document = null)
{
    return storage_path() . "/contract_copies/" . $contract_document;
}

function approval_report_path($report = null)
{

    return storage_path() . "/reports/hospitals/approval/" . Auth::user()->id . ($report ? ("/" . $report->filename) : '');
}

function payment_status_report_path($report = null)
{

    return storage_path() . "/reports/hospitals/payment_status/" . Auth::user()->id . ($report ? ("/" . $report->filename) : '');
}

function health_system_report_path($report = null)
{

    return storage_path() . "/reports/health_system/" . ($report ? ("/" . $report->filename) : '');
}

function complience_report_path($report = null)
{

    return storage_path() . "/reports/compliance/" . ($report ? ("/" . $report->filename) : '');
}

function performance_report_path($report = null)
{

    return storage_path() . "/reports/performance/" . ($report ? ("/" . $report->filename) : '');
}

function attestation_report_path($report = null)
{

    return storage_path() . "/reports/attestation/" . ($report ? ("/" . $report->filename) : '');
}

function payment_status_dashboard_report_path($report = null, $user_id) {
    return storage_path()."/reports/hospitals/payment_status/". $user_id .($report ? ("/".$report->filename) : '');
}
