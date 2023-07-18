<?php
namespace App\Console\Commands;

use App\HospitalReport;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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
use DateTime;
use StdClass;
use App\ContractType;
use App\Hospital;
use App\PaymentType;
use function App\Start\hospital_report_path;

/*
 * Column Header changed according to contract type
 * for Medical Directorship,Hours Expected and
 * for Other than that, Max Hours Possible
 * */

class BreakdownReportCommand extends ReportingCommand
{
    protected $name = "reports:breakdown";
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

    private $log_details_style = [
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_MEDIUM],
            'right' => ['borderStyle' => Border::BORDER_MEDIUM]
        ]
    ];

    private $sign_box_style = [
        'borders' => [

            'left' => ['borderStyle' => Border::BORDER_THIN],
            'right' => ['borderStyle' => Border::BORDER_THIN],
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

        $report_data = $this->getReportData($arguments);

        if (count($report_data) == 0) {
            $this->failure("generate_error");
        }

        $now = Carbon::now();
		$timezone = $now->timezone->getName();
		$timestamp = format_date((exec('time /T')), "h:i A");

        /*
         * Column Header changed according to contract type
         * for Medical Directorship,Hours Expected and
         * for Other than that, Max Hours Possible
         * */
        /*if ($arguments->contract_type == ContractType::MEDICAL_DIRECTORSHIP) {
            $contract_wise_header = "Max Hours Possible";
        } else {
            $contract_wise_header = "Hours Expected";
        }*//* remove the condition for adding payment type change*/
        //$header = "Physician Log Report\n" .
        //  "Run Date: " . format_date("now") . "\n";

        $header = '';
        $header .= strtoupper($hospital->name) . "\n";
        $header .= "Physician Log Report\n";
        $header .= "Period: " . $arguments->start_date->format("m/d/Y") . " - " . $arguments->end_date->format("m/d/Y") . "\n";
        $header .= "Run Date: " . with(new DateTime('now'))->format("m/d/Y");

        // $workbook = $this->loadTemplate("breakdown.xlsx");

        //Load template using phpSpreadsheet
        $reader = IOFactory::createReader("Xlsx");
		$workbook = $reader->load(storage_path()."/reports/templates/breakdown.xlsx");

        $workbook->setActiveSheetIndex(0)->setCellValue("B2", $header);
        //$workbook->setActiveSheetIndex(0)->setCellValue("G4", $contract_wise_header);
        $templateSheet = $workbook->getSheet(0);

        $sheetIndex = 0;
        foreach ($report_data as $agreement_data) {
            foreach ($agreement_data as $physician_data) {
                //if (count($physician_data["breakdown"]) > 0) {
                $sheetIndex++;
                $nextWorksheet = clone $templateSheet;
                $nextWorksheet->setTitle("" . $sheetIndex);
                $workbook->addSheet($nextWorksheet);
                if($physician_data['payment_type_id'] == PaymentType::HOURLY){
                    $contract_wise_header = "Max Hours Possible";
                } else {
                    $contract_wise_header = "Hours Expected";
                }
                $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("G4", $contract_wise_header);
                $i = 0;
                $j = 0;
                $current_row = 5;
                $current_row = $this->writeContractHeader($workbook, $sheetIndex, $current_row, $physician_data["agreement_name"]);
                $approved_date = $this->fetch_approved_date($physician_data["physician_id"], $physician_data["contract_id"], $arguments->start_date, $arguments->end_date);

                foreach ($approved_date as $approval_date) {
                    if ($approval_date->approval_date != "0000-00-00") {
                        $approve_date_arr[$j] = date('m/d/Y', strtotime($approval_date->approval_date));
                    }
                    else if ($approval_date->approval_date == "0000-00-00" && $approval_date->signature > 0) {
                        $approve_date_arr[$j] = date('m/d/Y', strtotime($approval_date->updated_at));
                    } else {
                        $approve_date_arr[$j] = "";
                    }
                    $j++;
                }

                $current_row = $this->writePeriodHeader($workbook, $sheetIndex, $current_row, $physician_data["agreement_start_date"], $physician_data["agreement_end_date"]);

                $workbook->setActiveSheetIndex($sheetIndex)
                    ->setCellValue("B{$current_row}", $physician_data["practice_name"])
                    ->setCellValue("C{$current_row}", $physician_data["physician_name"] . " Totals")
                    ->mergeCells("D{$current_row}:E{$current_row}")
                    ->setCellValue("F{$current_row}", $physician_data["date_range"])
                    ->setCellValue("G{$current_row}", //$physician_data["max_hours"])
                        ($arguments->contract_type == ContractType::MEDICAL_DIRECTORSHIP)?
                            $physician_data["max_hours"] : $physician_data["expected_hours"])
                    ->setCellValue("H{$current_row}", round($physician_data["worked_hours"], 2));
                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:I{$current_row}")->applyFromArray($this->total_style);
                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:I{$current_row}")->getFont()->setBold(true);
                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}")->applyFromArray($this->action_style);
                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("F{$current_row}")->applyFromArray($this->sign_box_style);
                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("G{$current_row}:I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                //$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:D{$current_row}")->applyFromArray($this->shaded_style);
                //$workbook->setActiveSheetIndex($sheetIndex)->getStyle("E{$current_row}:H{$current_row}")->applyFromArray($this->shaded_style);

                $workbook->getActiveSheet()->setTitle($physician_data["physician_name"]);
                $worked_hours_present = false;

                if ($physician_data["worked_hours"] > 0) {
                    $worked_hours_present = true;
                }

                $current_row++;
                $start_break_down_row = $current_row;
                foreach ($physician_data["breakdown"] as $breakdown) {

                    $workbook->setActiveSheetIndex($sheetIndex)
                        //->mergeCells("B{$current_row}:C{$current_row}")
                        //->setCellValue("B{$current_row}", $physician_data["physician_name"])
                        ->setCellValue("D{$current_row}", $breakdown["action"])
                        ->setCellValue("E{$current_row}", (strlen($breakdown["notes"]) > 0) ? $breakdown["notes"] : "-")
                        ->setCellValue("F{$current_row}", $breakdown["date"])
                        ->setCellValue("G{$current_row}", "-")
                        ->setCellValue("H{$current_row}", $breakdown["worked_hours"])
                        ->setCellValue("I{$current_row}", $approve_date_arr[$i]);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}:H{$current_row}")->applyFromArray($this->cell_style);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}")->applyFromArray($this->action_style);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("E{$current_row}")->getAlignment()->setWrapText(true);
                    $workbook->setActiveSheetIndex($sheetIndex)->getRowDimension($current_row)->setRowHeight(-1);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("F{$current_row}:H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("H{$current_row}")->applyFromArray($this->shaded_style_worked);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("I{$current_row}")->applyFromArray($this->shaded_style);
                    $current_row++;
                    $i++;
                    /*if($physician_data["worked_hours"] > 0){
                        $worker_hours = true;
                    }*/
                }
                $new_current_row = $current_row - 1;
                if (count($physician_data["breakdown"]) > 0) {
                    $physician_row = $current_row - (($current_row - $start_break_down_row) / 2);
                    $physician_row = floor($physician_row);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$start_break_down_row}:C{$new_current_row}")->applyFromArray($this->physician_breakdown_style);
                    $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("B{$physician_row}:C{$physician_row}");
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$physician_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("B{$physician_row}", $physician_data["physician_name"]);
                }
                $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$new_current_row}:I{$new_current_row}")->applyFromArray($this->border_bottom);

                if ($worked_hours_present) {
                    $monthArr = explode(" - ", $physician_data["date_range"]);
                    $start_date = mysql_date($monthArr[0]);
                    $end_date = mysql_date($monthArr[1]);
                    $signature = DB::table('signature')
                        ->where('physician_id', '=', $physician_data["physician_id"])
                        //->whereBetween("date", array($start_date, $end_date))
                        ->first();
                }

                //Display signature only if physician worked in that month
                if (count($physician_data["breakdown"]) > 0) {
                    // increment rows to add rows difference between table, and sign box
                    $current_row += 4;
                    $boxSize = $current_row + 3;
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("E{$current_row}:I{$boxSize}")->applyFromArray($this->sign_box_style);
                    $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("E{$current_row}:I{$current_row}");
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("E{$current_row}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->setCellValue("E{$current_row}", "Hours approved for the month of " . date("F Y", strtotime($arguments->agreements[0]->start_date)));
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("E{$current_row}")->applyFromArray($this->underline_text);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getRowDimension($current_row)
                        ->setRowHeight(25);
                    //$current_row += 2;

                    if (isset($signature->signature_path)) {
                        $current_row++;
                        $data = "data:image/png;base64," . $signature->signature_path;
                        list($type, $data) = explode(';', $data);
                        list(, $data) = explode(',', $data);

                        $data = base64_decode($data);
                        //echo storage_path()."/image.png";die;
                        file_put_contents(storage_path() . "/image" . $physician_data["physician_id"] . ".png", $data);
                        // $objDrawingPType = new PHPExcel_Worksheet_Drawing();
                        $objDrawingPType = new Drawing();
                        $objDrawingPType->setWorksheet($workbook->setActiveSheetIndex($sheetIndex)->mergeCells("F{$current_row}:I{$current_row}"));
                        $objDrawingPType->setName("Signature");
                        $objDrawingPType->setPath(storage_path() . "/image" . $physician_data["physician_id"] . ".png");
                        $objDrawingPType->setCoordinates("F" . $current_row);
                        $objDrawingPType->setOffsetX(1);
                        $objDrawingPType->setOffsetY(5);
                        $objDrawingPType->setWidthAndHeight(350, 100);
                        $objDrawingPType->setResizeProportional(true);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getColumnDimension('E')
                            ->setWidth(40);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getRowDimension($current_row)
                            ->setRowHeight(80);
                        $current_row++;
                    } else {
                        $current_row += 2;
                    }
                    $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("E{$current_row}:I{$current_row}");
                    $current_row++;
                    $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("E{$current_row}:I{$current_row}");
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("E{$current_row}:I{$current_row}")->applyFromArray($this->border_top);
                    $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("E{$current_row}", "Physician Signature");
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("E{$current_row}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                //changes for on call. change header and remove expected hours column
                if ($arguments->contract_type == 4) {
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->setCellValue("H4", "Days Worked");
                    $workbook->setActiveSheetIndex($sheetIndex)->removeColumn("G");
                }
            }
        }
        $workbook->removeSheetByIndex(0);

        if ($workbook->getSheetCount() == 0) {
            $this->failure("breakdowns.logs_unavailable");
            return;
        }

        $report_path = hospital_report_path($hospital);
        // $report_filename = "report_" . date('mdYhis') . ".xlsx";
        $report_filename = "report_" . $hospital->name . "_" . date('mdY') . "_" . str_replace(":", "", $timestamp) . "_" . $timezone . ".xlsx";

        if (!file_exists($report_path)) {
            mkdir($report_path, 0777, true);
        }

        $writer = IOFactory::createWriter($workbook, 'Xlsx');
        $writer->save("{$report_path}/{$report_filename}");

        $hospital_report = new HospitalReport;
        $hospital_report->hospital_id = $hospital->id;
        $hospital_report->filename = $report_filename;
        $hospital_report->type = 2;
        $hospital_report->save();

        $this->success('breakdowns.generate_success', $hospital_report->id, $hospital_report->filename);
    }

    private function writeContractHeader($workbook, $sheetIndex, $index, $contract_name)
    {
        $current_row = $index;

        $workbook->setActiveSheetIndex($sheetIndex)
            ->mergeCells("B{$current_row}:I{$current_row}")
            ->setCellValue("B{$current_row}", $contract_name);
        $workbook->setActiveSheetIndex($sheetIndex)
            ->getStyle("B{$current_row}:I{$current_row}")->applyFromArray($this->contract_style);
        $workbook->setActiveSheetIndex($sheetIndex)
            ->getRowDimension($current_row)->setRowHeight(-1);
        $workbook->setActiveSheetIndex($sheetIndex)
            ->getStyle("B{$current_row}:I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheetIndex)
            ->getStyle("B{$current_row}:I{$current_row}")->getFont()->setBold(true);
        $current_row++;
        return $current_row;
    }

    private function writePeriodHeader($workbook, $sheetIndex, $index, $start_date, $end_date)
    {
        $current_row = $index;
        $report_header = "Contract Period: " . $start_date . " - " . $end_date;
        $workbook->setActiveSheetIndex($sheetIndex)
            ->mergeCells("B{$current_row}:I{$current_row}")
            ->setCellValue("B{$current_row}", $report_header);
        $workbook->setActiveSheetIndex($sheetIndex)
            ->getStyle("B{$current_row}:I{$current_row}")->applyFromArray($this->period_style);
        $workbook->setActiveSheetIndex($sheetIndex)
            ->getRowDimension($current_row)->setRowHeight(-1);
        $workbook->setActiveSheetIndex($sheetIndex)
            ->getStyle("B{$current_row}:I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheetIndex)
            ->getStyle("B{$current_row}:I{$current_row}")->getFont()->setBold(true);
        $current_row++;
        return $current_row;
    }

    public function fetch_approved_date($physician_id, $contract_id, $start_date, $end_date)
    {
        $approval_date = DB::table('physician_logs')->select('approval_date','signature','updated_at')
            ->where("physician_id", "=", $physician_id)
            ->where("contract_id", "=", $contract_id)
            ->whereBetween("date", [mysql_date($start_date), mysql_date($end_date)])
            ->orderBy('date', 'ASC')
            ->get();

        return $approval_date;
    }

    private function getReportData($arguments)
    {
        $results = [];

        foreach ($arguments->agreements as $agreement) {
            $lastday=date("m/t/Y");
            if(strtotime($lastday) < strtotime($agreement->end_date))
            {
                $agreement->end_date=$lastday;
            }
            $results[] = $this->queryReportData($agreement, $arguments->physicians, $arguments->contract_type);
        }

        return $results;
    }

    private function queryReportData($agreement, $physicians, $contract_type)
    {

        $results = [];

        foreach ($physicians as $physician) {
            $start_date = mysql_date($agreement->start_date);
            $end_date = mysql_date($agreement->end_date);

            $query = DB::table("contracts")->select(
                DB::raw("practices.name as practice_name"),
                DB::raw("physicians.first_name as first_name"),
                DB::raw("physicians.last_name as last_name"),
                DB::raw("physicians.id as physician_id "),
                DB::raw("contracts.id as contract_id"),
                DB::raw("contracts.contract_type_id as contract_type_id"),
                DB::raw("contracts.contract_name_id as contract_name_id"),
                DB::raw("contract_types.name as contract_name"),
                DB::raw("contracts.expected_hours as expected_hours"),
                DB::raw("contracts.max_hours as max_hours"),
                DB::raw("sum(physician_logs.duration) as worked_hours"),
                DB::raw("agreements.start_date as start_date"),
                DB::raw("agreements.end_date as end_date"),
                DB::raw("agreements.name as agreement_name")

            )
                ->join("physicians", "physicians.id", "=", "contracts.physician_id")
                //drop column practice_id from table 'physicians' changes by 1254
                ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
                ->join('practices', 'practices.id', '=', 'physician_practices.practice_id')

                // ->join("practices", "practices.id", "=", "physicians.practice_id")
                
                ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                ->join("physician_logs", "physician_logs.contract_id", "=", "contracts.id")
                ->join("agreements", "contracts.agreement_id", "=", "agreements.id")
                ->where("contracts.agreement_id", "=", $agreement->id)
                ->where("contracts.physician_id", "=", $physician)
                ->whereBetween("physician_logs.date", [$start_date, $end_date]);

            if ($contract_type != -1) {
                $contracts = $query->where("contracts.contract_type_id", "=", $contract_type)->get();
            } else {
                $contracts = $query->get();
            }

            /*
             * "expected_hours" => $contract->expected_hours * $agreement->month_range
             * value of 'month_range' will always be 1 because,
             * user is allowed to select only one month
             */

            foreach ($contracts as $contract) {
                $contract_data = [
                    "physician_name" => "{$contract->last_name}, {$contract->first_name}",
                    "physician_id" => $contract->physician_id,
                    "practice_name" => $contract->practice_name,
                    "contract_id" => $contract->contract_id,
                    "date_range" => "{$agreement->start_date} - {$agreement->end_date}",
                    "expected_hours" => $contract->expected_hours * $agreement->month_range,
                    "max_hours" => $contract->max_hours * $agreement->month_range,
                    "worked_hours" => $contract->worked_hours,
                    "contract_name" => $contract->contract_name,
                    "agreement_name" => $contract->agreement_name,
                    "agreement_start_date" => date('m/d/Y', strtotime($contract->start_date)),
                    "agreement_end_date" => date('m/d/Y', strtotime($contract->end_date)),
                    "breakdown" => []
                ];

                $logs = DB::table("physician_logs")->select(
                    DB::raw("actions.name as action"),
                    DB::raw("actions.action_type_id as action_type_id"),
                    DB::raw("physician_logs.date as date"),
                    DB::raw("physician_logs.duration as worked_hours"),
                    DB::raw("physician_logs.details as notes")
                )
                    ->join("actions", "actions.id", "=", "physician_logs.action_id")
                    ->where("physician_logs.contract_id", "=", $contract->contract_id)
                    ->whereBetween("physician_logs.date", [$start_date, $end_date])
                    ->orderBy("physician_logs.date", "asc")
                    ->get();

                foreach ($logs as $log) {
                    if ($log->action_type_id == 3) $log->action = "Custom: Activity";
                    if ($log->action_type_id == 4) $log->action = "Custom: Mgmt Duty";

                    $contract_data["breakdown"][] = [
                        "action" => $log->action,
                        "date" => format_date($log->date),
                        "worked_hours" => $log->worked_hours,
                        "notes" => $log->notes
                    ];
                }
                $results[] = $contract_data;
            }
        }

        return $results;
        // 1. Query physician contracts
        // 2. Query actions for each contract
    }

    protected function parseArguments()
    {
        $result = new StdClass;
        $result->hospital_id = $this->argument("hospital");
        $result->contract_type = $this->argument("contract_type");
        $result->physician_ids = $this->argument("physicians");
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
            ["months", InputArgument::REQUIRED, "The agreement months."]
        ];
    }

    protected function getOptions()
    {
        return [];
    }
}
