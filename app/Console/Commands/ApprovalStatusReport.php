<?php
namespace App\Console\Commands;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
//Below imports are for php spreadsheets.
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use StdClass;
use DateTime;
use App\HospitalReport;
use function App\Start\payment_status_dashboard_report_path;
use function App\Start\approval_report_path;

/*
 * Column Header changed according to contract type
 * for Medical Directorship,Hours Expected and
 * for Other than that, Max Hours Possible
 * */

class ApprovalStatusReport extends ReportingCommand
{
	protected $name = "reports:approvalstatus";
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
			'top' => ['borderStyle' => Border::BORDER_MEDIUM],
		]
	];
	private $border_bottom = [
		'borders' => [
			'bottom' => ['borderStyle' => Border::BORDER_MEDIUM],
		]
	];

	private $border_right = [
		'borders' => [
			'right' => ['borderStyle' => Border::BORDER_MEDIUM],
		]
	];

	private $border_left = [
		'borders' => [
			'left' => ['borderStyle' => Border::BORDER_MEDIUM],
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
	private $shaded_style_status = [
		'fill' => [
			'fillType' => Fill::FILL_SOLID,
			'color' => ['rgb' => 'ffff00']
		],
		'borders' => [
			'inside' => ['borderStyle' => Border::BORDER_THIN],
			'left' => ['borderStyle' => Border::BORDER_MEDIUM],
			'right' => ['borderStyle' => Border::BORDER_MEDIUM],
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
		'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'dbeef4']],
		'font' => ['color' => ['rgb' => '000000'], 'size' => 12],
		'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
		'borders' => [
			'allborders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']],
			//'inside' => ['borderStyle' => Border::BORDER_MEDIUM,'color' => ['rgb' => 'FF0000']]
		]

	];

	private $all_border = [
		'borders' => [
			'allborders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']],
			//'inside' => ['borderStyle' => Border::BORDER_MEDIUM,'color' => ['rgb' => 'FF0000']]
		]

	];
	private $total_style = [
		'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'dbeef4']],
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

			'left' => ['borderStyle' => Border::BORDER_THIN],
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
		// $workbook = $this->loadTemplate("approval_status.xlsx");

		$now = Carbon::now();
		$timezone = $now->timezone->getName();
		$timestamp = format_date((exec('time /T')), "h:i A");
		
		//Load template using phpSpreadsheet
		$reader = IOFactory::createReader("Xlsx");
		$workbook = $reader->load(storage_path()."/reports/templates/approval_status.xlsx");

		$templateSheet = $workbook->getSheet(0);
		$sheetIndex = 1;
		$header_name_for_report = "Approval";
		if($arguments->report_type == 1){
			$header_name_for_report = "Payment";
		}
		//foreach($arguments->report_data as $physician_data){
			$nextWorksheet = clone $templateSheet;
			$nextWorksheet->setTitle("" . $sheetIndex);
			$workbook->addSheet($nextWorksheet);
			$workbook->getActiveSheet()
				->getPageSetup()
				->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
			$workbook->getActiveSheet()
				->getPageSetup()
				->setPaperSize(PageSetup::PAPERSIZE_A4);
			$header = '';
			$header .= $header_name_for_report." Status Report\n";
			$header .= "Run Date: " . with($arguments->localtimeZone);

			$workbook->setActiveSheetIndex($sheetIndex)->setCellValue("B2", $header);
			$current_row = 4;

            if($arguments->report_type == 0){
                foreach($arguments->report_data as $log_Obj) {

                    if($arguments->show_calculated_payment){
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("B{$current_row}:R{$current_row}")->getAlignment()->setWrapText(true);
//                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("B{$current_row}:R{$current_row}");
//                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:R{$current_row}")->applyFromArray($this->cell_style);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("B{$current_row}:R{$current_row}")->getFont()->setBold(true);
                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("B{$current_row}:C{$current_row}");
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:C{$current_row}")->applyFromArray($this->cell_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("B{$current_row}", "Period: " .$log_Obj["month"]);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("D{$current_row}:E{$current_row}");
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}:E{$current_row}")->applyFromArray($this->cell_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("D{$current_row}", "Calculated Payment : ". $log_Obj["calculated_payment"]);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("F{$current_row}:R{$current_row}");
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("F{$current_row}:R{$current_row}")->applyFromArray($this->cell_style);
                        $workbook->setActiveSheetIndex($sheetIndex);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}")->applyFromArray($this->border_left);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("R{$current_row}")->applyFromArray($this->border_right);
                        $current_row++;
                    }
                    foreach($log_Obj["logs"] as $log) {
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("B{$current_row}:R{$current_row}")->getAlignment()->setWrapText(true);

                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getRowDimension($current_row)
                            ->setRowHeight(80);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->setCellValue("B{$current_row}", format_date($log["log_date"]))
                            ->setCellValue("C{$current_row}", $log["hospital_name"])
                            ->setCellValue("D{$current_row}", $log["agreement_name"])
                            ->setCellValue("E{$current_row}", $log["contract_name"])
                            ->setCellValue("F{$current_row}", $log["practice_name"])
                            ->setCellValue("G{$current_row}", $log["physician_name"])
                            ->setCellValue("H{$current_row}", $log["action"])
                            ->setCellValue("I{$current_row}", $log["log_details"])
                            ->setCellValue("J{$current_row}", $log["duration"])
                            ->setCellValue("K{$current_row}", $log["submitted_by"])
                            ->setCellValue("L{$current_row}", $log["levels"][0]["status"])
                            ->setCellValue("M{$current_row}", $log["levels"][1]["status"]."\n".$log["levels"][1]["name"])
                            ->setCellValue("N{$current_row}", $log["levels"][2]["status"]."\n".$log["levels"][2]["name"])
                            ->setCellValue("O{$current_row}", $log["levels"][3]["status"]."\n".$log["levels"][3]["name"])
                            ->setCellValue("P{$current_row}", $log["levels"][4]["status"]."\n".$log["levels"][4]["name"])
                            ->setCellValue("Q{$current_row}", $log["levels"][5]["status"]."\n".$log["levels"][5]["name"])
                            ->setCellValue("R{$current_row}", $log["levels"][6]["status"]."\n".$log["levels"][6]["name"]);
                        /*->setCellValue("L{$current_row}", $breakdown["CM_name"])
                        ->setCellValue("M{$current_row}", $breakdown["FM_name"]);*/

                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:H{$current_row}")->applyFromArray($this->cell_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}")->applyFromArray($this->action_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("C{$current_row}")->getAlignment()->setWrapText(true);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}")->getAlignment()->setWrapText(true);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("E{$current_row}")->getAlignment()->setWrapText(true);
                        $workbook->setActiveSheetIndex($sheetIndex)->getRowDimension($current_row)->setRowHeight(-1);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("K{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("M{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("N{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("O{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("P{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("Q{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("R{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("B{$current_row}:R{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("G{$current_row}")->applyFromArray($this->shaded_style_worked);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("H{$current_row}")->applyFromArray($this->cell_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("I{$current_row}")->applyFromArray($this->cell_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("J{$current_row}")->applyFromArray($this->cell_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("K{$current_row}")->applyFromArray($this->cell_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("L{$current_row}")->applyFromArray($this->cell_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("M{$current_row}")->applyFromArray($this->cell_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("N{$current_row}")->applyFromArray($this->cell_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("O{$current_row}")->applyFromArray($this->cell_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("P{$current_row}")->applyFromArray($this->cell_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("Q{$current_row}")->applyFromArray($this->cell_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("R{$current_row}")->applyFromArray($this->cell_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("R{$current_row}")->applyFromArray($this->border_right);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}")->applyFromArray($this->border_left);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("R{$current_row}")->applyFromArray($this->border_right);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:C{$current_row}")->getFont()->setBold(true);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("G{$current_row}")->getFont()->setBold(true);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("B{$current_row}:R{$current_row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                        //$workbook->setActiveSheetIndex($sheetIndex)->getStyle("L{$current_row}:M{$current_row}")->applyFromArray($this->shaded_style); remove on 3sep2018
                        $current_row++;
                    }
                }
            }
            else if($arguments->report_type == 1){
                foreach($arguments->report_data as $log) {
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("B{$current_row}:R{$current_row}")->getAlignment()->setWrapText(true);

                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getRowDimension($current_row)
                        ->setRowHeight(80);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->setCellValue("B{$current_row}", format_date($log["log_date"]))
                        ->setCellValue("C{$current_row}", $log["hospital_name"])
                        ->setCellValue("D{$current_row}", $log["agreement_name"])
                        ->setCellValue("E{$current_row}", $log["contract_name"])
                        ->setCellValue("F{$current_row}", $log["practice_name"])
                        ->setCellValue("G{$current_row}", $log["physician_name"])
                        ->setCellValue("H{$current_row}", $log["action"])
                        ->setCellValue("I{$current_row}", $log["log_details"])
                        ->setCellValue("J{$current_row}", $log["duration"])
                        ->setCellValue("K{$current_row}", $log["submitted_by"])
                        ->setCellValue("L{$current_row}", $log["levels"][0]["status"])
                        ->setCellValue("M{$current_row}", $log["levels"][1]["status"]."\n".$log["levels"][1]["name"])
                        ->setCellValue("N{$current_row}", $log["levels"][2]["status"]."\n".$log["levels"][2]["name"])
                        ->setCellValue("O{$current_row}", $log["levels"][3]["status"]."\n".$log["levels"][3]["name"])
                        ->setCellValue("P{$current_row}", $log["levels"][4]["status"]."\n".$log["levels"][4]["name"])
                        ->setCellValue("Q{$current_row}", $log["levels"][5]["status"]."\n".$log["levels"][5]["name"])
                        ->setCellValue("R{$current_row}", $log["levels"][6]["status"]."\n".$log["levels"][6]["name"]);
                    /*->setCellValue("L{$current_row}", $breakdown["CM_name"])
                    ->setCellValue("M{$current_row}", $breakdown["FM_name"]);*/

                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:H{$current_row}")->applyFromArray($this->cell_style);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}")->applyFromArray($this->action_style);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("C{$current_row}")->getAlignment()->setWrapText(true);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}")->getAlignment()->setWrapText(true);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("E{$current_row}")->getAlignment()->setWrapText(true);
                    $workbook->setActiveSheetIndex($sheetIndex)->getRowDimension($current_row)->setRowHeight(-1);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("K{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("M{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("N{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("O{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("P{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("Q{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("R{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("B{$current_row}:R{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("G{$current_row}")->applyFromArray($this->shaded_style_worked);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("H{$current_row}")->applyFromArray($this->cell_style);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("I{$current_row}")->applyFromArray($this->cell_style);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("J{$current_row}")->applyFromArray($this->cell_style);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("K{$current_row}")->applyFromArray($this->cell_style);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("L{$current_row}")->applyFromArray($this->cell_style);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("M{$current_row}")->applyFromArray($this->cell_style);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("N{$current_row}")->applyFromArray($this->cell_style);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("O{$current_row}")->applyFromArray($this->cell_style);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("P{$current_row}")->applyFromArray($this->cell_style);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("Q{$current_row}")->applyFromArray($this->cell_style);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("R{$current_row}")->applyFromArray($this->cell_style);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("R{$current_row}")->applyFromArray($this->border_right);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}")->applyFromArray($this->border_left);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("R{$current_row}")->applyFromArray($this->border_right);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:C{$current_row}")->getFont()->setBold(true);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("G{$current_row}")->getFont()->setBold(true);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("B{$current_row}:R{$current_row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    //$workbook->setActiveSheetIndex($sheetIndex)->getStyle("L{$current_row}:M{$current_row}")->applyFromArray($this->shaded_style); remove on 3sep2018
                    $current_row++;
                }
            }
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:R{$current_row}")->applyFromArray($this->border_top);
		$workbook->removeSheetByIndex(0);
		if ($workbook->getSheetCount() == 0) {
			$this->failure("breakdowns.logs_unavailable");
			return;
		}

        // $report_path = approval_report_path();
        // $report_filename = "approvalStatusReport_" . date('mdYhis') . ".xlsx";
        $current_time = date('mdYhis');
        $time = str_replace(' ','_', $arguments->localtimeZone);
        $time = str_replace('/','', $time);
        $time = str_replace(':','', $time);

        $time = ($time == '' ) ? $current_time : $time;

        if($arguments->report_type == 1) {
            $report_path = payment_status_dashboard_report_path(null, $arguments->user_id);	// payment_status_report_path();
            $report_filename = "paymentStatusReport_" . $time . ".xlsx";
        } else {
            $report_path = approval_report_path();
            $report_filename = "approvalStatusReport_" . $time . ".xlsx";
        }

        if (!file_exists($report_path)) {
            mkdir($report_path, 0777, true);
        }

		$writer = IOFactory::createWriter($workbook, 'Xlsx');
		$writer->save("{$report_path}/{$report_filename}");

		$hospital_report = new HospitalReport;
        $hospital_report->hospital_id = 0;
		$hospital_report->filename = $report_filename;
		$hospital_report->type = 0;
		$hospital_report->save();

		$this->success('breakdowns.generate_success', $hospital_report->id, $hospital_report->filename);
	}

	protected function parseArguments()
	{
		$result = new StdClass;
		$result->report_data = $this->argument("report_data");
		$result->report_type = $this->argument("report_type");
		$result->start_date = null;
		$result->end_date = null;
		$result->localtimeZone = $this->argument('localtimeZone');
        $result->show_calculated_payment = $this->argument('show_calculated_payment');
		$result->user_id = $this->argument("user_id");
		return $result;
	}

	protected function getArguments()
	{
		return [
			["report_data", InputArgument::REQUIRED, "The report data."],
			["report_type", InputArgument::REQUIRED, "The report type."],
			["localtimeZone", InputArgument::REQUIRED, "The agreement localtimeZone."],
            ["show_calculated_payment", InputArgument::REQUIRED, "The calculated payment flag."],
			["user_id", InputArgument::OPTIONAL, ""]
		];
	}

	protected function getOptions()
	{
		return [];
	}
}
