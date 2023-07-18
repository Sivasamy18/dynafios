<?php

namespace App\Http\Controllers;

use App\Agreement;
use App\Amount_paid;
use App\Contract;
use App\ContractInterfaceLawsonApcdistrib;
use App\Hospital;
use App\HospitalInterfaceLawson;
use App\InterfaceTankLawson;
use App\InvoiceNote;
use App\PhysicianInterfaceLawsonApcinvoice;
use App\Services\EmailQueueService;
use App\customClasses\EmailSetup;
use Barryvdh\DomPDF\PDF;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use function App\Start\hospital_report_path;

class InterfaceController extends ResourceController
{
    public function lawsonInterface()
    {
        $id = Request::input('id');
        $interface_tank_lawson = InterfaceTankLawson::where('hospital_id', '=', $id)->where('is_sent', '=', false)->get();
        if (count($interface_tank_lawson) > 0) {
            $hospital_interface_lawson = HospitalInterfaceLawson::where('hospital_id', '=', $id)->first();
            //authorization header check
            if (($hospital_interface_lawson->api_username == Request::header('php-auth-user')) && ($hospital_interface_lawson->api_password == Request::header('php-auth-pw'))) {
                $report_path = hospital_report_path(Hospital::findOrFail($id));
                $report_path = $report_path . '/interfaceLawson';
                if (!file_exists($report_path)) {
                    mkdir($report_path, 0777, true);
                }
                $filenames = [];
                $filenames_short = [];
                $now = date('mdYhis');
                $timestamp = date('Y-m-d H:i:s');
                $apcinvoice_filename_short = $hospital_interface_lawson->apcinvoice_filename . "_" . $now . ".txt";
                $apcdistrib_filename_short = $hospital_interface_lawson->apcdistrib_filename . "_" . $now . ".txt";
                $apcinvoice_filename = $report_path . '/' . $apcinvoice_filename_short;
                $apcdistrib_filename = $report_path . '/' . $apcdistrib_filename_short;
                $script_filename = $report_path . '/' . $hospital_interface_lawson->hospital_id . '_ftp_script' . "_" . $now . ".txt";
                $degug_filename = $report_path . '/' . $hospital_interface_lawson->hospital_id . '_ftp_script_output' . "_" . $now . ".txt";
                array_push($filenames, $apcinvoice_filename, $apcdistrib_filename);
                array_push($filenames_short, $apcinvoice_filename_short, $apcdistrib_filename_short);
                $apcinvoice_file = fopen($apcinvoice_filename, "w");
                $apcdistrib_file = fopen($apcdistrib_filename, "w");

                //header records
                $apcinvoice_header = "COMPANY,VENDOR,EDI-NBR,INVOICE,SUFFIX,BATCH-NUM,VOUCHER-NBR,AUTH-CODE," .
                    "PROC-LEVEL,ACCR-CODE,INVOICE-TYPE,OLD-VENDOR,INV-CURRENCY,INVOICE-DTE," .
                    "TAX-PNT-DATE,PURCH-FR-LOC,PO-NUMBER,PO-RELEASE,PO-CODE,AUTO-MATCH,DESCRIPTION," .
                    "BASE-INV-AMT,TRAN-INV-AMT,TRAN-ALOW-AMT,TRAN-TXBL-AMT,TRAN-TAX-AMT,TRAN-DISC-AMT," .
                    "ORIG-CNV-RATE,ANTICIPATION,DISCOUNT-RT,DISC-DATE,DUE-DATE,NBR-RECUR-PMT," .
                    "RECUR-FREQ,REMIT-TO-CODE,CASH-CODE,SEP-CHK-FLAG,PAY-IMM-FLAG,ENCLOSURE," .
                    "CURR-RECALC,DISC-LOST-FLG,TAX-CODE,INCOME-CODE,HLD-CODE,DIST-CODE,TERM-CODE," .
                    "REC-STATUS,POSTING-STATUS,DISTRIB-DATE,PAY-VENDOR,TRANS-NBR,BANK-INST-CODE," .
                    "CHECK-DATE,INVOICE-GROUP,ONE-TIME-VEND,VENDOR-VNAME,VENDOR-SNAME,ADDR1,ADDR2," .
                    "ADDR3,ADDR4,CITY-ADDR5,STATE-PROV,POSTAL-CODE,COUNTY,COUNTRY,LEGAL-NAME,VEN-INC-CODE," .
                    "TAX-ID,JRNL-BOOK-NBR,BANK-CHK-AMT,BANK-ND,BNK-CNV-RATE,DISCOUNT-CODE,INVOICE-SOURCE," .
                    "INVC-REF-TYPE,APPROVED-FLAG,INC-ACCR-CODE,OPERATOR,INV-USR-FLD-01,INV-USR-FLD-02," .
                    "INV-USR-FLD-03,INV-USR-FLD-04,INV-USR-FLD-05,HANDLING-CODE,RCPT-INV-DATE,RECORD-ERROR," .
                    "REASON-CODE,CBPRINT-FL,RETAIL-AMT,SEGMENT-BLOCK,JBK-SEQ-NBR,VAT-REG-CTRY,VAT-REG-NBR," .
                    "DIVERSE-CODE,FLEX-FLAG,NOTC,STAT-PROC,FOB-CODE,SHIP-VIA,UNLOADING-PORT,DROPSHIP-FL," .
                    "TRANSPORT-MODE,TAX-TYPE,COUNTRY-CODE,DEST-COUNTRY,ORIGIN-COUNTRY,FOR-ECON-CODE," .
                    "DISCOUNT-RT1,DISCOUNT-RT2,DISCOUNT-RT3,BASE-DISC-AMT1,BASE-DISC-AMT2,BASE-DISC-AMT3," .
                    "TRAN-DISC-AMT1,TRAN-DISC-AMT2,TRAN-DISC-AMT3,DISC-DATE1,DISC-DATE2,DISC-DATE3," .
                    "CARRIER-FLAG,GLBL-DOC-TYPE,REF-TYPE,CUSTOMER-ID,PMT-CAT-CODE,NO-PAY-CONCERN," .
                    "REFERENCE-NO,LTR-OF-GUARAN,LETTER-OF-CR,L_Index\n";
                fwrite($apcinvoice_file, $apcinvoice_header);
                $apcdistrib_header = "COMPANY,VENDOR,EDI-NBR,INVOICE,SUFFIX,DIST-SEQ-NBR,ORIG-TRAN-AMT,TAXABLE-AMT," .
                    "DIST-COMPANY,TO-BASE-AMT,DIS-ACCT-UNIT,DIS-ACCOUNT,DIS-SUB-ACCT,TAX-INDICATOR," .
                    "TAX-SEQ-NBR,TAX-CODE,DESCRIPTION,DST-REFERENCE,ACTIVITY,ASSET-DESC,TAG-NBR," .
                    "ITEM-NBR,ITEM-DESC,ITEM-QUANTITY,ASSET-TEMPLATE,INSRV-DATE,PURCHASE-DATE," .
                    "MODEL-NUMBER,SERIAL-NUMBER,HOLD-AM,ASSET,ACCT-CATEGORY,BILL-CATEGORY," .
                    "UNT-AMOUNT,ITEM-TAX-TRAN,ASSET-GROUP,COMBINE,AU-GROUP,ACCT-UNIT,TAX-POINT," .
                    "PO-AOC-CODE,DIVISION,LOCATION-NAME,BAR-CODE,ITEM-LOC-DTL,TAX-USAGE-CD," .
                    "DST-USR-FLD-01,DST-USR-FLD-02,DST-USR-FLD-03,DST-USR-FLD-04,DST-USR-FLD-05," .
                    "SEGMENT-BLOCK,ICN-CODE,WEIGHT,SUPLMNTARY-QTY,LINE-TYPE\n";
                fwrite($apcdistrib_file, $apcdistrib_header);


                foreach ($interface_tank_lawson as $lawson_rec) {
                    $amount_paid = Amount_paid::findOrFail($lawson_rec->amount_paid_id);
                    $physician_interface_lawson_apcinvoice = PhysicianInterfaceLawsonApcinvoice::findOrFail($lawson_rec->physician_interface_lawson_apcinvoice_id);
                    $contract_interface_lawson_apcdistrib = ContractInterfaceLawsonApcdistrib::findOrFail($lawson_rec->contract_interface_lawson_apcdistrib_id);
                    //Invoice number suffix
                    $invoice_suffix = "DYNAFIOS";
                    if ($contract_interface_lawson_apcdistrib->invoice_number_suffix != "") {
                        $invoice_suffix = $contract_interface_lawson_apcdistrib->invoice_number_suffix;
                    }

                    //string out apcinvoice line
                    $apcinvoice_line = "";
                    $apcinvoice_line = $physician_interface_lawson_apcinvoice->cvi_company .
                        ',' .
                        '"' . str_pad($physician_interface_lawson_apcinvoice->cvi_vendor, 9, " ", STR_PAD_LEFT) . '"' .
                        ',,' .
                        '"' . date('M', strtotime($amount_paid->start_date)) . substr(date('Y', strtotime($amount_paid->start_date)), 2, 2) . '-' . $invoice_suffix . '"' .
                        ',,,,' .
                        '"' . $physician_interface_lawson_apcinvoice->cvi_auth_code . '"' .
                        ',' .
                        '"' . $physician_interface_lawson_apcinvoice->cvi_proc_level . '"' .
                        ',,,,,' .
                        '"' . date('m/d/Y', mktime(0, 0, 0, date('m', strtotime($amount_paid->end_date)), date('d', strtotime($amount_paid->end_date)), date('Y', strtotime($amount_paid->end_date)))) . '"' .
                        ',,,,,,,' .
                        '"' . 'DYNAFIOS ' . $lawson_rec->amount_paid_id . ' ' . date('M', strtotime($amount_paid->start_date)) . ' ' . date('Y', strtotime($amount_paid->start_date)) . '"' .
                        ',,' .
                        number_format($amount_paid->amountPaid, 2, ".", "") .
                        ',,,,,,,,,' .
                        '"' . date('m/d/Y', mktime(0, 0, 0, date('m', strtotime($amount_paid->end_date)), date('d', strtotime($amount_paid->end_date)), date('Y', strtotime($amount_paid->end_date)))) . '"' .
                        ',,,,,' .
                        '"' . $physician_interface_lawson_apcinvoice->cvi_sep_chk_flag . '"' .
                        ',"N",,,"N",,,,,' .
                        '"' . $physician_interface_lawson_apcinvoice->cvi_term_code . '"' .
                        ',' .
                        $physician_interface_lawson_apcinvoice->cvi_rec_status .
                        ',' .
                        $physician_interface_lawson_apcinvoice->cvi_posting_status .
                        ',' .
                        //'"' . date('m/d/Y', mktime(0, 0, 0, $amount_paid->created_at->month, $amount_paid->created_at->day, $amount_paid->created_at->year)) . '"' .
                        '"' . date('m/15/Y') . '"' .
                        ',,,' .
                        '"' . $physician_interface_lawson_apcinvoice->cvi_bank_inst_code . '"' .
                        ',,"TRC","N",,,,,,,,,,,,,,,,,,,,,' .
                        '"' . $physician_interface_lawson_apcinvoice->cvi_invc_ref_type . '"' .
                        ',,,,,,,,,,,,,"N",,,,,,,,,,,,,"N",,,,,,,,,,,,,,,,,,,,,,,,,,,,';
                    $apcinvoice_line = $apcinvoice_line . "\n";
                    //write to file
                    fwrite($apcinvoice_file, $apcinvoice_line);

                    //string out apcdistrib line
                    $apcdistrib_line = "";
                    $apcdistrib_line = $contract_interface_lawson_apcdistrib->cvd_company .
                        ',' .
                        '"' . str_pad($contract_interface_lawson_apcdistrib->cvd_vendor, 9, " ", STR_PAD_LEFT) . '"' .
                        ',,' .
                        '"' . date('M', strtotime($amount_paid->start_date)) . substr(date('Y', strtotime($amount_paid->start_date)), 2, 2) . '-' . $invoice_suffix . '"' .
                        ',,1,' .
                        number_format($amount_paid->amountPaid, 2, ".", "") .
                        ',,' .
                        $contract_interface_lawson_apcdistrib->cvd_dist_company .
                        ',,' .
                        '"' . $contract_interface_lawson_apcdistrib->cvd_dis_acct_unit . '"' .
                        ',' .
                        $contract_interface_lawson_apcdistrib->cvd_dis_account .
                        ',' .
                        $contract_interface_lawson_apcdistrib->cvd_dis_sub_acct .
                        ',,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,';
                    $apcdistrib_line = $apcdistrib_line . "\n";
                    //write to file
                    fwrite($apcdistrib_file, $apcdistrib_line);
                }

                fclose($apcinvoice_file);
                fclose($apcdistrib_file);

                //ftp with error check
                //if success, mark records as is_sent true, else notify dynafios staff
                $data['hosp_name'] = Hospital::findOrFail($hospital_interface_lawson->hospital_id)->name;
                $data['filenames'] = $filenames_short;
                $data['file'] = $degug_filename;
                $data['email'] = 'cody.adkins@dynafios.com';
                $data['name'] = 'DYNAFIOS Lawson Interface';
                $data['type'] = EmailSetup::LAWSON_FAILURE;
                $data['with'] = [
                    'hosp_name' => Hospital::findOrFail($hospital_interface_lawson->hospital_id)->name,
                    'filenames' => $filenames_short
                ];
                $data['attachment'] = [
                    'file1' => [
                        'file' => $degug_filename,
                        'file_name' => 'debug.txt'
                    ]
                ];
                if ($this->lawson_ftp($report_path, $filenames, $apcinvoice_filename_short, $apcdistrib_filename_short, $hospital_interface_lawson, $script_filename, $degug_filename)) {
                    foreach ($interface_tank_lawson as $lawson_rec) {
                        $lawson_rec->updated_by = 999999;
                        $lawson_rec->is_sent = true;
                        $lawson_rec->date_sent = $timestamp;
                        $lawson_rec->apcinvoice_filename = $apcinvoice_filename;
                        $lawson_rec->apcdistrib_filename = $apcdistrib_filename;
                        $lawson_rec->save();
                    }
                    foreach ($interface_tank_lawson as $lawson_rec) {
                        //generate invoice and email to recipient(s)
                        $this->emailInvoices($lawson_rec);
                    }
                } else {
                    EmailQueueService::sendEmail($data);

                    return Response::json(array(
                        'error' => true,
                        'msg' => 'FTP Error.'
                    ), 500);
                }
            } else {
                return Response::json(array(
                    'error' => true,
                    'msg' => 'Unauthorized'
                ), 401);
            }
        } else {
            return Response::json(array(
                'error' => true,
                'msg' => 'No Payments to Process.'
            ), 500);
        }
        return Response::json(array(
            'error' => false,
            'msg' => '200 OK'
        ), 200);
    }

    private function lawson_ftp($report_path, $filenames, $apcinvoice_filename_short, $apcdistrib_filename_short, $hospital_interface_lawson, $script_filename, $degug_filename)
    {
        //build script file
        $degug_file = fopen($degug_filename, "w");
        fclose($degug_file);
        chmod($degug_filename, 0777);
        $script_file = fopen($script_filename, "w");
        //debug
        fwrite($script_file, 'debug -o ' . $degug_filename . ' 4' . "\n");
        //open
        fwrite($script_file, 'open ' . $hospital_interface_lawson->protocol . '://' . $hospital_interface_lawson->host . " -p " . $hospital_interface_lawson->port . "\n");
        //user
        fwrite($script_file, 'user ' . $hospital_interface_lawson->username . ' ' . $hospital_interface_lawson->password . "\n");
        //foreach put filenames
        $i = 0;
        foreach ($filenames as $filename) {
            fwrite($script_file, 'put ' . $filename . "\n");
            if ($i === 0) {
                //$apcinvoice_success_string = 'Successfully transferred "/' . $apcinvoice_filename_short . '"';
                $apcinvoice_success_string = 'Transfer complete.';
            } else {
                //$apcdistrib_success_string = 'Successfully transferred "/' . $apcdistrib_filename_short . '"';
                $apcdistrib_success_string = 'Transfer complete.';
            }
            $i++;
        }
        //endforeach
        //exit
        fwrite($script_file, 'exit' . "\n");
        fclose($script_file);

        //run script file
        //putenv('DYLD_LIBRARY_PATH=/usr/bin');
        //exec('unset PATH ;');
        //putenv('PATH');
        //$path = getenv('PATH');
        //putenv('PATH='. $path .':/usr/local/Cellar/lftp/4.9.1/bin');
        //$command = '/usr/local/Cellar/lftp/4.9.1/bin/lftp -f ' . $script_filename;
        $command = '/usr/bin/lftp -f ' . $script_filename;
        try {
            $result = shell_exec($command);
        } catch (Exception $e) {
            return false;
        }

        //iterate log and return status
        $log_file = fopen($degug_filename, "r");
        try {
            $log = fread($log_file, filesize($degug_filename));
        } catch (Exception $e) {
            return false;
        }
        fclose($log_file);
        if ((strpos($log, $apcinvoice_success_string) !== false) && (strpos($log, $apcdistrib_success_string) !== false)) {
            return true;
        } else {
            return false;
        }
    }

    private function emailInvoices($lawson_rec)
    {
        $amount_paid = Amount_paid::findOrFail($lawson_rec->amount_paid_id);
        $contract_interface_lawson_apcdistrib = ContractInterfaceLawsonApcdistrib::findOrFail($lawson_rec->contract_interface_lawson_apcdistrib_id);
        $invoice_suffix = "DYNAFIOS";
        if ($contract_interface_lawson_apcdistrib->invoice_number_suffix != "") {
            $invoice_suffix = $contract_interface_lawson_apcdistrib->invoice_number_suffix;
        }
        $invoice_number = date('M', strtotime($amount_paid->start_date)) . substr(date('Y', strtotime($amount_paid->start_date)), 2, 2) . '-' . $invoice_suffix;
        $contract = Contract::findOrFail($amount_paid->contract_id);
        $periods = $contract->getContractPeriods($contract->id);
        foreach ($periods->start_dates as $key => $value) {
            if (strpos($value, format_date($amount_paid->start_date)) != false) {
                $selected_date = strval($key);
            }
        }
        $agreement_ids[] = $contract->agreement_id;
        $agreement = Agreement::findOrFail($contract->agreement_id);
        $hospital = Hospital::findOrFail($agreement->hospital_id);
        $recipient = explode(',', $agreement->invoice_receipient);
        $months_start[] = $selected_date;
        $months_end[] = $selected_date;
        $months[] = $selected_date;
        $months[] = $selected_date;
        $payed_practices[] = strval($amount_paid->practice_id);
        $practice_ids[] = strval($amount_paid->practice_id);
        $payed_contracts[] = strval($amount_paid->contract_id);
        $invoice_data = $amount_paid->invoiceReportData($hospital, $agreement_ids, $payed_practices, $months_start, $months_end, -1, false, $payed_contracts);
        $invoice_data[0]['agreement_data']['invoice_no'] = $invoice_number;
        $agreement_ids = implode(',', $agreement_ids);
        $practice_ids = implode(',', array_unique($practice_ids));
        $months = implode(',', $months);
        $invoice_notes = InvoiceNote::getInvoiceNotes($hospital->id, InvoiceNote::HOSPITAL, $hospital->id, 0); /*hospital invoice notes*/
        $start_date = $amount_paid->start_date;
        $result = [
            'hospital' => $hospital,
            'payment_type' => -1,
            'contract_type' => -1,
            'practices' => $practice_ids,
            'agreements' => $agreement_ids,
            'recipient' => array_filter($recipient),
            'months' => $months,
            'data' => $invoice_data,
            'start_date' => $start_date,
            'invoice_notes' => $invoice_notes,
            'is_lawson_interfaced' => true
        ];
        Queue::push(function ($job = "sendInvoice") use ($result) {
            $last_invoice_no = $result["data"][0]["agreement_data"]["invoice_no"];
            if (!file_exists("app/storage/signatures_" . $last_invoice_no)) {
                mkdir("app/storage/signatures_" . $last_invoice_no, 0777, true);
            }
            $start_date = mysql_date($result['start_date']);
            $report_path = hospital_report_path($result["hospital"]);
            $report_filename = "Lawson_invoices_" . date('mdYhis') . ".pdf";
            if (!file_exists($report_path)) {
                mkdir($report_path, 0777, true);
            }
            //$pdf = PDF::loadView('agreements/invoice_pdf',$result)->setPaper('a4', 'landscape')->save($report_path . '/'.$report_filename);
            try {
                $customPaper = array(0, 0, 1683.78, 595.28);
                $pdf = PDF::loadView('agreements/invoice_pdf', $result)->setPaper($customPaper, 'landscape')->save($report_path . '/' . $report_filename);
                //$pdf = PDF::loadView('agreements/invoice_pdf', $result)->setPaper('a4', 'landscape')->save($report_path . '/' . $report_filename);

                /*$hospital_invoice = new HospitalInvoice;
                $hospital_invoice->hospital_id = $result["hospital"]->id;
                $hospital_invoice->filename = $report_filename;
                $hospital_invoice->contracttype_id = 0;
                $hospital_invoice->period = mysql_date(date("m/d/Y"));
                $hospital_invoice->last_invoice_no = $last_invoice_no;
                $hospital_invoice->save();*/

                //return $pdf->download('invoice.pdf');

                //$report_id = $hospital_invoice->id;
                $report_path = hospital_report_path($result["hospital"]);
                $data['month'] = date("F", strtotime($start_date));
                $data['year'] = date("Y", strtotime($start_date));
                $data['name'] = "DYNAFIOS";
                $data['email'] = $result["recipient"];
                $data['file'] = $report_path . "/" . $report_filename;
                $data['type'] = EmailSetup::EMAIL_LAWSON_INVOICE;
                $data['with'] = [
                    'name' => "DYNAFIOS"
                ];
                $data['subject_param'] = [
                    'name' => '',
                    'date' => '',
                    'month' => date("F", strtotime($start_date)),
                    'year' => date("Y", strtotime($start_date)),
                    'requested_by' => '',
                    'manager' => '',
                    'subjects' => ''
                ];

                $data['attachment'] = [
                    'file1' => [
                        'file' => $report_path . "/" . $report_filename,
                        'file_name' => 'lawson_interfaced_invoice.pdf'
                    ]
                ];

                EmailQueueService::sendEmail($data);

                Agreement::delete_files("app/storage/signatures_" . $last_invoice_no);
            } catch (Exception $e) {
                Log::info('Message: ' . $e->getMessage());
            }
            $job->delete();
        });
        return true;
    }

}

?>