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


class PaymentSummaryReportCommandMultipleMonths extends ReportingCommand
{
    protected $name = "reports:paymentsummarymultiplemonths";
    protected $description = "Generates a DYNAFIOS payment summary report.";

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

    private $border_right_left = [
        'borders' => [
            'right' => ['borderStyle' => Border::BORDER_MEDIUM],
            'left' => ['borderStyle' => Border::BORDER_MEDIUM]
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

        $contract_wise_header = "Amount Paid";
        $reader = IOFactory::createReader("Xlsx");
        $workbook = $reader->load(storage_path()."/reports/templates/paymentsummary.xlsx");

        $workbook->setActiveSheetIndex(0)->setCellValue("G4", $contract_wise_header);
        $templateSheet = $workbook->getSheet(0);
        $sheetIndex = 0;
        $contract_name="";

        if(count($arguments->report_data) > 0){
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
            $header .= "Payment Summary Report\n";
            $header .= "Period: " . $arguments->period . "\n";
            $header .= "Run Date: " . with($arguments->localtimeZone);

            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("B2", $header);
            $current_row = 6;

            foreach($arguments->report_data as $physician_data){
                if($physician_data['contract_type'] != "" && $physician_data['contract_type'] != null){
                // if($contract_name = "" || $contract_name != $physician_data['contract_type']){  
                    $regionName = null;
                    $total_amountpaid = 0;
                    $total_worked_hours = 0;

                    $current_row = $this->writeContractHeader($workbook, $sheetIndex, $current_row, $physician_data["contract_type"], 0);
                    foreach($physician_data["payment_detail"] as $data) {
                        if(is_practice_manager()){
                            $report_practice_id = $data["practice_id"];
                        }

                        $sum_worked_hours = $data->sum_worked_hours;  
                        $physician_full_name= $data->last_name." ". $data->first_name;
                        $data_range=$data->start_date." - ".$data->end_date;
                        $total_amountpaid = $total_amountpaid + $data->amountPaidTotal; 
                        $total_worked_hours = $total_worked_hours + $sum_worked_hours; 

                        if((property_exists($data,"region_name")) == false){
                            if($regionName == null){ 
                                $regionName = "   "; 
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->setCellValue("B{$current_row}", $regionName)
                                    ->setCellValue("C{$current_row}", $data->hospital_name)    
                                    ->setCellValue("D{$current_row}", $data->practice_name)
                                    ->setCellValue("E{$current_row}", $physician_full_name)
                                    ->setCellValue("F{$current_row}", $data->specialty_name)
                                    ->setCellValue("G{$current_row}", number_format($sum_worked_hours, 2))
                                    ->setCellValue("H{$current_row}", "$" .number_format((float)$data->amountPaidTotal, 2));
                            }else{
                                $workbook->setActiveSheetIndex($sheetIndex) 
                                    ->setCellValue("D{$current_row}", $data->practice_name)
                                    ->setCellValue("E{$current_row}", $physician_full_name)
                                    ->setCellValue("F{$current_row}", $data->specialty_name)
                                    ->setCellValue("G{$current_row}", number_format($sum_worked_hours, 2))
                                    ->setCellValue("H{$current_row}", "$" .number_format((float)$data->amountPaidTotal, 2));
                            }
                        }else{
                            if($regionName == null || $regionName != $data->region_name){                
                                $regionName =  $data->region_name;
                                $workbook->setActiveSheetIndex($sheetIndex)
                                    ->setCellValue("B{$current_row}", $regionName)
                                    ->setCellValue("C{$current_row}", $data->hospital_name)    
                                    ->setCellValue("D{$current_row}", $data->practice_name)
                                    ->setCellValue("E{$current_row}", $physician_full_name)
                                    ->setCellValue("F{$current_row}", $data->specialty_name)
                                    ->setCellValue("G{$current_row}", number_format($sum_worked_hours, 2))
                                    ->setCellValue("H{$current_row}", "$" .number_format((float)$data->amountPaidTotal, 2));
                            }else{
                                $workbook->setActiveSheetIndex($sheetIndex) 
                                    ->setCellValue("D{$current_row}", $data->practice_name)
                                    ->setCellValue("E{$current_row}", $physician_full_name)
                                    ->setCellValue("F{$current_row}", $data->specialty_name)
                                    ->setCellValue("G{$current_row}", number_format($sum_worked_hours, 2))
                                    ->setCellValue("H{$current_row}", "$" .number_format((float)$data->amountPaidTotal, 2));
                            }
                        }

                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:H{$current_row}")->applyFromArray($this->border_right_left);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}")->applyFromArray($this->action_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("F{$current_row}")->applyFromArray($this->sign_box_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("G{$current_row}:G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:H{$current_row}")->applyFromArray($this->border_bottom);
                        // $title = strlen( $contract_name) > 31 ? substr( $contract_name,0,31):  $contract_name;
                        // $workbook->getActiveSheet()->setTitle($title);

                        $new_current_row = $current_row;
                        $physician_row = ($new_current_row) - ((($new_current_row) - $current_row) / 2);
                        $physician_row = floor($physician_row);
                
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:H{$current_row}")->applyFromArray($this->cell_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:H{$current_row}")->applyFromArray($this->border_right_left);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("E{$current_row}")->getAlignment()->setWrapText(true);
                        $workbook->setActiveSheetIndex($sheetIndex)->getRowDimension($current_row)->setRowHeight(-1);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}:E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)
                             ->getStyle("E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)
                             ->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $workbook->setActiveSheetIndex($sheetIndex)
                               ->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $current_row++;
                    }
                    //This code is added to print total amount and one blank row in report
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:H{$current_row}")->applyFromArray($this->cell_style);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:H{$current_row}")->applyFromArray($this->border_right_left);

                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->mergeCells("B{$current_row}:E{$current_row}")
                        ->setCellValue("F{$current_row}", "Total")
                        ->setCellValue("G{$current_row}", number_format((float)$total_worked_hours, 2))
                        ->setCellValue("H{$current_row}", "$" .number_format((float)$total_amountpaid, 2));
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)
			            ->getStyle("F{$current_row}:H{$current_row}")->getFont()->setBold(true);

                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:H{$current_row}")->applyFromArray($this->border_bottom);
                    $current_row++;
                }
            }
            
            $workbook->removeSheetByIndex(0);
            if ($workbook->getSheetCount() == 0) {
                $this->failure("paymentsummary.logs_unavailable");
                return;
            }
        
            if(is_practice_manager()) {
                $report_practice = Practice::findOrFail($report_practice_id);
                $report_path = practice_report_path($report_practice);
            }else{
                $report_path = hospital_report_path($hospital);
            }

            if(count($arguments->report_data) > 0){
                $timeZone = str_replace(' ','_', $arguments->report_data[0]["localtimeZone"]);
                $timeZone = str_replace('/','', $timeZone);
                $timeZone = str_replace(':','', $timeZone);
                $report_filename = "paymentsummaryReport_" . $hospital->name . "_"  . $timeZone . ".xlsx";

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
                $hospital_report->type = 6;
                $hospital_report->save();

                $this->success('paymentsummary.generate_success', $hospital_report->id, $hospital_report->filename);
            }
        }
    }
    
    private function writeContractHeader($workbook, $sheetIndex, $index, $contract_name,$period)
	{
		$current_row = $index;

		$workbook->setActiveSheetIndex($sheetIndex)
			->mergeCells("B{$current_row}:H{$current_row}")
			->setCellValue("B{$current_row}", $contract_name);
		$workbook->setActiveSheetIndex($sheetIndex)
			->getStyle("B{$current_row}:H{$current_row}")->applyFromArray($this->contract_style);
		$workbook->setActiveSheetIndex($sheetIndex)
			->getRowDimension($current_row)->setRowHeight(-1);
		$workbook->setActiveSheetIndex($sheetIndex)
			->getStyle("B{$current_row}:H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
		$workbook->setActiveSheetIndex($sheetIndex)
			->getStyle("B{$current_row}:H{$current_row}")->getFont()->setBold(true);
		$current_row++;
		
		return $current_row;
	}

    /*public function __invoke()
    {
        $arguments = $this->parseArguments();
        $hospital = Hospital::findOrFail($arguments->hospital_id);
        $now = Carbon::now();
        
        $contract_wise_header = "Amount Paid";
        $reader = IOFactory::createReader("Xlsx");
        $workbook = $reader->load(storage_path()."/reports/templates/paymentsummary.xlsx");

        $workbook->setActiveSheetIndex(0)->setCellValue("G4", $contract_wise_header);
        $templateSheet = $workbook->getSheet(0);
        $sheetIndex = 0;
        $agreement_name="";
        if(count($arguments->report_data) > 0){
            foreach($arguments->report_data as $physician_data){
                if($agreement_name="" || $agreement_name!= $physician_data['agreement_name']){
                    $sheetIndex++;
                    $agreement_name= $physician_data['agreement_name'];
                    $nextWorksheet = clone $templateSheet;
                    $nextWorksheet->setTitle("" . $sheetIndex);
                    $workbook->addSheet($nextWorksheet);
                    $header = '';
                    $header .= strtoupper($hospital->name) . "\n";
                    $header .= "Payment Summary Report\n";
                    $header .= "Period: " . $physician_data["Period"] . "\n";
        
                    $header .= "Run Date: " . with($physician_data["localtimeZone"]);
    
                    $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("B2", $header);
                    $current_row = 6;
                    $regionName=null;
                    $total_amountpaid= 0;
                    $total_worked_hours= 0;
                     
                     
                 foreach($physician_data["payment_detail"] as $data) {
                        if(is_practice_manager()){
                            
                            $report_practice_id = $data["practice_id"];
                        }  
                        
                      //      $sum_worked_hours = $datas["sum_worked_hours"]->sum_worked_hours[0]->sum_worked_hours;     
                        foreach($datas as $data) { 
                        $sum_worked_hours = $data->sum_worked_hours[0]->sum_worked_hours;  
                         $physician_full_name= $data->last_name." ". $data->first_name;
                         $data_range=$data->start_date." - ".$data->end_date;
                         $total_amountpaid = $total_amountpaid + $data->amountPaidTotal; 
                         $total_worked_hours = $total_worked_hours + $sum_worked_hours; 
                         if((property_exists($data,"region_name"))==false)
                         {
                          if($regionName==null){ 
                                $regionName = "   "; 
                            $workbook->setActiveSheetIndex($sheetIndex)
                            ->setCellValue("B{$current_row}", $regionName)
                            ->setCellValue("C{$current_row}", $data->hospital_name)    
                            ->setCellValue("D{$current_row}", $data->practice_name)
                            ->setCellValue("E{$current_row}", $physician_full_name)
                            ->setCellValue("F{$current_row}", $data->specialty_name)
                            ->setCellValue("G{$current_row}", number_format($sum_worked_hours, 2))
                            ->setCellValue("H{$current_row}", "$" .number_format((float)$data->amountPaidTotal, 2));
                          }
                          else{
                            $workbook->setActiveSheetIndex($sheetIndex) 
                            ->setCellValue("D{$current_row}", $data->practice_name)
                            ->setCellValue("E{$current_row}", $physician_full_name)
                            ->setCellValue("F{$current_row}", $data->specialty_name)
                            ->setCellValue("G{$current_row}", number_format($sum_worked_hours, 2))
                            ->setCellValue("H{$current_row}", "$" .number_format((float)$data->amountPaidTotal, 2));
                          }
                        }
                        else
                        {
                            if($regionName==null || $regionName !=$data->region_name){                
                                    $regionName =  $data->region_name;
                                $workbook->setActiveSheetIndex($sheetIndex)
                                ->setCellValue("B{$current_row}", $regionName)
                                ->setCellValue("C{$current_row}", $data->hospital_name)    
                                ->setCellValue("D{$current_row}", $data->practice_name)
                                ->setCellValue("E{$current_row}", $physician_full_name)
                                ->setCellValue("F{$current_row}", $data->specialty_name)
                                ->setCellValue("G{$current_row}", number_format($sum_worked_hours, 2))
                                ->setCellValue("H{$current_row}", "$" .number_format((float)$data->amountPaidTotal, 2));
                              }
                              else{
                                $workbook->setActiveSheetIndex($sheetIndex) 
                                ->setCellValue("D{$current_row}", $data->practice_name)
                                ->setCellValue("E{$current_row}", $physician_full_name)
                                ->setCellValue("F{$current_row}", $data->specialty_name)
                                ->setCellValue("G{$current_row}", number_format($sum_worked_hours, 2))
                                ->setCellValue("H{$current_row}", "$" .number_format((float)$data->amountPaidTotal, 2));
                              }
                        }
       
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:H{$current_row}")->applyFromArray($this->border_right_left);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}")->applyFromArray($this->action_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("F{$current_row}")->applyFromArray($this->sign_box_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("G{$current_row}:G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:H{$current_row}")->applyFromArray($this->border_bottom);
                        $title = strlen( $agreement_name) > 31 ? substr( $agreement_name,0,31):  $agreement_name;
                        $workbook->getActiveSheet()->setTitle($title);
                    
    
                        $new_current_row = $current_row;
                        $physician_row = ($new_current_row) - ((($new_current_row) - $current_row) / 2);
                        $physician_row = floor($physician_row);
                
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:H{$current_row}")->applyFromArray($this->cell_style);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:H{$current_row}")->applyFromArray($this->border_right_left);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("E{$current_row}")->getAlignment()->setWrapText(true);
                        $workbook->setActiveSheetIndex($sheetIndex)->getRowDimension($current_row)->setRowHeight(-1);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}:E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)
                             ->getStyle("E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)
                             ->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $workbook->setActiveSheetIndex($sheetIndex)
                               ->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $current_row++;
                        }
                        
                        //This code is added to print total amount and one blank row in report
                            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:H{$current_row}")->applyFromArray($this->cell_style);
                            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:H{$current_row}")->applyFromArray($this->border_right_left);

                            $workbook->setActiveSheetIndex($sheetIndex)    
                            ->setCellValue("D{$current_row}", "Total")
                            ->setCellValue("G{$current_row}", number_format((float)$total_worked_hours, 2))
                            ->setCellValue("H{$current_row}", "$" .number_format((float)$total_amountpaid, 2));
                            $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                            $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
       
                    }

                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:H{$current_row}")->applyFromArray($this->border_bottom);
                }

            }
        }

        $workbook->removeSheetByIndex(0);
        if ($workbook->getSheetCount() == 0) {
            $this->failure("paymentsummary.logs_unavailable");
            return;
        }
      
        if(is_practice_manager()) {
            $report_practice = Practice::findOrFail($report_practice_id);
            $report_path = practice_report_path($report_practice);
        }else{
            $report_path = hospital_report_path($hospital);
        }

        if(count($arguments->report_data) > 0){
            $timeZone = str_replace(' ','_', $arguments->report_data[0]["localtimeZone"]);
            $timeZone = str_replace('/','', $timeZone);
            $timeZone = str_replace(':','', $timeZone);
            $report_filename = "paymentsummaryReport_" . $hospital->name . "_"  . $timeZone . ".xlsx";

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
            $hospital_report->type = 6;
            $hospital_report->save();

            $this->success('paymentsummary.generate_success', $hospital_report->id, $hospital_report->filename);
        } 
    }*/

    protected function parseArguments(){
        $result = new StdClass;
        $result->hospital_id = $this->argument("hospital");
        $result->contract_type = $this->argument("contract_type");
        $result->physician_ids = $this->argument("physicians");
        $result->report_data = $this->argument("report_data");
        $result->localtimeZone = $this->argument("localtimeZone");
        $result->period = $this->argument("period");
        return $result;
    }

    protected function getArguments()
    {
        return [
            ["hospital", InputArgument::REQUIRED, "The hospital ID."],
            ["contract_type", InputArgument::REQUIRED, "The contract type."],
            ["physicians", InputArgument::REQUIRED, "The physician IDs."],
            ["agreements", InputArgument::REQUIRED, "The hospital agreement IDs."],
            ["report_data", InputArgument::REQUIRED, "The report data."],
            ["localtimeZone", InputArgument::REQUIRED, "The local time zone."],
            ["period", InputArgument::REQUIRED, "The Period."]
        ];
    }

    protected function getOptions()
    {
        return [];
    }

}