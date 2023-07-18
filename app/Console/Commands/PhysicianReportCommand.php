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
use DateTime;
use StdClass;
use App\Physician;
use App\Agreement;
use App\ContractName;
use App\PhysicianReport;
use Illuminate\Support\Facades\Log;
use function App\Start\physician_report_path;

class PhysicianReportCommand extends ReportingCOmmand
{
    protected $name = "reports:physician";
    protected $description = "Generates a DYNAFIOS physician report.";

    private $cell_style = [
        'borders' => [
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

    public function __invoke()
    {
        $arguments = $this->parseArguments();

        $now = Carbon::now();
		$timezone = $now->timezone->getName();
		$timestamp = format_date((exec('time /T')), "h:i A");

        $physician = Physician::findOrFail($arguments->physician_id);
        // $workbook = $this->loadTemplate('physician_report.xlsx');
        //one-many physician issue fixed for showing old physician report for existing hospital by 1254
        $practice_id = $arguments->practice_id;
        //end one-many physician issue fixed for showing old physician report for existing hospital by 1254
        $reader = IOFactory::createReader("Xlsx");//Load template using phpSpreadsheet
		$workbook = $reader->load(storage_path()."/reports/templates/physician_report.xlsx");

        $report_header = '';
        $report_header .= strtoupper("{$physician->first_name} {$physician->last_name}") . "\n";
        $report_header .= "Period Report\n";
        $report_header .= "Period: " . $arguments->start_date->format("m/d/Y") . " - " . $arguments->end_date->format("m/d/Y") . "\n";
        //$report_header .= "Run Date: " . with(new DateTime('now'))->format("m/d/Y");
        $report_header .= "Run Date: " . with($arguments->localtimeZone);


        $report_header_ytm = '';
        $report_header_ytm .= strtoupper("{$physician->first_name} {$physician->last_name}") . "\n";
        $report_header_ytm .= "Contract Year to Prior Month Report\n";
        $report_header_ytm .= "Period: " . $arguments->start_date->format("m/d/Y") . " - " . $arguments->end_date->format("m/d/Y") . "\n";
        // $report_header_ytm .= "Run Date: " . with(new DateTime('now'))->format("m/d/Y");
        $report_header_ytm .= "Run Date: " . with($arguments->localtimeZone);


        $report_header_ytd = '';
        $report_header_ytd .= strtoupper("{$physician->first_name} {$physician->last_name}") . "\n";
        $report_header_ytd .= "Contract Year To Date Report\n";
        $report_header_ytd .= "Period: " . $arguments->start_date->format("m/d/Y") . " - " . $arguments->end_date->format("m/d/Y") . "\n";
        // $report_header_ytd .= "Run Date: " . with(new DateTime('now'))->format("m/d/Y");
        $report_header_ytd .= "Run Date: " . with($arguments->localtimeZone);


        $workbook->setActiveSheetIndex(0)->setCellValue('B2', $report_header);
        $workbook->setActiveSheetIndex(1)->setCellValue('B2', $report_header_ytm);
        $workbook->setActiveSheetIndex(2)->setCellValue('B2', $report_header_ytd);

        $period_index = 4;
        $ytm_index = 4;
        $ytd_index = 4;

        foreach ($arguments->agreements as $agreement) {
            $contracts = $this->queryContracts($physician->id, $agreement, $agreement->start_date, $agreement->end_date, $arguments->contract_type);
            $physician_id = array($physician->id);
            $period_index = $this->writeData($workbook, 0, $period_index, $contracts->period,$agreement->start_date, $agreement->end_date);
            $ytm_index = $this->writeDataYTD($workbook, 1, $ytm_index, $contracts->year_to_month,$agreement->start_date,$physician_id, $agreement->end_date);
            $ytd_index = $this->writeDataYTD($workbook, 2, $ytd_index, $contracts->year_to_date,$agreement->start_date,$physician_id, $agreement->end_date);
        }
        //die;
        $report_path = physician_report_path($physician);
        // $report_filename = "report_" . date('mdYhis') . ".xlsx";
        $timeZone = str_replace(' ','_', $arguments->localtimeZone);
        $timeZone = str_replace('/','', $timeZone);
        $timeZone = str_replace(':','', $timeZone);
        // $report_filename = "report_" . $physician->first_name . "_" . $physician->last_name . "_"  . $timeZone . ".xlsx";
        $report_filename = "physicianLogReport_" . $physician->first_name . "_" . $physician->last_name . "_"  . $timeZone . ".xlsx";
       
        
        if (!file_exists($report_path)) {
            mkdir($report_path, 0777, true);
        }

        $writer = IOFactory::createWriter($workbook, 'Xlsx');
        $writer->save("{$report_path}/{$report_filename}");

        
        $physician_report = new PhysicianReport;
        $physician_report->physician_id = $physician->id;
        //one-many physician issue fixed for showing old physician report for existing hospital by 1254 
        $physician_report->practice_id = $practice_id;
        //end - one-many physician issue fixed for showing old physician report for existing hospital by 1254
        $physician_report->filename = $report_filename;
        $physician_report->save();


        $this->success('physicians.generate_report_success', $physician_report->id);
    }

    protected function writeData($workbook, $sheetIndex, $index, $contracts,$startDate,$end_date)
    {
       
        $totals = new StdClass;
        $totals->contract_name = null;
        $totals->practice_name = null;
        $totals->physician_name = null;
        $totals->physician_name_1 = null;
        $totals->expected_hours = 0.0;
        $totals->expected_hours_ytd = 0.0;
        $totals->worked_hours = 0.0;
        $totals->worked_hours_main = 0.0;
        $totals->days_on_call = 0.0;
        $totals->rate = 0.0;
        $totals->actual_rate = 0.0;
        $totals->paid = 0.0;
        $totals->amount = 0.0;
        $totals->amount_paid = 0.0;
        $totals->expected_payment = 0.0;
        $totals->amount_main = 0.0;
        $totals->amount_monthly = 0.0;
        $totals->count = 0.0;
        $totals->has_override = false;
        $totals->worked_hours = 0;
        $totals->contract_month = 0;
        $totals->contract_term = 0;

        $current_row = $index;
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
            $end_month_date = mysql_date(date("t-m-Y",strtotime($startDate)));
            $monthArr = explode("-",$month);
            //$month = $monthArr[1];
            //echo $contract->physician_id;


            /*$amount = DB::table('amount_paid')
                ->select(DB::raw("sum(amount_paid.amountPaid) as amountPaid"))
            ->where('start_date', '<=' , $month)
            ->where('end_date', '>=' , $month)
            ->where('physician_id','=',$contract->physician_id )
            ->where('contract_id','=',$contract->contract_id )
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();*/
            //$queries = DB::getQueryLog();
            //$last_query = end($queries);
            //print_r($last_query);die;
            if(isset($amount->amountPaid))
            {
                $formula->amount_paid = $amount->amountPaid;
            }
            else
            {
                $formula->amount_paid = 0;
            }
            $contract->expected_payment = $contract->expected_hours * $contract->rate;
            if ($sheetIndex == 0) {
                $current_row++;
                if($contract->worked_hours !=0)
                    $pmt_status = (($formula->amount_paid)/$contract->worked_hours) <= $contract->rate;
                else
                    $pmt_status = true;
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->setCellValue("B{$current_row}", $pmt_status?'Y':'N')
                    ->setCellValue("C{$current_row}", $contract->physician_name)
                    ->setCellValue("D{$current_row}", $contract->contract_name)
                    //->setCellValue("D{$current_row}", $contract->min_hours)
                    //->setCellValue("E{$current_row}", $contract->max_hours)
                    ->setCellValue("E{$current_row}", $contract->expected_hours)
                    ->setCellValue("F{$current_row}", $contract->expected_payment)
                    ->setCellValue("G{$current_row}", $contract->worked_hours)
                    ->setCellValue("H{$current_row}", $formula->amount_paid)
                    ->setCellValue("I{$current_row}", $contract->worked_hours?$formula->amount_paid/$contract->worked_hours:0)
                    ->setCellValue("J{$current_row}", $contract->rate)
                    ->setCellValue("K{$current_row}", $formula->days_on_call);

                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("B{$current_row}:K{$current_row}")->applyFromArray($this->cell_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("G{$current_row}:K{$current_row}")->applyFromArray($this->shaded_style);

                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("B{$current_row}")->applyFromArray($pmt_status ? $this->green_style : $this->red_style);
            } else {
                $current_row++;
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->setCellValue("B{$current_row}", $contract->physician_name)
                    ->setCellValue("C{$current_row}", $contract->contract_name)
                    ->setCellValue("D{$current_row}", $contract->expected_hours)
                    ->setCellValue("E{$current_row}", $contract->worked_hours)
                    ->setCellValue("F{$current_row}", $formula->days_on_call)
                    ->setCellValue("G{$current_row}", $formula->actual_rate)
                    ->setCellValue("H{$current_row}", $contract->paid)
                    ->setCellValue("I{$current_row}", $formula->amount)
                    ->setCellValue("J{$current_row}", $contract->contract_month)
                    ->setCellValue("K{$current_row}", $contract->contract_term);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("B{$current_row}:K{$current_row}")->applyFromArray($this->cell_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("H{$current_row}:K{$current_row}")->applyFromArray($this->shaded_style);
            }
        }

        return $current_row;
    }

    protected function writeDataYTD($workbook, $sheetIndex, $index, $contracts,$startDate,$physicians,$end_date)
    {

        $current_row = $index;

        $year = date('Y',strtotime($startDate));
        $current_practice_name = null;
        $previous_practice_name = null;
        $previous_contract_name = null;
        $current_contract_name = null;
        $current_row = $index;

        $totals = new StdClass;
        $totals->contract_name = null;
        $totals->practice_name = null;
        $totals->physician_name = null;
        $totals->physician_name_1 = null;
        $totals->expected_hours = 0.0;
        $totals->expected_hours_ytd = 0.0;
        $totals->worked_hours = 0.0;
        $totals->worked_hours_main = 0.0;
        $totals->days_on_call = 0.0;
        $totals->rate = 0.0;
        $totals->actual_rate = 0.0;
        $totals->paid = 0.0;
        $totals->amount = 0.0;
        $totals->amount_paid = 0.0;
        $totals->expected_payment = 0.0;
        $totals->amount_main = 0.0;
        $totals->amount_monthly = 0.0;
        $totals->count = 0.0;
        $totals->has_override = false;
        $totals->worked_hours = 0;
        $totals->contract_month = 0;
        $totals->contract_term = 0;
        $totals->expected_hours_ytd = 0.0;
        //$month_PMT = array("Ignore","Ignore","Ignore","Ignore","Ignore","apply","apply","apply","apply","apply","apply","apply");
        //print_r($contracts);die;
        $year = date('Y',strtotime($startDate));
        for($i = 0;$i<count($physicians);$i++)
        {
            $physician_id = $physicians[$i];
            $count1 = count($contracts);
            for($j=$count1-1;$j>= 0;$j--)
            {
                foreach ($contracts[$j] as $contract) {
                    //print_r($contract);die;

                    if($contract->physician_id == $physician_id )
                    {
                        if ($index == 0) {
                            $totals->physician_name = $contract->physician_name;
                            $totals->physician_name_1 = $contract->physician_name;
                        }
                        if($totals->physician_name != $contract->physician_name)
                        {

                            $totals->physician_name = $contract->physician_name;
                            $totals->physician_name_1 = $contract->physician_name;
                        }
                        else
                        {
                            $totals->physician_name_1 = "";
                        }

                        $contract->month_end_date = mysql_date(date($end_date));

                        $amount = DB::table('amount_paid')
                            ->where('start_date', '<=', mysql_date(date($contract->start_date_check)))
                            ->where('end_date', '>=', mysql_date(date($contract->start_date_check)))
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
                        $start_month_date = $contract->start_date_check;
                        $month = mysql_date(date($startDate));
                        $end_month_date = $contract->end_date_check;
                        //$month = $monthArr[1];
                        //echo $contract->physician_id;
                        /*$amount = DB::table('amount_paid')
                            ->select(DB::raw("sum(amount_paid.amountPaid) as amountPaid"))
                        ->where('start_date', '<=' , $month)
                        ->where('end_date', '>=' , $month)
                        ->where('physician_id','=',$contract->physician_id )
                        ->where('contract_id','=',$contract->contract_id )
                        ->orderBy('created_at', 'desc')
                        ->orderBy('id', 'desc')
                        ->first();*/
                        //$queries = DB::getQueryLog();
                        //$last_query = end($queries);
                        //print_r($last_query);die;
                        if(isset($amount->amountPaid))
                        {
                            $formula->amount_paid = $amount->amountPaid;
                        }
                        else
                        {
                            $formula->amount_paid = 0;
                        }

                        if($contract->worked_hours !=0)
                            $pmt_status = (($formula->amount_paid)/$contract->worked_hours) <= $contract->rate;
                        else
                            $pmt_status = true;
                        $contract->expected_payment = $contract->expected_hours * $contract->rate;
                        $totals->contract_name = $contract->contract_name;

                        if($sheetIndex == 1)
                        {
                            $current_row++;
                            $formula->days_on_call == 0 ? $formula->days_on_call1 = "N/A":$formula->days_on_call1 = $formula->days_on_call;

                            $workbook->setActiveSheetIndex($sheetIndex)
                                //->setCellValue("B{$current_row}", $pmt_status?'Y':'N')
                                ->setCellValue("B{$current_row}", $formula->payment_status?'Y':'N')
                                ->setCellValue("C{$current_row}", $totals->physician_name_1)
                                ->setCellValue("D{$current_row}", $contract->contract_name)
                                ->setCellValue("E{$current_row}", $contract->expected_hours)
                                ->setCellValue("F{$current_row}", $contract->expected_payment)
                                ->setCellValue("G{$current_row}", $contract->expected_hours * $contract->contract_month)
                                ->setCellValue("H{$current_row}", $contract->worked_hours)
                                ->setCellValue("I{$current_row}", $formula->amount_paid)
                                ->setCellValue("J{$current_row}", $contract->worked_hours?$formula->amount_paid/$contract->worked_hours:0)
                                ->setCellValue("K{$current_row}", $contract->contract_month)
                                ->setCellValue("L{$current_row}", $contract->contract_term)
                                ->setCellValue("M{$current_row}", $formula->days_on_call1);
                        }
                        $totals->rate = $contract->rate;
                        $totals->worked_hours = $totals->worked_hours + $contract->worked_hours;
                        $totals->expected_hours = $contract->expected_hours;
                        $totals->expected_hours_ytd = $contract->expected_hours * $contract->contract_month;
                        $totals->expected_payment = $contract->expected_hours * $contract->rate * $contract->contract_month;
                        $totals->amount_paid += $formula->amount_paid;
                        /*$signature = DB::table('signature')
                        ->where('physician_id','=',$contract->physician_id )
               ->whereBetween("date",array($start_month_date,$end_month_date))
                        ->first();
                        //$queries = DB::getQueryLog();
                        //$last_query = end($queries);
                        //print_r($last_query);die;
                        $totals->contract_term = $contract->contract_term;
                        $totals->contract_month = $contract->contract_month1;
                        $totals->expected_hours = $contract->expected_hours;
                        $totals->worked_hours += $contract->worked_hours;

                        $totals->actual_rate += $contract->worked_hours !=0?$formula->amount_paid/$contract->worked_hours:0;
                        $totals->amount_paid += $formula->amount_paid;
                        $totals->expected_payment += $contract->expected_payment;
                        $totals->days_on_call += $formula->days_on_call;
                        if(isset($signature->signature_path) && $sheetIndex == 1)
                        {
                            $data = "data:image/png;base64,".$signature->signature_path;
                            list($type, $data) = explode(';', $data);
                            list(, $data)      = explode(',', $data);

                            $data = base64_decode($data);
                            file_put_contents(storage_path()."/image.png", $data);
                           $objDrawingPType = new PHPExcel_Worksheet_Drawing();
                            $objDrawingPType->setWorksheet($workbook->setActiveSheetIndex($sheetIndex));
                            $objDrawingPType->setName("Signature");
                            $objDrawingPType->setPath(storage_path()."/image.png");
                            $objDrawingPType->setCoordinates("C".$current_row);
                            $objDrawingPType->setOffsetX(1);
                            $objDrawingPType->setOffsetY(5);
                            $objDrawingPType->setWidthAndHeight(148,74);
                            $objDrawingPType->setResizeProportional(true);
                             $workbook->setActiveSheetIndex($sheetIndex)
                                ->getColumnDimension('C')
                                ->setWidth(26);
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getRowDimension($current_row)
                                ->setRowHeight(70);
                        }*/
                        if($sheetIndex == 1)
                        {
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->cell_style);
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("H{$current_row}:M{$current_row}")->applyFromArray($this->shaded_style);
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                            $workbook->setActiveSheetIndex($sheetIndex)
                                //->getStyle("B{$current_row}")->applyFromArray($pmt_status ? $this->green_style : $this->red_style);
                                ->getStyle("B{$current_row}")->applyFromArray($formula->payment_status ? $this->green_style : $this->red_style);
                        }
                    }
                }
            }

            if($sheetIndex == 1)
            {

                if($totals->worked_hours !=0)
                    $pmt_status = (($totals->amount_paid)/$totals->worked_hours) <= $totals->rate;
                else
                    $pmt_status = true;
                $current_row++;
                $totals->days_on_call == 0 ? $totals->days_on_call = "N/A":"";
                $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("C{$current_row}:E{$current_row}")
                    ->setCellValue("B{$current_row}", $pmt_status?'Y':'N')
                    ->setCellValue("C{$current_row}", $totals->physician_name)
                    ->setCellValue("E{$current_row}", "-")
                    ->setCellValue("F{$current_row}", $totals->expected_payment)
                    ->setCellValue("G{$current_row}", $totals->expected_hours_ytd)
                    ->setCellValue("H{$current_row}", $totals->worked_hours)
                    ->setCellValue("I{$current_row}", $totals->amount_paid)
                    ->setCellValue("J{$current_row}", $totals->worked_hours?$totals->amount_paid/$totals->worked_hours:0)
                    ->setCellValue("K{$current_row}", "-")
                    ->setCellValue("L{$current_row}", "-")
                    ->setCellValue("M{$current_row}", "-");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->total_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("B{$current_row}:M{$current_row}")->getFont()->setBold(true);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("B{$current_row}")->applyFromArray($pmt_status ? $this->green_style : $this->red_style);
            }
            if($sheetIndex == 2)
            {
                if($totals->worked_hours !=0)
                    $pmt_status = (($totals->amount_paid)/$totals->worked_hours) <= $totals->rate;
                else
                    $pmt_status = true;
                $current_row++;
                $totals->days_on_call == 0 ? $totals->days_on_call = "N/A":"";
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->setCellValue("B{$current_row}", $pmt_status?'Y':'N')
                    ->setCellValue("C{$current_row}", $totals->physician_name)
                    ->setCellValue("D{$current_row}", $totals->contract_name)
                    ->setCellValue("E{$current_row}", $totals->expected_hours)
                    ->setCellValue("F{$current_row}", $totals->expected_payment)
                    ->setCellValue("G{$current_row}", $totals->expected_hours_ytd)
                    ->setCellValue("H{$current_row}", $totals->worked_hours)
                    ->setCellValue("I{$current_row}", $totals->amount_paid)
                    ->setCellValue("J{$current_row}", $totals->worked_hours?$totals->amount_paid/$totals->worked_hours:0)
                    ->setCellValue("K{$current_row}", $totals->contract_month)
                    ->setCellValue("L{$current_row}", $totals->contract_term)
                    ->setCellValue("M{$current_row}", $totals->days_on_call);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->cell_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("G{$current_row}:M{$current_row}")->applyFromArray($this->shaded_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("B{$current_row}")->applyFromArray($pmt_status ? $this->green_style : $this->red_style);
            }
        }
        return $current_row;
    }

    protected function queryContracts($physician_id, $agreement_data, $start_date, $end_date, $contract_type_id)
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
       
        $period_query = DB::table('physician_logs')->select(
            DB::raw("practices.name as practice_name"),
            DB::raw("practices.id as practice_id"),
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
                    ->on("practices.id", "=", "physician_logs.practice_id")
                    ->on("practices.hospital_id", "=", "agreements.hospital_id");
            })
            ->where('contracts.agreement_id', '=', $agreement->id)
            ->where('physicians.id', '=', $physician_id)
            ->whereBetween('physician_logs.date', [$start_date, $end_date])
            ->groupBy('physicians.id', 'contracts.id')
            ->orderBy('contract_types.name', 'asc')
            ->orderBy('practices.name', 'asc')
            ->orderBy('physicians.last_name', 'asc')
            ->orderBy('physicians.first_name', 'asc');

        $period_query2 = DB::table('physicians')->select(
            DB::raw("practices.name as practice_name"),
            DB::raw("practices.id as practice_id"),
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
            DB::raw("0 as worked_hours"),
            DB::raw("'{$contract_month}' as contract_month"),
            DB::raw("'{$contract_term}' as contract_term"),
            DB::raw("'{$log_range}' as log_range")
        )
            ->join('physician_contracts', 'physician_contracts.physician_id', '=', 'physicians.id')
            ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
            ->join('contracts', 'contracts.id', '=', 'physician_contracts.contract_id')
            ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('specialties', 'specialties.id', '=', 'physicians.specialty_id')
            ->join("practices", function ($join) {
                $join->on("physician_practices.practice_id", "=", "practices.id")
                    ->on("practices.id", "=", "physician_contracts.practice_id")
                    ->on("practices.hospital_id", "=", "agreements.hospital_id");
            })
            ->where('contracts.agreement_id', '=', $agreement->id)
            ->where('physicians.id', '=', $physician_id)
            ->groupBy('physicians.id', 'contracts.id')
            ->orderBy('contract_types.name', 'asc')
            ->orderBy('practices.name', 'asc')
            ->orderBy('physicians.last_name', 'asc')
            ->orderBy('physicians.first_name', 'asc');

        $date_start_agreement = DB::table('agreements')->where("id","=",$agreement->id)->first();
        //print_r($date_start_agreement);die;
        $start_date_ytm = $date_start_agreement->start_date;
        //echo $end_date;die;
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
        $contract_month = months($agreement->start_date, $start_date) + 1;
        //$end_month = date('m',strtotime($end_date));

        //print_r($end_month);die;
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


            $contract_month1 = months($agreement->start_date, $last_date_month);
            //echo $contract_month;
            //echo $last_date_month;die;
            $year_to_month_query[$i] = DB::table('physician_logs')->select(
                DB::raw("practices.name as practice_name"),
                DB::raw("practices.id as practice_id"),
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
                DB::raw("sum(physician_logs.duration) as worked_hours"),
                DB::raw("'{$contract_month}' as contract_month1"),
                DB::raw("'{$contract_month1}' as contract_month"),
                DB::raw("'{$contract_term}' as contract_term"),
                DB::raw("'{$contract_month}' as log_range"),
                DB::raw("'{$end_month_date}' as start_date_check"),
                DB::raw("'{$last_date_month}' as end_date_check")
            )
                ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                ->join('physicians', 'physicians.id', '=', 'contracts.physician_id')
                ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
                ->join('specialties', 'specialties.id', '=', 'physicians.specialty_id')
                ->join("practices", function ($join) {
                    $join->on("physician_practices.practice_id", "=", "practices.id")
                        ->on("practices.id", "=", "physician_logs.practice_id")
                        ->on("practices.hospital_id", "=", "agreements.hospital_id");
                })
                ->where('contracts.agreement_id', '=', $agreement->id)
                ->where('physicians.id', '=', $physician_id)
                ->whereBetween('physician_logs.date',array($end_month_date,$last_date_month))
                ->groupBy('physicians.id', 'contracts.id')
                ->orderBy('contract_types.name', 'asc')
                ->orderBy('practices.name', 'asc')
                ->orderBy('physicians.last_name', 'asc')
                ->orderBy('physicians.first_name', 'asc');

            $year_to_month_query2[$i] = DB::table('physicians')->select(
                DB::raw("practices.name as practice_name"),
                DB::raw("practices.id as practice_id"),
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
                DB::raw("0 as worked_hours"),
                DB::raw("'{$contract_month}' as contract_month1"),
                DB::raw("'{$contract_month1}' as contract_month"),
                DB::raw("'{$contract_term}' as contract_term"),
                DB::raw("'{$contract_month}' as log_range"),
                DB::raw("'{$end_month_date}' as start_date_check"),
                DB::raw("'{$last_date_month}' as end_date_check")
            )
                ->join('physician_contracts', 'physician_contracts.physician_id', '=', 'physicians.id')
                ->join('contracts', 'physician_contracts.physician_id', '=', 'physicians.id')
                ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
                ->join('specialties', 'specialties.id', '=', 'physicians.specialty_id')
                ->join("practices", function ($join) {
                    $join->on("physician_practices.practice_id", "=", "practices.id")
                        ->on("practices.id", "=", "physician_contracts.practice_id")
                        ->on("practices.hospital_id", "=", "agreements.hospital_id");
                })
                ->where('contracts.agreement_id', '=', $agreement->id)
                ->where('physicians.id', '=', $physician_id)
                ->groupBy('physicians.id', 'contracts.id')
                ->orderBy('contract_types.name', 'asc')
                ->orderBy('practices.name', 'asc')
                ->orderBy('physicians.last_name', 'asc')
                ->orderBy('physicians.first_name', 'asc');
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
        $contract_month = months($agreement->start_date, $start_date)+1;
        //$end_month = date('m',strtotime($end_date));

        //print_r($end_month);die;
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
            $contract_month1 = months($agreement->start_date, $last_date_month);
            $year_to_date_query[$i] = DB::table('physician_logs')->select(
                DB::raw("practices.name as practice_name"),
                DB::raw("practices.id as practice_id"),
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
                DB::raw("sum(physician_logs.duration) as worked_hours"),
                DB::raw("'{$contract_month}' as contract_month1"),
                DB::raw("'{$contract_month1}' as contract_month"),
                DB::raw("'{$contract_term}' as contract_term"),
                DB::raw("'{$contract_month}' as log_range"),
                DB::raw("'{$end_month_date}' as start_date_check"),
                DB::raw("'{$last_date_month}' as end_date_check")
            )
                ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                ->join('physicians', 'physicians.id', '=', 'contracts.physician_id')
                ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
                ->join('specialties', 'specialties.id', '=', 'physicians.specialty_id')
                ->join("practices", function ($join) {
                    $join->on("physician_practices.practice_id", "=", "practices.id")
                        ->on("practices.id", "=", "physician_logs.practice_id")
                        ->on("practices.hospital_id", "=", "agreements.hospital_id");
                })
                ->where('contracts.agreement_id', '=', $agreement->id)
                ->where('physicians.id', '=', $physician_id)
                ->whereBetween('physician_logs.date',array($end_month_date,$last_date_month))
                ->groupBy('physicians.id', 'contracts.id')
                ->orderBy('contract_types.name', 'asc')
                ->orderBy('practices.name', 'asc')
                ->orderBy('physicians.last_name', 'asc')
                ->orderBy('physicians.first_name', 'asc');

            $year_to_date_query2[$i] = DB::table('physicians')->select(
                DB::raw("practices.name as practice_name"),
                DB::raw("practices.id as practice_id"),
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
                DB::raw("0 as worked_hours"),
                DB::raw("'{$contract_month1}' as contract_month"),
                DB::raw("'{$contract_month}' as contract_month1"),
                DB::raw("'{$contract_term}' as contract_term"),
                DB::raw("'{$contract_month}' as log_range"),
                DB::raw("'{$end_month_date}' as start_date_check"),
                DB::raw("'{$last_date_month}' as end_date_check")
            )
                ->join('physician_contracts', 'physician_contracts.physician_id', '=', 'physicians.id')
                ->join('contracts', 'contracts.id', '=', 'physician_contracts.contract_id')
                ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                ->join('specialties', 'specialties.id', '=', 'physicians.specialty_id')
                ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
                ->join("practices", function ($join) {
                    $join->on("physician_practices.practice_id", "=", "practices.id")
                        ->on("practices.id", "=", "physician_contracts.practice_id")
                        ->on("practices.hospital_id", "=", "agreements.hospital_id");
                })
                ->where('contracts.agreement_id', '=', $agreement->id)
                ->where('physicians.id', '=', $physician_id)
                ->groupBy('physicians.id', 'contracts.id')
                ->orderBy('contract_types.name', 'asc')
                ->orderBy('practices.name', 'asc')
                ->orderBy('physicians.last_name', 'asc')
                ->orderBy('physicians.first_name', 'asc');

        }

        if ($contract_type_id != -1) {
            $period_query->where('contracts.contract_type_id', '=', $contract_type_id);
            $period_query2->where('contracts.contract_type_id', '=', $contract_type_id);
            foreach($year_to_month_query as $year_to_date_query_arr)
            {
                $year_to_date_query_arr->where('contracts.contract_type_id', '=', $contract_type_id);
                //print_r($results->year_to_date);die;
                //$temp++;
            }

            foreach($year_to_month_query2 as $year_to_date_query_arr)
            {
                $year_to_date_query_arr->where('contracts.contract_type_id', '=', $contract_type_id);
                //print_r($results->year_to_date);die;
                //$temp++;
            }
            //$year_to_month_query->where('contracts.contract_type_id', '=', $contract_type_id);
            foreach($year_to_date_query as $year_to_date_query_arr)
            {
                $year_to_date_query_arr->where('contracts.contract_type_id', '=', $contract_type_id);
                //print_r($results->year_to_date);die;
                //$temp++;
            }

            foreach($year_to_date_query2 as $year_to_date_query_arr)
            {
                $year_to_date_query_arr->where('contracts.contract_type_id', '=', $contract_type_id);
                //print_r($results->year_to_date);die;
                //$temp++;
            }
            //$year_to_date_query->where('contracts.contract_type_id', '=', $contract_type_id);
        }

        $results = new StdClass;
        $results->period = $period_query->get();
        //Practice and physician report shows empty data.(  period sheet data:empty) by 1254
        if($results->period)
            $results->period = $period_query2->get();
            $results->year_to_month = array();
        $temp = 0;
        foreach($year_to_month_query as $year_to_date_query_arr)
        {
            $results->year_to_month[$temp] = $year_to_date_query_arr->get();
            if(empty($results->year_to_month[$temp]))
                $results->year_to_month[$temp] = $year_to_month_query2[$temp+1]->get();
            //$queries = DB::getQueryLog();
            //$last_query = end($queries);
            //print_r($last_query);die;
            $temp++;
        }
        //echo "<pre>";
        //print_r($results->year_to_month);die;
        $results->year_to_date = array();
        $temp = 0;
        foreach($year_to_date_query as $year_to_date_query_arr)
        {
            $results->year_to_date[$temp] = $year_to_date_query_arr->get();
            if(empty($results->year_to_date[$temp]))
            {
                $results->year_to_date[$temp] = $year_to_date_query2[$temp+1]->get();
            }
            //print_r($results->year_to_date);die;
            $temp++;
        }
        // $results->year_to_date = $year_to_date_query->get();
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
            foreach ($result1 as $result) {
                if ($result->contract_name_id) {
                    $result->contract_name = ContractName::findOrFail($result->contract_name_id)->name;
                }

                $result->paid = DB::table("physician_payments")
                    ->where("physician_payments.physician_id", "=", $result->physician_id)
                    ->where("physician_payments.agreement_id", "=", $agreement_data->id)
                    ->sum("amount");
            }
        }

        foreach ($results->year_to_date as $result1) {
            foreach ($result1 as $result) {
                if ($result->contract_name_id) {
                    $result->contract_name = ContractName::findOrFail($result->contract_name_id)->name;
                }

                $result->paid = DB::table("physician_payments")
                    ->where("physician_payments.physician_id", "=", $result->physician_id)
                    ->where("physician_payments.agreement_id", "=", $agreement_data->id)
                    ->sum("amount");
            }
        }

        // foreach ($results->year_to_date as $result) {
        //     if ($result->contract_name_id) {
        //         $result->contract_name = ContractName::findOrFail($result->contract_name_id)->name;
        //     }

        //     $result->paid = DB::table("physician_payments")
        //         ->where("physician_payments.physician_id", "=", $result->physician_id)
        //         ->where("physician_payments.agreement_id", "=", $agreement_data->id)
        //         ->sum("amount");
        // }
        return $results;
    }

    protected function parseArguments() {
        $result = new StdClass;
        $result->physician_id = $this->argument('physician');
        $result->contract_type = $this->argument('contract_type');
        $result->agreements = parent::parseArguments();
        //one-many physician issue fixed for showing old physician report for existing hospital by 1254
        $result->practice_id = $this->argument('practice_id');
        //end-one-many physician issue fixed for showing old physician report for existing hospital by 1254
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
            ["physician", InputArgument::REQUIRED, "The physician ID."],
            ["contract_type", InputArgument::REQUIRED, "The contract type."],
            ["agreements", InputArgument::REQUIRED, "The hospital agreement IDs."],
            ["months", InputArgument::REQUIRED, "The agreement months."],
            //one-many physician issue fixed for showing old physician report for existing hospital by 1254 
            ["practice_id", InputArgument::REQUIRED, "The agreement practice_id."],
            //end-one-many physician issue fixed for showing old physician report for existing hospital by 1254
            ["localtimeZone", InputArgument::REQUIRED, "The agreement localtimeZone."]
        ];
    }
}