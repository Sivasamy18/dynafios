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

use App\Hospital;
use App\HospitalReport;
use function App\Start\hospital_report_path;


/*
 * Column Header changed according to contract type
 * for Medical Directorship,Hours Expected and
 * for Other than that, Max Hours Possible
 * */

class HospitalLawsonInterfacedReportCommand extends ReportingCommand
{
	protected $name = "reports:hospital_lawson_interfaced";
	protected $description = "Generates a DYNAFIOS hospital Lawson interface report.";

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
		$hospital = Hospital::findOrFail($arguments->hospital);
		// $workbook = $this->loadTemplate("hospital_lawson_interfaced_report.xlsx");

		$reader = IOFactory::createReader("Xlsx");//Load template using phpSpreadsheet
		$workbook = $reader->load(storage_path()."/reports/templates/hospital_lawson_interfaced_report.xlsx");

		$templateSheet = $workbook->getSheet(0);
		$sheetIndex = 1;

		$now = Carbon::now();
		$timezone = $now->timezone->getName();
		$timestamp = format_date((exec('time /T')), "h:i A");

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
			$header .= $hospital->name . "\n";
			$header .= "Lawson Interfaced Invoices Report\n";
			$header .= "The following invoices were sent to Lawson via AP520 on " . format_timestamp($arguments->interface_date) . ' Pacific.';

			$workbook->setActiveSheetIndex($sheetIndex)->setCellValue("D2", $header);
			$workbook->setActiveSheetIndex($sheetIndex)->getStyle("D2")->getFont()->setBold(true);
			$current_row = 4;
			foreach($arguments->report_data as $log) {
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("B{$current_row}:Q{$current_row}")->getAlignment()->setWrapText(true);

                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getRowDimension($current_row)
                        ->setRowHeight(80);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->setCellValue("B{$current_row}", format_timestamp($log["date_sent"]))
                        ->setCellValue("C{$current_row}", $log["practice_name"])
                        ->setCellValue("D{$current_row}", $log["last_name"] . ', ' . $log["first_name"])
                        ->setCellValue("E{$current_row}", $log["agreement_name"])
                        ->setCellValue("F{$current_row}", $log["contract_name"])
                        ->setCellValue("G{$current_row}", format_date($log["start_date"]) . ' - ' . format_date($log["end_date"]))
                        ->setCellValue("H{$current_row}", $log["amountPaid"])
                        ->setCellValue("I{$current_row}", $log["invoice_number"])
                        ->setCellValue("J{$current_row}", $log["cvi_vendor"])
                        ->setCellValue("K{$current_row}", $log["cvi_company"])
                        ->setCellValue("L{$current_row}", $log["cvi_auth_code"])
                        ->setCellValue("M{$current_row}", $log["cvi_proc_level"])
                        ->setCellValue("N{$current_row}", $log["cvd_dist_company"])
                        ->setCellValue("O{$current_row}", $log["cvd_dis_acct_unit"])
                        ->setCellValue("P{$current_row}", $log["cvd_dis_account"])
                        ->setCellValue("Q{$current_row}", $log["cvd_dis_sub_acct"]);
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
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("B{$current_row}:Q{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("G{$current_row}")->applyFromArray($this->cell_style);
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
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("Q{$current_row}")->applyFromArray($this->border_right);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}")->applyFromArray($this->border_left);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("Q{$current_row}")->applyFromArray($this->border_right);
                    //$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:C{$current_row}")->getFont()->setBold(true);
					//$workbook->setActiveSheetIndex($sheetIndex)->getStyle("G{$current_row}")->getFont()->setBold(true);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("H{$current_row}")->getNumberFormat()->setFormatCode('$#,##0.00');
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("B{$current_row}:Q{$current_row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    //$workbook->setActiveSheetIndex($sheetIndex)->getStyle("L{$current_row}:M{$current_row}")->applyFromArray($this->shaded_style); remove on 3sep2018
                    $current_row++;
			}
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:Q{$current_row}")->applyFromArray($this->border_top);
		$workbook->removeSheetByIndex(0);
		if ($workbook->getSheetCount() == 0) {
			$this->failure("lawson_interface.logs_unavailable");
			return;
		}

		$report_path = hospital_report_path($hospital);
		// $report_filename = "Lawson_Interfaced_Report_" . date('mdYhis') . ".xlsx";
		$report_filename = "Lawson_Interfaced_Report_" . $hospital->name . "_" . date('mdY') . "_" . str_replace(":", "", $timestamp) . "_" . $timezone . ".xlsx";

		if (!file_exists($report_path)) {
			mkdir($report_path, 0777, true);
		}

		$writer = IOFactory::createWriter($workbook, 'Xlsx');
		$writer->save("{$report_path}/{$report_filename}");

		$hospital_report = new HospitalReport;
        $hospital_report->hospital_id = $hospital->id;
		$hospital_report->filename = $report_filename;
		$hospital_report->type = 4;
		$hospital_report->save();

		$this->success('lawson_interface.generate_success', $hospital_report->id, $hospital_report->filename);
	}

	protected function parseArguments()
	{
		$result = new \stdClass();
		$result->hospital = $this->argument("hospital");
		$result->interface_date = $this->argument("interface_date");
		$result->report_data = $this->argument("report_data");

		return $result;
	}

	protected function getArguments()
	{
		return [
			["hospital", InputArgument::REQUIRED, "The hospital."],
			["interface_date", InputArgument::REQUIRED, "The interface date."],
			["report_data", InputArgument::REQUIRED, "The report data."]
		];
	}

	protected function getOptions()
	{
		return [];
	}
}