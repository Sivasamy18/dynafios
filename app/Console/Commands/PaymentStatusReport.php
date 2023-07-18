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
use StdClass;
use DateTime;
use App\Hospital;
use App\HospitalReport;
use App\PhysicianPractices;
use App\PhysicianHospitalReport;
use Illuminate\Support\Facades\Log;
use function App\Start\is_physician;
use function App\Start\hospital_report_path;

/*
 * Column Header changed according to contract type
 * for Medical Directorship,Hours Expected and
 * for Other than that, Max Hours Possible
 * */

class PaymentStatusReport extends ReportingCommand
{
	protected $name = "reports:paymentstatus";
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
		'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
		'borders' => [
			'allborders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']],
			//'inside' => ['borderStyle' => Border::BORDER_MEDIUM,'color' => ['rgb' => 'FF0000']]
		]

	];
	private $period_style = [
		'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'eeecea']],
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
		$hospital = Hospital::findOrFail($arguments->hospital_id);
		/*
         * Column Header changed according to contract type
         * for Medical Directorship,Hours Expected and
         * for Other than that, Max Hours Possible
         * */

		//$header = "Physician Log Report\n" . "Run Date: " . format_date("now") . "\n";

		// $workbook = $this->loadTemplate("payment_status.xlsx");

		$now = Carbon::now();
		$timezone = $now->timezone->getName();
		$timestamp = format_date((exec('time /T')), "h:i A");

		$reader = IOFactory::createReader("Xlsx");//Load template using phpSpreadsheet
		$workbook = $reader->load(storage_path()."/reports/templates/payment_status.xlsx");

		$templateSheet = $workbook->getSheet(0);
		$sheetIndex = 0;
		$Physician_signature ="";
		$CM_signature ="";
		$FM_signature ="";
		foreach($arguments->report_data as $physician_data){
		
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
			$header .= strtoupper($hospital->name) . "\n";
			$header .= "Payment Status Report\n";
			$header .= "Period: " . $physician_data["data"]["Period"] . "\n";
			//$header .= "Run Date: " . with(new DateTime('now'))->format("m/d/Y");
			$header .= "Run Date: " . with( $physician_data["data"]["localtimeZone"]);


			$workbook->setActiveSheetIndex($sheetIndex)->setCellValue("B2", $header);
			$current_row = 3;
			$current_row = $this->writeContractHeader($workbook, $sheetIndex, $current_row, $physician_data["data"]["agreement_name"], $physician_data["data"]["agreement_start_date"], $physician_data["data"]["agreement_end_date"], $physician_data["data"]["managers"]);
			$current_row++;
			foreach($physician_data["logs"] as $data) {
				$current_row = $this->writePeriodHeader($workbook, $sheetIndex, $current_row, $data["date_range"]);

//				$workbook->setActiveSheetIndex($sheetIndex)
//					->setCellValue("B{$current_row}", $data["practice_name"])
//					->setCellValue("C{$current_row}", $data["physician_name"])
//					->mergeCells("D{$current_row}:E{$current_row}")
//					->setCellValue("F{$current_row}", $data["date_range"])
//					->setCellValue("G{$current_row}", round($data["sum_worked_hour"], 2));
//				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:J{$current_row}")->applyFromArray($this->total_style);
//				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:J{$current_row}")->getFont()->setBold(true);
//				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}")->applyFromArray($this->action_style);
//				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("F{$current_row}")->applyFromArray($this->sign_box_style);
//				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
//				$workbook->setActiveSheetIndex($sheetIndex)
//					->getStyle("G{$current_row}:H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
//				$workbook->setActiveSheetIndex($sheetIndex)
//					->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
//				$workbook->setActiveSheetIndex($sheetIndex)
//					->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
				//$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:D{$current_row}")->applyFromArray($this->shaded_style);
				//$workbook->setActiveSheetIndex($sheetIndex)->getStyle("E{$current_row}:H{$current_row}")->applyFromArray($this->shaded_style);
				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:N{$current_row}")->applyFromArray($this->border_bottom);
				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("L{$current_row}:N{$current_row}")->applyFromArray($this->border_right);
				$title = strlen($data["physician_name"]) > 31 ? substr($data["physician_name"],0,31): $data["physician_name"];
				$workbook->getActiveSheet()->setTitle($title);
				$worked_hours_present = false;
				if ($data["worked_hours"] > 0) {
					$worked_hours_present = true;
				}

//				$current_row++;
				$new_current_row = count($data["breakdown"]) + $current_row;
				$physician_row = ($new_current_row) - ((($new_current_row) - $current_row) / 2);
				$physician_row = floor($physician_row);
				$new_current_row =$new_current_row -1;
//				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:C{$new_current_row}")->applyFromArray($this->physician_breakdown_style);
//				$workbook->setActiveSheetIndex($sheetIndex)->mergeCells("B{$physician_row}:C{$physician_row}");
//				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$physician_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

				$workbook->setActiveSheetIndex($sheetIndex)->setCellValue("B{$physician_row}", $data["physician_name"]);


				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$new_current_row}:N{$new_current_row}")->applyFromArray($this->border_bottom);
				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("L{$current_row}:N{$current_row}")->applyFromArray($this->border_right);
				
				foreach ($data["breakdown"] as $breakdown) {

					$workbook->setActiveSheetIndex($sheetIndex)
						->getStyle("J{$current_row}:O{$current_row}")->getAlignment()->setWrapText(true);
					$breakdown_date = strtotime($breakdown["date"]);
					$status_level= array();
					$status_level[1]="NA";
					$status_level[2]="NA";
					$status_level[3]="NA";
					$status_level[4]="NA";
					$status_level[5]="NA";
					$status_level[6]="NA";
					$in=1;
					foreach($physician_data["data"]["managers"] as $managers_status){
						if($breakdown["approval_status"] != "Approved") {
							$status_level[$in] = "Pending";
						}
						$in++;
					}
//                    if ((strtotime($month_start_date) <= $breakdown_date) && (strtotime($month_end_date) >= $breakdown_date)) {
					//if ($approve_date_arr[$i] != "") {
					//hide approval if has no contract
					  
						
					$workbook->setActiveSheetIndex($sheetIndex)
						->setCellValue("B{$current_row}", $data["practice_name"])
						->setCellValue("C{$current_row}", $data["physician_name"])
						->setCellValue("D{$current_row}", $data["contract_names"][0])
						->setCellValue("E{$current_row}", $breakdown["action"])
						->setCellValue("F{$current_row}", (strlen($breakdown["notes"]) > 0) ? $breakdown["notes"] : "-")
						->setCellValue("G{$current_row}", $breakdown["date"])
						->setCellValue("H{$current_row}", $breakdown["worked_hours"])
						->setCellValue("I{$current_row}", $breakdown["entered_by"])
						->setCellValue("J{$current_row}", $breakdown["Physician_approve_date"].'    '.(isset($breakdown["pending_log_approval_info"][0]['name'])?$breakdown["pending_log_approval_info"][0]['name']:$breakdown["log_approval_info"][0]['name']))
						->setCellValue("K{$current_row}", isset($breakdown["log_approval_info"][1]) ? $breakdown["log_approval_info"][1]['date'].'    '.$breakdown["log_approval_info"][1]['name']:$status_level[1].'       '.((isset($breakdown["pending_log_approval_info"][1]['name'])&&($status_level[1]=='Pending'))?$breakdown["pending_log_approval_info"][1]['name']:""))
						->setCellValue("L{$current_row}", isset($breakdown["log_approval_info"][2]) ? $breakdown["log_approval_info"][2]['date'].'    '.$breakdown["log_approval_info"][2]['name']:$status_level[2].'       '.((isset($breakdown["pending_log_approval_info"][2]['name'])&&($status_level[2]=='Pending'))?$breakdown["pending_log_approval_info"][2]['name']:""))
						->setCellValue("M{$current_row}", isset($breakdown["log_approval_info"][3]) ? $breakdown["log_approval_info"][3]['date'].'    '.$breakdown["log_approval_info"][3]['name']:$status_level[3].'       '.((isset($breakdown["pending_log_approval_info"][3]['name'])&&($status_level[3]=='Pending'))?$breakdown["pending_log_approval_info"][3]['name']:""))
						->setCellValue("N{$current_row}", isset($breakdown["log_approval_info"][4]) ? $breakdown["log_approval_info"][4]['date'].'    '.$breakdown["log_approval_info"][4]['name']:$status_level[4].'       '.((isset($breakdown["pending_log_approval_info"][4]['name'])&&($status_level[4]=='Pending'))?$breakdown["pending_log_approval_info"][4]['name']:""))
						->setCellValue("O{$current_row}", isset($breakdown["log_approval_info"][5]) ? $breakdown["log_approval_info"][5]['date'].'    '.$breakdown["log_approval_info"][5]['name']:$status_level[5].'       '.((isset($breakdown["pending_log_approval_info"][5]['name'])&&($status_level[5]=='Pending'))?$breakdown["pending_log_approval_info"][5]['name']:""))
						->setCellValue("P{$current_row}", isset($breakdown["log_approval_info"][6]) ? $breakdown["log_approval_info"][6]['date'].'    '.$breakdown["log_approval_info"][6]['name']:$status_level[6].'       '.((isset($breakdown["pending_log_approval_info"][6]['name'])&&($status_level[6]=='Pending'))?$breakdown["pending_log_approval_info"][6]['name']:""))
						->setCellValue("Q{$current_row}", $breakdown["approval_status"])
						->setCellValue("R{$current_row}", ($breakdown["approval_status"]=="Approved")?$breakdown["payment_approval_date"]:"");
					/*->setCellValue("L{$current_row}", $breakdown["CM_name"])
                    ->setCellValue("M{$current_row}", $breakdown["FM_name"]);*/

					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:I{$current_row}")->applyFromArray($this->cell_style);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}")->applyFromArray($this->action_style);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("E{$current_row}")->applyFromArray($this->action_style);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("C{$current_row}")->getAlignment()->setWrapText(true);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}")->getAlignment()->setWrapText(true);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("E{$current_row}")->getAlignment()->setWrapText(true);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("F{$current_row}")->getAlignment()->setWrapText(true);
					$workbook->setActiveSheetIndex($sheetIndex)->getRowDimension($current_row)->setRowHeight(-1);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
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
						->getStyle("G{$current_row}:H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("H{$current_row}")->applyFromArray($this->shaded_style_worked);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("I{$current_row}")->applyFromArray($this->cell_style);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("J{$current_row}")->applyFromArray($this->cell_style);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("K{$current_row}")->applyFromArray($this->cell_style);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("L{$current_row}")->applyFromArray($this->cell_style);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("M{$current_row}")->applyFromArray($this->cell_style);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("N{$current_row}")->applyFromArray($this->cell_style);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("O{$current_row}")->applyFromArray($this->cell_style);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("P{$current_row}")->applyFromArray($this->cell_style);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("Q{$current_row}")->applyFromArray($this->border_right);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("Q{$current_row}")->applyFromArray($this->border_left);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("R{$current_row}")->applyFromArray($this->cell_style);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("R{$current_row}")->applyFromArray($this->border_right);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}")->applyFromArray($this->border_left);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("Q{$current_row}")->applyFromArray($this->border_right);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:C{$current_row}")->getFont()->setBold(true);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("H{$current_row}")->getFont()->setBold(true);
					$workbook->setActiveSheetIndex($sheetIndex)->getStyle("Q{$current_row}")->applyFromArray($this->shaded_style_status);
					//$workbook->setActiveSheetIndex($sheetIndex)->getStyle("L{$current_row}:M{$current_row}")->applyFromArray($this->shaded_style); remove on 3sep2018
					$current_row++;
//                        $count++;
//                        $count_prev[$physician_data["physician_id"]]= $count;
//                    }
//                    $i++;
					//}
					/*$Physician_signature = $breakdown["Physician_approve_signature"];
					$CM_signature = $breakdown["CM_approve_signature"];
					$FM_signature = $breakdown["FM_approve_signature"];*/
				}
				

				$workbook->setActiveSheetIndex($sheetIndex)
					->setCellValue("B{$current_row}", "Total")
					->setCellValue("H{$current_row}", round($data["sum_worked_hour"], 2));
//				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:K{$current_row}")->applyFromArray($this->cell_style);
				$workbook->setActiveSheetIndex($sheetIndex)->mergeCells("B{$current_row}:G{$current_row}")->getStyle("B{$current_row}:G{$current_row}")->applyFromArray($this->cell_style);
				$workbook->setActiveSheetIndex($sheetIndex)->mergeCells("I{$current_row}:R{$current_row}")->getStyle("I{$current_row}:R{$current_row}")->applyFromArray($this->cell_style);
				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("H{$current_row}")->applyFromArray($this->shaded_style_worked);
				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}")->getFont()->setBold(true);
				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("H{$current_row}")->getFont()->setBold(true);
				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:R{$current_row}")->applyFromArray($this->all_border);
				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("Q{$current_row}")->applyFromArray($this->border_right);
				$workbook->setActiveSheetIndex($sheetIndex)->getStyle("R{$current_row}")->applyFromArray($this->border_right);
				$current_row++;
			}
		}
		$workbook->removeSheetByIndex(0);
		if ($workbook->getSheetCount() == 0) {
			$this->failure("breakdowns.logs_unavailable");
			return;
		}

		$report_path = hospital_report_path($hospital);
		// $report_filename = "statusReport_" . date('mdYhis') . ".xlsx";
		$timeZone = str_replace(' ','_', $physician_data["data"]["localtimeZone"]);
        $timeZone = str_replace('/','', $timeZone);
        $timeZone = str_replace(':','', $timeZone);
		$report_filename = "paymentStatusReport_" . $hospital->name . "_"  . $timeZone . ".xlsx";

		if (!file_exists($report_path)) {
			mkdir($report_path, 0777, true);
		}

		$writer = IOFactory::createWriter($workbook, 'Xlsx');
		$writer->save("{$report_path}/{$report_filename}");

		if(is_physician()){
            $practice_id = PhysicianPractices::select('physician_practices.practice_id')->where('physician_id', '=', $arguments->physician_ids)->where('hospital_id', '=', $hospital->id)->whereNull("deleted_at")->first();
            
            $hospital_report = new PhysicianHospitalReport;
            $hospital_report->hospital_id = $hospital->id;
            $hospital_report->physician_id = $arguments->physician_ids;
            $hospital_report->practice_id = $practice_id->practice_id;
            $hospital_report->filename = $report_filename;
            $hospital_report->type = 2;
            $hospital_report->save();
        }else{
			$hospital_report = new HospitalReport;
			$hospital_report->hospital_id = $hospital->id;
			$hospital_report->filename = $report_filename;
			$hospital_report->type = 3;
			$hospital_report->save();
		}
			
		$this->success('breakdowns.generate_success', $hospital_report->id, $hospital_report->filename);
	}

	private function writeContractHeader($workbook, $sheetIndex, $index, $contract_name,$start_date, $end_date,$managers)
	{
		$current_row = $index;

		$workbook->setActiveSheetIndex($sheetIndex)
			->mergeCells("B{$current_row}:R{$current_row}")
			->setCellValue("B{$current_row}", $contract_name);
		$workbook->setActiveSheetIndex($sheetIndex)
			->getStyle("B{$current_row}:R{$current_row}")->applyFromArray($this->contract_style);
		$workbook->setActiveSheetIndex($sheetIndex)
			->getRowDimension($current_row)->setRowHeight(-1);
		$workbook->setActiveSheetIndex($sheetIndex)
			->getStyle("B{$current_row}:R{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
		$workbook->setActiveSheetIndex($sheetIndex)
			->getStyle("B{$current_row}:R{$current_row}")->getFont()->setBold(true);
		$current_row++;
		$report_header = "Contract Period: " . format_date($start_date) . " - " . format_date($end_date);
		$workbook->setActiveSheetIndex($sheetIndex)
			->mergeCells("B{$current_row}:R{$current_row}")
			->setCellValue("B{$current_row}", $report_header);
		$workbook->setActiveSheetIndex($sheetIndex)
			->getStyle("B{$current_row}:R{$current_row}")->applyFromArray($this->period_style);
		$workbook->setActiveSheetIndex($sheetIndex)
			->getRowDimension($current_row)->setRowHeight(-1);
		$workbook->setActiveSheetIndex($sheetIndex)
			->getStyle("B{$current_row}:R{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
		$workbook->setActiveSheetIndex($sheetIndex)
			->getStyle("B{$current_row}:R{$current_row}")->getFont()->setBold(true);
		$current_row++;
		$this->writeManagersHeader($workbook, $sheetIndex, $current_row, $managers);
		return $current_row;
	}

	private function writeManagersHeader($workbook, $sheetIndex, $current_row, $managers)
	{
		
		$workbook->setActiveSheetIndex($sheetIndex)
			->setCellValue("K{$current_row}", isset($managers[0]) ? "Approval Level 1 ".$managers[0]->role." Approval Date": "Approval Level 1  NA" )
			->setCellValue("L{$current_row}", isset($managers[1]) ? "Approval Level 2 ".$managers[1]->role." Approval Date": "Approval Level 2  NA")
			->setCellValue("M{$current_row}", isset($managers[2]) ? "Approval Level 3 ".$managers[2]->role." Approval Date": "Approval Level 3  NA")
			->setCellValue("N{$current_row}", isset($managers[3]) ? "Approval Level 4 ".$managers[3]->role." Approval Date": "Approval Level 4  NA")
			->setCellValue("O{$current_row}", isset($managers[4]) ? "Approval Level 5 ".$managers[4]->role." Approval Date": "Approval Level 5  NA")
			->setCellValue("P{$current_row}", isset($managers[5]) ? "Approval Level 6 ".$managers[5]->role." Approval Date": "Approval Level 6  NA");


			if(!isset($managers[0])){
					
				$workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('K')->setVisible(false);
				
			}else
			{
				$workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('K')->setVisible(true);
			}

			if(!isset($managers[1])){
				$workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('L')->setVisible(false);
				
			}else
			{
				$workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('L')->setVisible(true);
			}

			if(!isset($managers[2])){
				$workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('M')->setVisible(false);

			}else
			{
				$workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('M')->setVisible(true);
			}

			if(!isset($managers[3])){
				$workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('N')->setVisible(false);
				
			}else
			{
				$workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('N')->setVisible(true);
			}
			if(!isset($managers[4])){
				$workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('O')->setVisible(false);
				
			}else
			{
				$workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('O')->setVisible(true);
			}
			if(!isset($managers[5])){
				$workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('P')->setVisible(false);
				
			}else
			{
				$workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('P')->setVisible(true);
			} 	
	}

	private function writePeriodHeader($workbook, $sheetIndex, $index, $date_range)
	{
		$current_row = $index;
		$report_header = "Report Period: " . $date_range;
		$workbook->setActiveSheetIndex($sheetIndex)
			->mergeCells("B{$current_row}:R{$current_row}")
			->setCellValue("B{$current_row}", $report_header);
		$workbook->setActiveSheetIndex($sheetIndex)
			->getStyle("B{$current_row}:R{$current_row}")->applyFromArray($this->period_style);
		$workbook->setActiveSheetIndex($sheetIndex)
			->getRowDimension($current_row)->setRowHeight(-1);
		$workbook->setActiveSheetIndex($sheetIndex)
			->getStyle("B{$current_row}:R{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
		$workbook->setActiveSheetIndex($sheetIndex)
			->getStyle("B{$current_row}:R{$current_row}")->getFont()->setBold(true);
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
