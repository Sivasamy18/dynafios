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
use App\Hospital;
use App\HospitalReport;
use function App\Start\hospital_report_path;

class OnCallScheduleCommand extends ReportingCommand
{
	protected $name = "reports:oncallschedule";
	protected $description = "report.";

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

	private $border_bottom = [
			'borders' => [
					'top' => ['borderStyle' => Border::BORDER_MEDIUM],
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

	private $contract_style = [
			'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '000000']],
			'font' => ['color' => ['rgb' => 'FFFFFF'], 'size' => 14],
			'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
			'borders' => [
					'allborders' => ['borderStyle' => Border::BORDER_MEDIUM,'color' => ['rgb' => '000000']],
				//'inside' => ['borderStyle' => Border::BORDER_MEDIUM,'color' => ['rgb' => 'FF0000']]
			]

	];
	private $period_style = [
			'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '000000']],
			'font' => ['color' => ['rgb' => 'FFFFFF'], 'size' => 12],
			'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
			'borders' => [
					'allborders' => ['borderStyle' => Border::BORDER_MEDIUM,'color' => ['rgb' => '000000']],
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

		$now = Carbon::now();
		$timezone = $now->timezone->getName();
		$timestamp = format_date((exec('time /T')), "h:i A");

		$hospital = Hospital::findOrFail($arguments->hospital_id);
		$oncall_data = DB::table('on_call_schedule')->select('id', 'agreement_id', 'physician_id',
			'physician_type', 'date')
			->where('agreement_id', '=', $arguments->agreement_id)
			->whereBetween("date", [mysql_date($arguments->report_start_date), mysql_date($arguments->report_end_date)])
			->orderBy('date', 'asc')
			->get();
		$header = '';
		$header .= strtoupper($hospital->name) . "\n";
		$header .= "On Call Schedule Report\n";
		$header .= "Period: " . $arguments->report_start_date . " - " . $arguments->report_end_date . "\n";
		$header .= "Run Date: " . with(new DateTime('now'))->format("m/d/Y");
		$sheetIndex = 0;
		$current_row = 6;
		// $workbook = $this->loadTemplate("onCallSchedule.xlsx");

		$reader = IOFactory::createReader("Xlsx");//Load template using phpSpreadsheet
		$workbook = $reader->load(storage_path()."/reports/templates/onCallSchedule.xlsx");

		$workbook->setActiveSheetIndex(0)->setCellValue("B2", $header);
		$templateSheet = $workbook->getSheet(0);
		$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B4:F4")->applyFromArray($this->log_details_style);
		$workbook->setActiveSheetIndex($sheetIndex)->setCellValue("B5", $arguments->contract_name);
		$date = array();
		$amp1 = array();
		$workbook->setActiveSheetIndex($sheetIndex)->setCellValue("B3", "On Call Schedule ".date('F Y', strtotime($arguments->report_start_date)));
		$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B3:F3")->applyFromArray($this->log_details_style);
		$amp = array();
		foreach ($oncall_data as $on_call) {
			$date = $on_call->date;
			$am = DB::table('on_call_schedule')
				->where("date", $on_call->date)
				->where("agreement_id", $on_call->agreement_id)
				->where("physician_type", '=', 1)
				->value('physician_id'); // Replaced pluck with value as pluck is deprecated by akash
			$pm = DB::table('on_call_schedule')
				->where("date", $on_call->date)
				->where("agreement_id", $on_call->agreement_id)
				->where("physician_type", '=', 2)
				->value('physician_id'); // Replaced pluck with value as pluck is deprecated by akash
			$amp[] = $on_call->date . "~" . $am . "~" . $pm;
		}
		$real_array = (array_unique($amp));

		foreach ($real_array as $data1) {
			$data = explode('~', $data1);
			if ($data[0] != "") {
				$date = $data[0];
				$workbook->setActiveSheetIndex($sheetIndex)
					->setCellValue("B{$current_row}", date('m/d/Y', strtotime($date)))
					->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:F{$current_row}")->applyFromArray($this->log_details_style);
			}
			if ($data[1] != "") {
				$am_physician_name = DB::table('physicians')
					->select(DB::raw('CONCAT(last_Name, ", ", first_Name) AS name'))
					->where('id', '=', $data[1])
					->first();
				$workbook->setActiveSheetIndex($sheetIndex)
					->mergeCells("C{$current_row}:D{$current_row}")
					->setCellValue("C{$current_row}", $am_physician_name->name)
					->getStyle("C{$current_row}:D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:F{$current_row}")->applyFromArray($this->log_details_style);
			} else {
				$workbook->setActiveSheetIndex($sheetIndex)
					->mergeCells("C{$current_row}:D{$current_row}")
					->setCellValue("C{$current_row}", "-")
					->getStyle("C{$current_row}:D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:F{$current_row}")->applyFromArray($this->log_details_style);
			}
			if ($data[2] != "") {
				$pm_physician_name = DB::table('physicians')
					->select(DB::raw('CONCAT(last_Name, ", ", first_Name) AS name'))
					->where('id', '=', $data[2])
					->first();
				$workbook->setActiveSheetIndex($sheetIndex)
					->mergeCells("E{$current_row}:F{$current_row}")
					->setCellValue("E{$current_row}", $pm_physician_name->name)
					->getStyle("E{$current_row}:F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:F{$current_row}")->applyFromArray($this->log_details_style);
			} else {
				$workbook->setActiveSheetIndex($sheetIndex)
					->mergeCells("E{$current_row}:F{$current_row}")
					->setCellValue("E{$current_row}", "-")
					->getStyle("E{$current_row}:F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:F{$current_row}")->applyFromArray($this->log_details_style);
			}
			$workbook->setActiveSheetIndex($sheetIndex)
				->mergeCells("C{$current_row}:D{$current_row}")
				->mergeCells("E{$current_row}:F{$current_row}");
			$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:F{$current_row}")->applyFromArray($this->cell_style);
			$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:F{$current_row}")->applyFromArray($this->log_details_style);
			$current_row++;
		}

		$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:F{$current_row}")->applyFromArray($this->border_bottom);
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
		$this->success('agreements.generate_success', $hospital_report->id, $hospital_report->filename);
	}

	protected function parseArguments()
	{
		$result = new StdClass;
		$result->hospital_id = $this->argument("hospital");
		$result->report_start_date = $this->argument("report_start_date");
		$result->report_end_date = $this->argument("report_end_date");
		$result->agreement_id = $this->argument("agreement_id");
		$result->contract_name = $this->argument("contract_name");
		return $result;
	}

	protected function getArguments()
	{
		return [
				["hospital", InputArgument::REQUIRED, "The hospital ID."],
				["report_start_date", InputArgument::OPTIONAL, "The start  date months."],
				["report_end_date", InputArgument::OPTIONAL, "The end date months."],
				["contract_name", InputArgument::OPTIONAL, "contract Name."],
				["agreement_id", InputArgument::OPTIONAL, "agreement id."]
		];
	}

	protected function getOptions()
	{
		return [];
	}
}
