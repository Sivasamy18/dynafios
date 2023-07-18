<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Lang;
use Redirect;
use Symfony\Component\Console\Input\InputArgument;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Facades\DB;
use StdClass;
use DateTime;
use App\Agreement;
use App\Hospital;
use App\Practice;
use App\PracticeManagerReport;
use App\HospitalReport;
use App\Contract;
use App\ContractType;
use App\PhysicianPracticeHistory;
use App\Physician;
use App\PhysicianPractices;
use Illuminate\Support\Facades\File;
use function App\Start\is_practice_manager;
use function App\Start\hospital_report_path;
use function App\Start\practice_report_path;

class OnCallReportCommand extends ReportingCommand
{
    protected $name = "reports:oncallreportcommand";
    protected $description = "Generates a DYNAFIOS hospital on call report.";
    protected $contract_length = 0;
    protected $contract_count = 0;

    private $cell_style = [
        'borders' => [
            'outline' => ['borderStyle' => Border::BORDER_THIN],
            'inside' => ['borderStyle' => Border::BORDER_THIN],
            'left' => ['borderStyle' => Border::BORDER_MEDIUM]
        ]
    ];

    private $top_border_style = [
        'borders' => [
            'top' => ['borderStyle' => Border::BORDER_MEDIUM],
            'left' => ['borderStyle' => Border::BORDER_NONE],
            'right' => ['borderStyle' => Border::BORDER_NONE]
        ]
    ];

    private $period_cell_style = [
        'borders' => [
            'outline' => ['borderStyle' => Border::BORDER_THIN],
            'inside' => ['borderStyle' => Border::BORDER_THIN],
            'left' => ['borderStyle' => Border::BORDER_THIN]
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
        'font' => ['color' => ['rgb' => 'FFFFFF'], 'size' => 11],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        'borders' => [
            'allborders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']],
            //'inside' => ['borderStyle' => Border::BORDER_MEDIUM,'color' => ['rgb' => 'FF0000']]
        ]
    ];

    private $total_style = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'EEECE1']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_MEDIUM],
            'right' => ['borderStyle' => Border::BORDER_MEDIUM],
            'outline' => ['borderStyle' => Border::BORDER_THIN],
            'inside' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ];

    private $summary_total_style = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'EEECE1']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_MEDIUM],
            'right' => ['borderStyle' => Border::BORDER_MEDIUM],
            'bottom' => ['borderStyle' => Border::BORDER_MEDIUM],
            'inside' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ];

    private $practice_style = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'eeecea']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_MEDIUM],
            'right' => ['borderStyle' => Border::BORDER_MEDIUM],
            'outline' => ['borderStyle' => Border::BORDER_THIN],
            'inside' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ];

    private $practice_style_first = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'eeecea']],
        'font' => ['color' => ['rgb' => '000000'], 'size' => 11],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_MEDIUM],
            'right' => ['borderStyle' => Border::BORDER_MEDIUM],
            'outline' => ['borderStyle' => Border::BORDER_THIN],
            'inside' => ['borderStyle' => Border::BORDER_THIN]
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

    private $grid_style = [
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_MEDIUM],
            'right' => ['borderStyle' => Border::BORDER_MEDIUM],
            'outline' => ['borderStyle' => Border::BORDER_THIN],
            'inside' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ];

    private $grid_style_left = [
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_THIN],
            'right' => ['borderStyle' => Border::BORDER_MEDIUM],
            'outline' => ['borderStyle' => Border::BORDER_THIN],
            'inside' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ];

    private $grid_style_right = [
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_MEDIUM],
            'right' => ['borderStyle' => Border::BORDER_THIN],
            'outline' => ['borderStyle' => Border::BORDER_THIN],
            'inside' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ];

    private $total_physician_style = [
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_MEDIUM],
            'right' => ['borderStyle' => Border::BORDER_MEDIUM],
            'outline' => ['borderStyle' => Border::BORDER_THIN],
            'inside' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ];
    private $left = [
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_MEDIUM]
        ]
    ];
    private $grid_bottom_style = [
        'borders' => [
            'bottom' => ['borderStyle' => Border::BORDER_MEDIUM]
        ]
    ];
    private $total_physician = [
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E0FFFF']],
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

    private $Total_shaded_style = [
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'color' => ['rgb' => 'eeece1']
        ],
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_MEDIUM]
        ]
    ];

    private $Total_shaded_style_summary = [
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'color' => ['rgb' => 'eeece1']
        ],
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_THIN],
            'right' => ['borderStyle' => Border::BORDER_MEDIUM],
            'outline' => ['borderStyle' => Border::BORDER_THIN],
            'inside' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ];

    private $Total_shaded_style_cytpm = [
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'color' => ['rgb' => 'eeece1']
        ],
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_MEDIUM],
            'right' => ['borderStyle' => Border::BORDER_MEDIUM],
            'outline' => ['borderStyle' => Border::BORDER_THIN],
            'inside' => ['borderStyle' => Border::BORDER_THIN]
        ]
    ];

    private $Total__blue_shaded_style = [
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'color' => ['rgb' => 'eeecea']
        ],
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

    private $period_bottom_row_style = [
        'borders' => [
            'top' => ['borderStyle' => Border::BORDER_THIN],
            'bottom' => ['borderStyle' => Border::BORDER_NONE],
            'left' => ['borderStyle' => Border::BORDER_NONE],
            'right' => ['borderStyle' => Border::BORDER_NONE]
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

        $hospital = Hospital::findOrFail($arguments->hospital_id);
        // $workbook = $this->loadTemplate('onCallReport.xlsx');

        $reader = IOFactory::createReader("Xlsx");//Load template using phpSpreadsheet
        $workbook = $reader->load(storage_path() . "/reports/templates/onCallReport.xlsx");

        $report_header = '';
        $report_header .= strtoupper($hospital->name) . "\n";
        $report_header .= "Period Report\n";
        $report_header .= "Period: " . $arguments->start_date->format("m/d/Y") . " - " . $arguments->end_date->format("m/d/Y") . "\n";
        $report_header .= "Run Date: " . with(new DateTime('now'))->format("m/d/Y");

        $report_header_ytm = '';
        $report_header_ytm .= strtoupper($hospital->name) . "\n";
        $report_header_ytm .= "Contract Year To Prior Month Report\n";
        $report_header_ytm .= "Period: " . $arguments->start_date->format("m/d/Y") . " - " . $arguments->end_date->format("m/d/Y") . "\n";
        $report_header_ytm .= "Run Date: " . with(new DateTime('now'))->format("m/d/Y");

        $report_header_ytd = '';
        $report_header_ytd .= strtoupper($hospital->name) . "\n";
        //$report_header_ytd .= "Summary Report\n";
        $report_header_ytd .= "Contract Year To Date Report\n";
        $report_header_ytd .= "Period: " . $arguments->start_date->format("m/d/Y") . " - " . $arguments->end_date->format("m/d/Y") . "\n";
        $report_header_ytd .= "Run Date: " . with(new DateTime('now'))->format("m/d/Y");

        $workbook->setActiveSheetIndex(0)->setCellValue('B2', $report_header);
        $workbook->setActiveSheetIndex(1)->setCellValue('B2', $report_header_ytm);
        $workbook->setActiveSheetIndex(2)->setCellValue('B2', $report_header_ytd);
        $period_index = 5;
        $sheet_index = 0;
        $cytm_index = 4;
        $summary_index = 4;
        $cytd_index = 4;
        $report_practice_id = 0;

        $this->contract_length = count($arguments->agreements);
        $this->contract_count = 0;
        $current_row = 5;
        foreach ($arguments->agreements as $agreementData) {
            $agreement = Agreement::findOrFail($agreementData->id);
            if ($agreement->archived == 0) {
                $practices = $this->queryPractices($arguments->hospital_id, $agreement->id, $arguments->physicians);
                if (is_practice_manager()) {
                    if (count($practices) > 0) {
                        $report_practice_id = $practices[0]->id;
                    }
                }
                $period_index = $this->write_period($workbook, $agreementData, $arguments, $period_index, $sheet_index, $practices);
                $cytm_index = $this->write_CYTPM($workbook, $agreementData, $arguments, $cytm_index, 1, $practices);
                $cytd_index = $this->write_CYTD($workbook, $agreementData, $arguments, $cytd_index, 2, $practices);
                //$summary_index = $this->write_summary($workbook, $agreementData, $arguments, $summary_index, 2, $practices);
            }
        }
        $workbook->setActiveSheetIndex($sheet_index)->removeRow($period_index);
        $period_index--;
        $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$period_index}:L{$period_index}")->applyFromArray($this->grid_bottom_style);
        if (is_practice_manager()) {
            $report_practice = Practice::findOrFail($report_practice_id);
            $report_path = practice_report_path($report_practice);
            $report_name = $report_practice->name;
        } else {
            $report_path = hospital_report_path($hospital);
            $report_name = $hospital->name;
        }

        // $report_filename = "report_" . date('mdYhis') . ".xlsx";
        $report_filename = "report_" . $report_name . "_" . date('mdY') . "_" . str_replace(":", "", $timestamp) . "_" . $timezone . ".xlsx";

        // if (!file_exists($report_path)) {
        // 	mkdir($report_path, 0777, true);
        // }
        if (!File::exists($report_path)) {
            File::makeDirectory($report_path, 0777, true, true);
        };
        $writer = IOFactory::createWriter($workbook, 'Xlsx');
        $writer->save("{$report_path}/{$report_filename}");

        if (is_practice_manager()) {
            $hospital_report = new PracticeManagerReport();
            $hospital_report->practice_id = $report_practice_id;
        } else {
            $hospital_report = new HospitalReport;
            $hospital_report->hospital_id = $hospital->id;
        }
        $hospital_report->filename = $report_filename;
        $hospital_report->type = 1;
        $hospital_report->save();

        $this->success('hospitals.generate_report_success', $hospital_report->id, $hospital_report->filename);
    }

    public function write_period($workbook, $agreementPasssed, $arguments, $current_row, $sheet_index, $practices)
    {
        $agreement = Agreement::findOrFail($agreementPasssed->id);
        if ($agreement->archived == 0) {
            $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:L{$current_row}")->setCellValue("B{$current_row}", $agreement->name)
                ->getStyle("B{$current_row}:L{$current_row}")
                ->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:L{$current_row}")->applyFromArray($this->contract_style);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:L{$current_row}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $current_row++;
            $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:L{$current_row}")->
            setCellValue("B{$current_row}", "Contract Period : " . format_date($agreement->start_date) . " - " . format_date($agreement->end_date))
                ->getStyle("B{$current_row}:L{$current_row}")
                ->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:L{$current_row}")->applyFromArray($this->contract_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:L{$current_row}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $current_row++;
            $agreement_row = $current_row;
            $workbook->setActiveSheetIndex($sheet_index)
                ->mergeCells("B{$current_row}:C{$current_row}")
                ->setCellValue("B{$current_row}", $agreement->name);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:C{$current_row}")
                ->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:C{$current_row}")
                ->applyFromArray($this->total_style);

            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("D{$current_row}:H{$current_row}")
                ->applyFromArray($this->total_style);

            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("I{$current_row}:L{$current_row}")
                ->applyFromArray($this->total_style);

            $current_row++;
            $agreement_duration_weekday_sum = 0;
            $agreement_duration_weekend_sum = 0;
            $agreement_duration_holiday_sum = 0;
            $agreement_rate_weekday_sum = 0;
            $agreement_rate_weekend_sum = 0;
            $agreement_rate_holiday_sum = 0;

            foreach ($practices as $practice) {

                $weekday_duration_sum = 0;
                $weekend_duration_sum = 0;
                $holiday_duration_sum = 0;
                $weekday_rate_sum = 0;
                $weekend_rate_sum = 0;
                $holiday_rate_sum = 0;
                $on_call_duration_sum = 0;
                $called_back_duration_sum = 0;
                $called_in_duration_sum = 0;
                $on_call_rate_sum = 0;
                $called_back_rate_sum = 0;
                $called_in_rate_sum = 0;
                $practice_duration_weekday_sum = 0;
                $practice_duration_weekend_sum = 0;
                $practice_duration_holiday_sum = 0;
                $practice_rate_weekday_sum = 0;
                $practice_rate_weekend_sum = 0;
                $practice_rate_holiday_sum = 0;
                $practice_duration_weekday_sum = 0;
                $practice_duration_weekend_sum = 0;
                $practice_duration_holiday_sum = 0;
                $practice_rate_weekday_sum = 0;
                $practice_rate_weekend_sum = 0;
                $practice_rate_holiday_sum = 0;
                $contract_rate_type = 0; /* for weekday, weekend, holiday */
                $agreement_phy_array = explode(',', $arguments->physician_ids);
                $physicians = $this->queryPhysicians($arguments->hospital_id, $agreement->id, $practice->id);

                if (count($physicians) > 0) {
                    $physicians_present = 0;
                    $physicians1 = $physicians;
                    foreach ($physicians1 as $physician) {
                        if (in_array($physician->id, $agreement_phy_array)) {
                            $physicians_present++;
                        }
                    }
                    if ($physicians_present > 0) {
                        $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("B{$current_row}", $practice->name);
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->getFont()->setBold(true);
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:L{$current_row}")->applyFromArray($this->contract_style);
                        $practice_row = $current_row;
                        $current_row++;
                    }
                }
                foreach ($physicians as $physician) {
                    if (in_array($physician->id, $agreement_phy_array)) {
                        $weekday_duration_sum = 0;
                        $weekend_duration_sum = 0;
                        $holiday_duration_sum = 0;
                        $weekday_rate_sum = 0;
                        $weekend_rate_sum = 0;
                        $holiday_rate_sum = 0;
                        $on_call_duration_sum = 0;
                        $called_back_duration_sum = 0;
                        $called_in_duration_sum = 0;
                        $on_call_rate_sum = 0;
                        $called_back_rate_sum = 0;
                        $called_in_rate_sum = 0;

                        $action_array = array();

                        $fetch_contract_id = Contract::where('agreement_id', '=', $agreement->id)
                            ->where("contract_type_id", "=", ContractType::ON_CALL)
                            ->where('physician_id', '=', $physician->id)
                            ->pluck('id');

                        $physician_logs = DB::table('physician_logs')->select('duration', 'action_id', 'date', 'log_hours')
                            ->where('physician_id', '=', $physician->id)//$physician->id
                            ->where('contract_id', '=', $fetch_contract_id)//$physician->id
                            ->where('practice_id', '=', $practice->id)//$practice->id
                            ->whereBetween("date", [mysql_date($arguments->start_date), mysql_date($arguments->end_date)])
                            ->orderBy("date", "DESC")
                            ->get();
                        foreach ($physician_logs as $physician_log) {
                            $action_names = DB::table('actions')->select('name')
                                ->where('id', '=', $physician_log->action_id)
                                ->first();
                            $action_array[] = [
                                "date" => date_format(date_create($physician_log->date), "m/d/Y"),
                                "duration" => $physician_log->duration,
                                "log_hours" => $physician_log->log_hours,
                                "action_name" => $action_names->name];
                        }
                        $physician_rates = DB::table('contracts')
                            ->select('contracts.*')
                            ->where('contracts.physician_id', '=', $physician->id)//$physician->id
                            ->where('contracts.agreement_id', '=', $agreement->id)
                            ->where('contracts.contract_type_id', '=', ContractType::ON_CALL)
                            ->first();
                        $weekday_rate = $physician_rates->weekday_rate;
                        $weekend_rate = $physician_rates->weekend_rate;
                        $holiday_rate = $physician_rates->holiday_rate;
                        $on_call_rate = $physician_rates->on_call_rate;
                        $called_back_rate = $physician_rates->called_back_rate;
                        $called_in_rate = $physician_rates->called_in_rate;
                        if ($weekday_rate == 0 && $weekend_rate == 0 && $holiday_rate == 0 && ($on_call_rate > 0 || $called_back_rate > 0 || $called_in_rate > 0)) {
                            $contract_rate_type = 1; /* for on-call, called back and called in*/
                        } else {
                            $contract_rate_type = 0; /* for weekday, weekend, holiday */
                        }

                        $physician_total_row = $current_row;
                        $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("B{$current_row}", $physician->last_name . ", " . $physician->first_name . " Totals");
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->getFont()->setBold(true);
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->applyFromArray($this->left);

                        $current_row++;
                        if (count($action_array) > 0) {
                            $show_physician_flag = 0;
                            foreach ($action_array as $action_data) {
                                if (strlen(strstr(strtoupper($action_data['action_name']), "WEEKDAY")) > 0 ||
                                    strlen(strstr(strtoupper($action_data['action_name']), "WEEKEND")) > 0 ||
                                    strlen(strstr(strtoupper($action_data['action_name']), "HOLIDAY")) > 0 ||
                                    $action_data['action_name'] == "On-Call" || $action_data['action_name'] == "Called-Back" ||
                                    $action_data['action_name'] == "Called-In") {
                                    $show_physician_flag = 1;
                                }
                            }

                            if ($show_physician_flag) {
                                //$workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("B{$current_row}", $physician->last_name . " , " . $physician->first_name);
                                //$workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->applyFromArray($this->left);
                                $physician_first_row = $current_row;
                                //	$current_row++;

                                $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("D{$current_row}", " Date");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("E{$current_row}", " " . $contract_rate_type == 0 ? "Weekday" : "On Call");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("F{$current_row}", " " . $contract_rate_type == 0 ? "Weekend" : "Called Back");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("G{$current_row}", " " . $contract_rate_type == 0 ? "Holiday" : "Called In");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("H{$current_row}", " Total");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("I{$current_row}", " " . $contract_rate_type == 0 ? "Weekday" : "On Call");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("J{$current_row}", " " . $contract_rate_type == 0 ? "Weekend" : "Called Back");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("K{$current_row}", " " . $contract_rate_type == 0 ? "Holiday" : "Called In");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("L{$current_row}", " Total");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:L{$current_row}")->applyFromArray($this->grid_style_left);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->applyFromArray($this->left);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:G{$current_row}")->applyFromArray($this->Total_shaded_style);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}")->applyFromArray($this->Total__blue_shaded_style);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$current_row}:K{$current_row}")->applyFromArray($this->Total_shaded_style);
                                //$workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->applyFromArray($this->Total_shaded_style);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->applyFromArray($this->Total__blue_shaded_style);
                                $current_row++;
                            }
                        }
                        foreach ($action_array as $action_data) {
                            if (strlen(strstr(strtoupper($action_data['action_name']), "WEEKDAY")) > 0) {
                                $weekday_duration_sum += $action_data['duration'];
                                $weekday_rate_sum += $action_data['log_hours'] * $weekday_rate;
                                $practice_duration_weekday_sum += $action_data['duration'];
                                $practice_rate_weekday_sum += $action_data['log_hours'] * $weekday_rate;
                                $weekday_value = $action_data['log_hours'] * $weekday_rate;

                                $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("D{$current_row}", " " . $action_data['date']);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("E{$current_row}", " " . number_format((float)$action_data['duration'], 1));
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("F{$current_row}", "-");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("G{$current_row}", "-");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("H{$current_row}", " " . number_format((float)$action_data['duration'], 1));
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("I{$current_row}", "$" . number_format($weekday_value, 2));
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("J{$current_row}", "-");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("K{$current_row}", "-");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("L{$current_row}", "$" . number_format($weekday_value, 2));
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:L{$current_row}")->applyFromArray($this->grid_style_left);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->applyFromArray($this->left);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:G{$current_row}")->applyFromArray($this->Total_shaded_style);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}")->applyFromArray($this->Total__blue_shaded_style);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$current_row}:K{$current_row}")->applyFromArray($this->Total_shaded_style);
                                //$workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->applyFromArray($this->Total_shaded_style);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->applyFromArray($this->Total__blue_shaded_style);
                                $current_row++;
                            }
                            if (strlen(strstr(strtoupper($action_data['action_name']), "WEEKEND")) > 0) {
                                $weekday_duration_sum += $action_data['duration'];
                                $weekday_rate_sum += $action_data['log_hours'] * $weekday_rate;
                                $practice_duration_weekday_sum += $action_data['duration'];
                                $practice_rate_weekday_sum += $action_data['log_hours'] * $weekday_rate;
                                $weekday_value = $action_data['log_hours'] * $weekday_rate;

                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("D{$current_row}", "" . $action_data['date']);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("E{$current_row}", "-");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("F{$current_row}", " " . number_format((float)$action_data['duration'], 1));
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("G{$current_row}", "-");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("H{$current_row}", " " . number_format((float)$action_data['duration'], 1));
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("I{$current_row}", "-");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("J{$current_row}", "$" . number_format($weekend_value, 2));
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("K{$current_row}", "-");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("L{$current_row}", "$" . number_format($weekend_value, 2));
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:L{$current_row}")->applyFromArray($this->grid_style_left);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->applyFromArray($this->left);
                                ////added
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:G{$current_row}")->applyFromArray($this->Total_shaded_style);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}")->applyFromArray($this->Total__blue_shaded_style);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$current_row}:K{$current_row}")->applyFromArray($this->Total_shaded_style);
                                //$workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->applyFromArray($this->Total_shaded_style);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->applyFromArray($this->Total__blue_shaded_style);
                                $current_row++;
                            }
                            if (strlen(strstr(strtoupper($action_data['action_name']), "HOLIDAY")) > 0) {
                                $holiday_duration_sum += $action_data['duration'];
                                $holiday_rate_sum += $action_data['log_hours'] * $holiday_rate;
                                $practice_duration_holiday_sum += $action_data['duration'];
                                $practice_rate_holiday_sum += $action_data['log_hours'] * $holiday_rate;
                                $holiday_value = $action_data['log_hours'] * $holiday_rate;

                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("D{$current_row}", " " . $action_data['date']);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("E{$current_row}", "-");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("F{$current_row}", "-");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("G{$current_row}", " " . number_format((float)$action_data['duration'], 1));
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("H{$current_row}", " " . number_format((float)$action_data['duration'], 1));
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("I{$current_row}", "-");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("J{$current_row}", "-");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("K{$current_row}", "$" . number_format($holiday_value, 2));
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("L{$current_row}", "$" . number_format($holiday_value, 2));
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:L{$current_row}")->applyFromArray($this->grid_style_left);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->applyFromArray($this->left);
                                ////added
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:G{$current_row}")->applyFromArray($this->Total_shaded_style);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}")->applyFromArray($this->Total__blue_shaded_style);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$current_row}:K{$current_row}")->applyFromArray($this->Total_shaded_style);
                                //$workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->applyFromArray($this->Total_shaded_style);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->applyFromArray($this->Total__blue_shaded_style);
                                $current_row++;
                            }
                            if ($action_data['action_name'] == "On-Call") {
                                $on_call_duration_sum += $action_data['duration'];
                                $on_call_rate_sum += $action_data['log_hours'] * $on_call_rate;
                                $practice_duration_weekday_sum += $action_data['duration'];
                                $practice_rate_weekday_sum += $action_data['log_hours'] * $on_call_rate;
                                $on_call_value = $action_data['log_hours'] * $on_call_rate;

                                $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("D{$current_row}", " " . $action_data['date']);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("E{$current_row}", " " . number_format((float)$action_data['duration'], 1));
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("F{$current_row}", "-");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("G{$current_row}", "-");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("H{$current_row}", " " . number_format((float)$action_data['duration'], 1));
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("I{$current_row}", "$" . number_format($on_call_value, 2));
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("J{$current_row}", "-");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("K{$current_row}", "-");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("L{$current_row}", "$" . number_format($on_call_value, 2));
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:L{$current_row}")->applyFromArray($this->grid_style_left);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->applyFromArray($this->left);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:G{$current_row}")->applyFromArray($this->Total_shaded_style);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}")->applyFromArray($this->Total__blue_shaded_style);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$current_row}:K{$current_row}")->applyFromArray($this->Total_shaded_style);
                                //$workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->applyFromArray($this->Total_shaded_style);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->applyFromArray($this->Total__blue_shaded_style);
                                $current_row++;
                            }
                            if ($action_data['action_name'] == "Called-Back") {
                                $called_back_duration_sum += $action_data['duration'];
                                $called_back_rate_sum += $action_data['log_hours'] * $called_back_rate;
                                $practice_duration_weekend_sum += $action_data['duration'];
                                $practice_rate_weekend_sum += $action_data['log_hours'] * $called_back_rate;
                                $called_back_value = $action_data['log_hours'] * $called_back_rate;

                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("D{$current_row}", "" . $action_data['date']);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("E{$current_row}", "-");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("F{$current_row}", " " . number_format((float)$action_data['duration'], 1));
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("G{$current_row}", "-");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("H{$current_row}", " " . number_format((float)$action_data['duration'], 1));
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("I{$current_row}", "-");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("J{$current_row}", "$" . number_format($called_back_value, 2));
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("K{$current_row}", "-");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("L{$current_row}", "$" . number_format($called_back_value, 2));
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:L{$current_row}")->applyFromArray($this->grid_style_left);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->applyFromArray($this->left);
                                ////added
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:G{$current_row}")->applyFromArray($this->Total_shaded_style);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}")->applyFromArray($this->Total__blue_shaded_style);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$current_row}:K{$current_row}")->applyFromArray($this->Total_shaded_style);
                                //$workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->applyFromArray($this->Total_shaded_style);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->applyFromArray($this->Total__blue_shaded_style);
                                $current_row++;
                            }
                            if ($action_data['action_name'] == "Called-In") {
                                $called_in_duration_sum += $action_data['duration'];
                                $called_in_rate_sum += $action_data['log_hours'] * $called_in_rate;
                                $practice_duration_holiday_sum += $action_data['duration'];
                                $practice_rate_holiday_sum += $action_data['log_hours'] * $called_in_rate;
                                $called_in_value = $action_data['log_hours'] * $called_in_rate;

                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("D{$current_row}", " " . $action_data['date']);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("E{$current_row}", "-");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("F{$current_row}", "-");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("G{$current_row}", " " . number_format((float)$action_data['duration'], 1));
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("H{$current_row}", " " . number_format((float)$action_data['duration'], 1));
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("I{$current_row}", "-");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("J{$current_row}", "-");
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("K{$current_row}", "$" . number_format($called_in_value, 2));
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("L{$current_row}", "$" . number_format($called_in_value, 2));
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:L{$current_row}")->applyFromArray($this->grid_style_left);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->applyFromArray($this->left);
                                ////added
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:G{$current_row}")->applyFromArray($this->Total_shaded_style);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}")->applyFromArray($this->Total__blue_shaded_style);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$current_row}:K{$current_row}")->applyFromArray($this->Total_shaded_style);
                                //$workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->applyFromArray($this->Total_shaded_style);
                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->applyFromArray($this->Total__blue_shaded_style);
                                $current_row++;
                            }
                        }
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$physician_total_row}:C{$physician_total_row}")->applyFromArray($this->grid_style);
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$physician_total_row}:H{$physician_total_row}")->applyFromArray($this->grid_style);
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$physician_total_row}:L{$physician_total_row}")->applyFromArray($this->grid_style);
                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("D{$physician_total_row}", " ");
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$physician_total_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("E{$physician_total_row}", " " . $contract_rate_type == 0 ? number_format((float)$weekday_duration_sum, 1) : number_format((float)$on_call_duration_sum, 1));
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$physician_total_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("F{$physician_total_row}", " " . $contract_rate_type == 0 ? number_format((float)$weekend_duration_sum, 1) : number_format((float)$called_back_duration_sum, 1));
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$physician_total_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("G{$physician_total_row}", " " . $contract_rate_type == 0 ? number_format((float)$holiday_duration_sum, 1) : number_format((float)$called_in_duration_sum, 1));
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$physician_total_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("H{$physician_total_row}", " " . $contract_rate_type == 0 ? number_format((float)$weekday_duration_sum + $weekend_duration_sum + $holiday_duration_sum, 1) : number_format((float)$on_call_duration_sum + $called_back_duration_sum + $called_in_duration_sum, 1));
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$physician_total_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("I{$physician_total_row}", "$" . number_format((float)$contract_rate_type == 0 ? $weekday_rate_sum : $on_call_rate_sum, 2));
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$physician_total_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("J{$physician_total_row}", "$" . number_format((float)$contract_rate_type == 0 ? $weekend_rate_sum : $called_back_rate_sum, 2));
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$physician_total_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("K{$physician_total_row}", "$" . number_format((float)$contract_rate_type == 0 ? $holiday_rate_sum : $called_in_rate_sum, 2));
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$physician_total_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("L{$physician_total_row}", "$" . number_format((float)$contract_rate_type == 0 ? $weekday_rate_sum + $weekend_rate_sum + $holiday_rate_sum : $on_call_rate_sum + $called_back_rate_sum + $called_in_rate_sum, 2));
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$physician_total_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$physician_total_row}:L{$physician_total_row}")->applyFromArray($this->Total__blue_shaded_style);

                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("D{$practice_row}", " ");
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("E{$practice_row}", " " . number_format((float)$practice_duration_weekday_sum, 1));
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("F{$practice_row}", " " . number_format((float)$practice_duration_weekend_sum, 1));
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("G{$practice_row}", " " . number_format((float)$practice_duration_holiday_sum, 1));
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("H{$practice_row}", " " . number_format((float)$practice_duration_weekday_sum + $practice_duration_weekend_sum + $practice_duration_holiday_sum, 1));
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("I{$practice_row}", "$" . number_format((float)$practice_rate_weekday_sum, 2));
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("J{$practice_row}", "$" . number_format((float)$practice_rate_weekend_sum, 2));
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("K{$practice_row}", "$" . number_format((float)$practice_rate_holiday_sum, 2));
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("L{$practice_row}", "$" . number_format((float)$practice_rate_weekday_sum + $practice_rate_weekend_sum + $practice_rate_holiday_sum, 2));
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:L{$current_row}")->applyFromArray($this->grid_style);
                        if (count($action_array) > 0) {
                            $show_physician_flag = 0;
                            foreach ($action_array as $action_data) {
                                if (strlen(strstr(strtoupper($action_data['action_name']), "WEEKDAY")) > 0 ||
                                    strlen(strstr(strtoupper($action_data['action_name']), "WEEKEND")) > 0 ||
                                    strlen(strstr(strtoupper($action_data['action_name']), "HOLIDAY")) > 0 ||
                                    $action_data['action_name'] == "On-Call" || $action_data['action_name'] == "Called-Back" ||
                                    $action_data['action_name'] == "Called-In") {
                                    $show_physician_flag = 1;
                                }
                            }

                            if ($show_physician_flag) {
                                $this->writeCYTPMPhysicianMiddle($workbook, $sheet_index, $physician_first_row, $current_row, $physician->first_name, $physician->last_name);
                            }
                        }

                    }
                }
                $agreement_duration_weekday_sum += $practice_duration_weekday_sum;
                $agreement_duration_weekend_sum += $practice_duration_weekend_sum;
                $agreement_duration_holiday_sum += $practice_duration_holiday_sum;
                $agreement_rate_weekday_sum += $practice_rate_weekday_sum;
                $agreement_rate_weekend_sum += $practice_rate_weekend_sum;
                $agreement_rate_holiday_sum += $practice_rate_holiday_sum;
            }
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("D{$agreement_row}", " ");
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("E{$agreement_row}", " " . number_format((float)$agreement_duration_weekday_sum, 1));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("F{$agreement_row}", " " . number_format((float)$agreement_duration_weekend_sum, 1));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("G{$agreement_row}", " " . number_format((float)$agreement_duration_holiday_sum, 1));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("H{$agreement_row}", " " . number_format((float)$agreement_duration_weekday_sum + $agreement_duration_weekend_sum + $agreement_duration_holiday_sum, 1));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("I{$agreement_row}", "$" . number_format((float)$agreement_rate_weekday_sum, 2));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("J{$agreement_row}", "$" . number_format((float)$agreement_rate_weekend_sum, 2));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("K{$agreement_row}", "$" . number_format((float)$agreement_rate_holiday_sum, 2));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("L{$agreement_row}", "$" . number_format((float)$agreement_rate_weekday_sum + $agreement_rate_weekend_sum + $agreement_rate_holiday_sum, 2));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            //$workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}");
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:L{$current_row}")->applyFromArray($this->grid_style);
            return $current_row;
        }
    }

    public function write_CYTPM($workbook, $agreementPassed, $arguments, $current_row, $sheet_index, $practices)
    {
        $agreement = Agreement::findOrFail($agreementPassed->id);
        $physician_first_row = $current_row;
        if ($agreement->archived == 0) {
            $agreement_date = new DateTime($agreementPassed->start_date);
            $agreement_start_date = $agreementPassed->start_date;
            $agreement_date1 = new DateTime($agreementPassed->end_date);
            $agreement_end_date = $agreementPassed->end_date;
            $duration_weekday = 0;
            $duration_weekend = 0;
            $duration_holiday = 0;
            $amount_weekday = 0;
            $amount_weekend = 0;
            $amount_holiday = 0;
            $duration_on_call = 0;
            $duration_called_back = 0;
            $duration_called_in = 0;
            $amount_on_call = 0;
            $amount_called_back = 0;
            $amount_called_in = 0;
            $amount_paid_agreement = 0;
            $amount_paid = 0;
            $practice_duration_weekday_sum = 0;
            $practice_duration_weekday_sum_amount = 0;
            $practice_duration_weekend_sum = 0;
            $practice_duration_weekend_sum_amount = 0;
            $practice_duration_holiday_sum = 0;
            $practice_duration_holiday_sum_amount = 0;
            $contract_rate_type = 0; /* for weekday, weekend, holiday */
            $current_row++;
            $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:M{$current_row}")->setCellValue("B{$current_row}", $agreement->name)
                ->getStyle("B{$current_row}:L{$current_row}")
                ->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->contract_style);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:M{$current_row}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $current_row++;
            $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:M{$current_row}")->
            setCellValue("B{$current_row}", "Contract Period : " . format_date($agreement->start_date) . " - " . format_date($agreement->end_date))
                ->getStyle("B{$current_row}:M{$current_row}")
                ->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->contract_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:N{$current_row}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $current_row++;
            $agreement_row = $current_row;
            $workbook->setActiveSheetIndex($sheet_index)
                ->mergeCells("B{$current_row}:C{$current_row}")
                ->setCellValue("B{$current_row}", $agreement->name);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:C{$current_row}")->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:C{$current_row}")->applyFromArray($this->total_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("D{$current_row}:G{$current_row}")->applyFromArray($this->total_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("H{$current_row}:K{$current_row}")->applyFromArray($this->total_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("L{$current_row}:M{$current_row}")->applyFromArray($this->total_style);

            foreach ($practices as $practice) {
                $practice_duration_weekday_sum = 0;
                $practice_duration_weekday_sum_amount = 0;
                $practice_duration_weekend_sum = 0;
                $practice_duration_weekend_sum_amount = 0;
                $practice_duration_holiday_sum = 0;
                $practice_duration_holiday_sum_amount = 0;
                $amount_paid_practice = 0;
                $physicians = $this->queryPhysicians($arguments->hospital_id, $agreement->id, $practice->id);

                $current_row++;
                $practice_row = $current_row;
                $workbook->setActiveSheetIndex($sheet_index)
                    ->mergeCells("B{$current_row}:C{$current_row}")
                    ->setCellValue("B{$current_row}", $practice->name);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:M{$current_row}")
                    ->applyFromArray($this->contract_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:C{$current_row}")->getFont()->setBold(true);

                //$current_row++;
                $start = strtotime($agreementPassed->start_date);
                $end = strtotime($agreementPassed->end_date);
                $agreement_phy_array = explode(',', $arguments->physician_ids);

                /*	foreach ($physicians as $physician) {
                        //if (in_array($physician->id, $agreement_phy_array))
                        {
                            $physician_totals_row = $current_row;
                            $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("B{$current_row}", $physician->last_name . ", " . $physician->first_name . " Totals");
                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->getFont()->setBold(true);
                            //$workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->applyFromArray($this->grid_style);
                            //$workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:G{$current_row}")->applyFromArray($this->grid_style);
                            //$workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}:K{$current_row}")->applyFromArray($this->grid_style);
                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->grid_style);
                        }
                    }*/
                $physician_weekday_total = 0;
                $physician_weekend_total = 0;
                $physician_holiday_total = 0;
                $physician_weekday_rate = 0;
                $physician_weekend_rate = 0;
                $physician_holiday_rate = 0;
                $pract_total_weekday = 0;
                $pract_total_weekend = 0;
                $pract_total_holiday = 0;
                $pract_total_weekday_amount = 0;
                $pract_total_weekend_amount = 0;
                $pract_total_holiday_amount = 0;
                foreach ($physicians as $physician) {
                    $amount_paid_physician = 0;
                    $amount_paid = 0;
                    $start = strtotime($agreement->start_date);
                    $end = strtotime($agreement->end_date);
                    $current_row++;
                    $physician_totals_row = $current_row;
                    $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("B{$current_row}", $physician->last_name . ", " . $physician->first_name . " Totals");
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->getFont()->setBold(true);
                    //$workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->applyFromArray($this->grid_style);
                    //$workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:G{$current_row}")->applyFromArray($this->grid_style);
                    //$workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}:K{$current_row}")->applyFromArray($this->grid_style);
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->grid_style);

                    $flag = 0;
                    $agreement_start_month_start_date = strtotime(date('m/01/Y', $start));
                    $history = PhysicianPracticeHistory::select('*')->where('physician_id', '=', $physician->id)->where('practice_id', '=', $practice->id)->orderBy('created_at', 'desc')->get();

                    $physician_rates = DB::table('contracts')
                        ->select('contracts.*')
                        ->where('contracts.physician_id', '=', $physician->id)//$physician->id
                        ->where('contracts.agreement_id', '=', $agreement->id)
                        ->where('contracts.contract_type_id', '=', ContractType::ON_CALL)
                        ->first();

                    while ($agreement_start_month_start_date <= $end) {
                        $month_start_date = date('m/01/Y', $end);
                        $month_end_date = date('m/t/Y', $end);
                        //$start = strtotime("+1 month", $start);
                        $print_date = strtotime($month_start_date);
                        $end = strtotime("-1 month", $end);
                        $prev_month_start_date = date('m/01/Y', $end);
                        $display = 0;
                        if ($physician_rates->manual_contract_end_date == "0000-00-00" || strtotime($physician_rates->manual_contract_end_date) >= strtotime($prev_month_start_date)) {
                            foreach ($history as $history_dates) {
                                $history_start_date = mysql_date($history_dates->start_date);
                                $history_end_date = mysql_date($history_dates->end_date);
                                $month_display = date("m", strtotime($month_start_date));
                                $year_display = date("Y", strtotime($month_start_date));
                                $start_month_history = date("m", strtotime($history_start_date));
                                $start_year_history = date("Y", strtotime($history_start_date));
                                $end_month_history = date("m", strtotime($history_end_date));
                                $end_year_history = date("Y", strtotime($history_end_date));
                                if ($start_year_history < $year_display && $year_display < $end_year_history) {
                                    $display++;
                                }
                                if ($start_year_history <= $year_display && $year_display < $end_year_history) {
                                    if ($start_month_history <= $month_display) {
                                        $display++;
                                    }
                                }
                                if ($start_year_history < $year_display && $year_display <= $end_year_history) {
                                    if ($month_display <= $end_month_history) {
                                        $display++;
                                    }
                                }
                                if ($start_year_history == $year_display && $year_display == $end_year_history) {
                                    if ($start_month_history <= $month_display && $month_display <= $end_month_history) {
                                        $display++;
                                    }
                                } elseif ($start_year_history == $year_display) {
                                    //Log::info("here--------------------------------------------");
                                }
                            }
                            if ($display > 0) {
                                if ($prev_month_start_date != $month_start_date) {
                                    //if($physician_rates->weekday_rate!="")
                                    $weekday_rate = $physician_rates->weekday_rate;
                                    //if($physician_rates->weekend_rate!=0)
                                    $weekend_rate = $physician_rates->weekend_rate;
                                    //if($physician_rates->holiday_rate!=0)
                                    $holiday_rate = $physician_rates->holiday_rate;
                                    $on_call_rate = $physician_rates->on_call_rate;
                                    $called_back_rate = $physician_rates->called_back_rate;
                                    $called_in_rate = $physician_rates->called_in_rate;
                                    if ($weekday_rate == 0 && $weekend_rate == 0 && $holiday_rate == 0 && ($on_call_rate > 0 || $called_back_rate > 0 || $called_in_rate > 0)) {
                                        $contract_rate_type = 1; /* for on-call, called back and called in*/
                                    } else {
                                        $contract_rate_type = 0; /* for weekday, weekend, holiday */
                                    }

                                    $fetch_contract_id = Contract::where('agreement_id', '=', $agreement->id)
                                        ->where("contract_type_id", "=", ContractType::ON_CALL)
                                        ->where('physician_id', '=', $physician->id)
                                        ->pluck('id');
                                    $amount = DB::table('amount_paid')
                                        ->where('start_date', '<=', mysql_date($month_start_date))
                                        ->where('end_date', '>=', mysql_date($month_start_date))
                                        ->where('physician_id', '=', $physician->id)
                                        ->where('contract_id', '=', $fetch_contract_id)
                                        ->where('practice_id', '=', $practice->id)
                                        ->orderBy('created_at', 'desc')
                                        ->orderBy('id', 'desc')
                                        ->value('amountPaid');
                                    if (json_encode($amount) == "null") {
                                        $amount_paid = "-";
                                    } else {
                                        $amount_paid = $amount;
                                        $amount_paid_physician += $amount;
                                        $amount_paid_practice += $amount;
                                        $amount_paid_agreement += $amount;
                                    }
                                    $physician_logs = DB::table('physician_logs')->select('duration', 'action_id', 'date', 'log_hours')
                                        ->where('physician_id', '=', $physician->id)//$physician->id
                                        ->where('contract_id', '=', $fetch_contract_id)//$physician->id
                                        ->where('practice_id', '=', $practice->id)//$practice->id
                                        ->whereBetween("date", [mysql_date($month_start_date), mysql_date($month_end_date)])
                                        ->get();
                                    $today = strtotime(date("Y-m-d"));
                                    $date = strtotime($month_end_date);
                                    if ($date < $today) {
                                        $week = 0;
                                        $weekend = 0;
                                        $holiday = 0;
                                        $on_call = 0;
                                        $called_back = 0;
                                        $called_in = 0;
                                        $action_array = array();
                                        $log_hours_week = 0;
                                        $log_hours_weekend = 0;
                                        $log_hours_holiday = 0;
                                        //if (count($physician_logs) > 0) {
                                        foreach ($physician_logs as $physician_log) {
                                            $action_names = DB::table('actions')->select('name')
                                                ->where('id', '=', $physician_log->action_id)//$physician->id
                                                ->first();
                                            $action_array[] = [
                                                "duration" => $physician_log->duration,
                                                "log_hours" => $physician_log->log_hours,
                                                "action_name" => $action_names->name];
                                        }

                                        foreach ($action_array as $action_data) {
                                            // Added condition for partial shift hours
                                            if (strlen(strstr(strtoupper($action_data['action_name']), "WEEKDAY")) > 0) {
                                                $duration_weekday += $action_data['duration'];
                                                $week += $action_data['duration'];
                                                $log_hours_week += $action_data['log_hours'];
                                            }
                                            if (strlen(strstr(strtoupper($action_data['action_name']), "WEEKEND")) > 0) {
                                                $duration_weekend += $action_data['duration'];
                                                $weekend += $action_data['duration'];
                                                $log_hours_weekend += $action_data['log_hours'];
                                            }
                                            if (strlen(strstr(strtoupper($action_data['action_name']), "HOLIDAY")) > 0) {
                                                $duration_holiday += $action_data['duration'];
                                                $holiday += $action_data['duration'];
                                                $log_hours_holiday += $action_data['log_hours'];
                                            }
                                            if ($action_data['action_name'] == "On-Call") {
                                                $duration_weekday += $action_data['duration'];
                                                $on_call += $action_data['duration'];
                                                $log_hours_week += $action_data['log_hours'];
                                            }
                                            if ($action_data['action_name'] == "Called-Back") {
                                                $duration_weekend += $action_data['duration'];
                                                $called_back += $action_data['duration'];
                                                $log_hours_weekend += $action_data['log_hours'];
                                            }
                                            if ($action_data['action_name'] == "Called-In") {
                                                $duration_holiday += $action_data['duration'];
                                                $called_in += $action_data['duration'];
                                                $log_hours_holiday += $action_data['log_hours'];
                                            }
                                        }
                                        //if ($week != 0 || $weekend != 0 || $holiday != 0) {
                                        $current_row++;
                                        // Log::info("current" . $current_row);
                                        if ($flag == 0) {
                                            $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}");
                                            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("D{$current_row}", " " . $contract_rate_type == 0 ? "Weekday" : "On Call");
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("E{$current_row}", " " . $contract_rate_type == 0 ? "Weekend" : "Called Back");
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("F{$current_row}", " " . $contract_rate_type == 0 ? "Holiday" : "Called In");
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("G{$current_row}", " Total");
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("H{$current_row}", " " . $contract_rate_type == 0 ? "Weekday" : "On Call");
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("I{$current_row}", " " . $contract_rate_type == 0 ? "Weekend" : "Called Back");
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("J{$current_row}", " " . $contract_rate_type == 0 ? "Holiday" : "Called In");
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("K{$current_row}", " Total");
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("L{$current_row}", " Amount Paid");
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("M{$current_row}", " ");
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("M{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->applyFromArray($this->left);

                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:F{$current_row}")->applyFromArray($this->Total_shaded_style_summary);
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$current_row}")->applyFromArray($this->Total__blue_shaded_style);
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}:J{$current_row}")->applyFromArray($this->Total_shaded_style);
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->applyFromArray($this->Total_shaded_style);
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$current_row}")->applyFromArray($this->Total__blue_shaded_style);
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$current_row}")->applyFromArray($this->grid_style_left);
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:G{$current_row}")->applyFromArray($this->grid_style_right);
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}:J{$current_row}")->applyFromArray($this->grid_style_right);
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->applyFromArray($this->grid_style_right);
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("M{$current_row}")->applyFromArray($this->Total_shaded_style_cytpm);

                                            $physician_first_row = $current_row;
                                            //$workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("B{$current_row}", $physician->last_name . " , " . $physician->first_name);
                                            //$workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                            //$workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->getFont()->setBold(true);
                                            //$workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:L{$current_row}")->applyFromArray($this->grid_style);
                                            $current_row++;
                                            $flag = 1;
                                        }
                                        /*elseif ($flag == 1){
                                                $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}");
                                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->applyFromArray($this->left);
                                            }*/
                                        $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}");
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("D{$current_row}", " " . $contract_rate_type == 0 ? number_format((float)$week, 1) : number_format((float)$on_call, 1));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("E{$current_row}", " " . $contract_rate_type == 0 ? number_format((float)$weekend, 1) : number_format((float)$called_back, 1));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("F{$current_row}", " " . $contract_rate_type == 0 ? number_format((float)$holiday, 1) : number_format((float)$called_in, 1));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("G{$current_row}", " " . $contract_rate_type == 0 ? number_format((float)$week + $weekend + $holiday, 1) : number_format((float)$on_call + $called_back + $called_in, 1));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        // Below code is added for partial shift ON/OFF condition by akash
                                        // It check the partial shift and displays the calculated value for months based on partial shift ON/OFF
                                        $temp_week_amt_month = $log_hours_week * ($contract_rate_type == 0 ? $weekday_rate : $on_call_rate);
                                        $temp_weekend_amt_month = $log_hours_weekend * ($contract_rate_type == 0 ? $weekend_rate : $called_back_rate);
                                        $temp_holiday_amt_month = $log_hours_holiday * ($contract_rate_type == 0 ? $holiday_rate : $called_in_rate);
                                        $total_amt_month = $temp_week_amt_month + $temp_weekend_amt_month + $temp_holiday_amt_month;

                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("H{$current_row}", "$" . number_format((float)$temp_week_amt_month, 2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("I{$current_row}", "$" . number_format((float)$temp_weekend_amt_month, 2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("J{$current_row}", "$" . number_format((float)$temp_holiday_amt_month, 2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("K{$current_row}", "$" . number_format((float)$total_amt_month, 2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("L{$current_row}", "$" . number_format((float)$amount_paid, 2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("M{$current_row}", date('M', $print_date));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("M{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->applyFromArray($this->left);
                                        //$workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:K{$current_row}")->applyFromArray($this->grid_style);
                                        //$workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->applyFromArray($this->grid_style);
                                        //cc
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:F{$current_row}")->applyFromArray($this->Total_shaded_style_summary);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$current_row}")->applyFromArray($this->Total__blue_shaded_style);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}:J{$current_row}")->applyFromArray($this->Total_shaded_style);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->applyFromArray($this->Total_shaded_style);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$current_row}")->applyFromArray($this->Total__blue_shaded_style);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$current_row}")->applyFromArray($this->grid_style_left);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:G{$current_row}")->applyFromArray($this->grid_style_right);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}:J{$current_row}")->applyFromArray($this->grid_style_right);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->applyFromArray($this->grid_style_right);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("M{$current_row}")->applyFromArray($this->Total_shaded_style_cytpm);
                                        //}
                                        /*$workbook->setActiveSheetIndex($sheet_index)->setCellValue("D{$physician_totals_row}", " ". number_format((float)$physician_weekday_total += $week,1));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("E{$physician_totals_row}", " ". number_format((float)$physician_weekend_total += $weekend,1));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("F{$physician_totals_row}", " ". number_format((float)$physician_holiday_total += $holiday,1));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("G{$physician_totals_row}", " ". number_format((float)$physician_weekday_total + $physician_weekend_total + $physician_holiday_total,1));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("H{$physician_totals_row}", "$" . number_format((float)$physician_weekday_rate += $week * $weekday_rate,2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("I{$physician_totals_row}", "$" . number_format((float)$physician_weekend_rate += $weekend * $weekend_rate,2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("J{$physician_totals_row}", "$" . number_format((float)$physician_holiday_rate += $holiday * $holiday_rate,2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("K{$physician_totals_row}", "$" . number_format((float)$physician_weekday_rate + $physician_weekend_rate + $physician_holiday_rate,2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("L{$physician_totals_row}", "$" . number_format((float)$amount_paid,2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        //$workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->grid_style);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$physician_totals_row}:M{$physician_totals_row}")->applyFromArray($this->Total__blue_shaded_style);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$physician_totals_row}:G{$physician_totals_row}")->applyFromArray($this->grid_style_right);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$physician_totals_row}:J{$physician_totals_row}")->applyFromArray($this->grid_style_right);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$physician_totals_row}")->applyFromArray($this->grid_style_right);*/
                                        //$workbook->setActiveSheetIndex($sheet_index)->getStyle("M{$physician_totals_row}")->applyFromArray($this->Total_shaded_style_cytpm);

                                        /*	$duration_weekday = 0;
                                            $duration_weekend = 0;
                                            $duration_holiday = 0;*/
                                        //} else {

                                        // Below code is added for partial shift ON/OFF condition by akash
                                        // It check the partial shift and displays the calculated value for months based on partial shift ON/OFF

                                        $physician_weekday_rate = $physician_weekday_rate + ($log_hours_week * ($contract_rate_type == 0 ? $weekday_rate : $on_call_rate));
                                        $physician_weekend_rate = $physician_weekend_rate + ($log_hours_weekend * ($contract_rate_type == 0 ? $weekend_rate : $called_back_rate));
                                        $physician_holiday_rate = $physician_holiday_rate + ($log_hours_holiday * ($contract_rate_type == 0 ? $holiday_rate : $called_in_rate));

                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("D{$physician_totals_row}", " " . number_format((float)$physician_weekday_total += $contract_rate_type == 0 ? $week : $on_call, 1));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("E{$physician_totals_row}", " " . number_format((float)$physician_weekend_total += $contract_rate_type == 0 ? $weekend : $called_back, 1));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("F{$physician_totals_row}", " " . number_format((float)$physician_holiday_total += $contract_rate_type == 0 ? $holiday : $called_in, 1));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("G{$physician_totals_row}", " " . number_format((float)$physician_weekday_total + $physician_weekend_total + $physician_holiday_total, 1));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("H{$physician_totals_row}", "$" . number_format((float)$physician_weekday_rate, 2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("I{$physician_totals_row}", "$" . number_format((float)$physician_weekend_rate, 2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("J{$physician_totals_row}", "$" . number_format((float)$physician_holiday_rate += $contract_rate_type == 0 ? $holiday * $holiday_rate : $called_in * $called_in_rate, 2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("K{$physician_totals_row}", "$" . number_format((float)$physician_weekday_rate + $physician_weekend_rate + $physician_holiday_rate, 2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("L{$physician_totals_row}", "$" . number_format((float)$amount_paid_physician, 2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        //$workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->grid_style);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$physician_totals_row}:M{$physician_totals_row}")->applyFromArray($this->Total__blue_shaded_style);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$physician_totals_row}:G{$physician_totals_row}")->applyFromArray($this->grid_style_right);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$physician_totals_row}:J{$physician_totals_row}")->applyFromArray($this->grid_style_right);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$physician_totals_row}")->applyFromArray($this->grid_style_right);
                                        //$workbook->setActiveSheetIndex($sheet_index)->getStyle("M{$physician_totals_row}")->applyFromArray($this->Total_shaded_style_cytpm);
                                        //}
                                        if ($contract_rate_type == 0) {
                                            $pract_total_weekday = $pract_total_weekday + $week;
                                            $pract_total_weekend = $pract_total_weekend + $weekend;
                                            $pract_total_holiday = $pract_total_holiday + $holiday;
                                            $pract_total_weekday_amount = $pract_total_weekday_amount + ($log_hours_week * $weekday_rate);
                                            $pract_total_weekend_amount = $pract_total_weekend_amount + ($log_hours_weekend * $weekend_rate);
                                            $pract_total_holiday_amount = $pract_total_holiday_amount + ($log_hours_holiday * $holiday_rate);
                                        } else {
                                            $pract_total_weekday = $pract_total_weekday + $on_call;
                                            $pract_total_weekend = $pract_total_weekend + $called_back;
                                            $pract_total_holiday = $pract_total_holiday + $called_in;
                                            $pract_total_weekday_amount = $pract_total_weekday_amount + ($log_hours_week * $on_call_rate);
                                            $pract_total_weekend_amount = $pract_total_weekend_amount + ($log_hours_weekend * $called_back_rate);
                                            $pract_total_holiday_amount = $pract_total_holiday_amount + ($log_hours_holiday * $called_in_rate);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $this->writeCYTPMPhysicianMiddle($workbook, $sheet_index, $physician_first_row, $current_row, $physician->first_name, $physician->last_name);
                    //reset physicians totals
                    $physician_weekday_total = 0;
                    $physician_weekend_total = 0;
                    $physician_holiday_total = 0;
                    $physician_weekday_rate = 0;
                    $physician_weekend_rate = 0;
                    $physician_holiday_rate = 0;
                }
                $practice_duration_weekday_sum += $pract_total_weekday;
                $practice_duration_weekday_sum_amount += $pract_total_weekday_amount;
                $practice_duration_weekend_sum += $pract_total_weekend;
                $practice_duration_weekend_sum_amount += $pract_total_weekend_amount;
                $practice_duration_holiday_sum += $pract_total_holiday;
                $practice_duration_holiday_sum_amount += $pract_total_holiday_amount;
                $amount_weekday += $pract_total_weekday_amount;
                $amount_weekend += $pract_total_weekend_amount;
                $amount_holiday += $pract_total_holiday_amount;
                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("D{$practice_row}", " " . number_format((float)$practice_duration_weekday_sum, 1));
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("E{$practice_row}", " " . number_format((float)$practice_duration_weekend_sum, 1));
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("F{$practice_row}", " " . number_format((float)$practice_duration_holiday_sum, 1));
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("G{$practice_row}", " " . number_format((float)$practice_duration_weekday_sum + $practice_duration_weekend_sum + $practice_duration_holiday_sum, 1));
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("H{$practice_row}", "$" . number_format((float)$practice_duration_weekday_sum_amount, 2));
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("I{$practice_row}", "$" . number_format((float)$practice_duration_weekend_sum_amount, 2));
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("J{$practice_row}", "$" . number_format((float)$practice_duration_holiday_sum_amount, 2));
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("K{$practice_row}", "$" . number_format((float)$practice_duration_weekday_sum_amount + $practice_duration_weekend_sum_amount + $practice_duration_holiday_sum_amount, 2));
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("L{$practice_row}", "$" . number_format((float)$amount_paid_practice, 2));
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                //$workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->grid_style);
                $practice_duration_weekday_sum_p = 0;
                $practice_duration_weekend_sum_p = 0;
                $practice_duration_holiday_sum_p = 0;
            }
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("D{$agreement_row}", " " . number_format((float)$duration_weekday, 1));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("E{$agreement_row}", " " . number_format((float)$duration_weekend, 1));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("F{$agreement_row}", " " . number_format((float)$duration_holiday, 1));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("G{$agreement_row}", " " . number_format((float)$duration_weekday + $duration_weekend + $duration_holiday, 1));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("H{$agreement_row}", "$" . number_format((float)$amount_weekday, 2));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("I{$agreement_row}", "$" . number_format((float)$amount_weekend, 2));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("J{$agreement_row}", "$" . number_format((float)$amount_holiday, 2));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("K{$agreement_row}", "$" . number_format($amount_weekday + $amount_weekend + $amount_holiday, 2));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("L{$agreement_row}", "$" . number_format((float)$amount_paid_agreement, 2));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->bottom_row_style);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("M{$agreement_row}")->applyFromArray($this->Total_shaded_style_cytpm);
            return $current_row;
        }
    }

    private function writeCYTPMPhysicianMiddle($workbook, $sheetIndex, $physician_first_row, $physician_last_row, $physician_first_name, $physician_last_name)
    {
        $physician_name_row = $physician_last_row - ($physician_last_row - $physician_first_row) / 2;
        $physician_name_row = floor($physician_name_row);

        /*Log::info("* practice_last_row: ".$practice_last_row);
        Log::info("* practice_first_row: ".$practice_first_row);
        Log::info("* practice_name_row: ".$practice_name_row);*/

        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$physician_first_row}:D{$physician_last_row}")->applyFromArray($this->cytd_breakdown_practice_style);
        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("B{$physician_name_row}", $physician_last_name . " , " . $physician_first_name);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$physician_name_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$physician_name_row}")->getAlignment()->setWrapText(true);

    }

    public function write_CYTD($workbook, $agreementPassed, $arguments, $current_row, $sheet_index, $practices)
    {
        $agreement = Agreement::findOrFail($agreementPassed->id);
        $physician_first_row = $current_row;
        if ($agreement->archived == 0) {
            $agreement_date = new DateTime($agreementPassed->start_date);
            $agreement_start_date = $agreementPassed->start_date;
            $agreement_date1 = new DateTime($agreementPassed->end_date);
            $agreement_end_date = $agreementPassed->end_date;
            $duration_weekday = 0;
            $duration_weekend = 0;
            $duration_holiday = 0;
            $amount_weekday = 0;
            $amount_weekend = 0;
            $amount_holiday = 0;
            $duration_on_call = 0;
            $duration_called_back = 0;
            $duration_called_in = 0;
            $amount_on_call = 0;
            $amount_called_back = 0;
            $amount_called_in = 0;
            $amount_paid_agreement = 0;
            $amount_paid = 0;
            $practice_duration_weekday_sum = 0;
            $practice_duration_weekday_sum_amount = 0;
            $practice_duration_weekend_sum = 0;
            $practice_duration_weekend_sum_amount = 0;
            $practice_duration_holiday_sum = 0;
            $practice_duration_holiday_sum_amount = 0;
            $contract_rate_type = 0; /* for weekday, weekend, holiday */
            $current_row++;
            $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:L{$current_row}")->setCellValue("B{$current_row}", $agreement->name)
                ->getStyle("B{$current_row}:L{$current_row}")
                ->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:L{$current_row}")->applyFromArray($this->contract_style);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:L{$current_row}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $current_row++;
            $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:L{$current_row}")->
            setCellValue("B{$current_row}", "Contract Period : " . format_date($agreement->start_date) . " - " . format_date($agreement->end_date))
                ->getStyle("B{$current_row}:L{$current_row}")
                ->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:L{$current_row}")->applyFromArray($this->contract_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:N{$current_row}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $current_row++;
            $agreement_row = $current_row;
            $workbook->setActiveSheetIndex($sheet_index)
                ->mergeCells("B{$current_row}:C{$current_row}")
                ->setCellValue("B{$current_row}", $agreement->name);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:C{$current_row}")->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:C{$current_row}")->applyFromArray($this->total_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("D{$current_row}:G{$current_row}")->applyFromArray($this->total_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("H{$current_row}:K{$current_row}")->applyFromArray($this->total_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("L{$current_row}")->applyFromArray($this->total_style);

            foreach ($practices as $practice) {
                $practice_duration_weekday_sum = 0;
                $practice_duration_weekday_sum_amount = 0;
                $practice_duration_weekend_sum = 0;
                $practice_duration_weekend_sum_amount = 0;
                $practice_duration_holiday_sum = 0;
                $practice_duration_holiday_sum_amount = 0;
                $amount_paid_practice = 0;
                $physicians = $this->queryPhysicians($arguments->hospital_id, $agreement->id, $practice->id);

                $current_row++;
                $practice_row = $current_row;
                $workbook->setActiveSheetIndex($sheet_index)
                    ->mergeCells("B{$current_row}:C{$current_row}")
                    ->setCellValue("B{$current_row}", $practice->name);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:L{$current_row}")
                    ->applyFromArray($this->contract_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:C{$current_row}")->getFont()->setBold(true);

                //$current_row++;
                $start = strtotime($agreementPassed->start_date);
                $end = strtotime($agreementPassed->end_date);
                $agreement_phy_array = explode(',', $arguments->physician_ids);

                /*	foreach ($physicians as $physician) {
                        //if (in_array($physician->id, $agreement_phy_array))
                        {
                            $physician_totals_row = $current_row;
                            $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("B{$current_row}", $physician->last_name . ", " . $physician->first_name . " Totals");
                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->getFont()->setBold(true);
                            //$workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->applyFromArray($this->grid_style);
                            //$workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:G{$current_row}")->applyFromArray($this->grid_style);
                            //$workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}:K{$current_row}")->applyFromArray($this->grid_style);
                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->grid_style);
                        }
                    }*/
                $physician_weekday_total = 0;
                $physician_weekend_total = 0;
                $physician_holiday_total = 0;
                $physician_weekday_rate = 0;
                $physician_weekend_rate = 0;
                $physician_holiday_rate = 0;
                $pract_total_weekday = 0;
                $pract_total_weekend = 0;
                $pract_total_holiday = 0;
                $pract_total_weekday_amount = 0;
                $pract_total_weekend_amount = 0;
                $pract_total_holiday_amount = 0;
                foreach ($physicians as $physician) {
                    $amount_paid_physician = 0;
                    $amount_paid = 0;
                    $start = strtotime($agreement->start_date);
                    $end = strtotime($agreement->end_date);
                    $current_row++;
                    $physician_totals_row = $current_row;
                    $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("B{$current_row}", $physician->last_name . ", " . $physician->first_name . " Totals");
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->getFont()->setBold(true);
                    //$workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->applyFromArray($this->grid_style);
                    //$workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:G{$current_row}")->applyFromArray($this->grid_style);
                    //$workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}:K{$current_row}")->applyFromArray($this->grid_style);
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:L{$current_row}")->applyFromArray($this->grid_style);

                    $flag = 0;
                    $agreement_start_month_start_date = strtotime(date('m/01/Y', $start));
                    $history = PhysicianPracticeHistory::select('*')->where('physician_id', '=', $physician->id)->where('practice_id', '=', $practice->id)->orderBy('created_at', 'desc')->get();
                    $physician_rates = DB::table('contracts')
                        ->select('contracts.*')
                        ->where('contracts.physician_id', '=', $physician->id)//$physician->id
                        ->where('contracts.agreement_id', '=', $agreement->id)//$physician->id
                        ->where('contracts.contract_type_id', '=', ContractType::ON_CALL)
                        ->first();

                    while ($agreement_start_month_start_date <= $end) {
                        $week = 0;
                        $weekend = 0;
                        $holiday = 0;
                        $on_call = 0;
                        $called_back = 0;
                        $called_in = 0;
                        $month_start_date = date('m/01/Y', $end);
                        $month_end_date = date('m/t/Y', $end);
                        //$start = strtotime("+1 month", $start);
                        $print_date = strtotime($month_start_date);
                        $end = strtotime("-1 month", $end);
                        $prev_month_start_date = date('m/01/Y', $end);
                        $display = 0;
                        $partial_shift_hours = false;
                        $log_hours_week = 0;
                        $log_hours_weekend = 0;
                        $log_hours_holiday = 0;
                        if ($physician_rates->manual_contract_end_date == "0000-00-00" || strtotime($physician_rates->manual_contract_end_date) >= strtotime($prev_month_start_date)) {
                            foreach ($history as $history_dates) {
                                $history_start_date = mysql_date($history_dates->start_date);
                                $history_end_date = mysql_date($history_dates->end_date);
                                $month_display = date("m", strtotime($month_start_date));
                                $year_display = date("Y", strtotime($month_start_date));
                                $start_month_history = date("m", strtotime($history_start_date));
                                $start_year_history = date("Y", strtotime($history_start_date));
                                $end_month_history = date("m", strtotime($history_end_date));
                                $end_year_history = date("Y", strtotime($history_end_date));
                                if ($start_year_history < $year_display && $year_display < $end_year_history) {
                                    $display++;
                                }
                                if ($start_year_history <= $year_display && $year_display < $end_year_history) {
                                    if ($start_month_history <= $month_display) {
                                        $display++;
                                    }
                                }
                                if ($start_year_history < $year_display && $year_display <= $end_year_history) {
                                    if ($month_display <= $end_month_history) {
                                        $display++;
                                    }
                                }
                                if ($start_year_history == $year_display && $year_display == $end_year_history) {
                                    if ($start_month_history <= $month_display && $month_display <= $end_month_history) {
                                        $display++;
                                    }
                                } elseif ($start_year_history == $year_display) {
                                    //Log::info("here--------------------------------------------");
                                }
                            }
                            if ($display > 0) {
                                if ($prev_month_start_date != $month_start_date) {
                                    //if($physician_rates->weekday_rate!="")
                                    $weekday_rate = $physician_rates->weekday_rate;
                                    //if($physician_rates->weekend_rate!=0)
                                    $weekend_rate = $physician_rates->weekend_rate;
                                    //if($physician_rates->holiday_rate!=0)
                                    $holiday_rate = $physician_rates->holiday_rate;
                                    $on_call_rate = $physician_rates->on_call_rate;
                                    $called_back_rate = $physician_rates->called_back_rate;
                                    $called_in_rate = $physician_rates->called_in_rate;
                                    if ($weekday_rate == 0 && $weekend_rate == 0 && $holiday_rate == 0 && ($on_call_rate > 0 || $called_back_rate > 0 || $called_in_rate > 0)) {
                                        $contract_rate_type = 1; /* for on-call, called back and called in*/
                                    } else {
                                        $contract_rate_type = 0; /* for weekday, weekend, holiday */
                                    }

                                    $fetch_contract_id = Contract::where('agreement_id', '=', $agreement->id)
                                        ->where("contract_type_id", "=", ContractType::ON_CALL)
                                        ->where('physician_id', '=', $physician->id)
                                        ->pluck('id');
                                    $amount = DB::table('amount_paid')
                                        ->where('start_date', '<=', mysql_date($month_start_date))
                                        ->where('end_date', '>=', mysql_date($month_start_date))
                                        ->where('physician_id', '=', $physician->id)
                                        ->where('contract_id', '=', $fetch_contract_id)
                                        ->where('practice_id', '=', $practice->id)
                                        ->orderBy('created_at', 'desc')
                                        ->orderBy('id', 'desc')
                                        ->value('amountPaid');
                                    if (json_encode($amount) == "null") {
                                        $amount_paid = "-";
                                    } else {
                                        $amount_paid = $amount;
                                        $amount_paid_physician += $amount;
                                        $amount_paid_practice += $amount;
                                        $amount_paid_agreement += $amount;
                                    }
                                    $physician_logs = DB::table('physician_logs')->select('duration', 'action_id', 'date', 'log_hours')
                                        ->where('physician_id', '=', $physician->id)//$physician->id
                                        ->where('contract_id', '=', $fetch_contract_id)//$physician->id
                                        ->where('practice_id', '=', $practice->id)//$practice->id
                                        ->whereBetween("date", [mysql_date($month_start_date), mysql_date($month_end_date)])
                                        ->get();
                                    $today = strtotime(date("Y-m-t"));
                                    $date = strtotime($month_end_date);
                                    if ($date <= $today) {
                                        $action_array = array();
                                        //if (count($physician_logs) > 0) {
                                        foreach ($physician_logs as $physician_log) {
                                            $action_names = DB::table('actions')->select('name')
                                                ->where('id', '=', $physician_log->action_id)//$physician->id
                                                ->first();
                                            $action_array[] = [
                                                "duration" => $physician_log->duration,
                                                "log_hours" => $physician_log->log_hours,
                                                "action_name" => $action_names->name];
                                        }

                                        foreach ($action_array as $action_data) {
                                            if (strlen(strstr(strtoupper($action_data['action_name']), "WEEKDAY")) > 0) {
                                                $duration_weekday += $action_data['duration'];
                                                $week += $action_data['duration'];
                                                $log_hours_week += $action_data['log_hours'];
                                            }
                                            if (strlen(strstr(strtoupper($action_data['action_name']), "WEEKEND")) > 0) {
                                                $duration_weekend += $action_data['duration'];
                                                $weekend += $action_data['duration'];
                                                $log_hours_weekend += $action_data['log_hours'];
                                            }
                                            if (strlen(strstr(strtoupper($action_data['action_name']), "HOLIDAY")) > 0) {
                                                $duration_holiday += $action_data['duration'];
                                                $holiday += $action_data['duration'];
                                                $log_hours_holiday += $action_data['log_hours'];
                                            }
                                            if ($action_data['action_name'] == "On-Call") {
                                                $duration_weekday += $action_data['duration'];
                                                $on_call += $action_data['duration'];
                                                $log_hours_week += $action_data['log_hours'];
                                            }
                                            if ($action_data['action_name'] == "Called-Back") {
                                                $duration_weekend += $action_data['duration'];
                                                $called_back += $action_data['duration'];
                                                $log_hours_weekend += $action_data['log_hours'];
                                            }
                                            if ($action_data['action_name'] == "Called-In") {
                                                $duration_holiday += $action_data['duration'];
                                                $called_in += $action_data['duration'];
                                                $log_hours_holiday += $action_data['log_hours'];
                                            }
                                        }
                                        //if ($week != 0 || $weekend != 0 || $holiday != 0) {
                                        if ($flag == 0) {
                                            $current_row++;
                                            $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}");
                                            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("D{$current_row}", " " . $contract_rate_type == 0 ? "Weekday" : "On Call");
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("E{$current_row}", " " . $contract_rate_type == 0 ? "Weekend" : "Called Back");
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("F{$current_row}", " " . $contract_rate_type == 0 ? "Holiday" : "Called In");
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("G{$current_row}", " Total");
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("H{$current_row}", " " . $contract_rate_type == 0 ? "Weekday" : "On Call");
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("I{$current_row}", " " . $contract_rate_type == 0 ? "Weekend" : "Called Back");
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("J{$current_row}", " " . $contract_rate_type == 0 ? "Holiday" : "Called In");
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("K{$current_row}", " Total");
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("L{$current_row}", " Amount Paid");
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->applyFromArray($this->left);

                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:F{$current_row}")->applyFromArray($this->Total_shaded_style_summary);
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$current_row}")->applyFromArray($this->Total__blue_shaded_style);
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}:J{$current_row}")->applyFromArray($this->Total_shaded_style);
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->applyFromArray($this->Total_shaded_style);
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$current_row}")->applyFromArray($this->Total__blue_shaded_style);
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$current_row}")->applyFromArray($this->grid_style_left);
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:G{$current_row}")->applyFromArray($this->grid_style_right);
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}:J{$current_row}")->applyFromArray($this->grid_style_right);
                                            $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->applyFromArray($this->grid_style_right);

                                            $physician_first_row = $current_row;
                                            //$workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("B{$current_row}", $physician->last_name . " , " . $physician->first_name);
                                            //$workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                            //$workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->getFont()->setBold(true);
                                            //$workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:L{$current_row}")->applyFromArray($this->grid_style);
                                            $current_row++;
                                            $flag = 1;
                                        }
                                        /*elseif ($flag == 1){
                                                $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}");
                                                $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->applyFromArray($this->left);
                                            }*/

                                        $physician_weekday_rate += $contract_rate_type == 0 ? $log_hours_week * $weekday_rate : $log_hours_week * $on_call_rate;
                                        $physician_weekend_rate += $contract_rate_type == 0 ? $log_hours_weekend * $weekend_rate : $log_hours_weekend * $called_back_rate;
                                        $physician_holiday_rate += $contract_rate_type == 0 ? $log_hours_holiday * $holiday_rate : $log_hours_holiday * $called_in_rate;

                                        $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}");
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("D{$current_row}", " " . number_format((float)$physician_weekday_total += $contract_rate_type == 0 ? $week : $on_call, 1));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("E{$current_row}", " " . number_format((float)$physician_weekend_total += $contract_rate_type == 0 ? $weekend : $called_back, 1));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("F{$current_row}", " " . number_format((float)$physician_holiday_total += $contract_rate_type == 0 ? $holiday : $called_in, 1));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("G{$current_row}", " " . number_format((float)$physician_weekday_total + $physician_weekend_total + $physician_holiday_total, 1));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("H{$current_row}", "$" . number_format((float)$physician_weekday_rate, 2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("I{$current_row}", "$" . number_format((float)$physician_weekend_rate, 2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("J{$current_row}", "$" . number_format((float)$physician_holiday_rate, 2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("K{$current_row}", "$" . number_format((float)$physician_weekday_rate + $physician_weekend_rate + $physician_holiday_rate, 2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("L{$current_row}", "$" . number_format((float)$amount_paid_physician, 2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")->applyFromArray($this->left);
                                        //$workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:K{$current_row}")->applyFromArray($this->grid_style);
                                        //$workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->applyFromArray($this->grid_style);
                                        //cc
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:F{$current_row}")->applyFromArray($this->Total_shaded_style_summary);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$current_row}")->applyFromArray($this->Total__blue_shaded_style);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}:J{$current_row}")->applyFromArray($this->Total_shaded_style);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->applyFromArray($this->Total_shaded_style);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$current_row}")->applyFromArray($this->Total__blue_shaded_style);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$current_row}")->applyFromArray($this->grid_style_left);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:G{$current_row}")->applyFromArray($this->grid_style_right);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}:J{$current_row}")->applyFromArray($this->grid_style_right);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->applyFromArray($this->grid_style_right);
                                        //}
                                        /*$workbook->setActiveSheetIndex($sheet_index)->setCellValue("D{$physician_totals_row}", " ". number_format((float)$physician_weekday_total += $week,1));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("E{$physician_totals_row}", " ". number_format((float)$physician_weekend_total += $weekend,1));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("F{$physician_totals_row}", " ". number_format((float)$physician_holiday_total += $holiday,1));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("G{$physician_totals_row}", " ". number_format((float)$physician_weekday_total + $physician_weekend_total + $physician_holiday_total,1));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("H{$physician_totals_row}", "$" . number_format((float)$physician_weekday_rate += $week * $weekday_rate,2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("I{$physician_totals_row}", "$" . number_format((float)$physician_weekend_rate += $weekend * $weekend_rate,2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("J{$physician_totals_row}", "$" . number_format((float)$physician_holiday_rate += $holiday * $holiday_rate,2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("K{$physician_totals_row}", "$" . number_format((float)$physician_weekday_rate + $physician_weekend_rate + $physician_holiday_rate,2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("L{$physician_totals_row}", "$" . number_format((float)$amount_paid,2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        //$workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->grid_style);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$physician_totals_row}:M{$physician_totals_row}")->applyFromArray($this->Total__blue_shaded_style);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$physician_totals_row}:G{$physician_totals_row}")->applyFromArray($this->grid_style_right);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$physician_totals_row}:J{$physician_totals_row}")->applyFromArray($this->grid_style_right);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$physician_totals_row}")->applyFromArray($this->grid_style_right);*/
                                        //$workbook->setActiveSheetIndex($sheet_index)->getStyle("M{$physician_totals_row}")->applyFromArray($this->Total_shaded_style_cytpm);

                                        /*	$duration_weekday = 0;
                                            $duration_weekend = 0;
                                            $duration_holiday = 0;*/
                                        //} else {
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("D{$physician_totals_row}", " " . number_format((float)$physician_weekday_total, 1));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("E{$physician_totals_row}", " " . number_format((float)$physician_weekend_total, 1));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("F{$physician_totals_row}", " " . number_format((float)$physician_holiday_total, 1));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("G{$physician_totals_row}", " " . number_format((float)$physician_weekday_total, 1));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("H{$physician_totals_row}", "$" . number_format((float)$physician_weekday_rate, 2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("I{$physician_totals_row}", "$" . number_format((float)$physician_weekend_rate, 2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("J{$physician_totals_row}", "$" . number_format((float)$physician_holiday_rate, 2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("K{$physician_totals_row}", "$" . number_format((float)$physician_weekday_rate, 2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheet_index)->setCellValue("L{$physician_totals_row}", "$" . number_format((float)$amount_paid_physician, 2));
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$physician_totals_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        //$workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->grid_style);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$physician_totals_row}:L{$physician_totals_row}")->applyFromArray($this->Total__blue_shaded_style);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$physician_totals_row}:G{$physician_totals_row}")->applyFromArray($this->grid_style_right);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$physician_totals_row}:J{$physician_totals_row}")->applyFromArray($this->grid_style_right);
                                        $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$physician_totals_row}")->applyFromArray($this->grid_style_right);
                                        //$workbook->setActiveSheetIndex($sheet_index)->getStyle("M{$physician_totals_row}")->applyFromArray($this->Total_shaded_style_cytpm);
                                        //}
                                        if ($contract_rate_type == 0) {
                                            $pract_total_weekday = $pract_total_weekday + $week;
                                            $pract_total_weekend = $pract_total_weekend + $weekend;
                                            $pract_total_holiday = $pract_total_holiday + $holiday;
                                            $pract_total_weekday_amount = $pract_total_weekday_amount + ($log_hours_week * $weekday_rate);
                                            $pract_total_weekend_amount = $pract_total_weekend_amount + ($log_hours_weekend * $weekend_rate);
                                            $pract_total_holiday_amount = $pract_total_holiday_amount + ($log_hours_holiday * $holiday_rate);
                                        } else {
                                            $pract_total_weekday = $pract_total_weekday + $on_call;
                                            $pract_total_weekend = $pract_total_weekend + $called_back;
                                            $pract_total_holiday = $pract_total_holiday + $called_in;
                                            $pract_total_weekday_amount = $pract_total_weekday_amount + ($log_hours_week * $on_call_rate);
                                            $pract_total_weekend_amount = $pract_total_weekend_amount + ($log_hours_weekend * $called_back_rate);
                                            $pract_total_holiday_amount = $pract_total_holiday_amount + ($log_hours_holiday * $called_in_rate);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $this->writeCYTPMPhysicianMiddle($workbook, $sheet_index, $physician_first_row, $current_row, $physician->first_name, $physician->last_name);
                    //reset physicians totals
                    $physician_weekday_total = 0;
                    $physician_weekend_total = 0;
                    $physician_holiday_total = 0;
                    $physician_weekday_rate = 0;
                    $physician_weekend_rate = 0;
                    $physician_holiday_rate = 0;
                }
                $practice_duration_weekday_sum += $pract_total_weekday;
                $practice_duration_weekday_sum_amount += $pract_total_weekday_amount;
                $practice_duration_weekend_sum += $pract_total_weekend;
                $practice_duration_weekend_sum_amount += $pract_total_weekend_amount;
                $practice_duration_holiday_sum += $pract_total_holiday;
                $practice_duration_holiday_sum_amount += $pract_total_holiday_amount;
                $amount_weekday += $pract_total_weekday_amount;
                $amount_weekend += $pract_total_weekend_amount;
                $amount_holiday += $pract_total_holiday_amount;
                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("D{$practice_row}", " " . number_format((float)$practice_duration_weekday_sum, 1));
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("E{$practice_row}", " " . number_format((float)$practice_duration_weekend_sum, 1));
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("F{$practice_row}", " " . number_format((float)$practice_duration_holiday_sum, 1));
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("G{$practice_row}", " " . number_format((float)$practice_duration_weekday_sum + $practice_duration_weekend_sum + $practice_duration_holiday_sum, 1));
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("H{$practice_row}", "$" . number_format((float)$practice_duration_weekday_sum_amount, 2));
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("I{$practice_row}", "$" . number_format((float)$practice_duration_weekend_sum_amount, 2));
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("J{$practice_row}", "$" . number_format((float)$practice_duration_holiday_sum_amount, 2));
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("K{$practice_row}", "$" . number_format((float)$practice_duration_weekday_sum_amount + $practice_duration_weekend_sum_amount + $practice_duration_holiday_sum_amount, 2));
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $workbook->setActiveSheetIndex($sheet_index)->setCellValue("L{$practice_row}", "$" . number_format((float)$amount_paid_practice, 2));
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$practice_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                //$workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:M{$current_row}")->applyFromArray($this->grid_style);
                $practice_duration_weekday_sum_p = 0;
                $practice_duration_weekend_sum_p = 0;
                $practice_duration_holiday_sum_p = 0;
            }
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("D{$agreement_row}", " " . number_format((float)$duration_weekday, 1));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("E{$agreement_row}", " " . number_format((float)$duration_weekend, 1));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("F{$agreement_row}", " " . number_format((float)$duration_holiday, 1));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("G{$agreement_row}", " " . number_format((float)$duration_weekday + $duration_weekend + $duration_holiday, 1));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("H{$agreement_row}", "$" . number_format((float)$amount_weekday, 2));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("I{$agreement_row}", "$" . number_format((float)$amount_weekend, 2));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("J{$agreement_row}", "$" . number_format((float)$amount_holiday, 2));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("K{$agreement_row}", "$" . number_format($amount_weekday + $amount_weekend + $amount_holiday, 2));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $workbook->setActiveSheetIndex($sheet_index)->setCellValue("L{$agreement_row}", "$" . number_format((float)$amount_paid_agreement, 2));
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:L{$current_row}")->applyFromArray($this->bottom_row_style);
            return $current_row;
        }
    }

    public function write_summary($workbook, $agreementPassed, $arguments, $current_row, $sheet_index, $practices)
    {
        $agreement = Agreement::findOrFail($agreementPassed->id);
        if ($agreement->archived == 0) {
            $current_row++;
            $agreement_date = new DateTime($agreementPassed->start_date);
            $agreement_start_date = $agreementPassed->start_date;
            $agreement_date1 = new DateTime($agreementPassed->end_date);
            $agreement_end_date = $agreementPassed->end_date;

            $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:K{$current_row}")->setCellValue("B{$current_row}", $agreement->name)
                ->getStyle("B{$current_row}:K{$current_row}")
                ->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:K{$current_row}")->applyFromArray($this->contract_style);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:K{$current_row}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $current_row++;

            $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:K{$current_row}")->
            setCellValue("B{$current_row}", "Contract Period : " . format_date($agreement->start_date) . " - " . format_date($agreement->end_date))
                ->getStyle("B{$current_row}:K{$current_row}")
                ->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:K{$current_row}")->applyFromArray($this->contract_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:K{$current_row}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $current_row++;

            $agreement_row = $current_row;

            $workbook->setActiveSheetIndex($sheet_index)
                ->mergeCells("B{$current_row}:C{$current_row}")
                ->setCellValue("B{$current_row}", $agreement->name);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:C{$current_row}")->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:K{$current_row}")->applyFromArray($this->summary_total_style);

            $start = strtotime($agreement->start_date);
            $end = strtotime($agreement->end_date);

            $today = strtotime(date("Y-m-t", strtotime("-1 month")));

            $daysOnCallWeekdayTotal = 0;
            $daysOnCallWeekendTotal = 0;
            $daysOnCallHolidayTotal = 0;

            $weekdayPaymentTotal = 0;
            $weekendPaymentTotal = 0;
            $holidayPaymentTotal = 0;
            $agreement_start_month_start_date = strtotime(date('m/01/Y', $start));
            $agreement_end_month_end_date = strtotime(date('m/t/Y', $end));

            while ($agreement_start_month_start_date <= $today) {
                $month_start_date = date('m/01/Y', $today);
                $month_end_date = date('m/t/Y', $today);

                if ($agreement_end_month_end_date >= $today) {

                    $practiceWeekdayDurationTotal = 0;
                    $practiceWeekendDurationTotal = 0;
                    $practiceHolidayDurationTotal = 0;

                    $practiceWeekdayPaymentTotal = 0;
                    $practiceWeekendPaymentTotal = 0;
                    $practiceHolidayPaymentTotal = 0;

                    foreach ($practices as $practice) {
                        $physicians = $this->queryPhysicians($arguments->hospital_id, $agreement->id, $practice->id);

                        foreach ($physicians as $physician) {

                            $weekdayDuration = 0;
                            $weekendDuration = 0;
                            $holidayDuration = 0;

                            $action_array = array();

                            $physician_rates = DB::table('contracts')
                                ->select('contracts.weekday_rate', 'contracts.weekend_rate', 'contracts.holiday_rate')
                                ->where('contracts.physician_id', '=', $physician->id)//$physician->id
                                ->where('contracts.agreement_id', '=', $agreement->id)//$physician->id
                                ->where('contracts.contract_type_id', '=', ContractType::ON_CALL)
                                ->first();

                            //if($physician_rates->weekday_rate!=0)
                            $weekday_rate = $physician_rates->weekday_rate;
                            //if($physician_rates->weekend_rate!=0)
                            $weekend_rate = $physician_rates->weekend_rate;
                            //if($physician_rates->holiday_rate!=0)
                            $holiday_rate = $physician_rates->holiday_rate;

                            $fetch_contract_id = Contract::where('agreement_id', '=', $agreement->id)
                                ->where("contract_type_id", "=", ContractType::ON_CALL)
                                ->where('physician_id', '=', $physician->id)
                                ->pluck('id');

                            $physician_logs = DB::table('physician_logs')->select('duration', 'action_id', 'date')
                                ->where('physician_id', '=', $physician->id)//$physician->id
                                ->where('contract_id', '=', $fetch_contract_id)//$physician->id
                                ->where('practice_id', '=', $practice->id)//$practice->id
                                ->whereBetween("date", [mysql_date($month_start_date), mysql_date($month_end_date)])
                                ->get();

                            if (count($physician_logs) > 0) {
                                foreach ($physician_logs as $physician_log) {
                                    $action_names = DB::table('actions')->select('name')
                                        ->where('id', '=', $physician_log->action_id)//$physician->id
                                        ->first();
                                    $action_array[] = [
                                        "duration" => $physician_log->duration,
                                        "action_name" => $action_names->name];
                                }
                                foreach ($action_array as $action_data) {
                                    if (strlen(strstr(strtoupper($action_data['action_name']), "WEEKDAY")) > 0) {
                                        $weekdayDuration += $action_data['duration'];
                                    }
                                    if (strlen(strstr(strtoupper($action_data['action_name']), "WEEKEND")) > 0) {
                                        $weekendDuration += $action_data['duration'];
                                    }
                                    if (strlen(strstr(strtoupper($action_data['action_name']), "HOLIDAY")) > 0) {
                                        $holidayDuration += $action_data['duration'];
                                    }
                                }
                                $daysOnCallWeekdayTotal += $weekdayDuration;
                                $daysOnCallWeekendTotal += $weekendDuration;
                                $daysOnCallHolidayTotal += $holidayDuration;

                                $practiceWeekdayDurationTotal += $weekdayDuration;
                                $practiceWeekendDurationTotal += $weekendDuration;
                                $practiceHolidayDurationTotal += $holidayDuration;

                                $practiceWeekdayPaymentTotal += ($weekdayDuration * $weekday_rate);
                                $practiceWeekendPaymentTotal += ($weekendDuration * $weekend_rate);
                                $practiceHolidayPaymentTotal += ($holidayDuration * $holiday_rate);
                            }
                        }
                    }
                    $weekdayPaymentTotal += $practiceWeekdayPaymentTotal;
                    $weekendPaymentTotal += $practiceWeekendPaymentTotal;
                    $holidayPaymentTotal += $practiceHolidayPaymentTotal;

                    $current_row++;
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:K{$current_row}")->applyFromArray($this->grid_style);
                    $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")->setCellValue("B{$current_row}", date('F', $today));
                    /*->getStyle("B{$current_row}:C{$current_row}")
                    ->getFont()->setBold(true);*/
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:C{$current_row}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $workbook->setActiveSheetIndex($sheet_index)->setCellValue("D{$current_row}", " " . number_format((float)$practiceWeekdayDurationTotal, 1));
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheet_index)->setCellValue("E{$current_row}", " " . number_format((float)$practiceWeekendDurationTotal, 1));
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheet_index)->setCellValue("F{$current_row}", " " . number_format((float)$practiceHolidayDurationTotal, 1));
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheet_index)->setCellValue("G{$current_row}", " " . number_format((float)$practiceWeekdayDurationTotal + $practiceWeekendDurationTotal + $practiceHolidayDurationTotal, 1));
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}:F{$current_row}")->applyFromArray($this->Total_shaded_style);
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$current_row}")->applyFromArray($this->Total__blue_shaded_style);

                    $workbook->setActiveSheetIndex($sheet_index)->setCellValue("H{$current_row}", "$" . number_format((float)$practiceWeekdayPaymentTotal, 2));
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $workbook->setActiveSheetIndex($sheet_index)->setCellValue("I{$current_row}", "$" . number_format((float)$practiceWeekendPaymentTotal, 2));
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $workbook->setActiveSheetIndex($sheet_index)->setCellValue("J{$current_row}", "$" . number_format((float)$practiceHolidayPaymentTotal, 2));
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $workbook->setActiveSheetIndex($sheet_index)->setCellValue("K{$current_row}", "$" . number_format((float)$practiceWeekdayPaymentTotal + $practiceWeekendPaymentTotal + $practiceHolidayPaymentTotal, 2));
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$current_row}:J{$current_row}")->applyFromArray($this->Total_shaded_style);
                    //$workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$current_row}")->applyFromArray($this->Total_shaded_style);
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$current_row}")->applyFromArray($this->Total__blue_shaded_style);

                    $workbook->setActiveSheetIndex($sheet_index)->setCellValue("D{$agreement_row}", " " . number_format((float)$daysOnCallWeekdayTotal, 1));
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$agreement_row}:F{$agreement_row}")->applyFromArray($this->Total_shaded_style);
                    $workbook->setActiveSheetIndex($sheet_index)->setCellValue("E{$agreement_row}", " " . number_format((float)$daysOnCallWeekendTotal, 1));
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("E{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheet_index)->setCellValue("F{$agreement_row}", " " . number_format((float)$daysOnCallHolidayTotal, 1));
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheet_index)->setCellValue("G{$agreement_row}", " " . number_format((float)$daysOnCallWeekdayTotal + $daysOnCallWeekendTotal + $daysOnCallHolidayTotal, 1));
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("G{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $workbook->setActiveSheetIndex($sheet_index)->setCellValue("H{$agreement_row}", " $" . number_format((float)$weekdayPaymentTotal, 2));
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("H{$agreement_row}:J{$agreement_row}")->applyFromArray($this->Total_shaded_style);
                    $workbook->setActiveSheetIndex($sheet_index)->setCellValue("I{$agreement_row}", " $" . number_format((float)$weekendPaymentTotal, 2));
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("I{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $workbook->setActiveSheetIndex($sheet_index)->setCellValue("J{$agreement_row}", " $" . number_format((float)$holidayPaymentTotal, 2));
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $workbook->setActiveSheetIndex($sheet_index)->setCellValue("K{$agreement_row}", " $" . number_format((float)$weekdayPaymentTotal + $weekendPaymentTotal + $holidayPaymentTotal, 2));
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$agreement_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$agreement_row}:K{$agreement_row}")->getFont()->setBold(true);
                }
                $today = strtotime("-1 month", $today);
                if ($month_start_date == date('m/01/Y', $today)) {
                    $today = strtotime("-1 month", $today);
                }
            }
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("B{$current_row}:k{$current_row}")->applyFromArray($this->bottom_row_style);
            return $current_row;
        }
    }

    public function queryPractices($hospital_id, $agreement_id, $physician_ids)
    {
        if (is_practice_manager()) {
            $physcian = Physician::findOrFail($physician_ids[0]);
            //drop column practice_id from table 'physicians' changes by 1254 : codereview
            $physicianpractices = PhysicianPractices::where('physician_id', '=', $physcian->id)
                ->where('hospital_id', '=', $hospital_id)
                ->whereRaw("start_date <= now()")
                ->whereRaw("end_date >= now()")
                ->whereNull("deleted_at")
                ->orderBy("start_date", "desc")
                ->pluck('practice_id')->toArray();

            if (empty($physicianpractices)) {
                return Redirect::back()->with([
                    'error' => Lang::get('physicians.practice_enddate_error')
                ]);
            }

            //end-drop column practice_id from table 'physicians' changes by 1254
            return $query = DB::table('practices')
                ->select(DB::raw("distinct(practices.id) as id,practices.name"))
                //->select('practices.id', 'practices.name')
                ->join('physician_practice_history', 'physician_practice_history.practice_id', '=', 'practices.id')
                ->join('contracts', 'contracts.physician_id', '=', 'physician_practice_history.physician_id')
                ->join('hospitals', 'hospitals.id', '=', 'practices.hospital_id')
                ->join("agreements", function ($join) {
                    $join->on("agreements.id", "=", "contracts.agreement_id")
                        ->on("practices.hospital_id", "=", "agreements.hospital_id");
                })
                ->where('hospitals.id', '=', $hospital_id)
                ->where('agreements.archived', '=', 0)
                ->where('contracts.archived', '=', 0)
                ->where('agreements.id', '=', $agreement_id)
                //drop column practice_id from table 'physicians' changes by 1254
                ->where('practices.id', '=', $physicianpractices[0])
                ->get();
        } else {
            return $query = DB::table('practices')
                ->select(DB::raw("distinct(practices.id) as id,practices.name"))
                //->select('practices.id', 'practices.name')
                ->join('physician_practice_history', 'physician_practice_history.practice_id', '=', 'practices.id')
                ->join('contracts', 'contracts.physician_id', '=', 'physician_practice_history.physician_id')
                ->join('hospitals', 'hospitals.id', '=', 'practices.hospital_id')
                ->join("agreements", function ($join) {
                    $join->on("agreements.id", "=", "contracts.agreement_id")
                        ->on("practices.hospital_id", "=", "agreements.hospital_id");
                })
                ->where('hospitals.id', '=', $hospital_id)
                ->where('agreements.archived', '=', 0)
                ->where('contracts.archived', '=', 0)
                ->where('agreements.id', '=', $agreement_id)
                ->get();
        }
    }

    public function queryPhysicians($hospital_id, $agreement_id, $practice_id)
    {
        return $query = DB::table('physician_practice_history')
            ->select(DB::raw("distinct(physician_practice_history.physician_id) as id,physician_practice_history.first_name,physician_practice_history.last_name"))
            //->select('physicians.id', 'physicians.first_name', 'physicians.last_name')
            ->join('practices', 'practices.id', '=', 'physician_practice_history.practice_id')
            ->join('contracts', 'contracts.physician_id', '=', 'physician_practice_history.physician_id')
            ->join('hospitals', 'hospitals.id', '=', 'practices.hospital_id')
            ->join("agreements", function ($join) {
                $join->on("agreements.id", "=", "contracts.agreement_id")
                    ->on("practices.hospital_id", "=", "agreements.hospital_id");
            })
            ->where('hospitals.id', '=', $hospital_id)
            ->where('agreements.archived', '=', 0)
            ->where('contracts.archived', '=', 0)
            ->where('contracts.contract_type_id', '=', ContractType::ON_CALL)
            ->where('agreements.id', '=', $agreement_id)
            ->where('practices.id', '=', $practice_id)
            ->get();
    }

    protected function parseArguments()
    {
        $result = new StdClass;
        $result->hospital_id = $this->argument('hospital');
        $result->contract_type = $this->argument('contract_type');
        $result->physician_ids = $this->argument('physicians');
        $result->physicians = explode(',', $result->physician_ids);
        $result->agreements = parent::parseArguments();
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
            ["finalized", InputArgument::REQUIRED, "Report Finalized."]
        ];
    }

    protected function getOptions()
    {
        return [];
    }
}