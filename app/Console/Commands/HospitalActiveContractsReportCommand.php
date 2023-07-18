<?php
namespace App\Console\Commands;
// use PHPExcel_Worksheet_PageSetup;
use StdClass;
use DateTime;
use App\Hospital;
use App\HospitalReport;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

//Below imports are for php spreadsheets.
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use function App\Start\hospital_report_path;

class HospitalActiveContractsReportCommand extends ReportingCommand
{
    protected $name = "reports:HospitalActiveContractsReport";
    protected $description = "Generates a DYNAFIOS Hospital Active contracts report.";
    protected $contract_length = 0;
    protected $contract_count = 0;

    private $cell_style = [
        'borders' => [
            'outline' => ['borderStyle' => Border::BORDER_THIN],
            'inside' => ['borderStyle' => Border::BORDER_THIN],
            'left' => ['borderStyle' => Border::BORDER_MEDIUM],
            'right' =>['borderStyle' => Border::BORDER_MEDIUM]
        ]
    ];

    private $border_top = [
      'borders' => [
        'top' => ['borderStyle' => Border::BORDER_MEDIUM]
      ]
    ];

    private $cell_left_justified = [
      'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
    ];

    private $cell_center_justified = [
      'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ];

    private $cell_right_justified = [
      'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
    ];


    private $contract_align = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '000000']],
        'font' => ['color' => ['rgb' => 'FFFFFF']],
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_MEDIUM],
            'right' => ['borderStyle' => Border::BORDER_THIN]
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

    private $amount_paid_style = [
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_MEDIUM],
            'right' => ['borderStyle' => Border::BORDER_MEDIUM]
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
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_MEDIUM],
            'right' => ['borderStyle' => Border::BORDER_MEDIUM],
            'top' => ['borderStyle' => Border::BORDER_NONE]
        ]
    ];

    private $CYTD_shaded_style = [
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'color' => ['rgb' => 'eeece1']
        ],
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_MEDIUM],
            'right' => ['borderStyle' => Border::BORDER_MEDIUM],
            'top' => ['borderStyle' => Border::BORDER_NONE],
            'bottom' => ['borderStyle' => Border::BORDER_NONE]
        ]
    ];

    private $CYTPM_lastRow_style = [
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_NONE],
            'right' => ['borderStyle' => Border::BORDER_NONE],
            'top' => ['borderStyle' => Border::BORDER_MEDIUM],
            'bottom' => ['borderStyle' => Border::BORDER_NONE]
        ]
    ];

    private $period_style = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '000000']],
        'font' => ['color' => ['rgb' => 'FFFFFF'], 'size' => 12],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        'borders' => [
            'allborders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']],
            //'inside' => ['style' => Border::BORDER_MEDIUM,'color' => ['rgb' => 'FF0000']]
        ]
    ];

    private $period_breakdown_practice_style = [
        'borders' => [

            'left' => ['borderStyle' => Border::BORDER_MEDIUM],
            'right' => ['borderStyle' => Border::BORDER_THIN],
            'top' => ['borderStyle' => Border::BORDER_THIN],
            'bottom' => ['borderStyle' => Border::BORDER_THIN],
        ]
    ];

    private $cytd_breakdown_practice_style = [
        'borders' => [

            'left' => ['borderStyle' => Border::BORDER_THIN],
            'right' => ['borderStyle' => Border::BORDER_THIN],
            'top' => ['borderStyle' => Border::BORDER_THIN],
            'bottom' => ['borderStyle' => Border::BORDER_THIN],
        ]
    ];

    private $bottom_row_style = [
        'borders' => [
            'top' => ['borderStyle' => Border::BORDER_NONE],
            'bottom' => ['borderStyle' => Border::BORDER_MEDIUM],
            'left' => ['borderStyle' => Border::BORDER_MEDIUM],
        ]
    ];

    private $cytd_ymt_cell_style = [
        'borders' => [
            'top' => ['borderStyle' => Border::BORDER_THIN],
            'bottom' => ['borderStyle' => Border::BORDER_THIN],
            'left' => ['borderStyle' => Border::BORDER_MEDIUM]
        ]
    ];

    private $cytd_ymt_bottom_cell_style = [
        'borders' => [
            'bottom' => ['borderStyle' => Border::BORDER_MEDIUM]
        ]
    ];

    private $blank_cell_style = [
        'borders' => [
            'top' => ['borderStyle' => Border::BORDER_NONE],
            'bottom' => ['borderStyle' => Border::BORDER_NONE],
        ]
    ];

    private $CYTPM_contract_period_bottom_style = [
        'borders' => [
            'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'FFFFFF']],
            //'inside' => ['style' => Border::BORDER_MEDIUM,'color' => ['rgb' => 'FF0000']]
        ]

    ];

    public function __invoke()
    {
        $arguments = $this->parseArguments();
        $hospital = Hospital::findOrFail($arguments->hospital_id);

        $now = Carbon::now();
		$timezone = $now->timezone->getName();
		$timestamp = format_date((exec('time /T')), "h:i A");

        // $workbook = $this->loadTemplate('hospital_active_contracts_template.xlsx');

        $reader = IOFactory::createReader("Xlsx");
        $workbook = $reader->load(storage_path()."/reports/templates/hospital_active_contracts_template.xlsx");

        $sheetIndex = 0;
        $workbook->getActiveSheet()
          ->getPageSetup()
          ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $workbook->getActiveSheet()
          ->getPageSetup()
          ->setPaperSize(PageSetup::PAPERSIZE_A4);

        $report_header = '';
        $report_header .= $arguments->filter_facility." - Active Contracts Report \n";
       // $report_header .= "Run Date: " . with(new DateTime('now'))->format("m/d/Y")." ";
        $report_header .= "Run Date: " . with($arguments->report_data[0]['localtimeZone']);
       
        


        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue('B2', $report_header);

        $active_contracts_index = 4;

        $write_data_active_contracts = $this->writeData($workbook, $sheetIndex, $active_contracts_index, $arguments->report_data);

        if($write_data_active_contracts == $active_contracts_index)
        {
          $this->failure("health_system_region.active_contracts_unavailable");
    			return;
        }

        $report_path = hospital_report_path($hospital);
        // $report_filename = "report_active_contracts_" . date('mdYhis') . ".xlsx";

        $timeZone = str_replace(' ','_', $arguments->report_data[0]['localtimeZone']);
        $timeZone = str_replace('/','', $timeZone);
        $timeZone = str_replace(':','', $timeZone);
		$report_filename = "report_active_contracts_" . $hospital->name . "_"  . $timeZone . ".xlsx";

        if (!file_exists($report_path)) {
            mkdir($report_path, 0777, true);
        }

        
        $writer = IOFactory::createWriter($workbook, 'Xlsx');
        $writer->save("{$report_path}/{$report_filename}");

        $hospital_active_contracts_report = new HospitalReport;

        $hospital_active_contracts_report->filename = $report_filename;

        //all fields to be saved in database
        $hospital_active_contracts_report->hospital_id= $arguments->hospital_id;
        $hospital_active_contracts_report->type = 5;
        $hospital_active_contracts_report->save();

        /*message for success in language folder*/
        $this->success('hospitals.generate_report_success', $hospital_active_contracts_report->id, $hospital_active_contracts_report->filename);
    }

    protected function writeData($workbook, $sheetIndex, $index, $data)
    {

        $current_row = $index;
        $level1 = false;
        $level2 = false;
        $level3 = false;
        $level4 = false;
        $level5 = false;
        $level6 = false;

        foreach($data as $contract_data)
        {
            $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("C{$current_row}:Q{$current_row}")->getAlignment()->setWrapText(true);

            $workbook->setActiveSheetIndex($sheetIndex)
                        ->getRowDimension($current_row)
                        ->setRowHeight(80);
            $workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('B')->setVisible(false);
            //hide approval if has no contract
            
            if(($contract_data['approval_level1'] != "-") && !$level1) {
                $level1 = true;
            }
            if(($contract_data['approval_level2'] != "-") && !$level2) {
                $level2 = true;
            }
            if(($contract_data['approval_level3'] != "-") && !$level3) {
                $level3 = true;
            }
            if(($contract_data['approval_level4'] != "-") && !$level4) {
                $level4 = true;
            }
            if(($contract_data['approval_level5'] != "-") && !$level5) {
                $level5 = true;
            }
            if(($contract_data['approval_level6'] != "-") && !$level6) {
                $level6 = true;
            }

          $workbook->setActiveSheetIndex($sheetIndex)
           //   ->setCellValue("B{$current_row}",'' )
              ->setCellValue("C{$current_row}", $contract_data['hospital_name'])
              ->setCellValue("D{$current_row}", $contract_data['contract_name'])
              ->setCellValue("E{$current_row}", $contract_data['contract_type'])
              ->setCellValue("F{$current_row}", $contract_data['physician_name'])
              ->setCellValue("G{$current_row}", $contract_data['physician_email'])
              ->setCellValue("H{$current_row}", $contract_data['agreement_start_date'])
              ->setCellValue("I{$current_row}", $contract_data['manual_contract_end_date'] )
              ->setCellValue("J{$current_row}", "$" . number_format((float)$contract_data['expected_spend'], 2))
              ->setCellValue("K{$current_row}", "$" . number_format((float)$contract_data['amount'], 2))
              ->setCellValue("L{$current_row}", $contract_data['approval_level1'] ."\n". $contract_data['approval_level_email1'])
              ->setCellValue("M{$current_row}", $contract_data['approval_level2'] ."\n". $contract_data['approval_level_email2'])
              ->setCellValue("N{$current_row}", $contract_data['approval_level3'] ."\n". $contract_data['approval_level_email3'])
              ->setCellValue("O{$current_row}", $contract_data['approval_level4'] ."\n". $contract_data['approval_level_email4'])
              ->setCellValue("P{$current_row}", $contract_data['approval_level5'] ."\n". $contract_data['approval_level_email5'])
              ->setCellValue("Q{$current_row}", $contract_data['approval_level6'] ."\n". $contract_data['approval_level_email6']);

          // $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:I{$current_row}")->applyFromArray($this->cell_style);
          $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:Q{$current_row}")->applyFromArray($this->cell_style);
          $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}")->applyFromArray($this->cell_center_justified);
          $workbook->setActiveSheetIndex($sheetIndex)->getStyle("C{$current_row}:E{$current_row}")->applyFromArray($this->cell_left_justified);
          $workbook->setActiveSheetIndex($sheetIndex)->getStyle("F{$current_row}:G{$current_row}")->applyFromArray($this->cell_left_justified);
          // $workbook->setActiveSheetIndex($sheetIndex)->getStyle("H{$current_row}:I{$current_row}")->applyFromArray($this->cell_right_justified);
          $workbook->setActiveSheetIndex($sheetIndex)->getStyle("H{$current_row}:I{$current_row}")->applyFromArray($this->cell_center_justified);
          $workbook->setActiveSheetIndex($sheetIndex)->getStyle("J{$current_row}:K{$current_row}")->applyFromArray($this->cell_center_justified);
          $workbook->setActiveSheetIndex($sheetIndex)->getRowDimension($current_row)->setRowHeight(-1);
          $workbook->setActiveSheetIndex($sheetIndex)->getStyle("L{$current_row}:M{$current_row}")->applyFromArray($this->cell_center_justified);
          $workbook->setActiveSheetIndex($sheetIndex)->getStyle("N{$current_row}:O{$current_row}")->applyFromArray($this->cell_center_justified);
          $workbook->setActiveSheetIndex($sheetIndex)->getStyle("P{$current_row}:Q{$current_row}")->applyFromArray($this->cell_right_justified);
          $current_row ++;
        }
        if(!$level1){
            $workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('L')->setVisible(false);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:K{$current_row}")->applyFromArray($this->border_top);
        }
        if(!$level2){
            $workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('M')->setVisible(false);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:L{$current_row}")->applyFromArray($this->border_top);
        }
        if(!$level3){
            $workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('N')->setVisible(false);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->border_top);
        }
        if(!$level4){
            $workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('O')->setVisible(false);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:N{$current_row}")->applyFromArray($this->border_top);
        }
        if(!$level5){
            $workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('P')->setVisible(false);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:O{$current_row}")->applyFromArray($this->border_top);
        }
        if(!$level6){
            $workbook->setActiveSheetIndex($sheetIndex)->getColumnDimension('Q')->setVisible(false);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:P{$current_row}")->applyFromArray($this->border_top);
        } else{
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:Q{$current_row}")->applyFromArray($this->border_top);
        }
        // $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:I{$current_row}")->applyFromArray($this->border_top);
        // $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:Q{$current_row}")->applyFromArray($this->border_top);
        return $current_row;
    }

    protected function parseArguments()
    {
        $result = new StdClass;
        $result->report_data =$this->argument('data');
        $result->user_id=$this->argument('user_id');
        $result->filter_facility=$this->argument('filter_facility');
        $result->hospital_id=$this->argument('hospital_id');

        return $result;
    }

    protected function getArguments()
    {
        return [
            ["data", InputArgument::REQUIRED, "data for active contracts are required."],
            ["user_id", InputArgument::REQUIRED, "user id is required"],
            ["filter_facility", InputArgument::REQUIRED, "filter_facility is required"],
            ["hospital_id", InputArgument::REQUIRED, "hospital id is required"]
        ];
    }

    protected function getOptions()
    {
        return [];
    }
}
