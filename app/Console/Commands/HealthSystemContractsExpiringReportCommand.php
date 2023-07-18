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
use App\HealthSystemReport;
use function App\Start\health_system_report_path;

class HealthSystemContractsExpiringReportCommand extends ReportingCommand
{
    protected $name = "reports:HealthSystemContractsExpiringReport";
    protected $description = "Generates a DYNAFIOS Health system contracts expiring report.";


    private $cell_style = [
        'borders' => [
            'outline' => ['borderStyle' => Border::BORDER_THIN],
            'inside' => ['borderStyle' => Border::BORDER_THIN],
            'left' => ['borderStyle' => Border::BORDER_MEDIUM],
            'right' =>['borderStyle' => Border::BORDER_MEDIUM]
        ]
    ];

    private $cell_highlight_style = [
      'fill' => [
          'fillType' => Fill::FILL_SOLID,
          'color' => ['rgb' => 'eeece1']
      ]

    ];

    private $cell_center_justified = [
      'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ];

    private $border_top = [
      'borders' => [
        'top' => ['borderStyle' => Border::BORDER_MEDIUM]
      ]
    ];


    private $cell_bottom_style = [
        'borders' => [
            'outline' => ['borderStyle' => Border::BORDER_THIN],
            'inside' => ['borderStyle' => Border::BORDER_THIN],
            'left' => ['borderStyle' => Border::BORDER_THIN],
            'right' => ['borderStyle' => Border::BORDER_MEDIUM],
            'bottom' => ['borderStyle' => Border::BORDER_MEDIUM]
        ]
    ];

    private $cell_left_border_style = [
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_MEDIUM]
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
            //'inside' => ['borderStyle' => Border::BORDER_MEDIUM,'color' => ['rgb' => 'FF0000']]
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
            //'inside' => ['borderStyle' => Border::BORDER_MEDIUM,'color' => ['rgb' => 'FF0000']]
        ]

    ];

    public function __invoke()
    {
        $arguments = $this->parseArguments();

        $now = Carbon::now();
		$timezone = $now->timezone->getName();
		$timestamp = format_date((exec('time /T')), "h:i A");

        // $workbook = $this->loadTemplate('health_system_contracts_expiring_template.xlsx');

        //Load template using phpSpreadsheet
        $reader = IOFactory::createReader("Xlsx");
        $workbook = $reader->load(storage_path()."/reports/templates/health_system_contracts_expiring_template.xlsx");
            
    		$sheetIndex = 0;
  			$workbook->getActiveSheet()
  				->getPageSetup()
  				->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
  			$workbook->getActiveSheet()
  				->getPageSetup()
  				->setPaperSize(PageSetup::PAPERSIZE_A4);

        $report_header = '';
        $report_header .= $arguments->filter_healthsystem." - Contracts Expiring Report \n";
        $report_header .= $arguments->filter_region." \n";
        $report_header .= $arguments->filter_facility." \n";
        $report_header .= "Period of contracts expiring: ".$arguments->expiring_in ." days\n";
        // $report_header .= "Run Date: " . with(new DateTime('now'))->format("m/d/Y");
        $report_header .= "Run Date: " . with($arguments->localtimeZone);


        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue('B2', $report_header);

        $contracts_expiring_index = 4;

        $write_data_contracts_Expiring = $this->writeData($workbook, $sheetIndex, $contracts_expiring_index, $arguments->report_data);

        if($write_data_contracts_Expiring == $contracts_expiring_index)
        {
          $this->failure("health_system_region.contracts_expiring_unavailable");
    			return;
        }

        $report_path = health_system_report_path();
        // $report_filename = "report_contracts_expiring_" . date('mdYhis') . ".xlsx";
        $timeZone = str_replace(' ','_', $arguments->localtimeZone);
        $timeZone = str_replace('/','', $timeZone);
        $timeZone = str_replace(':','', $timeZone);

        $report_filename = "report_contracts_expiring_" . $arguments->filter_healthsystem . "_" . $timeZone . ".xlsx";

        if (!file_exists($report_path)) {
            mkdir($report_path, 0777, true);
        }

        $writer = IOFactory::createWriter($workbook, 'Xlsx');
        $writer->save("{$report_path}/{$report_filename}");

        $health_system_expiring_contracts_report = new HealthSystemReport;

        $health_system_expiring_contracts_report->filename = $report_filename;

        //all fields to be saved in database
        $health_system_expiring_contracts_report->health_system_id= $arguments->health_system_id;
        $health_system_expiring_contracts_report->health_system_region_id= $arguments->health_system_region_id;
        $health_system_expiring_contracts_report->created_by_user_id= $arguments->user_id;
        $health_system_expiring_contracts_report->report_type = HealthSystemReport::CONTRACTS_EXPIRING_REPORTS;
        $health_system_expiring_contracts_report->is_region_level = false;
        $health_system_expiring_contracts_report->save();

        /*message for success in language folder*/
        $this->success('hospitals.generate_report_success', $health_system_expiring_contracts_report->id, $health_system_expiring_contracts_report->filename);
    }

    protected function writeData($workbook, $sheetIndex, $index, $data)
    {

        $current_row = $index;

        foreach($data as $contract_data)
        {
          $workbook->setActiveSheetIndex($sheetIndex)
              ->setCellValue("B{$current_row}",$contract_data['region_name'] )
              ->setCellValue("C{$current_row}", $contract_data['hospital_name'])
              ->setCellValue("D{$current_row}", $contract_data['contract_name'])
              ->setCellValue("E{$current_row}", $contract_data['physician_name'])
              ->setCellValue("F{$current_row}", $contract_data['agreement_start_date'])
              ->setCellValue("G{$current_row}", $contract_data['manual_contract_end_date'] );

          $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:G{$current_row}")->applyFromArray($this->cell_style);
          $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}")->applyFromArray($this->cell_center_justified);
          $workbook->setActiveSheetIndex($sheetIndex)->getStyle("G{$current_row}")->applyFromArray($this->cell_highlight_style);


              $current_row ++;
        }
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:G{$current_row}")->applyFromArray($this->border_top);
        return $current_row;
    }

    protected function parseArguments()
    {
        $result = new StdClass;
        $result->report_data =$this->argument('data');
        $result->user_id=$this->argument('user_id');
        $result->health_system_id=$this->argument('health_system_id');
        $result->health_system_region_id=$this->argument('health_system_region_id');
        $result->expiring_in=$this->argument('expiring_in');
        $result->filter_region=$this->argument('filter_region');
        $result->filter_facility=$this->argument('filter_facility');
        $result->filter_healthsystem=$this->argument('filter_healthsystem');
        $result->localtimeZone = $this->argument('localtimeZone');

        return $result;
    }

    protected function getArguments()
    {
        return [
            ["data", InputArgument::REQUIRED, "data for active contracts are required."],
            ["user_id", InputArgument::REQUIRED, "user id is required"],
            ["health_system_id", InputArgument::REQUIRED, "health_system_id is required"],
            ["health_system_region_id", InputArgument::REQUIRED, "health_system_region_id is required"],
            ["expiring_in", InputArgument::REQUIRED, "expiring_in is required"],
            ["filter_region", InputArgument::REQUIRED, "filter_region is required"],
            ["filter_facility", InputArgument::REQUIRED, "filter_facility is required"],
            ["filter_healthsystem", InputArgument::REQUIRED, "filter_healthsystem is required"],
            ["localtimeZone", InputArgument::REQUIRED, "The agreement localtimeZone."]
        ];
    }

    protected function getOptions()
    {
        return [];
    }
}
