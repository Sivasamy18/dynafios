<?php
namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use StdClass;
use DateTime;
use App\Hospital;
use App\HospitalReport;
use App\Physician;
use App\Practice;
use App\PracticeManagerReport;
//drop column practice_id from table 'physicians' changes by 1254
use App\PhysicianPractices;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

//Below imports are for php spreadsheets.
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use App\PhysicianHospitalReport;
use function App\Start\is_physician;
use function App\Start\is_practice_manager;
use function App\Start\hospital_report_path;
use function App\Start\practice_report_path;

class HospitalReportCommand extends ReportingCommand
{
    protected $name = "reports:hospital";
    protected $description = "Generates a DYNAFIOS hospital report.";
    protected $contract_length = 0;
    protected $contract_count = 0;

    private $cell_style = [
        'borders' => [
            'outline' => ['borderStyle'  => Border::BORDER_THIN],
            'inside' => ['borderStyle'  => Border::BORDER_THIN],
            'left' => ['borderStyle'  => Border::BORDER_MEDIUM]
        ]
    ];

    private $period_cell_style = [
        'borders' => [
            'outline' => ['borderStyle'  => Border::BORDER_THIN],
            'inside' => ['borderStyle'  => Border::BORDER_THIN],
            'left' => ['borderStyle'  => Border::BORDER_THIN]
        ]
    ];

    private $cell_bottom_style = [
        'borders' => [
            'outline' => ['borderStyle'  => Border::BORDER_THIN],
            'inside' => ['borderStyle'  => Border::BORDER_THIN],
            'left' => ['borderStyle'  => Border::BORDER_THIN],
            'right' => ['borderStyle'  => Border::BORDER_MEDIUM],
            'bottom' => ['borderStyle'  => Border::BORDER_MEDIUM]
        ]
    ];

    private $cell_left_border_style = [
        'borders' => [
            'left' => ['borderStyle'  => Border::BORDER_MEDIUM]
        ]
    ];

    private $contract_style = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '000000']],
        'font' => ['color' => ['rgb' => 'FFFFFF'], 'size' => 14],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        'borders' => [
            'allborders' => ['borderStyle'  => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']],
            //'inside' => ['borderStyle'  => Border::BORDER_MEDIUM,'color' => ['rgb' => 'FF0000']]
        ]
    ];

    private $contract_align = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '000000']],
        'font' => ['color' => ['rgb' => 'FFFFFF']],
        'borders' => [
            'left' => ['borderStyle'  => Border::BORDER_MEDIUM],
            'right' => ['borderStyle'  => Border::BORDER_THIN]
        ]
    ];

    private $total_style = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'eeecea']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        'borders' => [
            'left' => ['borderStyle'  => Border::BORDER_MEDIUM],
            'right' => ['borderStyle'  => Border::BORDER_MEDIUM],
            'outline' => ['borderStyle'  => Border::BORDER_THIN],
            'inside' => ['borderStyle'  => Border::BORDER_THIN]
        ]
    ];

    private $amount_paid_style = [
        'borders' => [
            'left' => ['borderStyle'  => Border::BORDER_MEDIUM],
            'right' => ['borderStyle'  => Border::BORDER_MEDIUM]
        ]
    ];

    private $red_style = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'ffc7ce']],
        'font' => ['color' => ['rgb' => '9c0006'], 'bold' => true],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ];

    private $green_style = [
        'fill' => ['type' => Fill::FILL_SOLID, 'color' => ['rgb' => 'c6efce']],
        'font' => ['color' => ['rgb' => '006100'], 'bold' => true],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ];

    private $shaded_style = [
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'color' => ['rgb' => 'eeece1']
        ],
        'borders' => [
            'left' => ['borderStyle'  => Border::BORDER_MEDIUM],
            'right' => ['borderStyle'  => Border::BORDER_MEDIUM],
            'top' => ['borderStyle'  => Border::BORDER_NONE]
        ]
    ];

    private $CYTD_shaded_style = [
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'color' => ['rgb' => 'eeece1']
        ],
        'borders' => [
            'left' => ['borderStyle'  => Border::BORDER_MEDIUM],
            'right' => ['borderStyle'  => Border::BORDER_MEDIUM],
            'top' => ['borderStyle'  => Border::BORDER_NONE],
            'bottom' => ['borderStyle'  => Border::BORDER_NONE]
        ]
    ];

    private $CYTPM_lastRow_style = [
        'borders' => [
            'left' => ['borderStyle'  => Border::BORDER_NONE],
            'right' => ['borderStyle'  => Border::BORDER_NONE],
            'top' => ['borderStyle'  => Border::BORDER_MEDIUM],
            'bottom' => ['borderStyle'  => Border::BORDER_NONE]
        ]
    ];

    private $period_style = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '000000']],
        'font' => ['color' => ['rgb' => 'FFFFFF'], 'size' => 12],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        'borders' => [
            'allborders' => ['borderStyle'  => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']],
            //'inside' => ['borderStyle'  => Border::BORDER_MEDIUM,'color' => ['rgb' => 'FF0000']]
        ]
    ];

    private $period_breakdown_practice_style = [
        'borders' => [

            'left' => ['borderStyle'  => Border::BORDER_MEDIUM],
            'right' => ['borderStyle'  => Border::BORDER_THIN],
            'top' => ['borderStyle'  => Border::BORDER_THIN],
            'bottom' => ['borderStyle'  => Border::BORDER_THIN],
        ]
    ];

    private $cytd_breakdown_practice_style = [
        'borders' => [

            'left' => ['borderStyle'  => Border::BORDER_THIN],
            'right' => ['borderStyle'  => Border::BORDER_THIN],
            'top' => ['borderStyle'  => Border::BORDER_THIN],
            'bottom' => ['borderStyle'  => Border::BORDER_THIN],
        ]
    ];

    private $bottom_row_style = [
        'borders' => [
            'top' => ['borderStyle'  => Border::BORDER_NONE],
            'bottom' => ['borderStyle'  => Border::BORDER_MEDIUM],
            'left' => ['borderStyle'  => Border::BORDER_MEDIUM],
        ]
    ];

    private $cytd_ymt_cell_style = [
        'borders' => [
            'top' => ['borderStyle'  => Border::BORDER_THIN],
            'bottom' => ['borderStyle'  => Border::BORDER_THIN],
            'left' => ['borderStyle'  => Border::BORDER_MEDIUM]
        ]
    ];

    private $cytd_ymt_bottom_cell_style = [
        'borders' => [
            'bottom' => ['borderStyle'  => Border::BORDER_MEDIUM]
        ]
    ];

    private $blank_cell_style = [
        'borders' => [
            'top' => ['borderStyle'  => Border::BORDER_NONE],
            'bottom' => ['borderStyle'  => Border::BORDER_NONE],
        ]
    ];

    private $CYTPM_contract_period_bottom_style = [
        'borders' => [
            'bottom' => ['borderStyle'  => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'FFFFFF']],
            //'inside' => ['borderStyle'  => Border::BORDER_MEDIUM,'color' => ['rgb' => 'FF0000']]
        ]

    ];

    public function __invoke()
    {
        $arguments = $this->parseArguments();

        $now = Carbon::now();
		$timezone = $now->timezone->getName();
		$timestamp = format_date((exec('time /T')), "h:i A");

        $hospital = Hospital::findOrFail($arguments->hospital_id);
        // $workbook = $this->loadTemplate('hospital_report_combine.xlsx');

        $reader = IOFactory::createReader("Xlsx");
        $workbook = $reader->load(storage_path()."/reports/templates/hospital_report_combine.xlsx");

        $report_header = '';
        $report_header .= strtoupper($hospital->name) . "\n";
        $report_header .= "Period Report\n";
        $report_header .= "Period: " . $arguments->start_date->format("m/d/Y") . " - " . $arguments->end_date->format("m/d/Y") . "\n";
        // $report_header .= "Run Date: " . with(new DateTime('now'))->format("m/d/Y");
        $report_header .= "Run Date: " . with($arguments->report_data['localtimeZone']);

       
       $report_header_ytm = '';
       $report_header_ytm .= strtoupper($hospital->name) . "\n";
       $report_header_ytm .= "Contract Year To Prior Month Report\n";
       $report_header_ytm .= "Period: " . $arguments->start_date->format("m/d/Y") . " - " . $arguments->end_date->format("m/d/Y") . "\n";
       //$report_header_ytm .= "Run Date: " . with(new DateTime('now'))->format("m/d/Y");
       $report_header_ytm .= "Run Date: " . with($arguments->report_data['localtimeZone']);


       $report_header_ytd = '';
       $report_header_ytd .= strtoupper($hospital->name) . "\n";
       $report_header_ytd .= "Contract Year To Date Report\n";
       $report_header_ytd .= "Period: " . $arguments->start_date->format("m/d/Y") . " - " . $arguments->end_date->format("m/d/Y") . "\n";
       //$report_header_ytd .= "Run Date: " . with(new DateTime('now'))->format("m/d/Y");
       $report_header_ytd .= "Run Date: " . with($arguments->report_data['localtimeZone']);


        $workbook->setActiveSheetIndex(0)->getStyle("B2:AC2")->applyFromArray($this->period_breakdown_practice_style);
        $workbook->setActiveSheetIndex(1)->getStyle("B2:AE2")->applyFromArray($this->period_breakdown_practice_style);
        $workbook->setActiveSheetIndex(2)->getStyle("B2:AD2")->applyFromArray($this->period_breakdown_practice_style);

        //$workbook->setActiveSheetIndex(0)->mergeCells("B3:AC3")->getRowDimension(3)->setRowHeight(70);
        //$workbook->setActiveSheetIndex(1)->mergeCells("B3:AE3")->getRowDimension(3)->setRowHeight(70);
        //$workbook->setActiveSheetIndex(2)->mergeCells("B3:AD3")->getRowDimension(3)->setRowHeight(70);

        $workbook->setActiveSheetIndex(0)->setCellValue('B3', $report_header);
        $workbook->setActiveSheetIndex(1)->setCellValue('B3', $report_header_ytm);
        $workbook->setActiveSheetIndex(2)->setCellValue('B3', $report_header_ytd);
        //$workbook->setActiveSheetIndex(0)->getColumnDimension('C')->setVisible(false);
        if(!$arguments->report_data['perDiem']) {
            foreach (range('D', 'K') as $col) {
                $workbook->setActiveSheetIndex(0)->getColumnDimension($col)->setOutlineLevel(1);
                $workbook->setActiveSheetIndex(0)->getColumnDimension($col)->setVisible(false);
                $workbook->setActiveSheetIndex(0)->getColumnDimension($col)->setCollapsed(true);
            }

            foreach (range('D', 'L') as $col) {
                $workbook->setActiveSheetIndex(1)->getColumnDimension($col)->setOutlineLevel(1);
                $workbook->setActiveSheetIndex(1)->getColumnDimension($col)->setVisible(false);
                $workbook->setActiveSheetIndex(1)->getColumnDimension($col)->setCollapsed(true);
                $workbook->setActiveSheetIndex(2)->getColumnDimension($col)->setOutlineLevel(1);
                $workbook->setActiveSheetIndex(2)->getColumnDimension($col)->setVisible(false);
                $workbook->setActiveSheetIndex(2)->getColumnDimension($col)->setCollapsed(true);
            }
        }
        if(!$arguments->report_data['hourly']) {
            foreach (range('L', 'R') as $col) {
                $workbook->setActiveSheetIndex(0)->getColumnDimension($col)->setOutlineLevel(1);
                $workbook->setActiveSheetIndex(0)->getColumnDimension($col)->setVisible(false);
                $workbook->setActiveSheetIndex(0)->getColumnDimension($col)->setCollapsed(true);
            }
            foreach (range('M', 'T') as $col) {
                $workbook->setActiveSheetIndex(1)->getColumnDimension($col)->setOutlineLevel(1);
                $workbook->setActiveSheetIndex(1)->getColumnDimension($col)->setVisible(false);
                $workbook->setActiveSheetIndex(1)->getColumnDimension($col)->setCollapsed(true);
                $workbook->setActiveSheetIndex(2)->getColumnDimension($col)->setOutlineLevel(1);
                $workbook->setActiveSheetIndex(2)->getColumnDimension($col)->setVisible(false);
                $workbook->setActiveSheetIndex(2)->getColumnDimension($col)->setCollapsed(true);
            }
        }
        if(!$arguments->report_data['stipend']) {
            foreach (range('S', 'Y') as $col) {
                $workbook->setActiveSheetIndex(0)->getColumnDimension($col)->setOutlineLevel(1);
                $workbook->setActiveSheetIndex(0)->getColumnDimension($col)->setVisible(false);
                $workbook->setActiveSheetIndex(0)->getColumnDimension($col)->setCollapsed(true);
            }
            foreach (range('U', 'Z') as $col) {
                $workbook->setActiveSheetIndex(1)->getColumnDimension($col)->setOutlineLevel(1);
                $workbook->setActiveSheetIndex(1)->getColumnDimension($col)->setVisible(false);
                $workbook->setActiveSheetIndex(1)->getColumnDimension($col)->setCollapsed(true);
                $workbook->setActiveSheetIndex(2)->getColumnDimension($col)->setOutlineLevel(1);
                $workbook->setActiveSheetIndex(2)->getColumnDimension($col)->setVisible(false);
                $workbook->setActiveSheetIndex(2)->getColumnDimension($col)->setCollapsed(true);
            }
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AA')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AA')->setVisible(false);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AA')->setCollapsed(true);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AA')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AA')->setVisible(false);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AA')->setCollapsed(true);
        }
        if(!$arguments->report_data['psa']) {
            $workbook->setActiveSheetIndex(0)->getColumnDimension('Z')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('Z')->setVisible(false);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AA')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AA')->setVisible(false);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AB')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AB')->setVisible(false);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AC')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AC')->setVisible(false);

            $workbook->setActiveSheetIndex(1)->getColumnDimension('AB')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AB')->setVisible(false);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AC')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AC')->setVisible(false);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AD')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AD')->setVisible(false);

            $workbook->setActiveSheetIndex(2)->getColumnDimension('AB')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AB')->setVisible(false);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AC')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AC')->setVisible(false);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AD')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AD')->setVisible(false);
        }
        if(!$arguments->report_data['uncompensated']) {
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AD')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AD')->setVisible(false);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AE')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AE')->setVisible(false);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AF')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AF')->setVisible(false);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AG')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AG')->setVisible(false);

            $workbook->setActiveSheetIndex(1)->getColumnDimension('AE')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AE')->setVisible(false);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AF')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AF')->setVisible(false);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AG')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AG')->setVisible(false);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AH')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AH')->setVisible(false);

            $workbook->setActiveSheetIndex(2)->getColumnDimension('AE')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AE')->setVisible(false);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AF')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AF')->setVisible(false);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AG')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AG')->setVisible(false);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AH')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AH')->setVisible(false);
        }
        if(!$arguments->report_data['timeStudy']) {
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AH')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AH')->setVisible(false);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AI')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AI')->setVisible(false);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AJ')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AJ')->setVisible(false);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AK')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AK')->setVisible(false);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AL')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AL')->setVisible(false);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AM')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AM')->setVisible(false);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AN')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AN')->setVisible(false);

            $workbook->setActiveSheetIndex(1)->getColumnDimension('AI')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AI')->setVisible(false);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AJ')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AJ')->setVisible(false);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AK')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AK')->setVisible(false);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AL')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AL')->setVisible(false);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AM')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AM')->setVisible(false);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AN')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AN')->setVisible(false);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AO')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AO')->setVisible(false);

            $workbook->setActiveSheetIndex(2)->getColumnDimension('AI')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AI')->setVisible(false);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AJ')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AJ')->setVisible(false);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AK')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AK')->setVisible(false);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AL')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AL')->setVisible(false);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AM')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AM')->setVisible(false);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AN')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AN')->setVisible(false);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AO')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AO')->setVisible(false);
        }
        if(!$arguments->report_data['perUnit']) {
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AO')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AO')->setVisible(false);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AP')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AP')->setVisible(false);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AQ')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AQ')->setVisible(false);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AR')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AR')->setVisible(false);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AS')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AS')->setVisible(false);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AT')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AT')->setVisible(false);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AU')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(0)->getColumnDimension('AU')->setVisible(false);

            $workbook->setActiveSheetIndex(1)->getColumnDimension('AP')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AP')->setVisible(false);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AQ')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AQ')->setVisible(false);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AR')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AR')->setVisible(false);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AS')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AS')->setVisible(false);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AT')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AT')->setVisible(false);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AU')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AU')->setVisible(false);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AV')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AV')->setVisible(false);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AW')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(1)->getColumnDimension('AW')->setVisible(false);

            $workbook->setActiveSheetIndex(2)->getColumnDimension('AP')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AP')->setVisible(false);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AQ')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AQ')->setVisible(false);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AR')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AR')->setVisible(false);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AS')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AS')->setVisible(false);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AT')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AT')->setVisible(false);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AU')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AU')->setVisible(false);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AV')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AV')->setVisible(false);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AW')->setOutlineLevel(1);
            $workbook->setActiveSheetIndex(2)->getColumnDimension('AW')->setVisible(false);
        }
        $period_index = 5;
        $ytm_index = 5;
        $ytd_index = 5;

        $this->contract_length = count($arguments->agreements);
        $this->contract_count = 0;

        if(is_practice_manager()){
            if(count($arguments->physicians) > 0) {
                $physician = Physician::findOrFail($arguments->physicians[0]);
               //drop column practice_id from table 'physicians' changes by 1254 : codereview  
                $physicianpractices =  PhysicianPractices::where('physician_id','=', $physician->id)
                ->where('hospital_id','=',$arguments->hospital_id)->whereNull("deleted_at")->orderBy("start_date", "desc")->pluck('practice_id')->toArray();	
                //$report_practice_id = $physician->practice_id;
                $report_practice_id = $physicianpractices[0];
            }
        }

        $period_index = $this->writeData($workbook, 0, $period_index, $arguments->report_data['period_data'] );
        $ytm_index = $this->writeDataCYTPM($workbook, 1, $ytm_index, $arguments->report_data['cytpm_data'] );
        $ytd_index = $this->writeDataCYTD($workbook, 2, $ytd_index, $arguments->report_data['cytd_data'] );
       
        /*foreach ($arguments->agreements as $agreement) {
            $contracts = $this->queryContracts($agreement, $agreement->start_date, $agreement->end_date, $arguments->contract_type, $arguments->physicians);

            $period_index = $this->writeData($workbook, 0, $period_index, $contracts->period, $agreement->start_date, $arguments->finalized, $agreement->end_date, $contracts->agreement_start_date, $contracts->agreement_end_date,$arguments->physicians, $agreement);
            $ytm_index = $this->writeDataCYTPM($workbook, 1, $ytm_index, $contracts->year_to_month, $agreement->start_date, $arguments->physicians, $agreement->end_date, $contracts->agreement_start_date, $contracts->agreement_end_date,$agreement);
            $ytd_index = $this->writeDataCYTD($workbook, 2, $ytd_index, $contracts->year_to_date, $agreement->start_date, $arguments->physicians, $agreement->end_date, $contracts->agreement_start_date, $contracts->agreement_end_date,$agreement);
            $this->contract_count++;
        }*/

        if(is_practice_manager()) {
            $report_practice = Practice::findOrFail($report_practice_id);
            $report_path = practice_report_path($report_practice);
        }else{
            $report_path = hospital_report_path($hospital);
        }

        $timeZone = str_replace(' ','_', $arguments->report_data['localtimeZone']);
        $timeZone = str_replace('/','', $timeZone);
        $timeZone = str_replace(':','', $timeZone);

       
        // $report_filename = "report_" . date('mdYhis') . ".xlsx";
        // $report_filename = "report_" . $hospital->name . "_"  . $timeZone . ".xlsx";
        $report_filename = "hospitalReport_" . $hospital->name . "_"  . $timeZone . ".xlsx";

        // if (!file_exists($report_path)) {
        //     mkdir($report_path, 0777, true);
        // }

        if(!File::exists($report_path)){
            File::makeDirectory($report_path, 0777, true, true);
        };

        $writer = IOFactory::createWriter($workbook, 'Xlsx');
        $writer->save("{$report_path}/{$report_filename}");

        
        if(is_physician()){
            $practice_id = PhysicianPractices::select('physician_practices.practice_id')->where('physician_id', '=', $arguments->physician_ids)->where('hospital_id', '=', $hospital->id)->whereNull("deleted_at")->first();
            
            $hospital_report = new PhysicianHospitalReport;
            $hospital_report->hospital_id = $hospital->id;
            $hospital_report->physician_id = $arguments->physician_ids;
            $hospital_report->practice_id = $practice_id->practice_id;
            $hospital_report->filename = $report_filename;
            $hospital_report->type = 1;
            $hospital_report->save();
        }else{
            if(is_practice_manager()){
                $hospital_report = new PracticeManagerReport();
                $hospital_report->practice_id = $report_practice_id;
            }else {
                $hospital_report = new HospitalReport;
                $hospital_report->hospital_id = $hospital->id;
            }
            $hospital_report->filename = $report_filename;
            $hospital_report->type = 1;
            $hospital_report->save();
        }

        $this->success('hospitals.generate_report_success', $hospital_report->id, $hospital_report->filename);
    }



    protected function writeData($workbook, $sheetIndex, $index, $period_data)
    {
        $contracts_count = count($period_data);
        $current_row = $index;

        foreach ($period_data as $contract_name_display) {
            $current_row = $this->writeContractHeader($workbook, $sheetIndex, $current_row, $contract_name_display);
            $current_row = $this->writePeriodHeader($workbook, $sheetIndex, $current_row, $contract_name_display);
            $current_row = $this->writecontractTotal($workbook, $sheetIndex, $current_row, $contract_name_display);
            foreach ($contract_name_display['practice_data'] as $practice_data ) {
                $current_row = $this->writePeriodPracticeTotal($workbook, $sheetIndex, $current_row, $practice_data['practice_info']);
                foreach($practice_data['physician_data'] as $physician_data){
                    if($physician_data['isPerDiem']){
                        $current_row ++;
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("B{$current_row}:C{$current_row}")
                            ->setCellValue("D{$current_row}", $physician_data['perDiem_rate_type'] == 1 ? "Weekday" : "On Call")
                            ->setCellValue("E{$current_row}", $physician_data['perDiem_rate_type'] == 1 ? "Weekend" : "Called Back")
                            ->setCellValue("F{$current_row}", $physician_data['perDiem_rate_type'] == 1 ? "Holiday" : "Called In")
                            ->setCellValue("G{$current_row}", " Total")
                            ->setCellValue("H{$current_row}", $physician_data['perDiem_rate_type'] == 1 ? "Weekday" : "On Call")
                            ->setCellValue("I{$current_row}", $physician_data['perDiem_rate_type'] == 1 ? "Weekend" : "Called Back")
                            ->setCellValue("J{$current_row}", $physician_data['perDiem_rate_type'] == 1 ? "Holiday" : "Called In")
                            ->setCellValue("K{$current_row}", " Total");

                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("B{$current_row}:K{$current_row}")->getFont()->setBold(true);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("B{$current_row}:AG{$current_row}")->applyFromArray($this->total_style);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("V{$current_row}:AG{$current_row}")->applyFromArray($this->cell_left_border_style);

                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:K{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                    if($physician_data['isuncompensated']){
                        $current_row ++;
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("B{$current_row}:C{$current_row}")
                            ->setCellValue("AD{$current_row}",  "On-Call/Uncompensated")
                            ->setCellValue("AE{$current_row}", " Total")
                            ->setCellValue("AF{$current_row}", "On-Call/Uncompensated")
                            ->setCellValue("AG{$current_row}", " Total");

                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("B{$current_row}:AG{$current_row}")->getFont()->setBold(true);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("B{$current_row}:AG{$current_row}")->applyFromArray($this->total_style);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("V{$current_row}:AG{$current_row}")->applyFromArray($this->cell_left_border_style);

                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:AG{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                    $current_row ++;
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("B{$current_row}:C{$current_row}")
                        ->setCellValue("B{$current_row}", $physician_data['physician_name']);
                    if($physician_data['isPerDiem']) {
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->setCellValue("D{$current_row}", number_format((float)$physician_data['colD'], 2))
                            ->setCellValue("E{$current_row}", number_format((float)$physician_data['colE'], 2))
                            ->setCellValue("F{$current_row}", number_format((float)$physician_data['colF'], 2))
                            ->setCellValue("G{$current_row}", number_format((float)$physician_data['colG'], 2))
                            ->setCellValue("H{$current_row}", "$" . number_format((float)$physician_data['colH'], 2))
                            ->setCellValue("I{$current_row}", "$" . number_format((float)$physician_data['colI'], 2))
                            ->setCellValue("J{$current_row}", "$" . number_format((float)$physician_data['colJ'], 2))
                            ->setCellValue("K{$current_row}", "$" . number_format((float)$physician_data['colK'], 2));
                    }
                    if($physician_data['isHourly']) {
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->setCellValue("L{$current_row}", number_format((float)$physician_data['min_hours'], 2))
                            ->setCellValue("M{$current_row}", "$" . number_format((float)$physician_data['potential_pay'], 2))
                            ->setCellValue("N{$current_row}", number_format((float)$physician_data['worked_hrs'], 2))
                            ->setCellValue("O{$current_row}", "$" . number_format((float)$physician_data['amount_paid_for_month'], 2))
                            ->setCellValue("P{$current_row}", "$" . number_format((float)$physician_data['amount_to_be_paid_for_month'], 2))
                            ->setCellValue("Q{$current_row}", "$" . number_format((float)$physician_data['actual_hourly_rate'], 2))
                            ->setCellValue("R{$current_row}", "$" . number_format((float)$physician_data['FMV_hourly'], 2));
                    }
                    if($physician_data['isStipend']) {
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->setCellValue("S{$current_row}", " ")
                            ->setCellValue("T{$current_row}", number_format((float)$physician_data['expected_hours'], 2))
                            ->setCellValue("U{$current_row}", "$" . number_format((float)$physician_data['expected_payment'], 2))
                            ->setCellValue("V{$current_row}", number_format((float)$physician_data['stipend_worked_hrs'], 2))
                            ->setCellValue("W{$current_row}", "$" . number_format((float)$physician_data['stipend_amount_paid_for_month'], 2))
                            ->setCellValue("X{$current_row}", "$" . number_format((float)$physician_data['actual_stipend_rate'], 2))
                            ->setCellValue("Y{$current_row}", "$" . number_format((float)$physician_data['fmv_stipend'], 2));
                    }
                    if($physician_data['isPsa']) {
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->setCellValue("Z{$current_row}", number_format((float)$physician_data['stipend_worked_hrs'], 2))
                            ->setCellValue("AA{$current_row}", "$" . number_format((float)$physician_data['stipend_amount_paid_for_month'], 2))
                            ->setCellValue("AB{$current_row}", "$" . number_format((float)$physician_data['actual_stipend_rate'], 2))
                            ->setCellValue("AC{$current_row}", "$" . number_format((float)$physician_data['fmv_stipend'], 2));
                    }
                    if($physician_data['isuncompensated']) {
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->setCellValue("AD{$current_row}", number_format((float)$physician_data['colAD'], 2))
                            ->setCellValue("AE{$current_row}", number_format((float)$physician_data['colAE'], 2))
                            ->setCellValue("AF{$current_row}", "$" . number_format((float)$physician_data['colAF'], 2))
                            ->setCellValue("AG{$current_row}", "$" . number_format((float)$physician_data['colAG'], 2));
                    }
                    if($physician_data['isTimeStudy']) {
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->setCellValue("AH{$current_row}", " ")
                            ->setCellValue("AI{$current_row}", number_format((float)$physician_data['expected_hours'], 2))
                            ->setCellValue("AJ{$current_row}", "$" . number_format((float)$physician_data['expected_payment'], 2))
                            ->setCellValue("AK{$current_row}", number_format((float)$physician_data['stipend_worked_hrs'], 2))
                            ->setCellValue("AL{$current_row}", "$" . number_format((float)$physician_data['stipend_amount_paid_for_month'], 2))
                            ->setCellValue("AM{$current_row}", "$" . number_format((float)$physician_data['actual_stipend_rate'], 2))
                            ->setCellValue("AN{$current_row}", "$" . number_format((float)$physician_data['fmv_time_study'], 2));
                    }
                    if($physician_data['isPerUnit']) {
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->setCellValue("AO{$current_row}", round($physician_data['min_hours'], 0))
                            ->setCellValue("AP{$current_row}", "$" . number_format((float)$physician_data['potential_pay'], 2))
                            ->setCellValue("AQ{$current_row}", round($physician_data['worked_hrs'], 0))
                            ->setCellValue("AR{$current_row}", "$" . number_format((float)$physician_data['amount_paid_for_month'], 2))
                            ->setCellValue("AS{$current_row}", "$" . number_format((float)$physician_data['amount_to_be_paid_for_month'], 2))
                            ->setCellValue("AT{$current_row}", "$" . number_format((float)$physician_data['actual_hourly_rate'], 2))
                            ->setCellValue("AU{$current_row}", "$" . number_format((float)$physician_data['fmv_per_unit'], 2));
                    }

                    /*$workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("B{$current_row}:K{$current_row}")->getFont()->setBold(true);*/
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("B{$current_row}:AU{$current_row}")->applyFromArray($this->total_style);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("V{$current_row}:Y{$current_row}")->applyFromArray($this->cell_left_border_style);
                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("Z{$current_row}:AU{$current_row}")->applyFromArray($this->cell_left_border_style);

                    $workbook->setActiveSheetIndex($sheetIndex)
                        ->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}:G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("N{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("T{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("V{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("Z{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("AD{$current_row}:AU{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            }
        }
        return $current_row;
    }

    private function writePeriodPracticeMiddle($workbook, $sheetIndex, $practice_first_row, $practice_last_row, $practice_name, $is_last_row)
    {
        $practice_name_row = $practice_last_row - ($practice_last_row - $practice_first_row) / 2;
        $practice_name_row = floor($practice_name_row);

        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$practice_first_row}:C{$practice_last_row}")->applyFromArray($this->period_breakdown_practice_style);
        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("B{$practice_name_row}", $practice_name);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$practice_name_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        if ($is_last_row) {
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("B{$practice_last_row}:C{$practice_last_row}")->applyFromArray($this->bottom_row_style);
        }
    }

    private function writePeriodPracticeTotal($workbook, $sheetIndex, $current_row, $practice_data)
    {
        $current_row ++;
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        if(!$practice_data['isPsa']){
            $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("B{$current_row}:C{$current_row}")
                ->setCellValue("B{$current_row}", "{$practice_data['practice_name']} Totals");
        }
        else {
            $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("B{$current_row}:C{$current_row}")
                ->setCellValue("B{$current_row}", "{$practice_data['practice_name']}");
        }
        if($sheetIndex == 0) {
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("B{$current_row}:AU{$current_row}")->applyFromArray($this->period_style);
            if ($practice_data['isPerDiem']) {
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->setCellValue("D{$current_row}", number_format((float)$practice_data['colD'], 2))
                    ->setCellValue("E{$current_row}", number_format((float)$practice_data['colE'], 2))
                    ->setCellValue("F{$current_row}", number_format((float)$practice_data['colF'], 2 ))
                    ->setCellValue("G{$current_row}", number_format((float)$practice_data['colG'], 2))
                    ->setCellValue("H{$current_row}", "$" . number_format((float)$practice_data['colH'], 2))
                    ->setCellValue("I{$current_row}", "$" . number_format((float)$practice_data['colI'], 2))
                    ->setCellValue("J{$current_row}", "$" . number_format((float)$practice_data['colJ'], 2))
                    ->setCellValue("K{$current_row}", "$" . number_format((float)$practice_data['colK'], 2));
            }
            if ($practice_data['isHourly']) {
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->setCellValue("L{$current_row}", number_format((float)$practice_data['min_hours'], 2))
                    ->setCellValue("M{$current_row}", "$" . number_format((float)$practice_data['potential_pay'], 2))
                    ->setCellValue("N{$current_row}", number_format((float)$practice_data['worked_hrs'], 2))
                    ->setCellValue("O{$current_row}", "$" . number_format((float)$practice_data['amount_paid_for_month'], 2))
                    ->setCellValue("P{$current_row}", "$" . number_format((float)$practice_data['amount_to_be_paid_for_month'], 2))
                    ->setCellValue("Q{$current_row}", "$" . number_format((float)$practice_data['actual_hourly_rate'], 2))
                    ->setCellValue("R{$current_row}", "$" . number_format((float)$practice_data['FMV_hourly'], 2));
            }
            if ($practice_data['isStipend']) {
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->setCellValue("S{$current_row}", $practice_data['practice_pmt_status'])
                    ->setCellValue("T{$current_row}", number_format((float)$practice_data['expected_hours'], 2))
                    ->setCellValue("U{$current_row}", "$" . number_format((float)$practice_data['expected_payment'], 2))
                    ->setCellValue("V{$current_row}", number_format((float)$practice_data['stipend_worked_hrs'], 2))
                    ->setCellValue("W{$current_row}", "$" . number_format((float)$practice_data['stipend_amount_paid_for_month'], 2))
                    ->setCellValue("X{$current_row}", "$" . number_format((float)$practice_data['actual_stipend_rate'], 2))
                    ->setCellValue("Y{$current_row}", "$" . number_format((float)$practice_data['fmv_stipend'], 2));
                $workbook->setActiveSheetIndex($sheetIndex)
                    //->getStyle("B{$current_row}")->applyFromArray(($totals->fmv >= $hourly_rate && $totals->worked_hours != 0) ? $this->green_style : $this->red_style);
                    ->getStyle("S{$current_row}")->applyFromArray(($practice_data['practice_pmt_status'] === 'Y') ? $this->green_style : $this->red_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("S{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            if ($practice_data['isuncompensated']) {
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->setCellValue("AD{$current_row}", number_format((float)$practice_data['colAD'], 2))
                    ->setCellValue("AE{$current_row}", number_format((float)$practice_data['colAE'], 2))
                    ->setCellValue("AF{$current_row}", "$" . number_format((float)$practice_data['colAF'], 2))
                    ->setCellValue("AG{$current_row}", "$" . number_format((float)$practice_data['colAG'], 2));
            }
            if ($practice_data['isTimeStudy']) {
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->setCellValue("AH{$current_row}", $practice_data['practice_pmt_status'])
                    ->setCellValue("AI{$current_row}", number_format((float)$practice_data['expected_hours'], 2))
                    ->setCellValue("AJ{$current_row}", "$" . number_format((float)$practice_data['expected_payment'], 2))
                    ->setCellValue("AK{$current_row}", number_format((float)$practice_data['stipend_worked_hrs'], 2))
                    ->setCellValue("AL{$current_row}", "$" . number_format((float)$practice_data['stipend_amount_paid_for_month'], 2))
                    ->setCellValue("AM{$current_row}", "$" . number_format((float)$practice_data['actual_stipend_rate'], 2))
                    ->setCellValue("AN{$current_row}", "$" . number_format((float)$practice_data['fmv_time_study'], 2));
                $workbook->setActiveSheetIndex($sheetIndex)
                    //->getStyle("B{$current_row}")->applyFromArray(($totals->fmv >= $hourly_rate && $totals->worked_hours != 0) ? $this->green_style : $this->red_style);
                    ->getStyle("AH{$current_row}")->applyFromArray(($practice_data['practice_pmt_status'] === 'Y') ? $this->green_style : $this->red_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("AH{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            if ($practice_data['isPerUnit']) {
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->setCellValue("AO{$current_row}", round($practice_data['min_hours'], 0))
                    ->setCellValue("AP{$current_row}", "$" . number_format((float)$practice_data['potential_pay'], 2))
                    ->setCellValue("AQ{$current_row}", round($practice_data['worked_hrs'], 0))
                    ->setCellValue("AR{$current_row}", "$" . number_format((float)$practice_data['amount_paid_for_month'], 2))
                    ->setCellValue("AS{$current_row}", "$" . number_format((float)$practice_data['amount_to_be_paid_for_month'], 2))
                    ->setCellValue("AT{$current_row}", "$" . number_format((float)$practice_data['actual_hourly_rate'], 2))
                    ->setCellValue("AU{$current_row}", "$" . number_format((float)$practice_data['fmv_per_unit'], 2));
            }
        }
        if($sheetIndex == 1 || $sheetIndex == 2) {
            if($sheetIndex == 1) {
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("B{$current_row}:AX{$current_row}")->applyFromArray($this->period_style);
            }
            if($sheetIndex == 2) {
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("B{$current_row}:AW{$current_row}")->applyFromArray($this->period_style);
            }
            if ($practice_data['isPerDiem']) {
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->setCellValue("D{$current_row}", number_format((float)$practice_data['colD'], 2))
                    ->setCellValue("E{$current_row}", number_format((float)$practice_data['colE'], 2))
                    ->setCellValue("F{$current_row}", number_format((float)$practice_data['colF'], 2))
                    ->setCellValue("G{$current_row}", number_format((float)$practice_data['colG'], 2))
                    ->setCellValue("H{$current_row}", "$" . number_format((float)$practice_data['colH'], 2))
                    ->setCellValue("I{$current_row}", "$" . number_format((float)$practice_data['colI'], 2))
                    ->setCellValue("J{$current_row}", "$" . number_format((float)$practice_data['colJ'], 2))
                    ->setCellValue("K{$current_row}", "$" . number_format((float)$practice_data['colK'], 2))
                    ->setCellValue("L{$current_row}", "$" . number_format((float)$practice_data['colL'], 2));
            }
            if ($practice_data['isHourly']) {
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->setCellValue("M{$current_row}", number_format((float)$practice_data['min_hours'], 2))
                    ->setCellValue("N{$current_row}", number_format((float)$practice_data['max_hours'], 2))
                    ->setCellValue("O{$current_row}", number_format((float)$practice_data['annual_cap'], 2))
                    ->setCellValue("P{$current_row}", "$" . number_format((float)$practice_data['potential_pay'], 2))
                    ->setCellValue("Q{$current_row}", number_format((float)$practice_data['min_hours_ytm'], 2))
                    ->setCellValue("R{$current_row}", number_format((float)$practice_data['worked_hrs']+$practice_data['prior_worked_hours'], 2))
                    ->setCellValue("S{$current_row}", "$" . number_format((float)$practice_data['amount_paid_for_month']+$practice_data['prior_amount_paid'], 2))
                    ->setCellValue("T{$current_row}", "$" . number_format((float)$practice_data['amount_to_be_paid_for_month'], 2));
            }
            if ($practice_data['isStipend']) {
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->setCellValue("U{$current_row}", $practice_data['practice_pmt_status'])
                    ->setCellValue("V{$current_row}", number_format((float)$practice_data['expected_hours'], 2))
                    ->setCellValue("W{$current_row}", "$" . number_format((float)$practice_data['expected_payment'], 2))
                    ->setCellValue("X{$current_row}", number_format((float)$practice_data['expected_hours_ytm'], 2))
                    ->setCellValue("Y{$current_row}", number_format((float)$practice_data['stipend_worked_hrs'], 2))
                    ->setCellValue("Z{$current_row}", "$" . number_format((float)$practice_data['stipend_amount_paid_for_month'], 2))
                    ->setCellValue("AA{$current_row}", "$" . number_format((float)$practice_data['actual_stipend_rate'], 2));
                $workbook->setActiveSheetIndex($sheetIndex)
                    //->getStyle("B{$current_row}")->applyFromArray(($totals->fmv >= $hourly_rate && $totals->worked_hours != 0) ? $this->green_style : $this->red_style);
                    ->getStyle("U{$current_row}")->applyFromArray(($practice_data['practice_pmt_status'] === 'Y') ? $this->green_style : $this->red_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("U{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            if ($practice_data['isuncompensated']) {
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->setCellValue("AE{$current_row}", number_format((float)$practice_data['colAE'], 2))
                    ->setCellValue("AF{$current_row}", number_format((float)$practice_data['colAF'], 2))
                    ->setCellValue("AG{$current_row}", "$" . number_format((float)$practice_data['colAG'], 2))
                    ->setCellValue("AH{$current_row}", "$" . number_format((float)$practice_data['colAH'], 2));
            }
            if ($practice_data['isTimeStudy']) {
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->setCellValue("AI{$current_row}", $practice_data['practice_pmt_status'])
                    ->setCellValue("AJ{$current_row}", number_format((float)$practice_data['expected_hours'], 2))
                    ->setCellValue("AK{$current_row}", "$" . number_format((float)$practice_data['expected_payment'], 2))
                    ->setCellValue("AL{$current_row}", number_format((float)$practice_data['expected_hours_ytm'], 2))
                    ->setCellValue("AM{$current_row}", number_format((float)$practice_data['stipend_worked_hrs'], 2))
                    ->setCellValue("AN{$current_row}", "$" . number_format((float)$practice_data['stipend_amount_paid_for_month'], 2))
                    ->setCellValue("AO{$current_row}", "$" . number_format((float)$practice_data['actual_stipend_rate'], 2));
                $workbook->setActiveSheetIndex($sheetIndex)
                    //->getStyle("B{$current_row}")->applyFromArray(($totals->fmv >= $hourly_rate && $totals->worked_hours != 0) ? $this->green_style : $this->red_style);
                    ->getStyle("AI{$current_row}")->applyFromArray(($practice_data['practice_pmt_status'] === 'Y') ? $this->green_style : $this->red_style);
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->getStyle("{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            if ($practice_data['isPerUnit']) {
                $workbook->setActiveSheetIndex($sheetIndex)
                    ->setCellValue("AP{$current_row}", round($practice_data['min_hours'], 0))
                    ->setCellValue("AQ{$current_row}", round($practice_data['max_hours'], 0))
                    ->setCellValue("AR{$current_row}", round($practice_data['annual_cap'], 0))
                    ->setCellValue("AS{$current_row}", "$" . number_format((float)$practice_data['potential_pay'], 2))
                    ->setCellValue("AT{$current_row}", round($practice_data['min_hours_ytm'], 0))
                    ->setCellValue("AU{$current_row}", round($practice_data['worked_hrs']+$practice_data['prior_worked_hours'], 0))
                    ->setCellValue("AV{$current_row}", "$" . number_format((float)$practice_data['amount_paid_for_month']+$practice_data['prior_amount_paid'], 2))
                    ->setCellValue("AW{$current_row}", "$" . number_format((float)$practice_data['amount_to_be_paid_for_month'], 2));
            }
        }
        if($sheetIndex == 0) {
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("B{$current_row}:AU{$current_row}")->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("N{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("T{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("V{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        if($sheetIndex == 1) {
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("B{$current_row}:AX{$current_row}")->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("M{$current_row}:O{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("Q{$current_row}:R{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("U{$current_row}:V{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("X{$current_row}:Y{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        if($sheetIndex == 2) {
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("B{$current_row}:AW{$current_row}")->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("M{$current_row}:O{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("Q{$current_row}:R{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("U{$current_row}:V{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("X{$current_row}:Y{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $workbook->setActiveSheetIndex($sheetIndex)
            ->getStyle("B{$current_row}:G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}:G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheetIndex)
            ->getStyle("AD{$current_row}:AR{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)
            ->getStyle("AT{$current_row}:AU{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return $current_row;
    }

    protected function writeDataCYTD($workbook, $sheetIndex, $index, $cytd_data )
    {
        $contracts_count = count($cytd_data);
        $current_row = $index;

        foreach ($cytd_data as $contract_name_display) {
            $current_row = $this->writeContractHeader($workbook, $sheetIndex, $current_row, $contract_name_display);
            $current_row = $this->writePeriodHeader($workbook, $sheetIndex, $current_row, $contract_name_display);
            $current_row = $this->writecontractTotal($workbook, $sheetIndex, $current_row, $contract_name_display);
            foreach ($contract_name_display['practice_data'] as $practice_data ) {
                $current_row = $this->writePeriodPracticeTotal($workbook, $sheetIndex, $current_row, $practice_data['practice_info']);
                foreach($practice_data['physician_data'] as $physician_data) {
                    if ($physician_data['totals']['isPerDiem']) {
                        $current_row++;
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("B{$current_row}:C{$current_row}")
                            ->setCellValue("D{$current_row}", $physician_data['totals']['perDiem_rate_type'] == 1 ? "Weekday" : "On Call")
                            ->setCellValue("E{$current_row}", $physician_data['totals']['perDiem_rate_type'] == 1 ? "Weekend" : "Called Back")
                            ->setCellValue("F{$current_row}", $physician_data['totals']['perDiem_rate_type'] == 1 ? "Holiday" : "Called In")
                            ->setCellValue("G{$current_row}", " Total")
                            ->setCellValue("H{$current_row}", $physician_data['totals']['perDiem_rate_type'] == 1 ? "Weekday" : "On Call")
                            ->setCellValue("I{$current_row}", $physician_data['totals']['perDiem_rate_type'] == 1 ? "Weekend" : "Called Back")
                            ->setCellValue("J{$current_row}", $physician_data['totals']['perDiem_rate_type'] == 1 ? "Holiday" : "Called In")
                            ->setCellValue("K{$current_row}", " Total")
                            ->setCellValue("L{$current_row}", " Amount paid");

                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("B{$current_row}:L{$current_row}")->getFont()->setBold(true);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("B{$current_row}:AH{$current_row}")->applyFromArray($this->total_style);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("V{$current_row}:AH{$current_row}")->applyFromArray($this->cell_left_border_style);

                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                    if ($physician_data['totals']['isuncompensated']) {
                        $current_row++;
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("B{$current_row}:C{$current_row}")
                            ->setCellValue("AE{$current_row}", "On-Call/Uncompensated")
                            ->setCellValue("AF{$current_row}", " Total")
                            ->setCellValue("AG{$current_row}", "On-Call/Uncompensated")
                            ->setCellValue("AH{$current_row}", " Total");
                            // ->setCellValue("L{$current_row}", " Amount paid");

                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("B{$current_row}:AH{$current_row}")->getFont()->setBold(true);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("B{$current_row}:AH{$current_row}")->applyFromArray($this->total_style);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("V{$current_row}:AH{$current_row}")->applyFromArray($this->cell_left_border_style);

                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                    $current_row++;

                    $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("B{$current_row}:C{$current_row}")
                        ->setCellValue("B{$current_row}", $physician_data['totals']['physician_name']);
                    if ($physician_data['totals']['isPerDiem']) {
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->setCellValue("D{$current_row}", number_format((float)$physician_data['totals']['colD'], 2))
                            ->setCellValue("E{$current_row}", number_format((float)$physician_data['totals']['colE'], 2))
                            ->setCellValue("F{$current_row}", number_format((float)$physician_data['totals']['colF'], 2))
                            ->setCellValue("G{$current_row}", number_format((float)$physician_data['totals']['colG'], 2))
                            ->setCellValue("H{$current_row}", "$" . number_format((float)$physician_data['totals']['colH'], 2))
                            ->setCellValue("I{$current_row}", "$" . number_format((float)$physician_data['totals']['colI'], 2))
                            ->setCellValue("J{$current_row}", "$" . number_format((float)$physician_data['totals']['colJ'], 2))
                            ->setCellValue("K{$current_row}", "$" . number_format((float)$physician_data['totals']['colK'], 2))
                            ->setCellValue("L{$current_row}", "$" . number_format((float)$physician_data['totals']['colL'], 2));
                    }
                    if ($physician_data['totals']['isHourly']) {
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->setCellValue("M{$current_row}", number_format((float)$physician_data['totals']['min_hours'], 2))
                            ->setCellValue("N{$current_row}", number_format((float)$physician_data['totals']['max_hours'], 2))
                            ->setCellValue("O{$current_row}", number_format((float)$physician_data['totals']['annual_cap'], 2))
                            ->setCellValue("P{$current_row}", "$" . number_format((float)$physician_data['totals']['potential_pay'], 2))
                            ->setCellValue("Q{$current_row}", number_format((float)$physician_data['totals']['min_hours_ytm'], 2))
                            ->setCellValue("R{$current_row}", number_format((float)$physician_data['totals']['worked_hrs']+$physician_data['totals']['prior_worked_hours'], 2))
                            ->setCellValue("S{$current_row}", "$" . number_format((float)$physician_data['totals']['amount_paid_for_month']+$physician_data['totals']['prior_amount_paid'], 2))
                            ->setCellValue("T{$current_row}", "$" . number_format((float)$physician_data['totals']['amount_to_be_paid_for_month'], 2));
                    }
                    if ($physician_data['totals']['isStipend']) {
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->setCellValue("U{$current_row}", " ")
                            ->setCellValue("V{$current_row}", number_format((float)$physician_data['totals']['expected_hours'], 2))
                            ->setCellValue("W{$current_row}", "$" . number_format((float)$physician_data['totals']['expected_payment'], 2))
                            ->setCellValue("X{$current_row}", number_format((float)$physician_data['totals']['expected_hours_ytm'], 2))
                            ->setCellValue("Y{$current_row}", number_format((float)$physician_data['totals']['stipend_worked_hrs'], 2))
                            ->setCellValue("Z{$current_row}", "$" . number_format((float)$physician_data['totals']['stipend_amount_paid_for_month'], 2))
                            ->setCellValue("AA{$current_row}", "$" . number_format((float)$physician_data['totals']['actual_stipend_rate'], 2));
                    }
                    if ($physician_data['totals']['isPsa']) {
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->setCellValue("AB{$current_row}", number_format((float)$physician_data['totals']['stipend_worked_hrs'], 2))
                            ->setCellValue("AC{$current_row}", "$" . number_format((float)$physician_data['totals']['stipend_amount_paid_for_month'], 2))
                            ->setCellValue("AD{$current_row}", "$" . number_format((float)$physician_data['totals']['actual_stipend_rate'], 2));
                    }
                    if ($physician_data['totals']['isuncompensated']) {
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->setCellValue("AE{$current_row}", number_format((float)$physician_data['totals']['colAE'], 2))
                            ->setCellValue("AF{$current_row}", number_format((float)$physician_data['totals']['colAF'], 2))
                            ->setCellValue("AG{$current_row}", "$" . number_format((float)$physician_data['totals']['colAG'], 2))
                            ->setCellValue("AH{$current_row}", "$" . number_format((float)$physician_data['totals']['colAH'], 2));
                    }
                    if ($physician_data['totals']['isTimeStudy']) {
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->setCellValue("AI{$current_row}", " ")
                            ->setCellValue("AJ{$current_row}", number_format((float)$physician_data['totals']['expected_hours'], 2))
                            ->setCellValue("AK{$current_row}", "$" . number_format((float)$physician_data['totals']['expected_payment'], 2))
                            ->setCellValue("AL{$current_row}", number_format((float)$physician_data['totals']['expected_hours_ytm'], 2))
                            ->setCellValue("AM{$current_row}", number_format((float)$physician_data['totals']['stipend_worked_hrs'], 2))
                            ->setCellValue("AN{$current_row}", "$" . number_format((float)$physician_data['totals']['stipend_amount_paid_for_month'], 2))
                            ->setCellValue("AO{$current_row}", "$" . number_format((float)$physician_data['totals']['actual_stipend_rate'], 2));
                    }
                    if ($physician_data['totals']['isPerUnit']) {
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->setCellValue("AP{$current_row}", round($physician_data['totals']['min_hours'], 0))
                            ->setCellValue("AQ{$current_row}", round($physician_data['totals']['max_hours'], 0))
                            ->setCellValue("AR{$current_row}", round($physician_data['totals']['annual_cap'], 0))
                            ->setCellValue("AS{$current_row}", "$" . number_format((float)$physician_data['totals']['potential_pay'], 2))
                            ->setCellValue("AT{$current_row}", round($physician_data['totals']['min_hours_ytm'], 0))
                            ->setCellValue("AU{$current_row}", round($physician_data['totals']['worked_hrs']+$physician_data['totals']['prior_worked_hours'], 0))
                            ->setCellValue("AV{$current_row}", "$" . number_format((float)$physician_data['totals']['amount_paid_for_month']+$physician_data['totals']['prior_amount_paid'], 2))
                            ->setCellValue("AW{$current_row}", "$" . number_format((float)$physician_data['totals']['amount_to_be_paid_for_month'], 2));
                    }

                        /*$workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("B{$current_row}:K{$current_row}")->getFont()->setBold(true);*/
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("B{$current_row}:AW{$current_row}")->applyFromArray($this->total_style);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("W{$current_row}:Y{$current_row}")->applyFromArray($this->cell_left_border_style);

                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}:G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
//                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("M{$current_row}:O{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("Q{$current_row}:R{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("U{$current_row}:V{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("X{$current_row}:Y{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("AE{$current_row}:AU{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        // $workbook->setActiveSheetIndex($sheetIndex)->getStyle("AI{$current_row}:AU{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        // $workbook->setActiveSheetIndex($sheetIndex)->getStyle("AS{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        //$workbook->setActiveSheetIndex($sheetIndex)->getStyle("W{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
//                        $current_row++;
                }
            }
        }
        return $current_row;
    }

    private function writeCYTDPracticeMiddle($workbook, $sheetIndex, $practice_first_row, $practice_last_row, $practice_name, $is_last_row)
    {
        $practice_name_row = $practice_last_row - ($practice_last_row - $practice_first_row) / 2;
        $practice_name_row = floor($practice_name_row);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("C{$practice_first_row}:C{$practice_last_row}")->applyFromArray($this->cytd_breakdown_practice_style);
        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("C{$practice_name_row}", $practice_name);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("C{$practice_name_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        if ($is_last_row) {
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("B{$practice_last_row}:C{$practice_last_row}")->applyFromArray($this->bottom_row_style);
        }
    }

    protected function writeDataCYTPM($workbook, $sheetIndex, $index, $cytpm_data )
    {
        $contracts_count = count($cytpm_data);
        $current_row = $index;

        foreach ($cytpm_data as $contract_name_display) {
            $current_row = $this->writeContractHeader($workbook, $sheetIndex, $current_row, $contract_name_display);
            $current_row = $this->writePeriodHeader($workbook, $sheetIndex, $current_row, $contract_name_display);
            $current_row = $this->writecontractTotal($workbook, $sheetIndex, $current_row, $contract_name_display);
            foreach ($contract_name_display['practice_data'] as $practice_data ) {
                $current_row = $this->writePeriodPracticeTotal($workbook, $sheetIndex, $current_row, $practice_data['practice_info']);
                foreach($practice_data['physician_data'] as $physician_data) {
                    if ($physician_data['totals']['isPerDiem']) {
                        $current_row++;
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("B{$current_row}:C{$current_row}")
                            ->setCellValue("D{$current_row}", $physician_data['totals']['perDiem_rate_type'] == 1 ? "Weekday" : "On Call")
                            ->setCellValue("E{$current_row}", $physician_data['totals']['perDiem_rate_type'] == 1 ? "Weekend" : "Called Back")
                            ->setCellValue("F{$current_row}", $physician_data['totals']['perDiem_rate_type'] == 1 ? "Holiday" : "Called In")
                            ->setCellValue("G{$current_row}", " Total")
                            ->setCellValue("H{$current_row}", $physician_data['totals']['perDiem_rate_type'] == 1 ? "Weekday" : "On Call")
                            ->setCellValue("I{$current_row}", $physician_data['totals']['perDiem_rate_type'] == 1 ? "Weekend" : "Called Back")
                            ->setCellValue("J{$current_row}", $physician_data['totals']['perDiem_rate_type'] == 1 ? "Holiday" : "Called In")
                            ->setCellValue("K{$current_row}", " Total")
                            ->setCellValue("L{$current_row}", " Amount paid");

                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("B{$current_row}:L{$current_row}")->getFont()->setBold(true);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("B{$current_row}:AI{$current_row}")->applyFromArray($this->total_style);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("V{$current_row}:AI{$current_row}")->applyFromArray($this->cell_left_border_style);

                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    } 
                    if ($physician_data['totals']['isuncompensated']) {
                        $current_row++;
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("B{$current_row}:C{$current_row}")
                            ->setCellValue("AE{$current_row}", "On-Call/Uncompensated")
                            ->setCellValue("AF{$current_row}", " Total")
                            ->setCellValue("AG{$current_row}", "On-Call/Uncompensated")
                            ->setCellValue("AH{$current_row}", " Total");
                            // ->setCellValue("L{$current_row}", " Amount paid");

                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("B{$current_row}:AH{$current_row}")->getFont()->setBold(true);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("B{$current_row}:AI{$current_row}")->applyFromArray($this->total_style);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("V{$current_row}:AI{$current_row}")->applyFromArray($this->cell_left_border_style);

                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}:AH{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                    $current_row++;
                    $current_row = $this->writeCYTPMPhysicianTotal($workbook, $sheetIndex, $current_row, $physician_data['totals']);
                    //$this->writeCYTPMPhysicianMiddle($workbook, $sheetIndex, $current_row, $current_row +(count($physician_data['months']) -1),  $physician_data['totals']);
                    if(($physician_data['totals']['isHourly'])&&($physician_data['totals']['prior_worked_hours'] > 0 ||$physician_data['totals']['prior_amount_paid'] > 0))
                    {
                      $this->writeCYTPMPhysicianMiddle($workbook, $sheetIndex, $current_row, $current_row +(count($physician_data['months'])),  $physician_data['totals']);
                    }else
                    {
                      $this->writeCYTPMPhysicianMiddle($workbook, $sheetIndex, $current_row, $current_row +(count($physician_data['months']) -1),  $physician_data['totals']);
                    }

                    /*$workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("B{$current_row}:C{$current_row}")
                        ->setCellValue("B{$current_row}", $physician_data['totals']['physician_name']);*/
                    $physician_data['months'] = array_reverse($physician_data['months']);
                    foreach ($physician_data['months'] as $month_data) {
                        if ($month_data['isPerDiem']) {
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->setCellValue("D{$current_row}", number_format((float)$month_data['colD'], 2))
                                ->setCellValue("E{$current_row}", number_format((float)$month_data['colE'], 2))
                                ->setCellValue("F{$current_row}", number_format((float)$month_data['colF'], 2))
                                ->setCellValue("G{$current_row}", number_format((float)$month_data['colG'], 2))
                                ->setCellValue("H{$current_row}", "$" . number_format((float)$month_data['colH'], 2))
                                ->setCellValue("I{$current_row}", "$" . number_format((float)$month_data['colI'], 2))
                                ->setCellValue("J{$current_row}", "$" . number_format((float)$month_data['colJ'], 2))
                                ->setCellValue("K{$current_row}", "$" . number_format((float)$month_data['colK'], 2))
                                ->setCellValue("L{$current_row}", "$" . number_format((float)$month_data['colL'], 2));
                        }
                        if ($month_data['isHourly']) {
                            $workbook->setActiveSheetIndex($sheetIndex)
                                /*->setCellValue("M{$current_row}", $month_data['min_hours'])
                                ->setCellValue("N{$current_row}", $month_data['max_hours'])
                                ->setCellValue("O{$current_row}", $month_data['annual_cap'])*/
                                ->setCellValue("P{$current_row}", "$" . number_format((float)$month_data['potential_pay'], 2))
                                ->setCellValue("Q{$current_row}", number_format((float)$month_data['min_hours'], 2))
                                ->setCellValue("R{$current_row}", number_format((float)$month_data['worked_hrs'], 2))
                                ->setCellValue("S{$current_row}", "$" . number_format((float)$month_data['amount_paid_for_month'], 2))
                                ->setCellValue("T{$current_row}", "$" . number_format((float)$month_data['amount_to_be_paid_for_month'], 2));
                        }
                        if ($month_data['isStipend']) {
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->setCellValue("U{$current_row}", " ")
                                /*->setCellValue("V{$current_row}", $month_data['expected_hours'])*/
                                ->setCellValue("W{$current_row}", "$" . number_format((float)$month_data['expected_payment'], 2))
                                ->setCellValue("X{$current_row}", number_format((float)$month_data['expected_hours'], 2))
                                ->setCellValue("Y{$current_row}", number_format((float)$month_data['stipend_worked_hrs'], 2))
                                ->setCellValue("Z{$current_row}", "$" . number_format((float)$month_data['stipend_amount_paid_for_month'], 2))
                                ->setCellValue("AA{$current_row}", "$" . number_format((float)$month_data['actual_stipend_rate'], 2));

                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("U{$current_row}")->applyFromArray($this->cytd_breakdown_practice_style);
                        }
                        if ($month_data['isPsa']) {
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->setCellValue("AB{$current_row}", number_format((float)$month_data['stipend_worked_hrs'], 2))
                                ->setCellValue("AC{$current_row}", "$" . number_format((float)$month_data['stipend_amount_paid_for_month'], 2))
                                ->setCellValue("AD{$current_row}", "$" . number_format((float)$month_data['actual_stipend_rate'], 2));
                        }
                        if ($month_data['isuncompensated']) {
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->setCellValue("AE{$current_row}", number_format((float)$month_data['colAE'], 2))
                                ->setCellValue("AF{$current_row}", number_format((float)$month_data['colAF'], 2))
                                ->setCellValue("AG{$current_row}", "$" . number_format((float)$month_data['colAG'], 2))
                                ->setCellValue("AH{$current_row}", "$" . number_format((float)$month_data['colAH'], 2));
                        }
                        if ($month_data['isTimeStudy']) {
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->setCellValue("AI{$current_row}", " ")
                                /*->setCellValue("V{$current_row}", $month_data['expected_hours'])*/
                                ->setCellValue("AK{$current_row}", "$" . number_format((float)$month_data['expected_payment'], 2))
                                ->setCellValue("AL{$current_row}", number_format((float)$month_data['expected_hours'], 2))
                                ->setCellValue("AM{$current_row}", number_format((float)$month_data['stipend_worked_hrs'], 2))
                                ->setCellValue("AN{$current_row}", "$" . number_format((float)$month_data['stipend_amount_paid_for_month'], 2))
                                ->setCellValue("AO{$current_row}", "$" . number_format((float)$month_data['actual_stipend_rate'], 2));

                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("AI{$current_row}")->applyFromArray($this->cytd_breakdown_practice_style);
                        }
                        if ($month_data['isPerUnit']) {
                            $workbook->setActiveSheetIndex($sheetIndex)
                                /*->setCellValue("M{$current_row}", $month_data['min_hours'])
                                ->setCellValue("N{$current_row}", $month_data['max_hours'])
                                ->setCellValue("O{$current_row}", $month_data['annual_cap'])*/
                                ->setCellValue("AS{$current_row}", "$" . number_format((float)$month_data['potential_pay'], 2))
                                ->setCellValue("AT{$current_row}", round($month_data['min_hours'], 0))
                                ->setCellValue("AU{$current_row}", round($month_data['worked_hrs'], 0))
                                ->setCellValue("AV{$current_row}", "$" . number_format((float)$month_data['amount_paid_for_month'], 2))
                                ->setCellValue("AW{$current_row}", "$" . number_format((float)$month_data['amount_to_be_paid_for_month'], 2));
                        }

                        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("AX{$current_row}", $month_data['month_string']);

                        /*$workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("B{$current_row}:K{$current_row}")->getFont()->setBold(true);*/
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("D{$current_row}:L{$current_row}")->applyFromArray($this->total_style);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("P{$current_row}:T{$current_row}")->applyFromArray($this->total_style);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("W{$current_row}:AE{$current_row}")->applyFromArray($this->total_style);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("D{$current_row}:AX{$current_row}")->applyFromArray($this->total_style);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("W{$current_row}:AA{$current_row}")->applyFromArray($this->cell_left_border_style);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("AB{$current_row}:AD{$current_row}")->applyFromArray($this->cell_left_border_style);

                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}:G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
//                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("M{$current_row}:O{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("Q{$current_row}:R{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("U{$current_row}:V{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("X{$current_row}:Y{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("AB{$current_row}:AB{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        //$workbook->setActiveSheetIndex($sheetIndex)->getStyle("W{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $current_row++;
                    }
                    //code for add row of hourly payment type prior_worked_hours & prior_amount_paid
                    if(($physician_data['totals']['isHourly'])&&($physician_data['totals']['prior_worked_hours'] > 0 ||$physician_data['totals']['prior_amount_paid'] > 0))
                    {
                      $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("P{$current_row}:Q{$current_row}")
                          ->setCellValue("P{$current_row}", "Prior Periods:");
                      $workbook->setActiveSheetIndex($sheetIndex)->getStyle("P{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                      $workbook->setActiveSheetIndex($sheetIndex)->getStyle("P{$current_row}")->getFont()->setBold(true);
                    	$workbook->setActiveSheetIndex($sheetIndex)->setCellValue("R{$current_row}", number_format((float)$physician_data['totals']['prior_worked_hours'], 2))
                                    ->setCellValue("S{$current_row}", "$" . number_format((float)$physician_data['totals']['prior_amount_paid'], 2));
                                    $workbook->setActiveSheetIndex($sheetIndex)
                                          ->getStyle("D{$current_row}:L{$current_row}")->applyFromArray($this->total_style);
                                      $workbook->setActiveSheetIndex($sheetIndex)
                                          ->getStyle("P{$current_row}:T{$current_row}")->applyFromArray($this->total_style);
                                      $workbook->setActiveSheetIndex($sheetIndex)
                                          ->getStyle("W{$current_row}:AE{$current_row}")->applyFromArray($this->total_style);
                                      $workbook->setActiveSheetIndex($sheetIndex)
                                          ->getStyle("W{$current_row}:AA{$current_row}")->applyFromArray($this->cell_left_border_style);
                                      $workbook->setActiveSheetIndex($sheetIndex)
                                          ->getStyle("AB{$current_row}:AD{$current_row}")->applyFromArray($this->cell_left_border_style);

                                      $workbook->setActiveSheetIndex($sheetIndex)
                                          ->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                      $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}:G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                      $workbook->setActiveSheetIndex($sheetIndex)->getStyle("M{$current_row}:O{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                      $workbook->setActiveSheetIndex($sheetIndex)->getStyle("Q{$current_row}:R{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                      $workbook->setActiveSheetIndex($sheetIndex)->getStyle("U{$current_row}:V{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                      $workbook->setActiveSheetIndex($sheetIndex)->getStyle("X{$current_row}:Y{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                      $workbook->setActiveSheetIndex($sheetIndex)->getStyle("AB{$current_row}:AB{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    	$current_row++;
                    }

                    $current_row--;
                }
            }
        }
        $current_row1 = $current_row + 1 ;
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("AB{$current_row1}")->applyFromArray($this->CYTPM_lastRow_style);
        return $current_row;
    }

    private function writeCYTPMPhysicianTotal($workbook, $sheetIndex, $current_row, $physician_totals){
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("B{$current_row}:C{$current_row}")
            ->setCellValue("B{$current_row}", $physician_totals['physician_name']);
        if ($physician_totals['isPerDiem']) {
            $workbook->setActiveSheetIndex($sheetIndex)
                ->setCellValue("D{$current_row}", number_format((float)$physician_totals['colD'], 2))
                ->setCellValue("E{$current_row}", number_format((float)$physician_totals['colE'], 2))
                ->setCellValue("F{$current_row}", number_format((float)$physician_totals['colF'], 2))
                ->setCellValue("G{$current_row}", number_format((float)$physician_totals['colG'], 2))
                ->setCellValue("H{$current_row}", "$" . number_format((float)$physician_totals['colH'], 2))
                ->setCellValue("I{$current_row}", "$" . number_format((float)$physician_totals['colI'], 2))
                ->setCellValue("J{$current_row}", "$" . number_format((float)$physician_totals['colJ'], 2))
                ->setCellValue("K{$current_row}", "$" . number_format((float)$physician_totals['colK'], 2))
                ->setCellValue("L{$current_row}", "$" . number_format((float)$physician_totals['colL'], 2));
        }
        if ($physician_totals['isHourly']) {
            $workbook->setActiveSheetIndex($sheetIndex)
                ->setCellValue("M{$current_row}", number_format((float)$physician_totals['min_hours'], 2))
                ->setCellValue("N{$current_row}", number_format((float)$physician_totals['max_hours'], 2))
                ->setCellValue("O{$current_row}", number_format((float)$physician_totals['annual_cap'], 2))
                ->setCellValue("P{$current_row}", "$" . number_format((float)$physician_totals['potential_pay'], 2))
                ->setCellValue("Q{$current_row}", number_format((float)$physician_totals['min_hours_ytm'], 2))
                ->setCellValue("R{$current_row}", number_format((float)$physician_totals['worked_hrs']+$physician_totals['prior_worked_hours'], 2))
                ->setCellValue("S{$current_row}", "$" . number_format((float)$physician_totals['amount_paid_for_month']+$physician_totals['prior_amount_paid'], 2))
                ->setCellValue("T{$current_row}", "$" . number_format((float)$physician_totals['amount_to_be_paid_for_month'], 2));
        }
        if ($physician_totals['isStipend']) {
            $workbook->setActiveSheetIndex($sheetIndex)
                ->setCellValue("U{$current_row}", " ")
                ->setCellValue("V{$current_row}", number_format((float)$physician_totals['expected_hours'], 2))
                ->setCellValue("W{$current_row}", "$" . number_format((float)$physician_totals['expected_payment'], 2))
                ->setCellValue("X{$current_row}", number_format((float)$physician_totals['expected_hours_ytm'], 2))
                ->setCellValue("Y{$current_row}", number_format((float)$physician_totals['stipend_worked_hrs'], 2))
                ->setCellValue("Z{$current_row}", "$" . number_format((float)$physician_totals['stipend_amount_paid_for_month'], 2))
                ->setCellValue("AA{$current_row}", "$" . number_format((float)$physician_totals['actual_stipend_rate'], 2));
        }
        if ($physician_totals['isPsa']) {
            $workbook->setActiveSheetIndex($sheetIndex)
                ->setCellValue("AB{$current_row}", number_format((float)$physician_totals['stipend_worked_hrs'], 2))
                ->setCellValue("AC{$current_row}", "$" . number_format((float)$physician_totals['stipend_amount_paid_for_month'], 2))
                ->setCellValue("AD{$current_row}", "$" . number_format((float)$physician_totals['actual_stipend_rate'], 2));
        }
        if ($physician_totals['isuncompensated']) {
            $workbook->setActiveSheetIndex($sheetIndex)
                ->setCellValue("AE{$current_row}", number_format((float)$physician_totals['colAE'], 2))
                ->setCellValue("AF{$current_row}", number_format((float)$physician_totals['colAF'], 2))
                ->setCellValue("AG{$current_row}", "$" . number_format((float)$physician_totals['colAG'], 2))
                ->setCellValue("AH{$current_row}", "$" . number_format((float)$physician_totals['colAH'], 2));
        }
        if ($physician_totals['isTimeStudy']) {
            $workbook->setActiveSheetIndex($sheetIndex)
                ->setCellValue("AI{$current_row}", " ")
                ->setCellValue("AJ{$current_row}", number_format((float)$physician_totals['expected_hours'], 2))
                ->setCellValue("AK{$current_row}", "$" . number_format((float)$physician_totals['expected_payment'], 2))
                ->setCellValue("AL{$current_row}", number_format((float)$physician_totals['expected_hours_ytm'], 2))
                ->setCellValue("AM{$current_row}", number_format((float)$physician_totals['stipend_worked_hrs'], 2))
                ->setCellValue("AN{$current_row}", "$" . number_format((float)$physician_totals['stipend_amount_paid_for_month'], 2))
                ->setCellValue("AO{$current_row}", "$" . number_format((float)$physician_totals['actual_stipend_rate'], 2));
        }
        if ($physician_totals['isPerUnit']) {
            $workbook->setActiveSheetIndex($sheetIndex)
                ->setCellValue("AP{$current_row}", round($physician_totals['min_hours'], 0))
                ->setCellValue("AQ{$current_row}", round($physician_totals['max_hours'], 0))
                ->setCellValue("AR{$current_row}", round($physician_totals['annual_cap'], 0))
                ->setCellValue("AS{$current_row}", "$" . number_format((float)$physician_totals['potential_pay'], 2))
                ->setCellValue("AT{$current_row}", round($physician_totals['min_hours_ytm'], 0))
                ->setCellValue("AU{$current_row}", round($physician_totals['worked_hrs']+$physician_totals['prior_worked_hours'], 0))
                ->setCellValue("AV{$current_row}", "$" . number_format((float)$physician_totals['amount_paid_for_month']+$physician_totals['prior_amount_paid'], 2))
                ->setCellValue("AW{$current_row}", "$" . number_format((float)$physician_totals['amount_to_be_paid_for_month'], 2));
        }


        /*$workbook->setActiveSheetIndex($sheetIndex)
            ->getStyle("B{$current_row}:K{$current_row}")->getFont()->setBold(true);*/
        $workbook->setActiveSheetIndex($sheetIndex)
            ->getStyle("B{$current_row}:AX{$current_row}")->applyFromArray($this->total_style);
        $workbook->setActiveSheetIndex($sheetIndex)
            ->getStyle("W{$current_row}:Y{$current_row}")->applyFromArray($this->cell_left_border_style);

        $workbook->setActiveSheetIndex($sheetIndex)
            ->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}:G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
//                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("M{$current_row}:O{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("Q{$current_row}:R{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("U{$current_row}:V{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("X{$current_row}:Y{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("AB{$current_row}:AD{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        //$workbook->setActiveSheetIndex($sheetIndex)->getStyle("W{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $current_row++;
        return $current_row;
    }
    private function writeCYTPMPhysicianMiddle($workbook, $sheetIndex, $physician_first_row, $physician_last_row, $physician_totals )
    {
        $physician_name_row = $physician_last_row - ($physician_last_row - $physician_first_row) / 2;
        $physician_name_row = floor($physician_name_row);

        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$physician_first_row}:C{$physician_last_row}")->applyFromArray($this->period_breakdown_practice_style);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("M{$physician_first_row}:M{$physician_last_row}")->applyFromArray($this->period_breakdown_practice_style);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("N{$physician_first_row}:N{$physician_last_row}")->applyFromArray($this->period_breakdown_practice_style);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("O{$physician_first_row}:O{$physician_last_row}")->applyFromArray($this->period_breakdown_practice_style);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("V{$physician_first_row}:V{$physician_last_row}")->applyFromArray($this->period_breakdown_practice_style);
        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("B{$physician_name_row}:C{$physician_name_row}")->setCellValue("B{$physician_name_row}", $physician_totals['physician_name']);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$physician_name_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$physician_name_row}")->getAlignment()->setWrapText(true);
        if($physician_totals['isPerUnit']){
            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("M{$physician_name_row}", round(0, 0));
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("M{$physician_name_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("M{$physician_name_row}")->getAlignment()->setWrapText(true);
            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("N{$physician_name_row}", round(0, 0));
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("N{$physician_name_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("N{$physician_name_row}")->getAlignment()->setWrapText(true);
            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("O{$physician_name_row}", round(0, 0));
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("O{$physician_name_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("O{$physician_name_row}")->getAlignment()->setWrapText(true);

            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("AP{$physician_name_row}", number_format((float)$physician_totals['min_hours'], 2));
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("AP{$physician_name_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("AP{$physician_name_row}")->getAlignment()->setWrapText(true);
            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("AQ{$physician_name_row}", number_format((float)$physician_totals['max_hours'], 2));
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("AQ{$physician_name_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("AQ{$physician_name_row}")->getAlignment()->setWrapText(true);
            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("AR{$physician_name_row}", number_format((float)$physician_totals['annual_cap'], 2));
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("AR{$physician_name_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("AR{$physician_name_row}")->getAlignment()->setWrapText(true);
        }else{
            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("M{$physician_name_row}", number_format((float)$physician_totals['min_hours'], 2));
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("M{$physician_name_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("M{$physician_name_row}")->getAlignment()->setWrapText(true);
            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("N{$physician_name_row}", number_format((float)$physician_totals['max_hours'], 2));
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("N{$physician_name_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("N{$physician_name_row}")->getAlignment()->setWrapText(true);
            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("O{$physician_name_row}", number_format((float)$physician_totals['annual_cap'], 2));
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("O{$physician_name_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("O{$physician_name_row}")->getAlignment()->setWrapText(true);
        }
        if($physician_totals['isTimeStudy']){
            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("V{$physician_name_row}", round(0, 0));
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("V{$physician_name_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("V{$physician_name_row}")->getAlignment()->setWrapText(true);

            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("AJ{$physician_name_row}", number_format((float)$physician_totals['expected_hours'], 2));
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("AJ{$physician_name_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("AJ{$physician_name_row}")->getAlignment()->setWrapText(true);
        }else{
            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("V{$physician_name_row}", number_format((float)$physician_totals['expected_hours'], 2));
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("V{$physician_name_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("V{$physician_name_row}")->getAlignment()->setWrapText(true);
        }
    }

    protected function writePeriodHeader($workbook, $sheet_index, $index, $contract_name_display)
    {
        $current_row = $index;
        $report_header = "Contract Period: " . $contract_name_display['contract_period'];
        if ($sheet_index == 0) {
            $current_row++;
            $workbook->setActiveSheetIndex($sheet_index)
                ->mergeCells("B{$current_row}:AU{$current_row}")
                ->setCellValue("B{$current_row}", $report_header);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:AU{$current_row}")->applyFromArray($this->period_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getRowDimension($current_row)->setRowHeight(-1);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:AU{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:AU{$current_row}")->getFont()->setBold(true);
            /*$workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}")->getFont()->setFontSize(14);*/
            //$workbook->setActiveSheetIndex($sheet_index)
            //    ->getRowDimension($current_row)->setRowHeight(-1);

        } else if ($sheet_index == 1) {
            $current_row++;
            $workbook->setActiveSheetIndex($sheet_index)
                ->mergeCells("B{$current_row}:AX{$current_row}")
                ->setCellValue("B{$current_row}", $report_header);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:AX{$current_row}")->applyFromArray($this->period_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getRowDimension($current_row)->setRowHeight(-1);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:AX{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:AX{$current_row}")->getFont()->setBold(true);
        } else {
            $current_row++;
            $workbook->setActiveSheetIndex($sheet_index)
                ->mergeCells("B{$current_row}:AW{$current_row}")
                ->setCellValue("B{$current_row}", $report_header);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:AW{$current_row}")->applyFromArray($this->period_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getRowDimension($current_row)->setRowHeight(-1);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:AW{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:AW{$current_row}")->getFont()->setBold(true);
        }
        return $current_row;
    }

    protected function writeContractHeader($workbook, $sheet_index, $index, $totals)
    {
        $current_row = $index;

        if ($sheet_index == 0) {
            $current_row++;
            $workbook->setActiveSheetIndex($sheet_index)
                ->mergeCells("B{$current_row}:AU{$current_row}")
                ->setCellValue("B{$current_row}", $totals['contract_name']);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:AU{$current_row}")->applyFromArray($this->contract_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getRowDimension($current_row)->setRowHeight(-1);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:AU{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:AU{$current_row}")->getFont()->setBold(true);
            /*$workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}")->getFont()->setFontSize(14);*/
            //$workbook->setActiveSheetIndex($sheet_index)
            //    ->getRowDimension($current_row)->setRowHeight(-1);
        } else if ($sheet_index == 1) {
            $current_row++;
            $workbook->setActiveSheetIndex($sheet_index)
                ->mergeCells("B{$current_row}:AX{$current_row}")
                ->setCellValue("B{$current_row}", $totals['contract_name']);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:AX{$current_row}")->applyFromArray($this->contract_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getRowDimension($current_row)->setRowHeight(-1);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:AX{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:AX{$current_row}")->getFont()->setBold(true);
        } else {
            $current_row++;
            $workbook->setActiveSheetIndex($sheet_index)
                ->mergeCells("B{$current_row}:AW{$current_row}")
                ->setCellValue("B{$current_row}", $totals['contract_name']);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:AW{$current_row}")->applyFromArray($this->contract_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getRowDimension($current_row)->setRowHeight(-1);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:AW{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:AW{$current_row}")->getFont()->setBold(true);
        }


        return $current_row;
    }

    protected function write_single_totalYTM($workbook, $sheet_index, $index, $totals, $is_final_row)
    {
        $current_row = $index;
        $current_row++;
        $hourly_rate = $totals->worked_hours && $totals->worked_hours != 0 ? $totals->amount_paid / $totals->worked_hours : 0;
        $workbook->setActiveSheetIndex($sheet_index)->mergeCells("C{$current_row}:D{$current_row}")
            ->setCellValue("B{$current_row}", $totals->fmv >= $hourly_rate && $totals->worked_hours != 0 ? 'Y' : 'N')
            ->setCellValue("C{$current_row}", "{$totals-> practice_name} Totals")
            ->setCellValue("E{$current_row}", $totals->expected_hours)
            ->setCellValue("F{$current_row}", $totals->expected_payment)
            ->setCellValue("G{$current_row}", $totals->expected_hours_ytd)
            ->setCellValue("H{$current_row}", $totals->worked_hours)
            ->setCellValue("I{$current_row}", $totals->amount_paid)
            ->setCellValue("J{$current_row}", $hourly_rate);
        //->setCellValue("K{$current_row}", '-');
        //->setCellValue("M{$current_row}", '-');

        $workbook->setActiveSheetIndex($sheet_index)
            ->getStyle("B{$current_row}:K{$current_row}")->getFont()->setBold(true);
        $workbook->setActiveSheetIndex($sheet_index)
            ->getStyle("B{$current_row}:K{$current_row}")->applyFromArray($this->contract_align);
        $workbook->setActiveSheetIndex($sheet_index)
            ->getStyle("H{$current_row}:K{$current_row}")->applyFromArray($this->contract_align);
        $workbook->setActiveSheetIndex($sheet_index)
            ->getStyle("L{$current_row}:K{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $workbook->setActiveSheetIndex($sheet_index)
            ->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $workbook->setActiveSheetIndex($sheet_index)
            ->getStyle("E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheet_index)
            ->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheet_index)
            ->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheet_index)
            ->getStyle("B{$current_row}")->applyFromArray(($totals->fmv >= $hourly_rate && $totals->worked_hours != 0) ? $this->green_style : $this->red_style);
        if ($is_final_row) {
            $this->resetTotalsYTM($totals);
        }
        return $current_row;
    }

    protected function write_single_total($workbook, $sheet_index, $index, $totals)
    {
        $current_row = $index;
        if ($sheet_index == 0) {
            $current_row++;
            $hourly_rate = $totals->worked_hours && $totals->worked_hours != 0 ? $totals->amount_paid / $totals->worked_hours : 0;
            $workbook->setActiveSheetIndex($sheet_index)->mergeCells("C{$current_row}:D{$current_row}")
                //->setCellValue("B{$current_row}", $totals->fmv >= $hourly_rate && $totals->worked_hours != 0 ? 'Y' : 'N')
                ->setCellValue("B{$current_row}", $totals->practice_pmt_status)
                ->setCellValue("C{$current_row}", "{$totals->practice_name} Totals")
                // ->setCellValue("F{$current_row}", '1.00')
                // ->setCellValue("G{$current_row}", $totals->max_hours)
                ->setCellValue("E{$current_row}", $totals->expected_hours)
                ->setCellValue("F{$current_row}", $totals->expected_payment)
                ->setCellValue("G{$current_row}", $totals->worked_hours_main)
                ->setCellValue("H{$current_row}", $totals->amount_paid)
                ->setCellValue("I{$current_row}", $hourly_rate)
                ->setCellValue("J{$current_row}", $totals->fmv);

            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:J{$current_row}")->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:J{$current_row}")->applyFromArray($this->total_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("G{$current_row}:J{$current_row}")->applyFromArray($this->cell_left_border_style);
            $workbook->setActiveSheetIndex($sheet_index)
                // ->getStyle("B{$current_row}")->applyFromArray(($totals->fmv >= $hourly_rate && $totals->worked_hours != 0) ? $this->green_style : $this->red_style);
                ->getStyle("B{$current_row}")->applyFromArray(($totals->practice_pmt_status === 'Y') ? $this->green_style : $this->red_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        } elseif ($sheet_index == 1) {
            $current_row++;
            $hourly_rate = $totals->worked_hours != 0 ? $totals->amount_paid / $totals->worked_hours : 0;
            $workbook->setActiveSheetIndex($sheet_index)->mergeCells("C{$current_row}:D{$current_row}")
                ->setCellValue("B{$current_row}", $totals->fmv >= $hourly_rate && $totals->worked_hours != 0 ? 'Y' : 'N')
                //->setCellValue("B{$current_row}", $totals->pmt_status)
                ->setCellValue("C{$current_row}", "{$totals->physician_name} Totals")
                ->setCellValue("E{$current_row}", $totals->expected_hours)//"wrong") //
                ->setCellValue("F{$current_row}", $totals->expected_payment)
                ->setCellValue("G{$current_row}", $totals->expected_hours_ytd)
                ->setCellValue("H{$current_row}", $totals->worked_hours)
                ->setCellValue("I{$current_row}", $totals->amount_paid)
                ->setCellValue("J{$current_row}", $hourly_rate);
            //->setCellValue("K{$current_row}", '-');
            //->setCellValue("M{$current_row}", '-');

            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:K{$current_row}")->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:K{$current_row}")->applyFromArray($this->total_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("H{$current_row}:J{$current_row}")->applyFromArray($this->cell_left_border_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}")->applyFromArray(($totals->fmv >= $hourly_rate && $totals->worked_hours != 0) ? $this->green_style : $this->red_style);
            //->getStyle("B{$current_row}")->applyFromArray(($totals->pmt_status === 'Y') ? $this->green_style : $this->red_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $this->resetTotals($totals);
        return $current_row;
    }


    protected function writeTotals($workbook, $sheet_index, $index, $totals, $practice_row)
    {
        $current_row = $index;

        // Calculate the actual hourly rate for the practice.
        //$totals->actual_rate = $totals->amount_main / $totals->worked_hours_main;

        if ($totals->count > 1) {
            $average_fmv = ($totals->rate * 1.1) / $totals->count;
            $has_payment = $totals->has_override || $average_fmv >= $totals->actual_rate;
            //$pmt_status = (($totals->amount_paid + $totals->expected_payment)/$contract->worked_hours) < $contract->rate;
            if ($sheet_index == 0) {
                $current_row++;
                $hourly_rate = $totals->worked_hours && $totals->worked_hours != 0 ? $totals->amount_paid / $totals->worked_hours : 0;
                $workbook->setActiveSheetIndex($sheet_index)->mergeCells("C{$current_row}:D{$current_row}")
                    //->setCellValue("B{$current_row}", $totals->fmv >= $hourly_rate && $totals->worked_hours != 0 ? 'Y' : 'N')
                    ->setCellValue("B{$current_row}", $totals->practice_pmt_status)
                    ->setCellValue("C{$current_row}", "{$totals->practice_name} Totals")
                    // ->setCellValue("F{$current_row}", '1.00')
                    // ->setCellValue("G{$current_row}", $totals->max_hours)
                    ->setCellValue("E{$current_row}", $totals->expected_hours)
                    ->setCellValue("F{$current_row}", $totals->expected_payment)
                    ->setCellValue("G{$current_row}", $totals->worked_hours_main)
                    ->setCellValue("H{$current_row}", $totals->amount_paid)
                    ->setCellValue("I{$current_row}", $hourly_rate)
                    ->setCellValue("J{$current_row}", $totals->fmv);


                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:J{$current_row}")->getFont()->setBold(true);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:J{$current_row}")->applyFromArray($this->total_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("G{$current_row}:J{$current_row}")->applyFromArray($this->cell_left_border_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    //->getStyle("B{$current_row}")->applyFromArray(($totals->fmv >= $hourly_rate && $totals->worked_hours != 0) ? $this->green_style : $this->red_style);
                    ->getStyle("B{$current_row}")->applyFromArray(($totals->practice_pmt_status === 'Y') ? $this->green_style : $this->red_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                //$workbook->setActiveSheetIndex($sheet_index)
                //    ->getStyle("I{$current_row}:K{$current_row}")->applyFromArray($this->shaded_style);
            } else if ($sheet_index == 1) {
                $current_row++;
                /*$workbook->setActiveSheetIndex($sheet_index)->mergeCells("C{$current_row}:E{$current_row}")
                    ->setCellValue("B{$current_row}", $totals->pmt_status)
                    ->setCellValue("C{$current_row}", "{$totals->practice_name} Totals")
                    ->setCellValue("F{$current_row}", $totals->worked_hours)
                    ->setCellValue("G{$current_row}", $totals->expected_hours_ytd)
                    ->setCellValue("H{$current_row}", /*$totals->expected_hours"-")
                    ->setCellValue("I{$current_row}", $totals->expected_payment)
                    ->setCellValue("J{$current_row}", $totals->amount_paid)
                    ->setCellValue("K{$current_row}", $totals->rate)
                    ->setCellValue("L{$current_row}", '-')
                    ->setCellValue("M{$current_row}", '-');*/
                $hourly_rate = $totals->worked_hours != 0 ? $totals->amount_paid / $totals->worked_hours : 0;
                $workbook->setActiveSheetIndex($sheet_index)->mergeCells("C{$current_row}:D{$current_row}")
                    ->setCellValue("B{$current_row}", $totals->fmv >= $hourly_rate && $totals->worked_hours != 0 ? 'Y' : 'N')
                    //->setCellValue("B{$current_row}", $totals->pmt_status)
                    ->setCellValue("C{$current_row}", "{$totals->physician_name} Totals")
                    ->setCellValue("E{$current_row}", $totals->expected_hours)// -- blue line
                    ->setCellValue("F{$current_row}", $totals->expected_payment)
                    ->setCellValue("G{$current_row}", $totals->expected_hours_ytd)
                    ->setCellValue("H{$current_row}", $totals->worked_hours)
                    ->setCellValue("I{$current_row}", $totals->amount_paid)
                    ->setCellValue("J{$current_row}", $hourly_rate);
                //->setCellValue("K{$current_row}", '-');
                //->setCellValue("M{$current_row}", '-');

                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:K{$current_row}")->getFont()->setBold(true);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:K{$current_row}")->applyFromArray($this->total_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("H{$current_row}:K{$current_row}")->applyFromArray($this->cell_left_border_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}")->applyFromArray(($totals->fmv >= $hourly_rate && $totals->worked_hours != 0) ? $this->green_style : $this->red_style);
                //->getStyle("B{$current_row}")->applyFromArray(($totals->pmt_status === 'Y') ? $this->green_style : $this->red_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                //$workbook->setActiveSheetIndex($sheet_index)
                //    ->getStyle("I{$current_row}:M{$current_row}")->applyFromArray($this->shaded_style);
            } else {
                $current_row++;
                $hourly_rate = $totals->worked_hours != 0 ? $totals->amount_paid / $totals->worked_hours : 0;
                $workbook->setActiveSheetIndex($sheet_index)->mergeCells("C{$current_row}:D{$current_row}")
                    ->setCellValue("B{$current_row}", $totals->fmv >= $hourly_rate && $totals->worked_hours != 0 ? 'Y' : 'N')
                    //->setCellValue("B{$current_row}", $totals->practice_pmt_status)
                    ->setCellValue("C{$current_row}", "{$totals->practice_name} Totals")
                    ->setCellValue("E{$current_row}", $totals->expected_hours)
                    ->setCellValue("F{$current_row}", $totals->expected_payment)
                    ->setCellValue("G{$current_row}", $totals->expected_hours_ytd)
                    ->setCellValue("H{$current_row}", $totals->worked_hours)
                    ->setCellValue("I{$current_row}", $totals->amount_paid)
                    ->setCellValue("J{$current_row}", $hourly_rate);
                //->setCellValue("K{$current_row}", '-');

                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:J{$current_row}")->getFont()->setBold(true);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:J{$current_row}")->applyFromArray($this->total_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("H{$current_row}:J{$current_row}")->applyFromArray($this->cell_left_border_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}")->applyFromArray(($totals->fmv >= $hourly_rate && $totals->worked_hours != 0) ? $this->green_style : $this->red_style);
                //->getStyle("B{$current_row}")->applyFromArray(($totals->practice_pmt_status === 'Y') ? $this->green_style : $this->red_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                //$workbook->setActiveSheetIndex($sheet_index)
                //    ->getStyle("I{$current_row}:M{$current_row}")->applyFromArray($this->shaded_style);
            }
        } /*else {
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("C{$current_row}:N{$current_row}")->getFont()->setBold(true);
        }*/
        if ($practice_row) {
            $this->resetTotals($totals);
        }
        return $current_row;
    }


    protected function writecontractTotal($workbook, $sheet_index, $index, $contract_name_display)
    {
        $current_row = $index;
        if ($sheet_index == 0) {
            $current_row++;
            $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}");
            if(!$contract_name_display['psa']){
                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("B{$current_row}", $contract_name_display['contract_name']. ' total');
            }
            if($contract_name_display['perDiem']) {
                $workbook->setActiveSheetIndex($sheet_index)
                    ->setCellValue("D{$current_row}", number_format((float)$contract_name_display['contract_totals']['colD'], 2))
                    ->setCellValue("E{$current_row}", number_format((float)$contract_name_display['contract_totals']['colE'], 2))
                    ->setCellValue("F{$current_row}", number_format((float)$contract_name_display['contract_totals']['colF'], 2))
                    ->setCellValue("G{$current_row}", number_format((float)$contract_name_display['contract_totals']['colG'], 2))
                    ->setCellValue("H{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['colH'], 2))
                    ->setCellValue("I{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['colI'], 2))
                    ->setCellValue("J{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['colJ'], 2))
                    ->setCellValue("K{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['colK'], 2));
            }
            if($contract_name_display['hourly']) {
                $workbook->setActiveSheetIndex($sheet_index)
                    ->setCellValue("L{$current_row}", number_format((float)$contract_name_display['contract_totals']['min_hours'], 2))
                    ->setCellValue("M{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['potential_pay'], 2))
                    ->setCellValue("N{$current_row}", number_format((float)$contract_name_display['contract_totals']['worked_hrs'], 2))
                    ->setCellValue("O{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['amount_paid_for_month'], 2))
                    ->setCellValue("P{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['amount_to_be_paid_for_month'], 2))
                    ->setCellValue("Q{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['actual_hourly_rate'], 2))
                    ->setCellValue("R{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['FMV_hourly'], 2));
            }
            if($contract_name_display['stipend']) {
                $workbook->setActiveSheetIndex($sheet_index)
                    ->setCellValue("S{$current_row}", " ")
                    ->setCellValue("T{$current_row}", number_format((float)$contract_name_display['contract_totals']['expected_hours'], 2))
                    ->setCellValue("U{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['expected_payment'], 2))
                    ->setCellValue("V{$current_row}", number_format((float)$contract_name_display['contract_totals']['stipend_worked_hrs'], 2))
                    ->setCellValue("W{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['stipend_amount_paid_for_month'], 2))
                    ->setCellValue("X{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['actual_stipend_rate'], 2))
                    ->setCellValue("Y{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['fmv_stipend'],2));
            }
            if($contract_name_display['uncompensated']) {
                $workbook->setActiveSheetIndex($sheet_index)
                    ->setCellValue("AD{$current_row}", number_format((float)$contract_name_display['contract_totals']['colAD'], 2))
                    ->setCellValue("AE{$current_row}", number_format((float)$contract_name_display['contract_totals']['colAE'], 2))
                    ->setCellValue("AF{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['colAF'], 2))
                    ->setCellValue("AG{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['colAG'], 2));
            }
            if($contract_name_display['timeStudy']) {
                $workbook->setActiveSheetIndex($sheet_index)
                    ->setCellValue("AH{$current_row}", " ")
                    ->setCellValue("AI{$current_row}", number_format((float)$contract_name_display['contract_totals']['expected_hours'], 2))
                    ->setCellValue("AJ{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['expected_payment'], 2))
                    ->setCellValue("AK{$current_row}", number_format((float)$contract_name_display['contract_totals']['stipend_worked_hrs'], 2))
                    ->setCellValue("AL{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['stipend_amount_paid_for_month'], 2))
                    ->setCellValue("AM{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['actual_stipend_rate'], 2))
                    ->setCellValue("AN{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['fmv_time_study'],2));
            }
            if($contract_name_display['perUnit']) {
                $workbook->setActiveSheetIndex($sheet_index)
                    ->setCellValue("AO{$current_row}", round($contract_name_display['contract_totals']['min_hours'], 0))
                    ->setCellValue("AP{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['potential_pay'], 2))
                    ->setCellValue("AQ{$current_row}", round($contract_name_display['contract_totals']['worked_hrs'], 0))
                    ->setCellValue("AR{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['amount_paid_for_month'], 2))
                    ->setCellValue("AS{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['amount_to_be_paid_for_month'], 2))
                    ->setCellValue("AT{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['actual_hourly_rate'], 2))
                    ->setCellValue("AU{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['fmv_per_unit'], 2));
            }

            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:J{$current_row}")->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:AU{$current_row}")->applyFromArray($this->total_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("G{$current_row}:AU{$current_row}")->applyFromArray($this->cell_left_border_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("N{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("T{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("V{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("AD{$current_row}:AU{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        } else if ($sheet_index == 1 || $sheet_index == 2) {
            $current_row++;
            if ($contract_name_display['perDiem']) {
                $workbook->setActiveSheetIndex($sheet_index)
                    ->setCellValue("D{$current_row}", number_format((float)$contract_name_display['contract_totals']['colD'], 2))
                    ->setCellValue("E{$current_row}", number_format((float)$contract_name_display['contract_totals']['colE'], 2))
                    ->setCellValue("F{$current_row}", number_format((float)$contract_name_display['contract_totals']['colF'], 2))
                    ->setCellValue("G{$current_row}", number_format((float)$contract_name_display['contract_totals']['colG'], 2))
                    ->setCellValue("H{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['colH'], 2))
                    ->setCellValue("I{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['colI'], 2))
                    ->setCellValue("J{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['colJ'], 2))
                    ->setCellValue("K{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['colK'], 2))
                    ->setCellValue("L{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['colL'], 2));
            }
            if ($contract_name_display['hourly']) {
                $workbook->setActiveSheetIndex($sheet_index)
                    ->setCellValue("M{$current_row}", number_format((float)$contract_name_display['contract_totals']['min_hours'], 2))
                    ->setCellValue("N{$current_row}", number_format((float)$contract_name_display['contract_totals']['max_hours'], 2))
                    ->setCellValue("O{$current_row}", number_format((float)$contract_name_display['contract_totals']['annual_cap'], 2))
                    ->setCellValue("P{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['potential_pay'], 2))
                    ->setCellValue("Q{$current_row}", number_format((float)$contract_name_display['contract_totals']['min_hours_ytm'], 2))
                    ->setCellValue("R{$current_row}", number_format((float)$contract_name_display['contract_totals']['worked_hrs']+$contract_name_display['contract_totals']['prior_worked_hours'], 2))
                    ->setCellValue("S{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['amount_paid_for_month']+$contract_name_display['contract_totals']['prior_amount_paid'], 2))
                    ->setCellValue("T{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['amount_to_be_paid_for_month'], 2));
            }
            if ($contract_name_display['stipend']) {
                $workbook->setActiveSheetIndex($sheet_index)
                    ->setCellValue("U{$current_row}", " ")
                    ->setCellValue("V{$current_row}", number_format((float)$contract_name_display['contract_totals']['expected_hours'], 2))
                    ->setCellValue("W{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['expected_payment'], 2))
                    ->setCellValue("X{$current_row}", number_format((float)$contract_name_display['contract_totals']['expected_hours_ytm'], 2))
                    ->setCellValue("Y{$current_row}", number_format((float)$contract_name_display['contract_totals']['stipend_worked_hrs'], 2))
                    ->setCellValue("Z{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['stipend_amount_paid_for_month'], 2))
                    ->setCellValue("AA{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['actual_stipend_rate'],2));
            }
            if($contract_name_display['uncompensated']) {
                $workbook->setActiveSheetIndex($sheet_index)
                    ->setCellValue("AE{$current_row}", number_format((float)$contract_name_display['contract_totals']['colAE'], 2))
                    ->setCellValue("AF{$current_row}", number_format((float)$contract_name_display['contract_totals']['colAF'], 2))
                    ->setCellValue("AG{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['colAG'], 2))
                    ->setCellValue("AH{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['colAH'], 2));
            }
            if ($contract_name_display['timeStudy']) {
                $workbook->setActiveSheetIndex($sheet_index)
                    ->setCellValue("AI{$current_row}", " ")
                    ->setCellValue("AJ{$current_row}", number_format((float)$contract_name_display['contract_totals']['expected_hours'], 2))
                    ->setCellValue("AK{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['expected_payment'], 2))
                    ->setCellValue("AL{$current_row}", number_format((float)$contract_name_display['contract_totals']['expected_hours_ytm'], 2))
                    ->setCellValue("AM{$current_row}", number_format((float)$contract_name_display['contract_totals']['stipend_worked_hrs'], 2))
                    ->setCellValue("AN{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['stipend_amount_paid_for_month'], 2))
                    ->setCellValue("AO{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['actual_stipend_rate'],2));
            }
            if ($contract_name_display['perUnit']) {
                $workbook->setActiveSheetIndex($sheet_index)
                    ->setCellValue("AP{$current_row}", round($contract_name_display['contract_totals']['min_hours'], 0))
                    ->setCellValue("AQ{$current_row}", round($contract_name_display['contract_totals']['max_hours'], 0))
                    ->setCellValue("AR{$current_row}", round($contract_name_display['contract_totals']['annual_cap'], 0))
                    ->setCellValue("AS{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['potential_pay'], 2))
                    ->setCellValue("AT{$current_row}", round($contract_name_display['contract_totals']['min_hours_ytm'], 0))
                    ->setCellValue("AU{$current_row}", round($contract_name_display['contract_totals']['worked_hrs']+$contract_name_display['contract_totals']['prior_worked_hours'], 0))
                    ->setCellValue("AV{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['amount_paid_for_month']+$contract_name_display['contract_totals']['prior_amount_paid'], 2))
                    ->setCellValue("AW{$current_row}", "$" . number_format((float)$contract_name_display['contract_totals']['amount_to_be_paid_for_month'], 2));
            }

            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:J{$current_row}")->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheet_index);
            if ($sheet_index == 1) {
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:AX{$current_row}")->applyFromArray($this->total_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("G{$current_row}:AX{$current_row}")->applyFromArray($this->cell_left_border_style);
            }
            if ($sheet_index == 2) {
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:AW{$current_row}")->applyFromArray($this->total_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("G{$current_row}:AW{$current_row}")->applyFromArray($this->cell_left_border_style);
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("AP{$current_row}:AR{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("AT{$current_row}:AU{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("M{$current_row}:O{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("Q{$current_row}:R{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("U{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("V{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("X{$current_row}:Y{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        }
        return $current_row;
    }

    protected function writeTotalsYTM($workbook, $sheet_index, $index, $totals, $is_final_row)
    {
        $current_row = $index;
        $hourly_rate = $totals->worked_hours && $totals->worked_hours != 0 ? $totals->amount_paid / $totals->worked_hours : 0;
        // Calculate the actual hourly rate for the practice.
        //$totals->actual_rate = $totals->amount_main / $totals->worked_hours_main;

        if ($totals->count > 1) {
            $average_fmv = ($totals->rate * 1.1) / $totals->count;
            $has_payment = $totals->has_override || $average_fmv >= $totals->actual_rate;

            if ($sheet_index == 0) {
                $current_row++;
                $workbook->setActiveSheetIndex($sheet_index)
                    ->setCellValue("B{$current_row}", $totals->fmv >= $hourly_rate && $totals->worked_hours != 0 ? 'Y' : 'N')
                    ->setCellValue("C{$current_row}", "{$totals->practice_name}")
                    ->setCellValue("D{$current_row}", "{$totals->physician_name}")
                    //->setCellValue("E{$current_row}", "{$totals->speciality_name}")

                    // ->setCellValue("F{$current_row}", '1.00')
                    // ->setCellValue("G{$current_row}", $totals->max_hours)
                    ->setCellValue("F{$current_row}", $totals->expected_hours)
                    ->setCellValue("G{$current_row}", $totals->expected_payment)
                    ->setCellValue("H{$current_row}", $totals->worked_hours_main)
                    ->setCellValue("I{$current_row}", $totals->amount_paid)
                    ->setCellValue("J{$current_row}", $hourly_rate)
                    ->setCellValue("K{$current_row}", $totals->rate);

                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}")->applyFromArray(($totals->fmv >= $hourly_rate && $totals->worked_hours != 0) ? $this->green_style : $this->red_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:K{$current_row}")->getFont()->setBold(true);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:K{$current_row}")->applyFromArray($this->cell_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("J{$current_row}:K{$current_row}")->applyFromArray($this->shaded_style);
            } else if ($sheet_index == 1) {
                $current_row++;
                $hourly_rate = $totals->worked_hours && $totals->worked_hours != 0 ? $totals->amount_paid / $totals->worked_hours : 0;
                $workbook->setActiveSheetIndex($sheet_index)->mergeCells("C{$current_row}:D{$current_row}")
                    ->setCellValue("B{$current_row}", $totals->fmv >= $hourly_rate && $totals->worked_hours != 0 ? 'Y' : 'N')
                    //->setCellValue("B{$current_row}", $totals->practice_pmt_status)
                    ->setCellValue("C{$current_row}", "{$totals-> practice_name} Totals")
                    ->setCellValue("E{$current_row}", $totals->expected_hours)// -- black line "wrong 3") //
                    ->setCellValue("F{$current_row}", $totals->expected_payment)
                    ->setCellValue("G{$current_row}", $totals->expected_hours_ytd)
                    ->setCellValue("H{$current_row}", $totals->worked_hours)
                    ->setCellValue("I{$current_row}", $totals->amount_paid)
                    ->setCellValue("J{$current_row}", $hourly_rate);
                //->setCellValue("K{$current_row}", '-');
                //->setCellValue("M{$current_row}", '-');

                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:K{$current_row}")->getFont()->setBold(true);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:K{$current_row}")->applyFromArray($this->contract_align);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("H{$current_row}:K{$current_row}")->applyFromArray($this->contract_align);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("L{$current_row}:K{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}")->applyFromArray(($totals->fmv >= $hourly_rate && $totals->worked_hours != 0) ? $this->green_style : $this->red_style);
                //->getStyle("B{$current_row}")->applyFromArray(($totals->practice_pmt_status === 'Y') ? $this->green_style : $this->red_style);
            } else {
                $current_row++;
                $hourly_rate = $totals->worked_hours && $totals->worked_hours != 0 ? $totals->amount_paid / $totals->worked_hours : 0;
                $workbook->setActiveSheetIndex($sheet_index)
                    //->setCellValue("B{$current_row}", $totals->pmt_status)
                    //->setCellValue("C{$current_row}", "{$totals->practice_name}")
                    ->setCellValue("D{$current_row}", "{$totals->physician_name}")
                    ->setCellValue("B{$current_row}", $totals->fmv >= $hourly_rate && $totals->worked_hours != 0 ? 'Y' : 'N')
                    //->setCellValue("C{$current_row}", "{$totals->practice_name}")
                    ->setCellValue("E{$current_row}", $totals->expected_hours)
                    ->setCellValue("F{$current_row}", $totals->expected_payment)
                    ->setCellValue("G{$current_row}", $totals->expected_hours_ytd)
                    ->setCellValue("H{$current_row}", $totals->worked_hours)
                    ->setCellValue("I{$current_row}", $totals->amount_paid)
                    ->setCellValue("J{$current_row}", $hourly_rate);
                //->setCellValue("K{$current_row}", $totals->contract_term);

                //$workbook->setActiveSheetIndex($sheet_index)
                //    ->getStyle("B{$current_row}:M{$current_row}")->getFont()->setBold(true);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}")->applyFromArray($this->cytd_ymt_cell_style);

                if ($is_final_row) {
                    //Log::info("is final row: ".$is_final_row);
                    $workbook->setActiveSheetIndex($sheet_index)
                        ->getStyle("B{$current_row}:J{$current_row}")->applyFromArray($this->cell_bottom_style);
                    $workbook->setActiveSheetIndex($sheet_index)
                        ->getStyle("B{$current_row}")->applyFromArray($this->cytd_ymt_bottom_cell_style);
                    $workbook->setActiveSheetIndex($sheet_index)
                        ->getStyle("H{$current_row}:J{$current_row}")->applyFromArray($this->shaded_style);
                    /*$workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$current_row}")->applyFromArray($this->blank_cell_style);*/
                } else {
                    //Log::info("else final row:");
                    $workbook->setActiveSheetIndex($sheet_index)
                        ->getStyle("D{$current_row}:J{$current_row}")->applyFromArray($this->period_cell_style);
                    $workbook->setActiveSheetIndex($sheet_index)
                        ->getStyle("H{$current_row}:J{$current_row}")->applyFromArray($this->shaded_style);
                    //$workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$current_row}")->applyFromArray($this->blank_cell_style);
                }

                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}")->applyFromArray(($totals->fmv >= $hourly_rate && $totals->worked_hours != 0) ? $this->green_style : $this->red_style);
                //->getStyle("B{$current_row}")->applyFromArray(($totals->pmt_status == 'Y') ? $this->green_style : $this->red_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("C{$current_row}")->getAlignment()->setWrapText(true);
            }
        } else {
            //$workbook->setActiveSheetIndex($sheet_index)->getStyle("C{$current_row}:N{$current_row}")->getFont()->setBold(true);
            if ($is_final_row) {
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:J{$current_row}")->applyFromArray($this->cell_bottom_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("H{$current_row}:J{$current_row}")->applyFromArray($this->cell_left_border_style);
            }
        }
        $this->resetTotalsYTM($totals);
        return $current_row;
    }

    protected function resetPhysicanAndPractice($totals)
    {
        $totals->physician_name = null;
        $totals->practice_name = null;
    }

    protected function resetTotals($totals)
    {
        $totals->expected_hours = 0.0;
        $totals->expected_hours_ytd = 0.0;
        $totals->worked_hours_main = 0.0;
        $totals->worked_hours = 0.0;
        $totals->days_on_call = 0.0;
        $totals->rate = 0.0;
        $totals->actual_rate = 0.0;
        $totals->paid = 0.0;
        $totals->amount_paid = 0.0;
        $totals->amount_main = 0.0;
        $totals->amount_monthly = 0.0;
        $totals->count = 0.0;
        $totals->has_override = false;
        $totals->expected_payment = 0;
        $totals->max_hours = 0;
        $totals->pmt_status = 'Y';
        $totals->practice_pmt_status = 'N';
    }

    protected function resetTotalsYTM($totals_ytm)
    {
        $totals_ytm->expected_hours = 0.0;
        $totals_ytm->expected_hours_ytd = 0.0;
        $totals_ytm->worked_hours_main = 0.0;
        $totals_ytm->worked_hours = 0.0;
        $totals_ytm->days_on_call = 0.0;
        $totals_ytm->rate = 0.0;
        $totals_ytm->actual_rate = 0.0;
        $totals_ytm->paid = 0.0;
        $totals_ytm->amount_paid = 0.0;
        $totals_ytm->amount_main = 0.0;
        $totals_ytm->amount_monthly = 0.0;
        $totals_ytm->count = 0.0;
        $totals_ytm->has_override = false;
        $totals_ytm->expected_payment = 0;
        $totals_ytm->max_hours = 0;
        $totals_ytm->pmt_status = 'Y';
        $totals_ytm->practice_pmt_status = 'N';
    }

    protected function parseArguments()
    {
        $result = new StdClass;
        $result->hospital_id = $this->argument('hospital');
        $result->contract_type = $this->argument('contract_type');
        $result->physician_ids = $this->argument('physicians');
        $result->physicians = explode(',', $result->physician_ids);
        $result->agreements = parent::parseArguments();
        $result->report_data =$this->argument('data');
        $result->start_date = null;
        $result->end_date = null;
        $result->finalized = $this->argument('finalized');

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
            ["data", InputArgument::REQUIRED, "The agreement months."],
            ["finalized", InputArgument::REQUIRED, "Report Finalized."]
        ];
    }

    protected function getOptions()
    {
        return [];
    }
}
