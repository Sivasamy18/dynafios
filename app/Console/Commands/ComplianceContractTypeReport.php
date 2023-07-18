<?php
namespace App\Console\Commands;

use App\ComplianceReport;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use StdClass;
use DateTime;
use App\Hospital;
use App\HospitalReport;
use function App\Start\complience_report_path;

/*
 * Column Header changed according to contract type
 * for Medical Directorship,Hours Expected and
 * for Other than that, Max Hours Possible
 * */

class ComplianceContractTypeReport extends ReportingCommand
{
	protected $name = "reports:complianceContractTypeReport";
	protected $description = "Generates a DYNAFIOS Contract Type complience report.";

	private $cell_style = [
		'borders' => [
			'inside' => ['borderStyle' => Border::BORDER_THIN],
			'left' => ['borderStyle' => Border::BORDER_MEDIUM],
			'right' => ['borderStyle' => Border::BORDER_MEDIUM],
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
			'left' => ['borderStyle' => Border::BORDER_MEDIUM],
			'right' => ['borderStyle' => Border::BORDER_MEDIUM],
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
		'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
		'borders' => [
			'allborders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']],
			//'inside' => ['borderStyle' => Border::BORDER_MEDIUM,'color' => ['rgb' => 'FF0000']]
		]

	];
	private $period_style = [
		'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'eeecea']],
		'font' => ['color' => ['rgb' => '000000'], 'size' => 12],
		'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
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
		'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'eeecea']],
		'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
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

		$reader = IOFactory::createReader("Xlsx");//Load template using phpSpreadsheet
		$workbook = $reader->load(storage_path()."/reports/templates/contract_type_compliance_report.xlsx");

		$templateSheet = $workbook->getSheet(0);
		$sheetIndex = 0;
		foreach($arguments->report_data as $hospitalData){
			$sheetIndex++;
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
			$header .= strtoupper($hospitalData['hospital']->name) . "\n";
			$header .= "Contract Type Compliance Report\n";
//			$header .= "Period: " . $hospitalData["data"]["Period"] . "\n";
			$header .= "Run Date: " . with($hospitalData['localtimeZone']);

			$workbook->setActiveSheetIndex($sheetIndex)->setCellValue("B2", $header);
			$current_row = 6;
//			$current_row = $this->writeContractHeader($workbook, $sheetIndex, $current_row, $physician_data["data"]["agreement_name"], $physician_data["data"]["agreement_start_date"], $physician_data["data"]["agreement_end_date"], $physician_data["data"]["managers"]);
//			$current_row++;
			foreach($hospitalData["agreementInfo"] as $agreementInfo) {
				$current_row = $this->writeContractHeader($workbook, $sheetIndex, $current_row, $agreementInfo[0]["contract_type"], 0);
				foreach($agreementInfo as $data){
					$current_row = $this->writeContractHeader($workbook, $sheetIndex, $current_row, $data["name"], $data["period"]);
					//$current_row++;
					$current_row = $this->writePeriodHeader($workbook, $sheetIndex, $current_row, $data["current_period"]);

					$workbook->setActiveSheetIndex($sheetIndex)
						->setCellValue("B{$current_row}", $data["contract_type"])
						->setCellValue("C{$current_row}", $data["period"])
						->setCellValue("D{$current_row}", $data["submit_count"])
						->setCellValue("E{$current_row}", $data["reject_count"])
						->setCellValue("F{$current_row}", formatNumber($data["rejection_percent"]).'%')
						->setCellValue("G{$current_row}", $data["submit_cytd"])
						->setCellValue("H{$current_row}", $data["reject_cytd"])
						->setCellValue("I{$current_row}", formatNumber($data["rejection_percent_cytd"]).'%')
						->setCellValue("J{$current_row}", "-");
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:J{$current_row}")->applyFromArray($this->cell_style);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("C{$current_row}:J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
					$current_row++;

					$workbook->setActiveSheetIndex($sheetIndex)
					->mergeCells("B{$current_row}:C{$current_row}")
					->setCellValue("B{$current_row}", "Total");

					$workbook->setActiveSheetIndex($sheetIndex)
						->setCellValue("D{$current_row}", $data["submit_count"])
						->setCellValue("E{$current_row}", $data["reject_count"])
						->setCellValue("F{$current_row}", formatNumber($data["rejection_percent"]).'%')
						->setCellValue("G{$current_row}", $data["submit_cytd"])
						->setCellValue("H{$current_row}", $data["reject_cytd"])
						->setCellValue("I{$current_row}", formatNumber($data["rejection_percent_cytd"]).'%')
						->setCellValue("J{$current_row}", $hospitalData["hospital"]["benchmark_rejection_percentage"].'%');
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:J{$current_row}")->applyFromArray($this->cell_style);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("C{$current_row}:J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
					$current_row++;
				}
			}
		}
		$workbook->removeSheetByIndex(0);
		if ($workbook->getSheetCount() == 0) {
			$this->failure("breakdowns.logs_unavailable");
			return;
		}

		$report_path = complience_report_path();
		//$report_filename = "compliance_contract_type_" . date('mdYhis') . ".xlsx";
		$timeZone = str_replace(' ','_', $hospitalData['localtimeZone']);
        $timeZone = str_replace('/','', $timeZone);
        $timeZone = str_replace(':','', $timeZone);
		$report_filename = "compliance_contract_type_" . $timeZone . ".xlsx";

		if (!file_exists($report_path)) {
			mkdir($report_path, 0777, true);
		}

		$writer = IOFactory::createWriter($workbook, 'Xlsx');
		$writer->save("{$report_path}/{$report_filename}");

		$compliance_report = new ComplianceReport();
		$compliance_report->created_by_user_id = Auth::user()->id;
		$compliance_report->filename = $report_filename;
		$compliance_report->report_type = 3;
		$compliance_report->save();

		$this->success('breakdowns.generate_success', $compliance_report->id, $report_filename);
	}

	private function writeContractHeader($workbook, $sheetIndex, $index, $contract_name,$period)
	{
		$current_row = $index;

		$workbook->setActiveSheetIndex($sheetIndex)
			->mergeCells("B{$current_row}:J{$current_row}")
			->setCellValue("B{$current_row}", $contract_name);
		$workbook->setActiveSheetIndex($sheetIndex)
			->getStyle("B{$current_row}:J{$current_row}")->applyFromArray($this->contract_style);
		$workbook->setActiveSheetIndex($sheetIndex)
			->getRowDimension($current_row)->setRowHeight(-1);
		$workbook->setActiveSheetIndex($sheetIndex)
			->getStyle("B{$current_row}:J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
		$workbook->setActiveSheetIndex($sheetIndex)
			->getStyle("B{$current_row}:J{$current_row}")->getFont()->setBold(true);
		$current_row++;

		if($period != 0){
			$report_header = "Contract Period: " . $period;
			$workbook->setActiveSheetIndex($sheetIndex)
				->mergeCells("B{$current_row}:J{$current_row}")
				->setCellValue("B{$current_row}", $report_header);
			$workbook->setActiveSheetIndex($sheetIndex)
				->getStyle("B{$current_row}:J{$current_row}")->applyFromArray($this->period_style);
			$workbook->setActiveSheetIndex($sheetIndex)
				->getRowDimension($current_row)->setRowHeight(-1);
			$workbook->setActiveSheetIndex($sheetIndex)
				->getStyle("B{$current_row}:J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
			$workbook->setActiveSheetIndex($sheetIndex)
				->getStyle("B{$current_row}:J{$current_row}")->getFont()->setBold(true);
			$current_row++;
		}
		
		return $current_row;
	}

	private function writePeriodHeader($workbook, $sheetIndex, $index, $date_range)
	{
		$current_row = $index;
		$report_header = "Report Period: " . $date_range;
		$workbook->setActiveSheetIndex($sheetIndex)
			->mergeCells("B{$current_row}:J{$current_row}")
			->setCellValue("B{$current_row}", $report_header);
		$workbook->setActiveSheetIndex($sheetIndex)
			->getStyle("B{$current_row}:J{$current_row}")->applyFromArray($this->period_style);
		$workbook->setActiveSheetIndex($sheetIndex)
			->getRowDimension($current_row)->setRowHeight(-1);
		$workbook->setActiveSheetIndex($sheetIndex)
			->getStyle("B{$current_row}:J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
		$workbook->setActiveSheetIndex($sheetIndex)
			->getStyle("B{$current_row}:J{$current_row}")->getFont()->setBold(true);
		$current_row++;
		return $current_row;
	}

	protected function parseArguments()
	{
		$result = new StdClass;
		$result->report_data = $this->argument("report_data");
		$result->start_date = null;
		$result->end_date = null;
		return $result;
	}

	protected function getArguments()
	{
		return [
			["agreements", InputArgument::REQUIRED, "The hospital agreement IDs."],
			["report_data", InputArgument::REQUIRED, "The report data."]
		];
	}

	protected function getOptions()
	{
		return [];
	}
}
