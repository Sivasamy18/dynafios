<?php
namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use StdClass;
use DateTime;
use Log;
use App\Practice;
use App\Agreement;
use App\ContractName;
use App\PracticeReport;
// issue fixed : unable to create practice report for payment type hourly and stipend  by1254
use App\ContractType;
use function App\Start\practice_report_path;


class PracticeReportCommand extends ReportingCommand
{
    protected $name = "reports:practice";
    protected $description = "Generates a DYNAFIOS practice report.";

    private $cell_style = [
        'borders' => [
            'outline' => ['borderStyle' => Border::BORDER_THIN],
            'inside' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ];

    private $contract_style = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '000000']],
        'font' => ['color' => ['rgb' => 'FFFFFF']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        'borders' => [
            'allborders' => ['borderStyle' => Border::BORDER_THICK,'color' => ['rgb' => '000000']],
            //'inside' => ['borderStyle' => Border::BORDER_MEDIUM,'color' => ['rgb' => 'FF0000']]
        ]
    ];

    private $total_style = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'eeecea']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_THIN],
            'right' => ['borderStyle' => Border::BORDER_MEDIUM],
            'outline' => ['borderStyle' => Border::BORDER_THIN],
            'inside' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ];

    private $red_style = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'ffc7ce']],
        'font' => ['color' => ['rgb' => '9c0006'], 'bold' => true],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ];

    private $green_style = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'c6efce']],
        'font' => ['color' => ['rgb' => '006100'], 'bold' => true],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ];

    private $shaded_style = [
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'color' => ['rgb' => 'eeece1']
        ],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_MEDIUM],
            'right' => ['borderStyle' => Border::BORDER_MEDIUM]
        ]
    ];

    public function __invoke()
    {
        $arguments = $this->parseArguments();
        
        $now = Carbon::now();
		$timezone = $now->timezone->getName();
		$timestamp = format_date((exec('time /T')), "h:i A");

        $practice = Practice::findOrFail($arguments->practice_id);
        // $workbook = $this->loadTemplate('practice_report.xlsx');
       
        $reader = IOFactory::createReader("Xlsx");//Load template using phpSpreadsheet
		$workbook = $reader->load(storage_path()."/reports/templates/practice_report.xlsx");

        $report_header = '';
        $report_header .= strtoupper($practice->name) . "\n";
        $report_header .= "Period Report\n";
        $report_header .= "Period: " . $arguments->start_date->format("m/d/Y") . " - " . $arguments->end_date->format("m/d/Y")  . "\n";
       // $report_header .= "Run Date: " . with(new DateTime('now'))->format("m/d/Y");
        $report_header .= "Run Date: " . with($arguments->localtimeZone);

       

        $report_header_ytm = '';
        $report_header_ytm .= strtoupper($practice->name) . "\n";
        $report_header_ytm .= "Contract Year to Prior Month Report\n";
        $report_header_ytm .= "Period: " . $arguments->start_date->format("m/d/Y") . " - " . $arguments->end_date->format("m/d/Y")  . "\n";
       // $report_header_ytm .= "Run Date: " . with(new DateTime('now'))->format("m/d/Y");
        $report_header_ytm .= "Run Date: " . with($arguments->localtimeZone);

        $report_header_ytd = '';
        $report_header_ytd .= strtoupper($practice->name) . "\n";
        $report_header_ytd .= "Contract Year To Date Report\n";
        $report_header_ytd .= "Period: " . $arguments->start_date->format("m/d/Y") . " - " . $arguments->end_date->format("m/d/Y")  . "\n";
       // $report_header_ytd .= "Run Date: " . with(new DateTime('now'))->format("m/d/Y");
       $report_header_ytd .= "Run Date: " . with($arguments->localtimeZone);


        $workbook->setActiveSheetIndex(0)->setCellValue('B2', $report_header);
        $workbook->setActiveSheetIndex(1)->setCellValue('B2', $report_header_ytm);
        $workbook->setActiveSheetIndex(2)->setCellValue('B2', $report_header_ytd);

        $period_index = 4;
        $ytm_index = 4;
        $ytd_index = 4;

        foreach ($arguments->agreements as $agreement) {
            $contracts = $this->queryContracts($practice->id, $agreement, $agreement->start_date, $agreement->end_date, $arguments->contract_type);
            $physician_id = array($practice->id);
            $period_index = $this->writeData($workbook, 0, $period_index, $contracts->period,$agreement->start_date,$agreement->end_date);
            $ytm_index = $this->writeDataYTD($workbook, 1, $ytm_index, $contracts->year_to_month,$agreement->start_date,$physician_id,$agreement->end_date);
            $ytd_index = $this->writeDataYTD($workbook, 2, $ytd_index, $contracts->year_to_date,$agreement->start_date,$physician_id,$agreement->end_date);
        }
//die;
        $report_path = practice_report_path($practice);
        // $report_filename = "report_" . date('mdYhis') . ".xlsx";
        $timeZone = str_replace(' ','_', $arguments->localtimeZone);
        $timeZone = str_replace('/','', $timeZone);
        $timeZone = str_replace(':','', $timeZone);
		$report_filename = "report_" . $practice->name . "_"  . $timeZone . ".xlsx";
        
        
        if (!file_exists($report_path)) {
            mkdir($report_path, 0777, true);
        }

        $writer = IOFactory::createWriter($workbook, 'Xlsx');
        $writer->save("{$report_path}/{$report_filename}");

        $practice_report = new PracticeReport;
        $practice_report->practice_id = $practice->id;
        $practice_report->filename = $report_filename;
        $practice_report->save();

        $this->success('practices.generate_report_success', $practice_report->id);
    }

    protected function writeData($workbook, $sheetIndex, $index, $contracts,$startDate,$end_date)
    {

        $current_row = $index;
        $totals = new StdClass;
        $totals->physician_name = null;
        $totals->contract_name = null;
        $totals->physician_name_1 = null;
        $totals->contract_name_1 = null;
        $totals->expected_hours = 0.0;
        $totals->expected_hours_ytd = 0.0;
        $totals->worked_hours = 0.0;
        $totals->days_on_call = 0.0;
        $totals->per_hour = 0.0;
        $totals->rate = 0.0;
        $totals->amount_paid = 0.0;
        $totals->expected_payment = 0.0;
        $totals->contract_term = 0;
        $totals->contract_month = 0;
        foreach ($contracts as $index => $contract) {
            $contract->month_end_date = mysql_date(date($end_date));

            $amount = DB::table('amount_paid')
                ->where('start_date', '<=', mysql_date(date($startDate)))
                ->where('end_date', '>=', mysql_date(date($startDate)))
                ->where('physician_id', '=', $contract->physician_id)
                ->where('contract_id', '=', $contract->contract_id)
                ->where('practice_id', '=', $contract->practice_id)
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->first();
            if (isset($amount->amountPaid)) {
                $contract->amount_paid = $amount->amountPaid;
            } else {
                $contract->amount_paid = 0;
            }
            $formula = $this->applyFormula($contract);
            $month = mysql_date(date($startDate));
            $monthArr = explode("-", $month);
            //$month = $monthArr[1];
            //echo $contract->physician_id;
            /*$amount = DB::table('amount_paid')
                ->where('start_date', '<=', $month)
                ->where('end_date', '>=', $month)
                ->where('physician_id', '=', $contract->physician_id)
                ->where('contract_id', '=', $contract->contract_id)
                ->where('practice_id', '=', $contract->practice_id)
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->first();*/
            //$queries = DB::getQueryLog();
            //$last_query = end($queries);
            //print_r($last_query);die;
            if (isset($amount->amountPaid)) {
                $formula->amount_paid = $amount->amountPaid;
            } else {
                $formula->amount_paid = 0;
            }
            $contract->expected_payment = $contract->expected_hours * $contract->rate;
            if ($contract->worked_hours != 0)
                $pmt_status = (($formula->amount_paid) / $contract->worked_hours) <= $contract->rate;
            else
                $pmt_status = false;

            if ($sheetIndex == 0) {
                $current_row++;

                if ($contract->contract_type_id == 4) {
                    //Log::info('contract_id',array($contract));
                    $contract_actions = DB::table('action_contract')->select(
                        DB::raw("actions.name as action"),
                        DB::raw("action_contract.hours as duration")
                    )
                        ->join('contracts', 'contracts.id', '=', 'action_contract.contract_id')
                        ->join('actions', 'actions.id', '=', 'action_contract.action_id')
                        ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                        ->where('contracts.id', '=', $contract->contract_id);
                    $contract_actions = $contract_actions->get();
                    $contract_expected_payment = 0;
                    foreach ($contract_actions as $action) {
                        if (($action->action == 'Weekday - HALF Day - On Call') || ($action->action == 'Weekday - FULL Day - On Call')) {
                            $contract_expected_payment = $contract_expected_payment + ($action->duration * $contract->weekday_rate);
                        }
                        if (($action->action == 'Weekend - HALF Day - On Call') || ($action->action == 'Weekend - FULL Day - On Call')) {
                            $contract_expected_payment = $contract_expected_payment + ($action->duration * $contract->weekend_rate);
                        }
                        if (($action->action == 'Holiday - HALF Day - On Call') || ($action->action == 'Holiday - FULL Day - On Call')) {
                            $contract_expected_payment = $contract_expected_payment + ($action->duration * $contract->holiday_rate);
                        }
                    }

                } else {
                    $contract_expected_payment = $contract->expected_payment;
                }

                $workbook->setActiveSheetIndex($sheetIndex)
                    ->setCellValue("B{$current_row}", $pmt_status ? 'Y' : 'N')
                    ->setCellValue("C{$current_row}", $contract->physician_name)
                    ->setCellValue("D{$current_row}", $contract->contract_name)
                    //->setCellValue("D{$current_row}", $contract->min_hours)
                    //->setCellValue("E{$current_row}", $contract->max_hours)
                    ->setCellValue("E{$current_row}", $contract->expected_hours)
                    ->setCellValue("F{$current_row}", $contract_expected_payment)
                    ->setCellValue("G{$current_row}", $contract->worked_hours)
                    ->setCellValue("H{$current_row}", $formula->amount_paid)
                    ->setCellValue("I{$current_row}", $contract->worked_hours != 0 ? $formula->amount_paid / $contract->worked_hours : 0)
                    ->setCellValue("J{$current_row}", $contract->rate)
                    ->setCellValue("K{$current_row}", $formula->days_on_call);

                $totals->contract_name = $contract->contract_name;
                $totals->expected_payment += $contract->expected_payment;
                $totals->worked_hours += $contract->worked_hours;
                $totals->amount_paid += $formula->amount_paid;
                $totals->per_hour += $contract->worked_hours != 0 ? $formula->amount_paid / $contract->worked_hours : 0;
                $totals->rate = $contract->rate;

               // $totals->days_on_call += $formula->days_on_call;
                // issue fixed : unable to create practice report for payment type hourly and stipend  by 1254
                if ($contract->contract_type_id == ContractType::ON_CALL) {
                    $totals->days_on_call += $formula->days_on_call;
                }


                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("B{$current_row}:K{$current_row}")->applyFromArray($this->cell_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("G{$current_row}:K{$current_row}")->applyFromArray($this->shaded_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("B{$current_row}")->applyFromArray($pmt_status ? $this->green_style : $this->red_style);
            } else {
                $current_row++;
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->setCellValue("B{$current_row}", $contract->contract_name)
                    ->setCellValue("C{$current_row}", $contract->contract_name)
                    ->setCellValue("D{$current_row}", $contract->expected_hours * $contract->contract_month)
                    ->setCellValue("E{$current_row}", $contract->expected_hours)
                    ->setCellValue("F{$current_row}", $contract->worked_hours)
                    ->setCellValue("G{$current_row}", $formula->days_on_call)
                    ->setCellValue("H{$current_row}", $formula->actual_rate)
                    ->setCellValue("I{$current_row}", $formula->amount / $contract->contract_month)
                    ->setCellValue("J{$current_row}", $contract->contract_month)
                    ->setCellValue("K{$current_row}", $contract->paid)
                    ->setCellValue("L{$current_row}", $formula->amount)
                    ->setCellValue("M{$current_row}", $contract->contract_term);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->cell_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("D{$current_row}:M{$current_row}")->applyFromArray($this->shaded_style);
            }
            //}
            $current_row++;
            $per_hour = $totals->worked_hours != 0 ? $totals->amount_paid / $totals->worked_hours : 0;
            $totals->worked_hours != 0 ? $pmt_status = $per_hour <= $totals->rate : false;

            $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("C{$current_row}:D{$current_row}")
                ->setCellValue("B{$current_row}", $pmt_status ? 'Y' : 'N')
                ->setCellValue("C{$current_row}", $contract->contract_name . " totals")
                //->setCellValue("D{$current_row}", $contract->contract_name)
                //->setCellValue("D{$current_row}", $contract->min_hours)
                //->setCellValue("E{$current_row}", $contract->max_hours)
                ->setCellValue("E{$current_row}", "-")
                ->setCellValue("F{$current_row}", $totals->expected_payment)
                ->setCellValue("G{$current_row}", $totals->worked_hours)
                ->setCellValue("H{$current_row}", $totals->amount_paid)
                ->setCellValue("I{$current_row}", $totals->worked_hours != 0 ? $totals->amount_paid / $totals->worked_hours : 0)
                ->setCellValue("J{$current_row}", "-")
                ->setCellValue("K{$current_row}", $totals->days_on_call != 0 ? $totals->days_on_call : "-");

            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("B{$current_row}:K{$current_row}")->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("B{$current_row}:K{$current_row}")->applyFromArray($this->total_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("B{$current_row}")->applyFromArray($pmt_status ? $this->green_style : $this->red_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }
        return $current_row;
    }


    protected function writeDataYTD($workbook, $sheetIndex, $index, $contracts,$startDate,$physicians,$end_date)
    {
        $current_row = $index;
        $year = date('Y',strtotime($startDate));
        $totals = new StdClass;
        $totals->physician_name = null;
        $totals->contract_name = null;
        $totals->physician_name_1 = null;
        $totals->contract_name_1 = null;
        $totals->expected_hours = 0.0;
        $totals->expected_hours_ytd = 0.0;
        $totals->worked_hours = 0.0;
        $totals->days_on_call = 0.0;
        $totals->per_hour = 0.0;
        $totals->rate = 0.0;
        $totals->amount_paid = 0.0;
        $totals->expected_payment = 0.0;
        $totals->contract_term = 0;
        $totals->contract_month = 0;
        $totals->contract_month1 = 0;

        $totals_main = new StdClass;
        $totals_main->physician_name = null;
        $totals_main->contract_name = null;
        $totals_main->physician_name_1 = null;
        $totals_main->contract_name_1 = null;
        $totals_main->expected_hours = 0.0;
        $totals_main->expected_hours_ytd = 0.0;
        $totals_main->worked_hours = 0.0;
        $totals_main->days_on_call = 0.0;
        $totals_main->per_hour = 0.0;
        $totals_main->rate = 0.0;
        $totals_main->amount_paid = 0.0;
        $totals_main->expected_payment = 0.0;
        $totals_main->contract_term = 0;
        $totals_main->contract_month = 0;
        $totals_main->contract_month1 = 0;

        for($i = 0;$i<count($physicians);$i++)
        {
            $physician_id = $physicians[$i];
            $count1 = count($contracts);
            $contracts1 = array();
            for($i1 = $count1-1;$i1 >= 0;$i1--)
            {
                for($j = 0;$j<count($contracts[$i1]);$j++)
                {
                    $contracts1[$j][$i1] = $contracts[$i1][$j];
                }
            }
            $contracts = $contracts1;

            //echo "<pre>";
            //print_r($contracts);die;
            $count1 = count($contracts);
            //$count1 = count($contracts[0]);
            //echo $count1;die;
            for($j=$count1-1;$j >= 0;$j--)
            {
                //echo $physician_id;

                foreach ($contracts[$j] as $contract) {
                    //print_r($contract);die;
                    //if($contract->physician_id == $physician_id )
                    //{
                    //print_r($contract);die;
                    if ($contract) {
                        if ($index == 0) {
                            $totals->physician_name = $contract->physician_name;
                            $totals->physician_name_1 = $contract->physician_name;
                            $totals->contract_name = $contract->contract_name;
                            $totals->contract_name_1 = $contract->contract_name;
                        }
                        if ($totals->physician_name != $contract->physician_name) {

                            if ($totals->contract_term != 0) {
                                //echo "hey".$sheetIndex;
                                //$totals_main->expected_hours_ytd += $totals->expected_hours_ytd;
                                $current_row++;

                                if ($totals->worked_hours != 0)
                                    $pmt_status = (($totals->amount_paid) / $totals->worked_hours) <= $totals->rate;
                                else
                                    $pmt_status = false;
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->setCellValue("B{$current_row}", $pmt_status ? 'Y' : 'N')
                                    ->setCellValue("C{$current_row}", $totals->physician_name)
                                    ->setCellValue("D{$current_row}", $totals->contract_name)
                                    ->setCellValue("E{$current_row}", $totals->expected_hours)
                                    ->setCellValue("F{$current_row}", $totals->expected_payment)
                                    ->setCellValue("G{$current_row}", $totals->expected_hours_ytd)
                                    ->setCellValue("H{$current_row}", $totals->worked_hours)
                                    ->setCellValue("I{$current_row}", $totals->amount_paid)
                                    ->setCellValue("J{$current_row}", $totals->per_hour)
                                    ->setCellValue("K{$current_row}", $totals->contract_month1)
                                    ->setCellValue("L{$current_row}", $totals->contract_term)
                                    ->setCellValue("M{$current_row}", $totals->days_on_call > 0 ? $totals->days_on_call : "-");

                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->cell_style);
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("H{$current_row}:M{$current_row}")->applyFromArray($this->shaded_style);
                                if ($sheetIndex == 1) {

                                    if ($totals->worked_hours != 0)
                                        $pmt_status = (($totals->amount_paid) / $totals->worked_hours) <= $totals->rate;
                                    else
                                        $pmt_status = false;
                                    $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("C{$current_row}:d{$current_row}")
                                        ->setCellValue("B{$current_row}", $pmt_status ? 'Y' : 'N')
                                        ->setCellValue("C{$current_row}", $totals->physician_name . " totals")
                                        //->setCellValue("D{$current_row}", $totals->contract_name)
                                        ->setCellValue("E{$current_row}", "-")
                                        ->setCellValue("F{$current_row}", $totals->expected_payment)
                                        ->setCellValue("G{$current_row}", $totals->expected_hours_ytd)
                                        ->setCellValue("H{$current_row}", $totals->worked_hours)
                                        ->setCellValue("I{$current_row}", $totals->amount_paid)
                                        ->setCellValue("J{$current_row}", $totals->worked_hours != 0 ? $totals->amount_paid / $totals->worked_hours : 0)
                                        ->setCellValue("K{$current_row}", "-")
                                        ->setCellValue("L{$current_row}", "-")
                                        ->setCellValue("M{$current_row}", $totals->days_on_call > 0 ? $totals->days_on_call : "-");
                                    $workbook->setActiveSheetIndex($sheetIndex)
                                        ->getStyle("B{$current_row}:M{$current_row}")->getFont()->setBold(true);
                                    $workbook->setActiveSheetIndex($sheetIndex)
                                        ->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->contract_style);
                                    $workbook->setActiveSheetIndex($sheetIndex)
                                        ->getStyle("E{$current_row}:M{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                }
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("B{$current_row}")->applyFromArray($pmt_status ? $this->green_style : $this->red_style);

                                $totals->expected_hours = 0;
                                $totals->expected_hours_ytd = 0;
                                $totals->worked_hours = 0;
                                $totals->days_on_call = 0;
                                $totals->per_hour = 0;
                                $totals->amount_paid = 0;
                                $totals->expected_payment = 0;
                            }
                            $totals->physician_name = $contract->physician_name;
                            $totals->physician_name_1 = $contract->physician_name;
                        } else {
                            $totals->physician_name_1 = "";
                        }
                        //if(count($contract) > 0) {
                        if($contract) {
                            if ($sheetIndex == 2 && $totals->contract_name != $contract->contract_name && $totals->contract_name != '') {
                                //echo "hey".$sheetIndex;
                                $current_row++;
                                if ($totals_main->worked_hours != 0)
                                    $pmt_status = (($totals_main->amount_paid) / $totals_main->worked_hours) <= $totals_main->rate;
                                else
                                    $pmt_status = false;
                                $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("C{$current_row}:D{$current_row}")
                                    ->setCellValue("B{$current_row}", $pmt_status ? 'Y' : 'N')
                                    ->setCellValue("C{$current_row}", $totals->contract_name . " totals")
                                    ->setCellValue("E{$current_row}", "-")
                                    ->setCellValue("F{$current_row}", $totals_main->expected_payment)
                                    ->setCellValue("G{$current_row}", $totals_main->expected_hours_ytd)
                                    ->setCellValue("H{$current_row}", $totals_main->worked_hours)
                                    ->setCellValue("I{$current_row}", $totals_main->amount_paid)
                                    ->setCellValue("J{$current_row}", $totals_main->worked_hours != 0 ? $totals_main->amount_paid / $totals_main->worked_hours : 0)
                                    ->setCellValue("K{$current_row}", "-")
                                    ->setCellValue("L{$current_row}", "-")
                                    ->setCellValue("M{$current_row}", $totals_main->days_on_call > 0 ? $totals_main->days_on_call : "-");
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("B{$current_row}:M{$current_row}")->getFont()->setBold(true);
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->total_style);
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("B{$current_row}")->applyFromArray($pmt_status ? $this->green_style : $this->red_style);
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                                $this->resetMainTotal($totals_main);
                            }
                            if ($sheetIndex == 1 && $totals->contract_name != $contract->contract_name && $totals->contract_name != '') {
                                //echo "hey".$sheetIndex;
                                $current_row++;
                                if ($totals_main->worked_hours != 0)
                                    $pmt_status = (($totals_main->amount_paid) / $totals_main->worked_hours) <= $totals_main->rate;
                                else
                                    $pmt_status = false;
                                $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("C{$current_row}:d{$current_row}")
                                    ->setCellValue("B{$current_row}", $pmt_status ? 'Y' : 'N')
                                    ->setCellValue("C{$current_row}", $totals->contract_name . " totals")
                                    ->setCellValue("E{$current_row}", "-")
                                    ->setCellValue("F{$current_row}", $totals_main->expected_payment)
                                    ->setCellValue("G{$current_row}", $totals_main->expected_hours_ytd)
                                    ->setCellValue("H{$current_row}", $totals_main->worked_hours)
                                    ->setCellValue("I{$current_row}", $totals_main->amount_paid)
                                    ->setCellValue("J{$current_row}", $totals_main->worked_hours != 0 ? $totals_main->amount_paid / $totals_main->worked_hours : 0)
                                    ->setCellValue("K{$current_row}", "-")
                                    ->setCellValue("L{$current_row}", "-")
                                    ->setCellValue("M{$current_row}", $totals_main->days_on_call > 0 ? $totals_main->days_on_call : "-");
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("B{$current_row}:M{$current_row}")->getFont()->setBold(true);
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->total_style);
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("B{$current_row}")->applyFromArray($pmt_status ? $this->green_style : $this->red_style);
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                                $this->resetMainTotal($totals_main);
                            }
                        }
                        if ($totals->contract_name != $contract->contract_name) {

                            $totals->contract_name = $contract->contract_name;
                            $totals->contract_name_1 = $contract->contract_name;
                        } else {
                            //$totals->physician_name_1 = "";
                            $totals->contract_name_1 = "";
                        }

                        $start_month_date = $contract->start_date_check;
                        $contract->month_end_date = mysql_date(date($end_date));

                        $amount = DB::table('amount_paid')
                            ->where('start_date', '<=', mysql_date(date($start_month_date)))
                            ->where('end_date', '>=', mysql_date(date($start_month_date)))
                            ->where('physician_id', '=', $contract->physician_id)
                            ->where('contract_id', '=', $contract->contract_id)
                            ->where('practice_id', '=', $contract->practice_id)
                            ->orderBy('created_at', 'desc')
                            ->orderBy('id', 'desc')
                            ->first();
                        if (isset($amount->amountPaid)) {
                            $contract->amount_paid = $amount->amountPaid;
                        }else{
                            $contract->amount_paid = 0;
                        }
                        $formula = $this->applyFormula($contract);
                        $end_month_date = $contract->end_date_check;
                        /*$amount = DB::table('amount_paid')
                            ->where('start_date', '<=', $start_month_date)
                            ->where('end_date', '>=', $start_month_date)
                            ->where('physician_id', '=', $contract->physician_id)
                            ->where('contract_id','=',$contract->contract_id)
                            ->where('practice_id','=',$contract->practice_id)
                            ->orderBy('created_at', 'desc')
                            ->orderBy('id', 'desc')
                            ->first();*/
                        //$queries = DB::getQueryLog();
                        //$last_query = end($queries);
                        //print_r($last_query);die;
                        if (isset($amount->amountPaid)) {
                            $formula->amount_paid = $amount->amountPaid;
                        } else {
                            $formula->amount_paid = 0;
                        }
                        $contract->expected_payment = $contract->expected_hours * $contract->rate;

                        $totals->expected_hours = $contract->expected_hours;
                        $totals->expected_hours_ytd += $contract->expected_hours;
                        $totals_main->expected_hours_ytd += $contract->expected_hours;
                        $totals->worked_hours += $contract->worked_hours;

                       
                      //  $totals->days_on_call += $formula->days_on_call;
                        // issue fixed : unable to create practice report for payment type hourly and stipend  by1254
                        if ($contract->contract_type_id == ContractType::ON_CALL) {
                            $totals->days_on_call += $formula->days_on_call;
                        }
                        
                        $totals->rate = $contract->rate;
                        $totals->per_hour += $contract->worked_hours != 0 ? $formula->amount_paid / $contract->worked_hours : 0;
                        $totals->amount_paid += $formula->amount_paid;
                        $totals->expected_payment += $contract->expected_payment;
                        $totals->contract_term = $contract->contract_term;
                        $totals->contract_month = $contract->contract_month;
                        $totals->rate = $contract->rate;
                        //$totals->days_on_call += $formula->days_on_call;
                        $totals_main->expected_hours = $contract->expected_hours;
                        //$totals_main->expected_hours_ytd = $contract->expected_hours * $contract->contract_month;
                        $totals_main->worked_hours += $contract->worked_hours;
                        // issue fixed : unable to create practice report for payment type hourly and stipend  by1254
                        if ($contract->contract_type_id == ContractType::ON_CALL) {
                            $totals_main->days_on_call += $formula->days_on_call;
                        }

                      //  $totals_main->days_on_call += $formula->days_on_call;
                        $totals_main->rate = $contract->rate;
                        $totals_main->per_hour += $contract->worked_hours != 0 ? $formula->amount_paid / $contract->worked_hours : 0;
                        //$totals_main->expected_hours_ytd = $contract->expected_hours * $contract->contract_month;
                        $totals_main->amount_paid += $formula->amount_paid;
                        $totals_main->expected_payment += $contract->expected_payment;
                        $totals_main->contract_term = $contract->contract_term;
                        $totals_main->contract_month = $contract->contract_month;
                        if ($sheetIndex == 2) {
                            $totals_main->contract_month1 = $contract->contract_month1;
                            $totals->contract_month1 = $contract->contract_month1;
                        }
                        $totals_main->contract_name = $contract->contract_name;
                        $totals_main->rate = $contract->rate;
                        //$totals_main->days_on_call += $formula->days_on_call;

                        if ($sheetIndex == 1) {
                            $current_row++;
                            if ($contract->worked_hours != 0)
                                $pmt_status = (($formula->amount_paid) / $contract->worked_hours) <= $contract->rate;
                            else
                                $pmt_status = false;
                            //if(!$pmt_status)
                            // {
                            //     $totals->pmt_status = 'N';
                            //     $totals_ytm->pmt_status = 'N';
                            // }
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->setCellValue("B{$current_row}", $pmt_status ? 'Y' : 'N')
                                ->setCellValue("C{$current_row}", $totals->physician_name_1)
                                ->setCellValue("D{$current_row}", $totals->contract_name_1)
                                ->setCellValue("E{$current_row}", $contract->expected_hours)
                                ->setCellValue("F{$current_row}", $contract->expected_payment)
                                ->setCellValue("G{$current_row}", $contract->expected_hours * $contract->contract_month)
                                ->setCellValue("H{$current_row}", $contract->worked_hours)
                                ->setCellValue("I{$current_row}", $formula->amount_paid)
                                ->setCellValue("J{$current_row}", $contract->worked_hours != 0 ? $formula->amount_paid / $contract->worked_hours : 0)
                                ->setCellValue("K{$current_row}", $contract->contract_month)
                                ->setCellValue("L{$current_row}", $contract->contract_term)
                                ->setCellValue("M{$current_row}", $formula->days_on_call > 0 ? $formula->days_on_call : "-");

                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->cell_style);
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("H{$current_row}:M{$current_row}")->applyFromArray($this->shaded_style);
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("B{$current_row}")->applyFromArray($pmt_status ? $this->green_style : $this->red_style);
                        }
                        // }
                    }
                }
            }
            //$totals_main->expected_hours_ytd += $totals->expected_hours_ytd;
            if($sheetIndex == 2)
            {
                //echo "hey".$sheetIndex;
                $current_row++;
                //echo $totals->contract_month1;die;
                //$totals_main->expected_hours_ytd += $totals->expected_hours_ytd;
                if($totals->worked_hours !=0)
                    $pmt_status = (($totals->amount_paid)/$totals->worked_hours) <= $totals->rate;
                else
                    $pmt_status = false;
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->setCellValue("B{$current_row}", $pmt_status?'Y':'N')
                    ->setCellValue("C{$current_row}", $totals->physician_name)
                    ->setCellValue("D{$current_row}", $totals->contract_name)
                    ->setCellValue("E{$current_row}", $totals->expected_hours)
                    ->setCellValue("F{$current_row}", $totals->expected_payment)
                    ->setCellValue("G{$current_row}", $totals->expected_hours_ytd)
                    ->setCellValue("H{$current_row}", $totals->worked_hours)
                    ->setCellValue("I{$current_row}", $totals->amount_paid)
                    ->setCellValue("J{$current_row}", $totals->per_hour)
                    ->setCellValue("K{$current_row}", $totals->contract_month1)
                    ->setCellValue("L{$current_row}", $totals->contract_term)
                    ->setCellValue("M{$current_row}", $totals->days_on_call>0?$totals->days_on_call:"-");

                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->cell_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("H{$current_row}:M{$current_row}")->applyFromArray($this->shaded_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("B{$current_row}")->applyFromArray($pmt_status ? $this->green_style : $this->red_style);
            }
            if($sheetIndex == 1)
            {
                $current_row++;
                //$totals_main->expected_hours_ytd += $totals->expected_hours_ytd;
                if($totals->worked_hours !=0)
                    $pmt_status = (($totals->amount_paid)/$totals->worked_hours) <= $totals->rate;
                else
                    $pmt_status = false;
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->setCellValue("B{$current_row}", $pmt_status?'Y':'N')->mergeCells("C{$current_row}:d{$current_row}")
                    ->setCellValue("C{$current_row}", $totals->physician_name." totals")
                    //->setCellValue("D{$current_row}", $totals->contract_name)
                    ->setCellValue("E{$current_row}", "-")
                    ->setCellValue("F{$current_row}", $totals->expected_payment)
                    ->setCellValue("G{$current_row}", $totals->expected_hours_ytd)
                    ->setCellValue("H{$current_row}", $totals->worked_hours)
                    ->setCellValue("I{$current_row}", $totals->amount_paid)
                    ->setCellValue("J{$current_row}", $totals->worked_hours!=0?$totals->amount_paid/$totals->worked_hours:0)
                    ->setCellValue("K{$current_row}", "-")
                    ->setCellValue("L{$current_row}", "-")
                    ->setCellValue("M{$current_row}", $totals->days_on_call > 0 ? $totals->days_on_call:"-");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("B{$current_row}:M{$current_row}")->getFont()->setBold(true);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->contract_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("E{$current_row}:M{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("B{$current_row}")->applyFromArray($pmt_status ? $this->green_style : $this->red_style);
            }
        }

        if($sheetIndex == 2)
        {
            //echo "hey".$sheetIndex;
            $current_row++;

            if($totals_main->worked_hours !=0)
                $pmt_status = (($totals_main->amount_paid)/$totals_main->worked_hours) <= $totals_main->rate;
            else
                $pmt_status = false;
            $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("C{$current_row}:D{$current_row}")
                ->setCellValue("B{$current_row}", $pmt_status?'Y':'N')
                ->setCellValue("C{$current_row}", $totals->contract_name." totals")
                ->setCellValue("E{$current_row}", "-")
                ->setCellValue("F{$current_row}", $totals_main->expected_payment)
                ->setCellValue("G{$current_row}", $totals_main->expected_hours_ytd)
                ->setCellValue("H{$current_row}", $totals_main->worked_hours)
                ->setCellValue("I{$current_row}", $totals_main->amount_paid)
                ->setCellValue("J{$current_row}", $totals_main->worked_hours!=0?$totals_main->amount_paid/$totals_main->worked_hours:0)
                ->setCellValue("K{$current_row}", "-")
                ->setCellValue("L{$current_row}", "-")
                ->setCellValue("M{$current_row}", $totals_main->days_on_call > 0 ? $totals_main->days_on_call:"-");

            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("B{$current_row}:M{$current_row}")->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->total_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("B{$current_row}")->applyFromArray($pmt_status?$this->green_style : $this->red_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }
        if($sheetIndex == 1)
        {
            //echo "hey".$sheetIndex;
            $current_row++;

            if($totals_main->worked_hours !=0)
                $pmt_status = (($totals_main->amount_paid)/$totals_main->worked_hours) <= $totals_main->rate;
            else
                $pmt_status = false;
            $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("C{$current_row}:d{$current_row}")
                ->setCellValue("B{$current_row}", $pmt_status?'Y':'N')
                ->setCellValue("C{$current_row}", $totals->contract_name." totals")
                ->setCellValue("E{$current_row}", "-")
                ->setCellValue("F{$current_row}", $totals_main->expected_payment)
                ->setCellValue("G{$current_row}", $totals_main->expected_hours_ytd)
                ->setCellValue("H{$current_row}", $totals_main->worked_hours)
                ->setCellValue("I{$current_row}", $totals_main->amount_paid)
                ->setCellValue("J{$current_row}", $totals_main->worked_hours!=0?$totals_main->amount_paid/$totals_main->worked_hours:0)
                ->setCellValue("K{$current_row}", "-")
                ->setCellValue("L{$current_row}", "-")
                ->setCellValue("M{$current_row}", $totals_main->days_on_call > 0 ? $totals_main->days_on_call:"-");

            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("B{$current_row}:M{$current_row}")->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->total_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("B{$current_row}")->applyFromArray($pmt_status?$this->green_style : $this->red_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        return $current_row;
    }

    protected function queryContracts($practice_id, $agreement_data, $start_date, $end_date,  $contract_type_id)
    {
        $agreement = Agreement::findOrFail($agreement_data->id);

        $start_date = mysql_date($start_date);
        $end_date = mysql_date($end_date);

        $contract_month = months($agreement->start_date, 'now');
        $contract_term = months($agreement->start_date, $agreement->end_date);
        $log_range = months($start_date, $end_date);

        if ($contract_month > $contract_term) {
            $contract_month = $contract_term;
        }

        //drop column practice_id from table 'physicians' changes by 1254 :updated with physician_practices 
        $period_query = DB::table('physician_logs')->select(
            DB::raw("practices.name as practice_name"),
            DB::raw("physicians.id as physician_id"),
            DB::raw("concat(physicians.last_name, ', ', physicians.first_name) as physician_name"),
            DB::raw("specialties.name as specialty_name"),
            DB::raw("contracts.id as contract_id"),
            DB::raw("contracts.contract_name_id as contract_name_id"),
            DB::raw("contracts.contract_type_id as contract_type_id"),
            DB::raw("contract_types.name as contract_name"),
            DB::raw("contracts.min_hours as min_hours"),
            DB::raw("contracts.max_hours as max_hours"),
            DB::raw("contracts.expected_hours as expected_hours"),
            DB::raw("contracts.rate as rate"),
            DB::raw("contracts.weekday_rate as weekday_rate"),
            DB::raw("contracts.weekend_rate as weekend_rate"),
            DB::raw("contracts.holiday_rate as holiday_rate"),
            DB::raw("'{$practice_id}' as practice_id"),
            DB::raw("sum(physician_logs.duration) as worked_hours"),
            DB::raw("'{$contract_month}' as contract_month"),
            DB::raw("'{$contract_term}' as contract_term"),
            DB::raw("'{$log_range}' as log_range")
        )
            ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
            ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
            ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
            ->join('specialties', 'specialties.id', '=', 'physicians.specialty_id')
            ->join("practices", function ($join) {
                $join->on("physician_practices.practice_id", "=", "practices.id")
                    ->on("practices.id", "=", "contracts.practice_id")
                    ->on("practices.hospital_id", "=", "agreements.hospital_id");
            })
            ->where('contracts.agreement_id', '=', $agreement_data->id)
            ->where('practices.id', '=', $practice_id)
            ->where('physician_logs.practice_id', '=', $practice_id)
            ->whereBetween('physician_logs.date', [$start_date, $end_date])
            ->groupBy('physicians.id', 'contracts.id')
            ->orderBy('contract_types.name', 'asc')
            ->orderBy('practices.name', 'asc')
            ->orderBy('physicians.last_name', 'asc')
            ->orderBy('physicians.first_name', 'asc');

        $period_query2 = DB::table('physicians')->select(
            DB::raw("practices.name as practice_name"),
            DB::raw("physicians.id as physician_id"),
            DB::raw("concat(physicians.last_name, ', ', physicians.first_name) as physician_name"),
            DB::raw("specialties.name as specialty_name"),
            DB::raw("contracts.id as contract_id"),
            DB::raw("contracts.contract_name_id as contract_name_id"),
            DB::raw("contracts.contract_type_id as contract_type_id"),
            DB::raw("contract_types.name as contract_name"),
            DB::raw("contracts.min_hours as min_hours"),
            DB::raw("contracts.max_hours as max_hours"),
            DB::raw("contracts.expected_hours as expected_hours"),
            DB::raw("0 as worked_hours"),
            DB::raw("contracts.rate as rate"),
            DB::raw("contracts.weekday_rate as weekday_rate"),
            DB::raw("contracts.weekend_rate as weekend_rate"),
            DB::raw("contracts.holiday_rate as holiday_rate"),
            DB::raw("'{$practice_id}' as practice_id"),
            DB::raw("'{$contract_month}' as contract_month"),
            DB::raw("'{$contract_term}' as contract_term"),
            DB::raw("'{$log_range}' as log_range")
        )
            ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
            ->join('physician_contracts', 'physician_contracts.physician_id', '=', 'physicians.id')
            ->join('contracts', 'contracts.id', '=', 'physician_contracts.contract_id')
            ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('specialties', 'specialties.id', '=', 'physicians.specialty_id')
            ->join("practices", function ($join) {
                $join->on("physician_practices.practice_id", "=", "practices.id")
                    ->on("practices.id", "=", "physician_contracts.practice_id")
                    ->on("practices.hospital_id", "=", "agreements.hospital_id");
            })
            ->where('contracts.agreement_id', '=', $agreement_data->id)
            ->where('practices.id', '=', $practice_id)
            ->groupBy('physicians.id', 'contracts.id')
            ->orderBy('contract_types.name', 'asc')
            ->orderBy('practices.name', 'asc')
            ->orderBy('physicians.last_name', 'asc')
            ->orderBy('physicians.first_name', 'asc');


        $date_start_agreement = DB::table('agreements')->where("id","=",$agreement->id)->first();
        //print_r($date_start_agreement);die;
        $start_date_ytm = $date_start_agreement->start_date;
        if($end_date > $date_start_agreement->end_date)
        {
            $end_date = date('Y-m-d',strtotime($date_start_agreement->end_date));
        }

        $ts1 = strtotime($start_date_ytm);
        $ts2 = strtotime($end_date);

        $year1 = date('Y', $ts1);
        $year2 = date('Y', $ts2);

        $month1 = date('m', $ts1);
        $month2 = date('m', $ts2);

        $diff = (($year2 - $year1) * 12) + ($month2 - $month1) + 1;

        //$end_month = date('m',strtotime($end_date));

        //print_r($end_month);die;
        //print_r($diff);die;
        for($i=1;$i<=$diff;$i++)
        {
            //$start_month_date = $i."-".date('Y', strtotime($end_date));
            if($month1 > 12)
            {
                //echo "hi";
                $m = $month1 - (12*floor($month1/12));
                //echo $m;
                $y = $year1 + floor($month1/12);
            }
            else
            {
                $m = $month1;
                $y = $year1;
            }
            $month1++;

            //echo $month1."        ";
            $end_month_date = mysql_date(date('d-m-Y', strtotime("01-".$m."-".$y)));

            //$start_month_date = "01-".$i."-".date('Y', strtotime($end_date));
            //$first_date_month = mysql_date($start_month_date);
            //$first_date_month = mysql_date("01-".$end_month_date);
            //echo $end_month_date;
            $last_date_month = mysql_date(date('t-F-Y', strtotime($end_month_date)));

            $contract_month = months($agreement->start_date, $last_date_month);
            //echo $contract_month;

            //echo $last_date_month;die;

            $physicians = DB::table('physicians')->select('physicians.id')
                                                 ->join('physician_practices','physician_practices.physician_id','=','physicians.id')
                                                -> where('physician_practices.practice_id', '=', $practice_id)->get();
            //print_r($physicians);die;
            for($j = 0;$j<count($physicians);$j++)
            {
                $year_to_month_query[$i][$j] = DB::table('physician_logs')->select(
                    DB::raw("practices.name as practice_name"),
                    DB::raw("physicians.id as physician_id"),
                    DB::raw("concat(physicians.last_name, ', ', physicians.first_name) as physician_name"),
                    DB::raw("specialties.name as specialty_name"),
                    DB::raw("contracts.id as contract_id"),
                    DB::raw("contracts.contract_name_id as contract_name_id"),
                    DB::raw("contracts.contract_type_id as contract_type_id"),
                    DB::raw("contract_types.name as contract_name"),
                    DB::raw("contracts.min_hours as min_hours"),
                    DB::raw("contracts.max_hours as max_hours"),
                    DB::raw("contracts.expected_hours as expected_hours"),
                    DB::raw("contracts.rate as rate"),
                    DB::raw("contracts.weekday_rate as weekday_rate"),
                    DB::raw("contracts.weekend_rate as weekend_rate"),
                    DB::raw("contracts.holiday_rate as holiday_rate"),
                    DB::raw("'{$practice_id}' as practice_id"),
                    DB::raw("sum(physician_logs.duration) as worked_hours"),
                    DB::raw("'{$contract_month}' as contract_month"),
                    DB::raw("'{$contract_term}' as contract_term"),
                    DB::raw("'{$contract_month}' as log_range"),
                    DB::raw("'{$end_month_date}' as start_date_check"),
                    DB::raw("'{$last_date_month}' as end_date_check")
                )
                    ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                    ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                    ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                    ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
                    ->join('specialties', 'specialties.id', '=', 'physicians.specialty_id')
                    ->join('physician_practices','physician_practices.physician_id','=','physicians.id')
                    ->join("practices", function ($join) {
                        $join->on("physician_practices.practice_id", "=", "practices.id")
//                            ->on("practices.id", "=", "contracts.practice_id")
                            ->on("practices.hospital_id", "=", "agreements.hospital_id");
                    })
                    ->where('contracts.agreement_id', '=', $agreement_data->id)
                    ->where('practices.id', '=', $practice_id)
                    ->where('physicians.id', '=', $physicians[$j]->id)
                    ->where('physician_logs.practice_id', '=', $practice_id)
                    ->whereBetween('physician_logs.date',array($end_month_date,$last_date_month))
                    ->groupBy('physicians.id', 'contracts.id')
                    ->orderBy('contract_types.name', 'asc')
                    ->orderBy('practices.name', 'asc')
                    ->orderBy('physicians.last_name', 'asc')
                    ->orderBy('physicians.first_name', 'asc');

                $year_to_month_query2[$i][$j] = DB::table('physicians')->select(
                    DB::raw("practices.name as practice_name"),
                    DB::raw("physicians.id as physician_id"),
                    DB::raw("concat(physicians.last_name, ', ', physicians.first_name) as physician_name"),
                    DB::raw("specialties.name as specialty_name"),
                    DB::raw("contracts.id as contract_id"),
                    DB::raw("contracts.contract_name_id as contract_name_id"),
                    DB::raw("contracts.contract_type_id as contract_type_id"),
                    DB::raw("contract_types.name as contract_name"),
                    DB::raw("contracts.min_hours as min_hours"),
                    DB::raw("contracts.max_hours as max_hours"),
                    DB::raw("contracts.expected_hours as expected_hours"),
                    DB::raw("0 as worked_hours"),
                    DB::raw("contracts.rate as rate"),
                    DB::raw("contracts.weekday_rate as weekday_rate"),
                    DB::raw("contracts.weekend_rate as weekend_rate"),
                    DB::raw("contracts.holiday_rate as holiday_rate"),
                    DB::raw("'{$practice_id}' as practice_id"),
                    DB::raw("'{$contract_month}' as contract_month"),
                    DB::raw("'{$contract_term}' as contract_term"),
                    DB::raw("'{$contract_month}' as log_range"),
                    DB::raw("'{$end_month_date}' as start_date_check"),
                    DB::raw("'{$last_date_month}' as end_date_check")
                )
                    ->join('physician_contracts','physician_contracts.physician_id','=','physicians.id')
                    ->join('physician_practices','physician_practices.physician_id','=','physicians.id')
                    ->join('contracts', 'contracts.id', '=', 'physician_contracts.contract_id')
                    ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                    ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                    ->join('specialties', 'specialties.id', '=', 'physicians.specialty_id')
                    ->join("practices", function ($join) {
                        $join->on("physician_practices.practice_id", "=", "practices.id")
                            ->on("practices.id", "=", "contracts.practice_id")
                            ->on("practices.hospital_id", "=", "agreements.hospital_id");
                    })
                    ->where('contracts.agreement_id', '=', $agreement_data->id)
                    ->where('practices.id', '=', $practice_id)
                    ->where('physicians.id', '=', $physicians[$j]->id)
                    ->groupBy('physicians.id', 'contracts.id')
                    ->orderBy('contract_types.name', 'asc')
                    ->orderBy('practices.name', 'asc')
                    ->orderBy('physicians.last_name', 'asc')
                    ->orderBy('physicians.first_name', 'asc');

            }
        }
        $date_start_agreement = DB::table('agreements')->where("id","=",$agreement->id)->first();
        $start_date_ytm = $date_start_agreement->start_date;

        if($date_start_agreement->end_date > date('Y-m-t'))
        {
            $end_date = date('Y-m-t');
        }
        else
        {
            $end_date = mysql_date($date_start_agreement->end_date);
        }

        $ts1 = strtotime($start_date_ytm);
        $ts2 = strtotime($end_date);

        $year1 = date('Y', $ts1);
        $year2 = date('Y', $ts2);

        $month1 = date('m', $ts1);
        $month2 = date('m', $ts2);

        $diff = (($year2 - $year1) * 12) + ($month2 - $month1) + 1;


        //$end_month = date('m',strtotime($end_date));

        //print_r($end_month);die;
        $contract_month1 = months($agreement->start_date, $start_date) + 1;

        for($i=1;$i<=$diff;$i++)
        {
            //$start_month_date = $i."-".date('Y', strtotime($end_date));
            if($month1 > 12)
            {
                //echo "hi";
                $m = $month1 - (12*floor($month1/12));
                //echo $m;
                $y = $year1 + floor($month1/12);
            }
            else
            {
                $m = $month1;
                $y = $year1;
            }
            $month1++;

            //echo $month1."        ";
            $end_month_date = mysql_date(date('d-m-Y', strtotime("01-".$m."-".$y)));

            //$start_month_date = "01-".$i."-".date('Y', strtotime($end_date));
            //$first_date_month = mysql_date($start_month_date);
            //$first_date_month = mysql_date("01-".$end_month_date);
            //echo $end_month_date;die;
            $last_date_month = mysql_date(date('t-F-Y', strtotime($end_month_date)));
            $contract_month = months($agreement->start_date, $start_date)+1;
            
            //issue fixes : one to many physicians for practice report
           // $physicians = DB::table('physicians')->select('id')->where('practice_id', '=', $practice_id)->get();
            $physicians = DB::table('physicians')->select('physicians.id')
                                    ->join('physician_practices','physician_practices.physician_id','=','physicians.id')
                                    -> where('physician_practices.practice_id', '=', $practice_id)->get();

            $contract_month1 = months($agreement->start_date, $last_date_month);
            //print_r($physicians);die;
            for($j = 0;$j<count($physicians);$j++)
            {
                $year_to_date_query[$i][$j] = DB::table('physician_logs')->select(
                    DB::raw("practices.name as practice_name"),
                    DB::raw("physicians.id as physician_id"),
                    DB::raw("concat(physicians.last_name, ', ', physicians.first_name) as physician_name"),
                    DB::raw("specialties.name as specialty_name"),
                    DB::raw("contracts.id as contract_id"),
                    DB::raw("contracts.contract_name_id as contract_name_id"),
                    DB::raw("contracts.contract_type_id as contract_type_id"),
                    DB::raw("contract_types.name as contract_name"),
                    DB::raw("contracts.min_hours as min_hours"),
                    DB::raw("contracts.max_hours as max_hours"),
                    DB::raw("contracts.expected_hours as expected_hours"),
                    DB::raw("contracts.rate as rate"),
                    DB::raw("contracts.weekday_rate as weekday_rate"),
                    DB::raw("contracts.weekend_rate as weekend_rate"),
                    DB::raw("contracts.holiday_rate as holiday_rate"),
                    DB::raw("'{$practice_id}' as practice_id"),
                    DB::raw("sum(physician_logs.duration) as worked_hours"),
                    DB::raw("'{$contract_month1}' as contract_month"),
                    DB::raw("'{$contract_month}' as contract_month1"),
                    DB::raw("'{$contract_term}' as contract_term"),
                    DB::raw("'{$contract_month}' as log_range"),
                    DB::raw("'{$end_month_date}' as start_date_check"),
                    DB::raw("'{$last_date_month}' as end_date_check")
                )
                    ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                    ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                    ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                    ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
                    ->join('specialties', 'specialties.id', '=', 'physicians.specialty_id')
                    ->join('physician_practices','physician_practices.physician_id','=','physicians.id')
                    ->join("practices", function ($join) {
                        $join->on("physician_practices.practice_id", "=", "practices.id")
//                            ->on("practices.id", "=", "contracts.practice_id")
                            ->on("practices.hospital_id", "=", "agreements.hospital_id");
                    })
                    ->where('contracts.agreement_id', '=', $agreement_data->id)
                    ->where('practices.id', '=', $practice_id)
                    ->where('physicians.id', '=', $physicians[$j]->id)
                    ->where('physician_logs.practice_id', '=', $practice_id)
                    ->whereBetween('physician_logs.date',array($end_month_date,$last_date_month))
                    ->groupBy('physicians.id', 'contracts.id')
                    ->orderBy('contract_types.name', 'asc')
                    ->orderBy('practices.name', 'asc')
                    ->orderBy('physicians.last_name', 'asc')
                    ->orderBy('physicians.first_name', 'asc');

                $year_to_date_query2[$i][$j] = DB::table('physicians')->select(
                    DB::raw("practices.name as practice_name"),
                    DB::raw("physicians.id as physician_id"),
                    DB::raw("concat(physicians.last_name, ', ', physicians.first_name) as physician_name"),
                    DB::raw("specialties.name as specialty_name"),
                    DB::raw("contracts.id as contract_id"),
                    DB::raw("contracts.contract_name_id as contract_name_id"),
                    DB::raw("contracts.contract_type_id as contract_type_id"),
                    DB::raw("contract_types.name as contract_name"),
                    DB::raw("contracts.min_hours as min_hours"),
                    DB::raw("contracts.max_hours as max_hours"),
                    DB::raw("contracts.expected_hours as expected_hours"),
                    DB::raw("contracts.rate as rate"),
                    DB::raw("contracts.weekday_rate as weekday_rate"),
                    DB::raw("contracts.weekend_rate as weekend_rate"),
                    DB::raw("contracts.holiday_rate as holiday_rate"),
                    DB::raw("'{$practice_id}' as practice_id"),
                    DB::raw("0 as worked_hours"),
                    DB::raw("'{$contract_month1}' as contract_month"),
                    DB::raw("'{$contract_month}' as contract_month1"),
                    DB::raw("'{$contract_term}' as contract_term"),
                    DB::raw("'{$contract_month}' as log_range"),
                    DB::raw("'{$end_month_date}' as start_date_check"),
                    DB::raw("'{$last_date_month}' as end_date_check")
                )
                    ->join('physician_contracts','physician_contracts.physician_id','=','physicians.id')
                    ->join('physician_practices','physician_practices.physician_id','=','physicians.id')
                    ->join('contracts', 'contracts.id', '=', 'physician_contracts.contract_id')
                    ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                    ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                    ->join('specialties', 'specialties.id', '=', 'physicians.specialty_id')
                    ->join("practices", function ($join) {
                        $join->on("physician_practices.practice_id", "=", "practices.id")
                            ->on("practices.id", "=", "contracts.practice_id")
                            ->on("practices.hospital_id", "=", "agreements.hospital_id");
                    })
                    ->where('physicians.id', '=', $physicians[$j]->id)
                    ->where('contracts.agreement_id', '=', $agreement_data->id)
                    ->where('practices.id', '=', $practice_id)
                    ->groupBy('physicians.id', 'contracts.id')
                    ->orderBy('contract_types.name', 'asc')
                    ->orderBy('practices.name', 'asc')
                    ->orderBy('physicians.last_name', 'asc')
                    ->orderBy('physicians.first_name', 'asc');
            }
        }
        if ($contract_type_id != -1) {
            $period_query->where('contracts.contract_type_id', '=', $contract_type_id);
            $period_query2->where('contracts.contract_type_id', '=', $contract_type_id);
            foreach($year_to_month_query as $year_to_month_query_arr)
            {
                foreach($year_to_month_query_arr as $year_to_month_query_arr3)
                {
                    $year_to_month_query_arr3->where('contracts.contract_type_id', '=', $contract_type_id);
                    //print_r($results->year_to_date);die;
                    //$temp++;
                }
            }
            foreach($year_to_month_query2 as $year_to_month_query_arr2)
            {
                foreach($year_to_month_query_arr2 as $year_to_month_query_arr3)
                {
                    $year_to_month_query_arr3->where('contracts.contract_type_id', '=', $contract_type_id);
                    //print_r($results->year_to_date);die;
                    //$temp++;
                }
            }
            foreach($year_to_date_query as $year_to_date_query_arr2)
            {
                foreach($year_to_date_query_arr2 as $year_to_date_query_arr3)
                {
                    $year_to_date_query_arr3->where('contracts.contract_type_id', '=', $contract_type_id);
                    //print_r($results->year_to_date);die;
                    //$temp++;
                }
            }

            foreach($year_to_date_query2 as $year_to_date_query_arr2)
            {
                foreach($year_to_date_query_arr2 as $year_to_date_query_arr3)
                {
                    $year_to_date_query_arr3->where('contracts.contract_type_id', '=', $contract_type_id);
                }
                //print_r($results->year_to_date);die;
                //$temp++;
            }
        }

        $results = new StdClass;
        $results->period = $period_query->get();
        //Practice and physician report shows empty data.(  period sheet data:empty) by 1254
        if($results->period)
        {$results->period = $period_query2->get();
        }
        //print_r($results->period);die;
        $results->year_to_month = array();
        $temp = 0;
        foreach($year_to_month_query as $year_to_month_query_arr)
        {
            $temp1 = 0;
            foreach ($year_to_month_query_arr as $year_to_date_query_arr2) {
                $results->year_to_month[$temp][$temp1] = $year_to_date_query_arr2->first();
                if(empty($results->year_to_month[$temp][$temp1]))
                {
                    $results->year_to_month[$temp][$temp1] = $year_to_month_query2[$temp+1][$temp1]->first();
                }
                $temp1++;
            }
            //print_r($results->year_to_month[$temp]);die;
            $temp++;
        }
//echo "<pre>";
//print_r($results->year_to_month);die;
        $results->year_to_date = array();
        $temp = 0;
        foreach($year_to_date_query as $year_to_date_query_arr)
        {
            $temp1 = 0;
            foreach ($year_to_date_query_arr as $year_to_date_query_arr2) {

                $results->year_to_date[$temp][$temp1] = $year_to_date_query_arr2->first();
                //print_r($results->year_to_date);die;
                if(empty($results->year_to_date[$temp][$temp1]))
                {
                    $results->year_to_date[$temp][$temp1] = $year_to_date_query2[$temp+1][$temp1]->first();
                }
                $temp1++;
            }
            $temp++;
        }
        //echo "<pre>";
        //print_r($results->year_to_date);die;
        foreach ($results->period as $result) {
            if ($result->contract_name_id) {
                $result->contract_name = ContractName::findOrFail($result->contract_name_id)->name;
            }

            $result->paid = DB::table("physician_payments")
                ->where("physician_payments.physician_id", "=", $result->physician_id)
                ->whereBetween("physician_payments.month", [$agreement_data->start_month, $agreement_data->end_month])
                ->sum("amount");
        }

        foreach ($results->year_to_month as $result1) {
            if($result1){
                foreach ($result1 as $result) {
                    if($result){
                        if ($result->contract_name_id) {
                            $result->contract_name = ContractName::findOrFail($result->contract_name_id)->name;
                        }

                        $result->paid = DB::table("physician_payments")
                            ->where("physician_payments.physician_id", "=", $result->physician_id)
                            ->where("physician_payments.agreement_id", "=", $agreement_data->id)
                            ->sum("amount");
                    }

                }
            }
        }


        foreach ($results->year_to_date as $result1) {
            if($result1){
                foreach ($result1 as $result) {
                    if($result) {
                        if ($result->contract_name_id) {
                            $result->contract_name = ContractName::findOrFail($result->contract_name_id)->name;
                        }

                        $result->paid = DB::table("physician_payments")
                            ->where("physician_payments.physician_id", "=", $result->physician_id)
                            ->where("physician_payments.agreement_id", "=", $agreement_data->id)
                            ->sum("amount");
                    }
                }
            }
        }

        return $results;
    }

    protected function resetMainTotal($totals_main)
    {
        $totals_main->physician_name = null;
        $totals_main->contract_name = null;
        $totals_main->physician_name_1 = null;
        $totals_main->contract_name_1 = null;
        $totals_main->expected_hours = 0.0;
        $totals_main->expected_hours_ytd = 0.0;
        $totals_main->worked_hours = 0.0;
        $totals_main->days_on_call = 0.0;
        $totals_main->per_hour = 0.0;
        $totals_main->rate = 0.0;
        $totals_main->amount_paid = 0.0;
        $totals_main->expected_payment = 0.0;
        $totals_main->contract_term = 0;
        $totals_main->contract_month = 0;
        $totals_main->contract_month1 = 0;
    }

    protected function parseArguments() {
        $result = new StdClass;
        $result->practice_id = $this->argument('practice');
        $result->contract_type = $this->argument('contract_type');
        $result->agreements = parent::parseArguments();
        $result->start_date = null;
        $result->end_date = null;
        $result->localtimeZone = $this->argument('localtimeZone');
       
        foreach ($result->agreements as $agreement) {
            if ($result->start_date == null) $result->start_date = Carbon::parse($agreement->start_date);
            if ($result->end_date == null) $result->end_date = Carbon::parse($agreement->end_date);

            if (compare_date($result->start_date, $agreement->start_date) < 0) {
                $result->start_date = Carbon::parse($agreement->start_date);
            }
            if (compare_date($result->end_date, $agreement->end_date) > 0) {
                $result->end_date = Carbon::parse($agreement->end_date);
            }
        }

        return $result;
    }

    protected function getArguments() {
        return [
            ["practice", InputArgument::REQUIRED, "The practice ID."],
            ["contract_type", InputArgument::REQUIRED, "The contract type."],
            ["agreements", InputArgument::REQUIRED, "The hospital agreement IDs."],
            ["months", InputArgument::REQUIRED, "The agreement months."],
            ["localtimeZone", InputArgument::REQUIRED, "The agreement localtimeZone."]
        ];
    }
}
