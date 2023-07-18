<?php
namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use StdClass;
use App\Hospital;
use App\InvoiceNote;
use App\HospitalInvoice;
use Log;
use function App\Start\hospital_report_path;

class RehabHospitalInvoiceCommand extends ReportingCommand
{
    protected $name = "rehab_invoices:hospital";
    protected $description = "Generates a DYNAFIOS hospital invoice.";

    private $cell_style = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'ffffff']],
        'font' => ['color' => ['rgb' => '000000']],
        'borders' => [
            'outline' => ['borderStyle' => Border::BORDER_THIN],
            'inside' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ];

    private $totals_style = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'f2f2f2']],
        'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
        'borders' => [
            'outline' => ['borderStyle' => Border::BORDER_THIN],
            'inside' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ];

    private $period_style = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '000000']],
        'font' => ['color' => ['rgb' => 'FFFFFF'], 'size' => 12],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'vertical' => Alignment::VERTICAL_CENTER,
        'borders' => [
            'allborders' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '000000']],
            //'inside' => ['borderStyle' => Border::BORDER_MEDIUM,'color' => ['rgb' => 'FF0000']]
        ]
    ];

    private $header_style = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'a09284']],
        'font' => ['color' => ['rgb' => 'FFFFFF'], 'size' => 12],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'vertical' => Alignment::VERTICAL_CENTER,
        'borders' => [
            'allborders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            //'inside' => ['borderStyle' => Border::BORDER_MEDIUM,'color' => ['rgb' => 'FF0000']]
        ]
    ];

    private $cell_left_border_style = [
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '000000']]
        ]
    ];

    private $cell_right_border_style = [
        'borders' => [
            'right' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '000000']]
        ]
    ];

    private $cell_left_right_border_style = [
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '000000']],
            'right' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '000000']]
        ]
    ];

    private $cell_top_border_style = [
        'borders' => [
            'top' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '000000']]
        ]
    ];
    private $cell_bottom_border_style = [
        'borders' => [
            'bottom' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '000000']]
        ]
    ];

    private $all_border = [
        'borders' => [
            'allborders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]
        ]
    ];

    public function __invoke()
    {
        $arguments = $this->parseArguments();
        $hospital = Hospital::findOrFail($arguments->hospital_id);
        $workbook = $this->loadTemplate('hospital_invoice.xlsx');

        $reader = IOFactory::createReader("Xlsx");//Load template using phpSpreadsheet
		$workbook = $reader->load(storage_path()."/reports/templates/hospital_invoice_rehab.xlsx");

       //$run_date = format_date('now', 'm/d/Y \a\t h:i:s A');
        $run_date = $arguments->localtimeZone;

        $templateSheet = $workbook->getSheet(0);
        $sheetIndex = 0;
        $current_row = 0;
        $last_invoice_no = 0;
        $CM_signature = "NA";
        $FM_signature = "NA";

        $now = Carbon::now();
		$timezone = $now->timezone->getName();
		$timestamp = format_date((exec('time /T')), "h:i A");

        foreach($arguments->data as $agreement){
            $last_invoice_no = $agreement["agreement_data"]["invoice_no"];
            $invoice_no_period = $agreement["agreement_data"]["invoice_no_period"];
            $invoice_no = $invoice_no_period;
            $sheetIndex++;
            $nextWorksheet = clone $templateSheet;
            $title = strlen($hospital->name) > 31 ? substr($hospital->name,0,31): $hospital->name;
            $invalidCharacters = $nextWorksheet->getInvalidCharacters();//added for remove special charachters
            $title = str_replace($invalidCharacters, '', $title);//added for remove special charachters
            $nextWorksheet->setTitle($title);
            $workbook->addSheet($nextWorksheet);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("A5")->getAlignment()->setWrapText(true);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("B5")->getAlignment()->setWrapText(true);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->setCellValue("A5", $invoice_no);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->setCellValue("B5", $run_date)
                ->setCellValue("B7", $hospital->name)
                ->setCellValue("B8", $hospital->address)
                ->setCellValue("B9","{$hospital->city}, {$hospital->state->name}");
            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("F5", "Date Range:".$agreement["agreement_data"]["period"]);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("A3:A9")->applyFromArray($this->cell_left_border_style);
            $current_row=10;
//            $invoice_notes = InvoiceNote::getInvoiceNotes($hospital->id, InvoiceNote::HOSPITAL, $hospital->id); /*hospital invoice notes*/
//            foreach($invoice_notes as $index => $invoice_note){
//                $workbook->setActiveSheetIndex($sheetIndex)
//                    ->getStyle("A{$current_row}")->applyFromArray($this->cell_left_border_style);
//                $workbook->setActiveSheetIndex($sheetIndex)
//                    ->getStyle("B{$current_row}")->applyFromArray($this->cell_left_border_style);
//                $workbook->setActiveSheetIndex($sheetIndex)
//                    ->getStyle("C{$current_row}")->applyFromArray($this->cell_left_border_style);
//                $workbook->setActiveSheetIndex($sheetIndex)
//                    ->getStyle("N{$current_row}")->applyFromArray($this->cell_right_border_style);
//                $workbook->setActiveSheetIndex($sheetIndex)
//                    ->getStyle("B{$current_row}")->getAlignment()->setWrapText(true);
//                $workbook->setActiveSheetIndex($sheetIndex)
//                    ->setCellValue("B{$current_row}", $invoice_note);
//                $current_row++;
//            }
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("A{$current_row}")->applyFromArray($this->cell_left_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("AG{$current_row}")->applyFromArray($this->cell_right_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("A{$current_row}:AG{$current_row}")->applyFromArray($this->cell_top_border_style);
            $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("A{$current_row}:AG{$current_row}");
            $current_row++;
            foreach($agreement["practices"] as $practice){
                $grant_total = 0;
                $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("A{$current_row}:AG{$current_row}");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("A{$current_row}:AG{$current_row}")->getFont()->setBold(true);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("A{$current_row}", $practice["name"]);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("A{$current_row}:AG{$current_row}")->applyFromArray($this->period_style);
                $current_row++;
//                foreach($practice['practice_invoice_notes'] as $index => $practice_invoice_note){
//                    $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("A{$current_row}:AG{$current_row}");
//                    $workbook->setActiveSheetIndex($sheetIndex)
//                        ->getStyle("A{$current_row}:AG{$current_row}")->getFont()->setBold(true);
//                    $workbook->setActiveSheetIndex($sheetIndex)
//                        ->getStyle("A{$current_row}")->getAlignment()->setWrapText(true);
//                    $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("A{$current_row}", $practice_invoice_note);
//                    $workbook->setActiveSheetIndex($sheetIndex)
//                        ->getStyle("A{$current_row}:AG{$current_row}")->applyFromArray($this->period_style);
//                    $current_row++;
//                }
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("A{$current_row}:AG{$current_row}")->applyFromArray($this->header_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("A{$current_row}:AG{$current_row}")->getFont()->setBold(true);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("A{$current_row}:AG{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("A{$current_row}:AG{$current_row}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getRowDimension($current_row)
                    ->setRowHeight(30);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("A{$current_row}", "Date");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("A{$current_row}")->applyFromArray($this->cell_left_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("B{$current_row}", "1");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("B{$current_row}")->applyFromArray($this->cell_left_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("C{$current_row}", "2");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("C{$current_row}")->applyFromArray($this->cell_left_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("D{$current_row}", "3");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("D{$current_row}")->applyFromArray($this->cell_left_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("E{$current_row}", "4");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("E{$current_row}")->applyFromArray($this->cell_left_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("F{$current_row}", "5");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("F{$current_row}")->applyFromArray($this->cell_left_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("G{$current_row}", "6");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("G{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("H{$current_row}", "7");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("H{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("I{$current_row}", "8");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("I{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("J{$current_row}", "9");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("J{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("K{$current_row}", "10");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("K{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("L{$current_row}", "11");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("L{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("M{$current_row}", "12");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("M{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("N{$current_row}", "13");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("N{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("O{$current_row}", "14");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("O{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("P{$current_row}", "15");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("P{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("Q{$current_row}", "16");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("Q{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("R{$current_row}", "17");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("R{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("S{$current_row}", "18");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("S{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("T{$current_row}", "19");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("T{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("U{$current_row}", "20");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("U{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("V{$current_row}", "21");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("V{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("W{$current_row}", "22");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("W{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("X{$current_row}", "23");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("X{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("Y{$current_row}", "24");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("Y{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("Z{$current_row}", "25");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("Z{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("AA{$current_row}", "26");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("AA{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("AB{$current_row}", "27");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("AB{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("AC{$current_row}", "28");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("AC{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("AD{$current_row}", "29");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("AD{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("AE{$current_row}", "30");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("AE{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("AF{$current_row}", "31");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("AF{$current_row}")->applyFromArray($this->cell_right_border_style);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("AG{$current_row}", "Total");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("AG{$current_row}")->applyFromArray($this->cell_right_border_style);
                $current_row++;

                $processed_contracts = [];
                $Physician_signature = "NA";

                foreach($practice["contract_data"] as $contract_data){
                    if(!in_array($contract_data['contract_id'],$processed_contracts)){
                        $admin_per_day_total = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
                        $approver_details = [];
                        foreach($contract_data["rehab_category_action_list"] as $category => $action) {

                            if($category != "Total Clinical"){
                                $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("A{$current_row}:AG{$current_row}");
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("A{$current_row}:AG{$current_row}")->getFont()->setBold(true);
                                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("A{$current_row}", "$category");
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("AG{$current_row}")->applyFromArray($this->cell_right_border_style);
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("A{$current_row}:AG{$current_row}")->applyFromArray($this->cell_style);
                                $current_row++;

                                foreach ($action as $action_detail){
                                    $workbook->setActiveSheetIndex($sheetIndex)
                                        ->getStyle("A{$current_row}")->applyFromArray($this->cell_style);
                                    $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("A{$current_row}", $action_detail["name"]);

                                    if(count($contract_data["breakdown"]) > 0){

                                        if (!array_key_exists($action_detail->id,$contract_data["breakdown"])){
                                            $lastColumn = $workbook->setActiveSheetIndex($sheetIndex)->getHighestColumn();
                                            $lastColumn++;
                                            for ($column = 'B'; $column != $lastColumn; $column++) {
                                                $workbook->setActiveSheetIndex($sheetIndex)
                                                    ->getStyle("$column{$current_row}")->applyFromArray($this->cell_style);
                                                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("$column{$current_row}", "0");
                                            }
                                        } else {

                                            $action_logs = $contract_data["breakdown"][$action_detail->id]["action_logs"];

                                            $day = 1;
                                            $lastColumn = $workbook->setActiveSheetIndex($sheetIndex)->getHighestColumn();
                                            $action_total = 0;
                                            for ($column = 'B'; $column != $lastColumn; $column++) {
                                                $workbook->setActiveSheetIndex($sheetIndex)
                                                    ->getStyle("$column{$current_row}")->applyFromArray($this->cell_style);

                                                foreach($action_logs as $action_log){
                                                    list($y, $m, $d) = explode('-', $action_log["date"]);
                                                    $d = ltrim($d, "0");
                                                    if($day == $d){
                                                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("$column{$current_row}", $action_log["worked_hours"]);
                                                        $action_total = $action_total + $action_log["worked_hours"];
                                                        $number = $admin_per_day_total[$day-1] + $action_log["worked_hours"];
                                                        $admin_per_day_total[$day-1] = number_format((float)$number, 2, '.', '');
                                                        break;
                                                    } else {
                                                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("$column{$current_row}", "0");
                                                    }
                                                }
                                                $day++;
                                            }
                                            $workbook->setActiveSheetIndex($sheetIndex)
                                                ->getStyle("$lastColumn{$current_row}")->applyFromArray($this->cell_style);
                                            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("$lastColumn{$current_row}", $action_total);

                                            /**
                                             * This block of code is for setting the approver's signature array.
                                             */
                                            foreach ($contract_data["breakdown"][$action_detail->id]["levels"] as $level => $level_detail){
                                                if($level_detail['signature'] != 'NA'){
                                                    if(!array_key_exists($level, $approver_details)){
                                                        $approver_details[$level] = $level_detail;
                                                    }
                                                }
                                            }
                                            if($Physician_signature != ""){
                                                $Physician_signature = $contract_data["breakdown"][$action_detail->id]['Physician_approve_signature'];
                                            }
                                            // Ends setting approver's signature array here.

                                        }

                                        $current_row++;
                                    }
                                }
                            }
                            else {

                                /*
                                 * This block of code is for Total Admin hours.
                                 */
                                $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("A{$current_row}:AG{$current_row}");
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("A{$current_row}:AG{$current_row}")->getFont()->setBold(true);
                                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("A{$current_row}", "Total Admin");
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("AG{$current_row}")->applyFromArray($this->cell_right_border_style);
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("A{$current_row}:AG{$current_row}")->applyFromArray($this->cell_style);
                                $current_row++;

                                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("A{$current_row}", "Total Admin Hours");

                                $index = 0;
                                $lastColumn = $workbook->setActiveSheetIndex($sheetIndex)->getHighestColumn();
                                for ($column = 'B'; $column != $lastColumn; $column++) {
                                    $workbook->setActiveSheetIndex($sheetIndex)
                                        ->getStyle("$column{$current_row}")->applyFromArray($this->cell_style);
                                    $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("$column{$current_row}", "$admin_per_day_total[$index]");

                                    $index++;
                                }
                                $admin_hour_row_total = array_sum($admin_per_day_total);
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("$column{$current_row}")->applyFromArray($this->cell_style);
                                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("$column{$current_row}", "$admin_hour_row_total");
                                $current_row++;

                                // Admin hours code block ends here.

                                /*
                                 * This block of code again repeated to run for Total Clinical hours only.
                                 */
                                $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("A{$current_row}:AG{$current_row}");
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("A{$current_row}:AG{$current_row}")->getFont()->setBold(true);
                                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("A{$current_row}", "$category");
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("AG{$current_row}")->applyFromArray($this->cell_right_border_style);
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("A{$current_row}:AG{$current_row}")->applyFromArray($this->cell_style);
                                $current_row++;

                                $per_day_total = $admin_per_day_total;

                                foreach ($action as $action_detail){
                                    $workbook->setActiveSheetIndex($sheetIndex)
                                        ->getStyle("A{$current_row}")->applyFromArray($this->cell_style);
                                    $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("A{$current_row}", $action_detail["name"]);

                                    if(count($contract_data["breakdown"]) > 0){

                                        if (!array_key_exists($action_detail->id,$contract_data["breakdown"])){
                                            $lastColumn = $workbook->setActiveSheetIndex($sheetIndex)->getHighestColumn();
                                            $lastColumn++;
                                            for ($column = 'B'; $column != $lastColumn; $column++) {
                                                $workbook->setActiveSheetIndex($sheetIndex)
                                                    ->getStyle("$column{$current_row}")->applyFromArray($this->cell_style);
                                                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("$column{$current_row}", "0");
                                            }
                                        } else {

                                            $action_logs = $contract_data["breakdown"][$action_detail->id]["action_logs"];

                                            $day = 1;
                                            $lastColumn = $workbook->setActiveSheetIndex($sheetIndex)->getHighestColumn();
                                            $action_total = 0;
                                            for ($column = 'B'; $column != $lastColumn; $column++) {
                                                $workbook->setActiveSheetIndex($sheetIndex)
                                                    ->getStyle("$column{$current_row}")->applyFromArray($this->cell_style);

                                                foreach($action_logs as $action_log){
                                                    list($y, $m, $d) = explode('-', $action_log["date"]);
                                                    $d = ltrim($d, "0");
                                                    if($day == $d){
                                                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("$column{$current_row}", $action_log["worked_hours"]);
                                                        $action_total = $action_total + $action_log["worked_hours"];
                                                        $number = $per_day_total[$day-1] + $action_log["worked_hours"];
                                                        $per_day_total[$day-1] = number_format((float)$number, 2, '.', '');
                                                        break;
                                                    } else {
                                                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("$column{$current_row}", "0");
                                                    }
                                                }
                                                $day++;
                                            }
                                            $workbook->setActiveSheetIndex($sheetIndex)
                                                ->getStyle("$lastColumn{$current_row}")->applyFromArray($this->cell_style);
                                            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("$lastColumn{$current_row}", $action_total);
                                        }

                                        $current_row++;
                                    }
                                }
                                // Total Clinical Hours code block ends here.


                                /*
                                 * This block of code is for Total hours.
                                 */
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("A{$current_row}")->getFont()->setBold(true);
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("A{$current_row}")->applyFromArray($this->cell_style);
                                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("A{$current_row}", "Total Hours");

                                $index = 0;
                                $lastColumn = $workbook->setActiveSheetIndex($sheetIndex)->getHighestColumn();
                                for ($column = 'B'; $column != $lastColumn; $column++) {
                                    $workbook->setActiveSheetIndex($sheetIndex)
                                        ->getStyle("$column{$current_row}")->applyFromArray($this->cell_style);
                                    $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("$column{$current_row}", "$per_day_total[$index]");

                                    $index++;
                                }
                                $admin_hour_row_total = array_sum($per_day_total);
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->getStyle("$column{$current_row}")->applyFromArray($this->cell_style);
                                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("$column{$current_row}", "$admin_hour_row_total");
                                $current_row++;

                                // Total hours code block ends here.
                            }
                        }

                        $approver_details_last_to_first = array_reverse($approver_details);
                        $workbook->setActiveSheetIndex($sheetIndex)->getRowDimension($current_row)->setRowHeight(70);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("A{$current_row}:AG{$current_row}")
                            ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);

                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("A{$current_row}:D{$current_row}");
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("A{$current_row}:D{$current_row}")->applyFromArray($this->cell_style);

                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("A{$current_row}", "Medical Director/Covering Medical Director's Signature");

                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("E{$current_row}:W{$current_row}");
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("E{$current_row}:W{$current_row}")->applyFromArray($this->cell_style);

                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("X{$current_row}:AF{$current_row}");
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("X{$current_row}:AF{$current_row}")->applyFromArray($this->cell_style);

                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("X{$current_row}", "Contracted Admin Hours to be paid for month");

                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("AG{$current_row}")->applyFromArray($this->cell_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("AG{$current_row}", ($contract_data['rehab_max_hours_per_month']));
                        $current_row++;

                        $workbook->setActiveSheetIndex($sheetIndex)->getRowDimension($current_row)->setRowHeight(70);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("A{$current_row}:AG{$current_row}")
                            ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);

                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("A{$current_row}:D{$current_row}");
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("A{$current_row}:D{$current_row}")->applyFromArray($this->cell_style);

                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("A{$current_row}", "Program Director/Administrator Signature");

                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("E{$current_row}:W{$current_row}");
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("E{$current_row}:W{$current_row}")->applyFromArray($this->cell_style);

                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("X{$current_row}:AF{$current_row}");
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("X{$current_row}:AF{$current_row}")->applyFromArray($this->cell_style);

                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("X{$current_row}", "Additional Approved Admin Hours to be paid");

//                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("D{$current_row}", "Signature Program direactor.");

                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("AG{$current_row}")->applyFromArray($this->cell_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("AG{$current_row}", ($contract_data['rehab_admin_hours']));
                        $current_row++;


                        $workbook->setActiveSheetIndex($sheetIndex)->getRowDimension($current_row)->setRowHeight(30);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("A{$current_row}:AG{$current_row}")
                            ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("A{$current_row}:W{$current_row}");
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("A{$current_row}:W{$current_row}")->applyFromArray($this->cell_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("X{$current_row}:AF{$current_row}");
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("X{$current_row}:AF{$current_row}")->applyFromArray($this->cell_style);

                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("X{$current_row}", "Total Admin Hours to be paid for month");

                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("AG{$current_row}")->applyFromArray($this->cell_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("AG{$current_row}", ($contract_data['rehab_max_hours_per_month'] + $contract_data['rehab_admin_hours']));
                        $current_row++;

                        $workbook->setActiveSheetIndex($sheetIndex)->getRowDimension($current_row)->setRowHeight(30);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("A{$current_row}:AG{$current_row}")
                            ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("A{$current_row}:W{$current_row}");
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("A{$current_row}:W{$current_row}")->applyFromArray($this->cell_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("X{$current_row}:AF{$current_row}");
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("X{$current_row}:AF{$current_row}")->applyFromArray($this->cell_style);

                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("X{$current_row}", "Total Amount to be paid for month");

                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("AG{$current_row}")->applyFromArray($this->cell_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("AG{$current_row}", (($contract_data['rehab_max_hours_per_month'] + $contract_data['rehab_admin_hours']) * $contract_data['rate']));

                        $current_row = $current_row - 3;

                        // Below code is for writing the physician signature.
                        if ($Physician_signature != "NA" && $Physician_signature != "") {
                            $dataSP = "data:image/png;base64," . $Physician_signature;
                            list($type, $dataS) = explode(';', $dataSP);
                            list(, $dataSP) = explode(',', $dataSP);

                            $dataSP = base64_decode($dataSP);
                            file_put_contents(storage_path() . "/image" . $contract_data["physician_id"] . ".png", $dataSP);
                            $objDrawingPType = new Drawing();
                            $objDrawingPType->setWorksheet($workbook->setActiveSheetIndex($sheetIndex));
                            $objDrawingPType->setName("Signature");
                            $objDrawingPType->setPath(storage_path() . "/image" . $contract_data["physician_id"] . ".png");
                            $objDrawingPType->setCoordinates("E" . $current_row);
                            $objDrawingPType->setOffsetX(1);
                            $objDrawingPType->setOffsetY(5);
                            $objDrawingPType->setWidthAndHeight(100, 70);
                            $objDrawingPType->setResizeProportional(true);
                        }
                        $current_row++;

                        // This code is for writing approver's signature.
//                        for($i = 0; $i <= 1; $i++){

                            $dataSCM = "data:image/png;base64," . $approver_details_last_to_first[0]["signature"];
                            list($type, $dataSCM) = explode(';', $dataSCM);
                            list(, $dataSCM) = explode(',', $dataSCM);
                            $dataSCM = base64_decode($dataSCM);

                            file_put_contents(storage_path() . "/imageUserM" ."E".$current_row. date("mdYHis") . ".png", $dataSCM);
                            // $objDrawingPType = new PHPExcel_Worksheet_Drawing();
                            $objDrawingPType = new Drawing();
                            $objDrawingPType->setWorksheet($workbook->setActiveSheetIndex($sheetIndex));
                            $objDrawingPType->setName("Signature");
                            $objDrawingPType->setPath(storage_path() . "/imageUserM" ."E".$current_row. date("mdYHis") . ".png");
                            $objDrawingPType->setCoordinates("E" . $current_row);
                            $objDrawingPType->setOffsetX(1);
                            $objDrawingPType->setOffsetY(5);
                            $objDrawingPType->setWidthAndHeight(100, 70);
                            $objDrawingPType->setResizeProportional(true);

                            $current_row++;
//                        }

                        $current_row++;

                        array_push($processed_contracts, $contract_data['contract_id']);
                    }
                }

                $current_row++;
            }
        }


        $workbook->removeSheetByIndex(0);
        if ($workbook->getSheetCount() == 0) {
            $this->failure("breakdowns.logs_unavailable");
            return;
        }

        $report_path = hospital_report_path($hospital);
        if (!file_exists($report_path)) {
            mkdir($report_path, 0777, true);
        }

        $timeZone = str_replace(' ','_', $arguments->localtimeZone);
        $timeZone = str_replace('/','', $timeZone);
        $timeZone = str_replace(':','', $timeZone);
        $report_filename = "Invoices_" . $hospital->name . "_" . $timeZone . ".xlsx";

        $writer = IOFactory::createWriter($workbook, 'Xlsx');
        $writer->save("{$report_path}/{$report_filename}");

        $hospital_invoice = new HospitalInvoice;
        $hospital_invoice->hospital_id = $hospital->id;
        $hospital_invoice->filename = $report_filename;
        $hospital_invoice->contracttype_id = $arguments->contract_type;
        $hospital_invoice->period = mysql_date(date("m/d/Y"));
        $hospital_invoice->last_invoice_no = $last_invoice_no;
        $hospital_invoice->save();

        return $this->success('hospitals.generate_invoice_success', $hospital_invoice->id, $hospital_invoice->filename);
    }

    protected function parseArguments()
    {
        $result = new StdClass;
        $result->hospital_id = $this->argument('hospital');
        $result->contract_type = $this->argument('contract_type');
        $result->practice_ids = $this->argument('practices');
        $result->localtimeZone = $this->argument('localtimeZone');
        $result->practices = explode(',', $result->practice_ids);
        $result->data = $this->argument('data');
        $result->agreements = parent::parseArguments();
        return $result;
    }

    protected function getArguments()
    {
        return [
            ["hospital", InputArgument::REQUIRED, "The hospital ID."],
            ["contract_type", InputArgument::REQUIRED, "The contract type."],
            ["practices", InputArgument::REQUIRED, "The practice IDs."],
            ["agreements", InputArgument::REQUIRED, "The hospital agreement IDs."],
            ["months", InputArgument::REQUIRED, "The agreement months."],
            ["data", InputArgument::REQUIRED, "No Logs for display."],
            ["localtimeZone", InputArgument::REQUIRED, "The agreement localtimeZone."]

        ];
    }

    protected function getOptions()
    {
        return [];
    }
}