<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InterfaceTankLawson extends Model
{

    protected $table = 'interface_tank_lawson';

    public static function getInterfaceDates($hospital_id)
    {
        $interfaceDates = self::select('date_sent as date_sent', 'hospital_id as hospital_id')
            ->where('hospital_id', '=', $hospital_id)
            ->where('date_sent', '!=', '0000-00-00 00:00:00')
            ->orderBy('date_sent')
            ->distinct()
            ->pluck("date_sent", "date_sent");
        return $interfaceDates;
    }

    public static function getReportData($hospital, $interface_date)
    {
        $tank_records = self::select('interface_tank_lawson.date_sent', 'practices.name as practice_name', 'physicians.last_name', 'physicians.first_name', 'agreements.name as agreement_name', 'contract_names.name as contract_name', 'amount_paid.start_date', 'amount_paid.end_date', 'amount_paid.amountPaid', 'physician_interface_lawson_apcinvoice.cvi_vendor', 'physician_interface_lawson_apcinvoice.cvi_company', 'physician_interface_lawson_apcinvoice.cvi_auth_code', 'physician_interface_lawson_apcinvoice.cvi_proc_level', 'contract_interface_lawson_apcdistrib.cvd_dist_company', 'contract_interface_lawson_apcdistrib.cvd_dis_acct_unit', 'contract_interface_lawson_apcdistrib.cvd_dis_account', 'contract_interface_lawson_apcdistrib.cvd_dis_sub_acct', 'interface_tank_lawson.id', 'amount_paid.id as amount_paid_id', 'contract_interface_lawson_apcdistrib.invoice_number_suffix as invoice_number_suffix')
            ->join('amount_paid', 'amount_paid.id', '=', 'interface_tank_lawson.amount_paid_id')
            ->join('physicians', 'physicians.id', '=', 'amount_paid.physician_id')
            ->join('practices', 'practices.id', '=', 'amount_paid.practice_id')
            ->join('contracts', 'contracts.id', '=', 'amount_paid.contract_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('physician_interface_lawson_apcinvoice', 'physician_interface_lawson_apcinvoice.id', '=', 'interface_tank_lawson.physician_interface_lawson_apcinvoice_id')
            ->join('contract_interface_lawson_apcdistrib', 'contract_interface_lawson_apcdistrib.id', '=', 'interface_tank_lawson.contract_interface_lawson_apcdistrib_id')
            ->where('date_sent', '=', $interface_date)
            ->orderBy('date_sent')
            ->orderBy("practices.name")
            ->orderBy("physicians.last_name")
            ->orderBy("physicians.first_name")
            ->orderBy("agreements.name")
            ->orderBy("contract_names.name")
            ->get();
        foreach ($tank_records as $key => $tank_record) {
            if ($tank_record->invoice_number_suffix == "") {
                $tank_record['invoice_number'] = date('M', strtotime($tank_record->start_date)) . substr(date('Y', strtotime($tank_record->start_date)), 2, 2) . '-' . 'DYNAFIOS';
            } else {
                $tank_record['invoice_number'] = date('M', strtotime($tank_record->start_date)) . substr(date('Y', strtotime($tank_record->start_date)), 2, 2) . '-' . $tank_record->invoice_number_suffix;
            }
        }
        return $tank_records;
    }

}
