<?php
namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use DateTime;
use StdClass;
use App\Hospital;
use App\ContractType;
use App\HospitalReport;
use App\Practice;
use App\PracticeManagerReport;
use Log;
use function App\Start\hospital_report_path;

//Below imports are for php spreadsheets.
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use function App\Start\is_practice_manager;
use function App\Start\practice_report_path;

/*
 * Column Header changed according to contract type
 * for Medical Directorship,Hours Expected and
 * for Other than that, Max Hours Possible
 * */

class BreakdownReportCommandMultipleMonths extends ReportingCommand
{
    protected $name = "reports:breakdownmultiplemonths";
    protected $description = "Generates a DYNAFIOS physician logs breakdown report.";

    private $cell_style = [
        'borders' => [
            'inside' => ['borderStyle' => Border::BORDER_THIN],
            'left' => ['borderStyle' => Border::BORDER_THIN],
            'right' => ['borderStyle' => Border::BORDER_THIN],
            'top' => ['borderStyle' => Border::BORDER_THIN],
            'bottom' => ['borderStyle' => Border::BORDER_THIN],
        ]
    ];

    private $underline_text = [
        'font' => [
            'underline' => Font::UNDERLINE_SINGLE
        ]
    ];

    private $border_top = [
        'borders' => [
            'top' => ['borderStyle' => Border::BORDER_THIN],
        ]
    ];
    private $border_bottom = [
        'borders' => [
            'bottom' => ['borderStyle' => Border::BORDER_MEDIUM],
        ]
    ];

    private $border_right_top = [
        'borders' => [
            'right' => ['borderStyle' => Border::BORDER_MEDIUM],
            'top' => ['borderStyle' => Border::BORDER_MEDIUM]
        ]
    ];

    private $log_details_style = [
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_MEDIUM],
            'right' => ['borderStyle' => Border::BORDER_MEDIUM]
        ]
    ];

    private $sign_box_style = [
        'borders' => [

            'left' => ['borderStyle' => Border::BORDER_THIN],
            'right' => ['borderStyle' => Border::BORDER_MEDIUM],
            'top' => ['borderStyle' => Border::BORDER_THIN],
            'bottom' => ['borderStyle' => Border::BORDER_THIN],
        ]
    ];

    private $physician_breakdown_style = [
        'borders' => [

            'left' => ['borderStyle' => Border::BORDER_MEDIUM],
            'right' => ['borderStyle' => Border::BORDER_THIN],
            'top' => ['borderStyle' => Border::BORDER_THIN],
            'bottom' => ['borderStyle' => Border::BORDER_MEDIUM],
        ]
    ];

    private $shaded_style = [
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'color' => ['rgb' => 'eeece1']
        ],
        'borders' => [
            'inside' => ['borderStyle' => Border::BORDER_THIN],
            'left' => ['borderStyle' => Border::BORDER_THIN],
            'right' => ['borderStyle' => Border::BORDER_MEDIUM],
            'top' => ['borderStyle' => Border::BORDER_THIN],
            'bottom' => ['borderStyle' => Border::BORDER_THIN],
        ]
    ];

    private $shaded_style_worked = [
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'color' => ['rgb' => 'eeece1']
        ],
        'borders' => [
            'inside' => ['borderStyle' => Border::BORDER_THIN],
            'left' => ['borderStyle' => Border::BORDER_THIN],
            'right' => ['borderStyle' => Border::BORDER_THIN],
            'top' => ['borderStyle' => Border::BORDER_THIN],
            'bottom' => ['borderStyle' => Border::BORDER_THIN],
        ]
    ];
    private $contract_style = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '000000']],
        'font' => ['color' => ['rgb' => 'FFFFFF'], 'size' => 14],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        'borders' => [
            'allborders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']],
            //'inside' => ['borderStyle' => Border::BORDER_MEDIUM,'color' => ['rgb' => 'FF0000']]
        ]

    ];
    private $period_style = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '000000']],
        'font' => ['color' => ['rgb' => 'FFFFFF'], 'size' => 12],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        'borders' => [
            'allborders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']],
            //'inside' => ['borderStyle' => Border::BORDER_MEDIUM,'color' => ['rgb' => 'FF0000']]
        ]

    ];
    private $total_style = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'eeecea']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_MEDIUM],
            'right' => ['borderStyle' => Border::BORDER_MEDIUM],
            'outline' => ['borderStyle' => Border::BORDER_THIN],
            'inside' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ];
    private $action_style = [
        'borders' => [

            'left' => ['borderStyle' => Border::BORDER_MEDIUM],
            'right' => ['borderStyle' => Border::BORDER_THIN],
            'top' => ['borderStyle' => Border::BORDER_THIN],
            'bottom' => ['borderStyle' => Border::BORDER_THIN],
        ]
    ];
    private $physician_total_merged_style = [
        'borders' => [

            'left' => ['borderStyle' => Border::BORDER_THIN],
            'right' => ['borderStyle' => Border::BORDER_NONE],
            'top' => ['borderStyle' => Border::BORDER_THIN],
            'bottom' => ['borderStyle' => Border::BORDER_THIN],
        ]
    ];

    public function __invoke()
    {
        $arguments = $this->parseArguments();
        $hospital = Hospital::findOrFail($arguments->hospital_id);

        $now = Carbon::now();
		$timezone = $now->timezone->getName();
        $timestamp = format_date((exec('time /T')), "h:i A");
        
        /*
         * Column Header changed according to contract type
         * for Medical Directorship,Hours Expected and
         * for Other than that, Max Hours Possible
         * */
        if ($arguments->contract_type == ContractType::MEDICAL_DIRECTORSHIP) {
            $contract_wise_header = "Max Hours/Units Possible";
        } else {
            $contract_wise_header = "Hours/Units Expected";
        }
        //$header = "Physician Log Report\n" . "Run Date: " . format_date("now") . "\n";

        // $workbook = $this->loadTemplate("breakdown_updated.xlsx");

        $reader = IOFactory::createReader("Xlsx");
        $workbook = $reader->load(storage_path()."/reports/templates/breakdown_updated.xlsx");

        $workbook->setActiveSheetIndex(0)->setCellValue("G4", $contract_wise_header);
        $templateSheet = $workbook->getSheet(0);
        $sheetIndex = 0;
        $Physician_signature ="";
        $l1_signature ="";
        $l2_signature ="";
        $l3_signature ="";
        $l4_signature ="";
        $l5_signature ="";
        $l6_signature ="";
        foreach($arguments->report_data as $physician_data){
            $sheetIndex++;
            $nextWorksheet = clone $templateSheet;
            $nextWorksheet->setTitle("" . $sheetIndex);
            $workbook->addSheet($nextWorksheet);
            $header = '';
            $header .= strtoupper($hospital->name) . "\n";
            $header .= "Cumulative Physician Log Report\n";
            $header .= "Period: " . $physician_data["data"]["Period"] . "\n";
            // $header .= "Run Date: " . with(new DateTime('now'))->format("m/d/Y");
            $header .= "Run Date: " . with($physician_data["data"]["localtimeZone"]);

           

            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("B2", $header);
            $current_row = 5;
            $current_row = $this->writeContractHeader($workbook, $sheetIndex, $current_row, $physician_data["data"]["agreement_name"], $physician_data["data"]["agreement_start_date"], $physician_data["data"]["agreement_end_date"]);
            foreach($physician_data["logs"] as $data) {
                $current_row = $this->writePeriodHeader($workbook, $sheetIndex, $current_row, $data["date_range"]);
                if(is_practice_manager()){
                        $report_practice_id = $data["practice_id"];
                }
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->setCellValue("B{$current_row}", $data["practice_name"])
                    ->setCellValue("C{$current_row}", $data["physician_name"])
                    ->mergeCells("D{$current_row}:E{$current_row}")
                    ->setCellValue("F{$current_row}", $data["date_range"])
                    ->setCellValue("G{$current_row}",
                        ($arguments->contract_type == ContractType::MEDICAL_DIRECTORSHIP) ?
                            $data["sum_max_hours"] : $data["expected_hours"])
                    ->setCellValue("H{$current_row}", round($data["sum_worked_hour"], 2));
                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:O{$current_row}")->applyFromArray($this->total_style);
                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:O{$current_row}")->getFont()->setBold(true);
                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}")->applyFromArray($this->action_style);
                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("F{$current_row}")->applyFromArray($this->sign_box_style);
                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("G{$current_row}:H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                //$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:D{$current_row}")->applyFromArray($this->shaded_style);
                //$workbook->setActiveSheetIndex($sheetIndex)->getStyle("E{$current_row}:H{$current_row}")->applyFromArray($this->shaded_style);
                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:O{$current_row}")->applyFromArray($this->border_bottom);
                $title = strlen($data["physician_name"]) > 31 ? substr($data["physician_name"],0,31): $data["physician_name"];
                $workbook->getActiveSheet()->setTitle($title);
                $worked_hours_present = false;
                if ($data["worked_hours"] > 0) {
                    $worked_hours_present = true;
                }

                $current_row++;
                $new_current_row = count($data["breakdown"]) + $current_row;
                $physician_row = ($new_current_row) - ((($new_current_row) - $current_row) / 2);
                $physician_row = floor($physician_row);
                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:C{$new_current_row}")->applyFromArray($this->physician_breakdown_style);
                $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("B{$physician_row}:C{$physician_row}");
                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$physician_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("B{$physician_row}", $data["physician_name"]);


                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$new_current_row}:O{$new_current_row}")->applyFromArray($this->border_bottom);
                $status_level= array();
                $status_level[1]="NA";
                $status_level[2]="NA";
                $status_level[3]="NA";
                $status_level[4]="NA";
                $status_level[5]="NA";
                $status_level[6]="NA";
                foreach ($data["breakdown"] as $breakdown) {
                    $breakdown_date = strtotime($breakdown["date"]);
//                    if ((strtotime($month_start_date) <= $breakdown_date) && (strtotime($month_end_date) >= $breakdown_date)) {
                    //if ($approve_date_arr[$i] != "") {
  //hide approval if has no contract
                        if(!isset($breakdown["log_approval_info"][1]))
                        {
                            $workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('J')->setVisible(false);
                        }

                        if(!isset($breakdown["log_approval_info"][2]))
                        {
                            $workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('K')->setVisible(false);
                        }

                        if(!isset($breakdown["log_approval_info"][3]))
                        {
                            $workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('L')->setVisible(false);
                        }

                        if(!isset($breakdown["log_approval_info"][4]))
                        {
                            $workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('M')->setVisible(false);
                        }

                        if(!isset($breakdown["log_approval_info"][5]))
                        {
                            $workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('N')->setVisible(false);
                        }

                        if(!isset($breakdown["log_approval_info"][6]))
                        {
                            $workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('O')->setVisible(false);
                        }
	
                    $workbook->setActiveSheetIndex($sheetIndex)
                        //->mergeCells("B{$current_row}:C{$current_row}")
                        //->setCellValue("B{$current_row}", $physician_data["physician_name"])
                        ->setCellValue("D{$current_row}", $breakdown["action"])
                        ->setCellValue("E{$current_row}", (strlen($breakdown["notes"]) > 0) ? $breakdown["notes"] : "-")
                        ->setCellValue("F{$current_row}", $breakdown["date"])
                        ->setCellValue("G{$current_row}", "-")
                        ->setCellValue("H{$current_row}", $breakdown["worked_hours"])
                        ->setCellValue("I{$current_row}", $breakdown["Physician_approve_date"])
                        ->setCellValue("J{$current_row}", isset($breakdown["log_approval_info"][1]) ? $breakdown["log_approval_info"][1]['date']:$status_level[1])
                        ->setCellValue("K{$current_row}", isset($breakdown["log_approval_info"][2]) ? $breakdown["log_approval_info"][2]['date']:$status_level[2])
                        ->setCellValue("L{$current_row}", isset($breakdown["log_approval_info"][3]) ? $breakdown["log_approval_info"][3]['date']:$status_level[3])
                        ->setCellValue("M{$current_row}", isset($breakdown["log_approval_info"][4]) ? $breakdown["log_approval_info"][4]['date']:$status_level[4])
                        ->setCellValue("N{$current_row}", isset($breakdown["log_approval_info"][5]) ? $breakdown["log_approval_info"][5]['date']:$status_level[5])
                        ->setCellValue("O{$current_row}", isset($breakdown["log_approval_info"][6]) ? $breakdown["log_approval_info"][6]['date']:$status_level[6]);

                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}:I{$current_row}")->applyFromArray($this->cell_style);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}")->applyFromArray($this->action_style);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("E{$current_row}")->getAlignment()->setWrapText(true);
                    $workbook->setActiveSheetIndex($sheetIndex)->getRowDimension($current_row)->setRowHeight(-1);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("K{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("M{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("N{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("O{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("F{$current_row}:H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("H{$current_row}")->applyFromArray($this->shaded_style_worked);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("I{$current_row}")->applyFromArray($this->shaded_style_worked);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("J{$current_row}")->applyFromArray($this->shaded_style_worked);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("K{$current_row}")->applyFromArray($this->shaded_style_worked);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("L{$current_row}")->applyFromArray($this->shaded_style_worked);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("M{$current_row}")->applyFromArray($this->shaded_style_worked);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("N{$current_row}")->applyFromArray($this->shaded_style_worked);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("O{$current_row}")->applyFromArray($this->shaded_style);
                    $current_row++;
//                        $count++;
//                        $count_prev[$physician_data["physician_id"]]= $count;
//                    }
//                    $i++;
                    //}
                    $Physician_signature = isset($breakdown["log_approval_info"][0]) ? $breakdown["log_approval_info"][0]["approve_signature"]: "NA";
                    $l1_signature = isset($breakdown["log_approval_info"][1]) ? $breakdown["log_approval_info"][1]["approve_signature"]: "NA";
                    $l2_signature = isset($breakdown["log_approval_info"][2]) ? $breakdown["log_approval_info"][2]["approve_signature"]: "NA";
                    $l3_signature = isset($breakdown["log_approval_info"][3]) ? $breakdown["log_approval_info"][3]["approve_signature"]: "NA";
                    $l4_signature = isset($breakdown["log_approval_info"][4]) ? $breakdown["log_approval_info"][4]["approve_signature"]: "NA";
                    $l5_signature = isset($breakdown["log_approval_info"][5]) ? $breakdown["log_approval_info"][5]["approve_signature"]: "NA";
                    $l6_signature = isset($breakdown["log_approval_info"][6]) ? $breakdown["log_approval_info"][6]["approve_signature"]: "NA";
                }
            }
            $boxSize = $current_row + 0;
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:H{$boxSize}")->applyFromArray($this->sign_box_style);
            $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("B{$current_row}:H{$current_row}");
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("B{$current_row}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("B{$current_row}")
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->setCellValue("B{$current_row}", "Signature(s)");
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("E{$current_row}")->applyFromArray($this->underline_text);
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getRowDimension($current_row)
                ->setRowHeight(25);

            if ($Physician_signature != "NA" && $Physician_signature != "") {
                $dataSP = "data:image/png;base64," . $Physician_signature;
                list($type, $dataS) = explode(';', $dataSP);
                list(, $dataSP) = explode(',', $dataSP);

                $dataSP = base64_decode($dataSP);
                //echo storage_path()."/image.png";die;
                file_put_contents(storage_path() . "/image" . $data["physician_id"] . ".png", $dataSP);
                $objDrawingPType = new Drawing();
                //$objDrawingPType->setWorksheet($workbook->setActiveSheetIndex($sheetIndex)->mergeCells("I{$current_row}:I{$current_row}"));
                $objDrawingPType->setWorksheet($workbook->setActiveSheetIndex($sheetIndex));
                $objDrawingPType->setName("Signature(s)");
                $objDrawingPType->setPath(storage_path() . "/image" . $data["physician_id"] . ".png");
                $objDrawingPType->setCoordinates("I" . $current_row);
                $objDrawingPType->setOffsetX(1);
                $objDrawingPType->setOffsetY(5);
                $objDrawingPType->setWidthAndHeight(150, 110);
                $objDrawingPType->setResizeProportional(true);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getColumnDimension('D')
                    ->setWidth(40);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getRowDimension($current_row)
                    ->setRowHeight(90);
                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("I{$current_row}")->applyFromArray($this->border_right_top);
//                    unset($count_prev);
//                    $count_prev = array();
                if ($l1_signature != "NA" && $l1_signature != "") {
                    $dataSl1 = "data:image/png;base64," . $l1_signature;
                    list($type, $dataSl1) = explode(';', $dataSl1);
                    list(, $dataSl1) = explode(',', $dataSl1);

                    $dataSl1 = base64_decode($dataSl1);
                    //echo storage_path()."/image.png";die;
                    file_put_contents(storage_path() . "/imageUserl1" . $data["physician_id"] . ".png", $dataSl1);
                    $objDrawingPType = new Drawing();
                    //$objDrawingPType->setWorksheet($workbook->setActiveSheetIndex($sheetIndex)->mergeCells("J{$current_row}:J{$current_row}"));
                    $objDrawingPType->setWorksheet($workbook->setActiveSheetIndex($sheetIndex));
                    $objDrawingPType->setName("Signature(s)");
                    $objDrawingPType->setPath(storage_path() . "/imageUserl1" . $data["physician_id"] . ".png");
                    $objDrawingPType->setCoordinates("J" . $current_row);
                    $objDrawingPType->setOffsetX(1);
                    $objDrawingPType->setOffsetY(5);
                    $objDrawingPType->setWidthAndHeight(150, 110);
                    $objDrawingPType->setResizeProportional(true);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getColumnDimension('D')
                        ->setWidth(40);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getRowDimension($current_row)
                        ->setRowHeight(90);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("J{$current_row}")->applyFromArray($this->border_right_top);
//                    unset($count_prev);
//                    $count_prev = array();
                } else {
                    //$current_row += 2;
                    $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("J{$current_row}", "NA");
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("J{$current_row}")
                        ->getAlignment()
                        ->setVertical(Alignment::VERTICAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("J{$current_row}")->applyFromArray($this->border_right_top);
                }

                if ($l2_signature != "NA" && $l2_signature != "") {
                    $dataSl2 = "data:image/png;base64," . $l2_signature;
                    list($type, $dataSl2) = explode(';', $dataSl2);
                    list(, $dataSl2) = explode(',', $dataSl2);

                    $dataSl2 = base64_decode($dataSl2);
                    //echo storage_path()."/image.png";die;
                    file_put_contents(storage_path() . "/imageUserl2" . $data["physician_id"] . ".png", $dataSl2);
                    $objDrawingPType = new Drawing();
                    //$objDrawingPType->setWorksheet($workbook->setActiveSheetIndex($sheetIndex)->mergeCells("J{$current_row}:J{$current_row}"));
                    $objDrawingPType->setWorksheet($workbook->setActiveSheetIndex($sheetIndex));
                    $objDrawingPType->setName("Signature(s)");
                    $objDrawingPType->setPath(storage_path() . "/imageUserl2" . $data["physician_id"] . ".png");
                    $objDrawingPType->setCoordinates("K" . $current_row);
                    $objDrawingPType->setOffsetX(1);
                    $objDrawingPType->setOffsetY(5);
                    $objDrawingPType->setWidthAndHeight(150, 110);
                    $objDrawingPType->setResizeProportional(true);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getColumnDimension('D')
                        ->setWidth(40);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getRowDimension($current_row)
                        ->setRowHeight(90);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("K{$current_row}")->applyFromArray($this->border_right_top);
//                    unset($count_prev);
//                    $count_prev = array();
                } else {
                    //$current_row += 2;
                    $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("K{$current_row}", "NA");
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("K{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("K{$current_row}")
                        ->getAlignment()
                        ->setVertical(Alignment::VERTICAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("K{$current_row}")->applyFromArray($this->border_right_top);
                }
                if ($l3_signature != "NA" && $l3_signature != "") {
                    $dataSl3 = "data:image/png;base64," . $l3_signature;
                    list($type, $dataSl3) = explode(';', $dataSl3);
                    list(, $dataSl3) = explode(',', $dataSl3);

                    $dataSl3 = base64_decode($dataSl3);
                    //echo storage_path()."/image.png";die;
                    file_put_contents(storage_path() . "/imageUserl3" . $data["physician_id"] . ".png", $dataSl3);
                    $objDrawingPType = new Drawing();
                    //$objDrawingPType->setWorksheet($workbook->setActiveSheetIndex($sheetIndex)->mergeCells("J{$current_row}:J{$current_row}"));
                    $objDrawingPType->setWorksheet($workbook->setActiveSheetIndex($sheetIndex));
                    $objDrawingPType->setName("Signature(s)");
                    $objDrawingPType->setPath(storage_path() . "/imageUserl3" . $data["physician_id"] . ".png");
                    $objDrawingPType->setCoordinates("L" . $current_row);
                    $objDrawingPType->setOffsetX(1);
                    $objDrawingPType->setOffsetY(5);
                    $objDrawingPType->setWidthAndHeight(150, 110);
                    $objDrawingPType->setResizeProportional(true);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getColumnDimension('D')
                        ->setWidth(40);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getRowDimension($current_row)
                        ->setRowHeight(90);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("L{$current_row}")->applyFromArray($this->border_right_top);
//                    unset($count_prev);
//                    $count_prev = array();
                } else {
                    //$current_row += 2;
                    $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("L{$current_row}", "NA");
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("L{$current_row}")
                        ->getAlignment()
                        ->setVertical(Alignment::VERTICAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("L{$current_row}")->applyFromArray($this->border_right_top);
                }
                if ($l4_signature != "NA" && $l4_signature != "") {
                    $dataSl4 = "data:image/png;base64," . $l4_signature;
                    list($type, $dataSl4) = explode(';', $dataSl4);
                    list(, $dataSl4) = explode(',', $dataSl4);

                    $dataSl4 = base64_decode($dataSl4);
                    //echo storage_path()."/image.png";die;
                    file_put_contents(storage_path() . "/imageUserl4" . $data["physician_id"] . ".png", $dataSl4);
                    $objDrawingPType = new Drawing();
                    //$objDrawingPType->setWorksheet($workbook->setActiveSheetIndex($sheetIndex)->mergeCells("J{$current_row}:J{$current_row}"));
                    $objDrawingPType->setWorksheet($workbook->setActiveSheetIndex($sheetIndex));
                    $objDrawingPType->setName("Signature(s)");
                    $objDrawingPType->setPath(storage_path() . "/imageUserl4" . $data["physician_id"] . ".png");
                    $objDrawingPType->setCoordinates("M" . $current_row);
                    $objDrawingPType->setOffsetX(1);
                    $objDrawingPType->setOffsetY(5);
                    $objDrawingPType->setWidthAndHeight(150, 110);
                    $objDrawingPType->setResizeProportional(true);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getColumnDimension('D')
                        ->setWidth(40);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getRowDimension($current_row)
                        ->setRowHeight(90);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("M{$current_row}")->applyFromArray($this->border_right_top);
//                    unset($count_prev);
//                    $count_prev = array();
                } else {
                    //$current_row += 2;
                    $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("M{$current_row}", "NA");
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("M{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("M{$current_row}")
                        ->getAlignment()
                        ->setVertical(Alignment::VERTICAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("M{$current_row}")->applyFromArray($this->border_right_top);
                }
                if ($l5_signature != "NA" && $l5_signature != "") {
                    $dataSl5 = "data:image/png;base64," . $l5_signature;
                    list($type, $dataSl5) = explode(';', $dataSl5);
                    list(, $dataSl5) = explode(',', $dataSl5);

                    $dataSl5 = base64_decode($dataSl5);
                    //echo storage_path()."/image.png";die;
                    file_put_contents(storage_path() . "/imageUserl5" . $data["physician_id"] . ".png", $dataSl5);
                    $objDrawingPType = new Drawing();
                    //$objDrawingPType->setWorksheet($workbook->setActiveSheetIndex($sheetIndex)->mergeCells("J{$current_row}:J{$current_row}"));
                    $objDrawingPType->setWorksheet($workbook->setActiveSheetIndex($sheetIndex));
                    $objDrawingPType->setName("Signature(s)");
                    $objDrawingPType->setPath(storage_path() . "/imageUserl5" . $data["physician_id"] . ".png");
                    $objDrawingPType->setCoordinates("N" . $current_row);
                    $objDrawingPType->setOffsetX(1);
                    $objDrawingPType->setOffsetY(5);
                    $objDrawingPType->setWidthAndHeight(150, 110);
                    $objDrawingPType->setResizeProportional(true);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getColumnDimension('D')
                        ->setWidth(40);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getRowDimension($current_row)
                        ->setRowHeight(90);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("N{$current_row}")->applyFromArray($this->border_right_top);
//                    unset($count_prev);
//                    $count_prev = array();
                } else {
                    //$current_row += 2;
                    $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("N{$current_row}", "NA");
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("N{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("N{$current_row}")
                        ->getAlignment()
                        ->setVertical(Alignment::VERTICAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("N{$current_row}")->applyFromArray($this->border_right_top);
                }

                if ($l6_signature != "NA" && $l6_signature != "") {
                    $dataSl6 = "data:image/png;base64," . $l6_signature;
                    list($type, $dataSl6) = explode(';', $dataSl6);
                    list(, $dataSl6) = explode(',', $dataSl6);

                    $dataSl6 = base64_decode($dataSl6);
                    //echo storage_path()."/image.png";die;
                    file_put_contents(storage_path() . "/imageUserl6" . $data["physician_id"] . ".png", $dataSl6);
                    $objDrawingPType = new Drawing();
                    //$objDrawingPType->setWorksheet($workbook->setActiveSheetIndex($sheetIndex)->mergeCells("K{$current_row}:K{$current_row}"));
                    $objDrawingPType->setWorksheet($workbook->setActiveSheetIndex($sheetIndex));
                    $objDrawingPType->setName("Signature(s)");
                    $objDrawingPType->setPath(storage_path() . "/imageUserl6" . $data["physician_id"] . ".png");
                    $objDrawingPType->setCoordinates("O" . $current_row);
                    $objDrawingPType->setOffsetX(1);
                    $objDrawingPType->setOffsetY(5);
                    $objDrawingPType->setWidthAndHeight(150, 110);
                    $objDrawingPType->setResizeProportional(true);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getColumnDimension('D')
                        ->setWidth(40);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getRowDimension($current_row)
                        ->setRowHeight(90);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("O{$current_row}")->applyFromArray($this->border_right_top);
//                    unset($count_prev);
//                    $count_prev = array();
                } else {
                    //$current_row += 2;
                    $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("O{$current_row}", "NA");
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("O{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("O{$current_row}")
                        ->getAlignment()
                        ->setVertical(Alignment::VERTICAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("O{$current_row}")->applyFromArray($this->border_right_top);
                }

            } else {
                //$current_row += 2;
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("I{$current_row}", "NA");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("I{$current_row}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("I{$current_row}")->applyFromArray($this->border_right_top);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("J{$current_row}", "NA");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("J{$current_row}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("J{$current_row}")->applyFromArray($this->border_right_top);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("K{$current_row}", "NA");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("K{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("K{$current_row}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("K{$current_row}")->applyFromArray($this->border_right_top);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("L{$current_row}", "NA");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("L{$current_row}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("L{$current_row}")->applyFromArray($this->border_right_top);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("M{$current_row}", "NA");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("M{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("M{$current_row}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("M{$current_row}")->applyFromArray($this->border_right_top);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("N{$current_row}", "NA");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("N{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("N{$current_row}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("N{$current_row}")->applyFromArray($this->border_right_top);
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("O{$current_row}", "NA");
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("O{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("O{$current_row}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("O{$current_row}")->applyFromArray($this->border_right_top);
            }
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("I{$current_row}")->getAlignment()->applyFromArray($this->sign_box_style);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("J{$current_row}")->getAlignment()->applyFromArray($this->sign_box_style);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("K{$current_row}:O{$current_row}")->getAlignment()->applyFromArray($this->sign_box_style);
//                $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("E{$current_row}:I{$current_row}");
//                $current_row++;
//                $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("E{$current_row}:I{$current_row}");
//                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("E{$current_row}:I{$current_row}")->applyFromArray($this->border_top);
//                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("E{$current_row}", "Physician Signature");
//                $workbook->setActiveSheetIndex($sheetIndex)
//                    ->getStyle("E{$current_row}")
//                    ->getAlignment()
//                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
        $workbook->removeSheetByIndex(0);
        if ($workbook->getSheetCount() == 0) {
            $this->failure("breakdowns.logs_unavailable");
            return;
        }

        if(is_practice_manager()) {
            $report_practice = Practice::findOrFail($report_practice_id);
            $report_path = practice_report_path($report_practice);
        }else{
            $report_path = hospital_report_path($hospital);
        }
        // $report_filename = "report_" . date('mdYhis') . ".xlsx";
        $timeZone = str_replace(' ','_', $physician_data["data"]["localtimeZone"]);
        $timeZone = str_replace('/','', $timeZone);
        $timeZone = str_replace(':','', $timeZone);
		$report_filename = "physicianLogReport_" . $hospital->name . "_"  . $timeZone . ".xlsx";

        if (!file_exists($report_path)) {
            mkdir($report_path, 0777, true);
        }

        $writer = IOFactory::createWriter($workbook, 'Xlsx');
        $writer->save("{$report_path}/{$report_filename}");

        if(is_practice_manager()){
            $hospital_report = new practiceManagerReport();
            $hospital_report->practice_id = $report_practice_id;
        }else {
            $hospital_report = new HospitalReport;
            $hospital_report->hospital_id = $hospital->id;
        }
        $hospital_report->filename = $report_filename;
        $hospital_report->type = 2;
        $hospital_report->save();

        $this->success('breakdowns.generate_success', $hospital_report->id, $hospital_report->filename);
    }

    private function writeContractHeader($workbook, $sheetIndex, $index, $contract_name,$start_date, $end_date)
    {
        $current_row = $index;

        $workbook->setActiveSheetIndex($sheetIndex)
            ->mergeCells("B{$current_row}:O{$current_row}")
            ->setCellValue("B{$current_row}", $contract_name);
        $workbook->setActiveSheetIndex($sheetIndex)
            ->getStyle("B{$current_row}:O{$current_row}")->applyFromArray($this->contract_style);
        $workbook->setActiveSheetIndex($sheetIndex)
            ->getRowDimension($current_row)->setRowHeight(-1);
        $workbook->setActiveSheetIndex($sheetIndex)
            ->getStyle("B{$current_row}:O{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheetIndex)
            ->getStyle("B{$current_row}:O{$current_row}")->getFont()->setBold(true);
        $current_row++;
        $report_header = "Contract Period: " . format_date($start_date) . " - " . format_date($end_date);
        $workbook->setActiveSheetIndex($sheetIndex)
            ->mergeCells("B{$current_row}:O{$current_row}")
            ->setCellValue("B{$current_row}", $report_header);
        $workbook->setActiveSheetIndex($sheetIndex)
            ->getStyle("B{$current_row}:O{$current_row}")->applyFromArray($this->period_style);
        $workbook->setActiveSheetIndex($sheetIndex)
            ->getRowDimension($current_row)->setRowHeight(-1);
        $workbook->setActiveSheetIndex($sheetIndex)
            ->getStyle("B{$current_row}:O{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheetIndex)
            ->getStyle("B{$current_row}:O{$current_row}")->getFont()->setBold(true);
        $current_row++;
        return $current_row;
    }

    private function writePeriodHeader($workbook, $sheetIndex, $index, $date_range)
    {
        $current_row = $index;
        $report_header = "Report Period: " . $date_range;
        $workbook->setActiveSheetIndex($sheetIndex)
            ->mergeCells("B{$current_row}:O{$current_row}")
            ->setCellValue("B{$current_row}", $report_header);
        $workbook->setActiveSheetIndex($sheetIndex)
            ->getStyle("B{$current_row}:O{$current_row}")->applyFromArray($this->period_style);
        $workbook->setActiveSheetIndex($sheetIndex)
            ->getRowDimension($current_row)->setRowHeight(-1);
        $workbook->setActiveSheetIndex($sheetIndex)
            ->getStyle("B{$current_row}:O{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheetIndex)
            ->getStyle("B{$current_row}:O{$current_row}")->getFont()->setBold(true);
        $current_row++;
        return $current_row;
    }

    protected function parseArguments()
    {
        $result = new StdClass;
        $result->hospital_id = $this->argument("hospital");
        $result->contract_type = $this->argument("contract_type");
        $result->physician_ids = $this->argument("physicians");
        $result->report_data = $this->argument("report_data");
        $result->physicians = explode(",", $result->physician_ids);
        $result->start_date = null;
        $result->end_date = null;
        $result->agreements = parent::parseArguments();

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

    protected function getArguments()
    {
        return [
            ["hospital", InputArgument::REQUIRED, "The hospital ID."],
            ["contract_type", InputArgument::REQUIRED, "The contract type."],
            ["physicians", InputArgument::REQUIRED, "The physician IDs."],
            ["agreements", InputArgument::REQUIRED, "The hospital agreement IDs."],
            ["months", InputArgument::REQUIRED, "The agreement months."],
            ["report_data", InputArgument::REQUIRED, "The report data."]
        ];
    }

    protected function getOptions()
    {
        return [];
    }
}