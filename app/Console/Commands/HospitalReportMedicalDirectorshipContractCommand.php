<?php
namespace App\Console\Commands;

use App\HospitalReport;
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
use Log;
use App\Hospital;
use App\Agreement;
use App\Physician;
use App\ContractType;
use App\PhysicianPracticeHistory;
use App\ContractName;
use App\Practice;
use App\PracticeManagerReport;
use function App\Start\is_practice_manager;
use function App\Start\hospital_report_path;
use function App\Start\practice_report_path;

class HospitalReportMedicalDirectorshipContractCommand extends ReportingCommand
{
    protected $name = "reports:hospitalMedicalDirectorshipContract";
    protected $description = "Generates a DYNAFIOS hospital report.";
    protected $contract_length = 0;
    protected $contract_count = 0;

    private $cell_style = [
        'borders' => [
            'outline' => ['borderStyle' => Border::BORDER_THIN],
            'inside' => ['borderStyle' => Border::BORDER_THIN],
            'left' => ['borderStyle' => Border::BORDER_MEDIUM]
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
            'right' => ['borderStyle' => Border::BORDER_THIN],
            'bottom' => ['borderStyle' => Border::BORDER_MEDIUM]
        ]
    ];

    private $bottom_style = [
        'borders' => [
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
            'allborders' => ['borderStyle' => Border::BORDER_MEDIUM,'color' => ['rgb' => '000000']],
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

    private $shaded_style_fmv = [
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'color' => ['rgb' => 'eeece1']
        ],
        'borders' => [
            'left' => ['borderStyle' => Border::BORDER_MEDIUM],
            'right' => ['borderStyle' => Border::BORDER_MEDIUM],
            'top' => ['borderStyle' => Border::BORDER_THIN]
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

    private $CYTPM_contract_period_bottom_style = [
        'borders' => [
            'bottom' => ['borderStyle' => Border::BORDER_MEDIUM,'color' => ['rgb' => 'FFFFFF']]
        ]

    ];
    public function __invoke()
    {
        $arguments = $this->parseArguments();

        $now = Carbon::now();
		$timezone = $now->timezone->getName();
		$timestamp = format_date((exec('time /T')), "h:i A");

        $hospital = Hospital::findOrFail($arguments->hospital_id);
        // $workbook = $this->loadTemplate('medical_directorship_hospital_report.xlsx');

        $reader = IOFactory::createReader("Xlsx");//Load template using phpSpreadsheet
		$workbook = $reader->load(storage_path()."/reports/templates/medical_directorship_hospital_report.xlsx");

        $report_header = '';
        $report_header .= strtoupper($hospital->name) . "\n";
        $report_header .= "Period Report\n";
        $report_header .= "Period: " . $arguments->start_date->format("m/d/Y") . " - " . $arguments->end_date->format("m/d/Y")  . "\n";
        $report_header .= "Run Date: " . with(new DateTime('now'))->format("m/d/Y");

        $report_header_ytm = '';
        $report_header_ytm .= strtoupper($hospital->name) . "\n";
        $report_header_ytm .= "Contract Year To Prior Month Report\n";
        $report_header_ytm .= "Period: " . $arguments->start_date->format("m/d/Y") . " - " . $arguments->end_date->format("m/d/Y")  . "\n";
        $report_header_ytm .= "Run Date: " . with(new DateTime('now'))->format("m/d/Y");

        $report_header_ytd = '';
        $report_header_ytd .= strtoupper($hospital->name) . "\n";
        $report_header_ytd .= "Contract Year To Date Report\n";
        $report_header_ytd .= "Period: " . $arguments->start_date->format("m/d/Y") . " - " . $arguments->end_date->format("m/d/Y")  . "\n";
        $report_header_ytd .= "Run Date: " . with(new DateTime('now'))->format("m/d/Y");

        $workbook->setActiveSheetIndex(0)->setCellValue('B2', $report_header);
        $workbook->setActiveSheetIndex(1)->setCellValue('B2', $report_header_ytm);

        $period_index = 4;
        $ytm_index = 4;
        $ytd_index = 4;
        //print_r($arguments->agreements);die;
        $this ->contract_length = count($arguments->agreements);
        $this ->contract_count = 0;
        foreach ($arguments->agreements as $agreement) {
            $contracts = $this->queryContracts($agreement, $agreement->start_date, $agreement->end_date, $arguments->contract_type, $arguments->physicians);
            if(is_practice_manager()){
                if(count($arguments->physicians) > 0) {
                    $p = Physician::findOrFail($arguments->physicians[0]);
                    $report_practice_id = $p->practice_id;
                }
            }
            $period_index = $this->writeData($workbook, 0, $period_index, $contracts->period,$agreement->start_date,$arguments->finalized, $agreement->end_date, $contracts->agreement_start_date, $contracts->agreement_end_date, $arguments->physicians, $agreement);
            //echo $ytm_index;
            $ytm_index = $this->writeDataCYTPM($workbook, 1, $ytm_index, $contracts->year_to_month,$agreement->start_date,$arguments->physicians, $agreement->end_date, $contracts->agreement_start_date, $contracts->agreement_end_date,$agreement);
            $this ->contract_count ++;
        }

        //die;

        //die;


        if(is_practice_manager()) {
            $report_practice = Practice::findOrFail($report_practice_id);
            $report_path = practice_report_path($report_practice);
            $report_name = $report_practice->name;
        }else{
            $report_path = hospital_report_path($hospital);
            $report_name = $hospital->name;
        }

        // $report_filename = "report_" . date('mdYhis') . ".xlsx";
        $report_filename = "report_" . $report_name . "_" . date('mdY') . "_" . str_replace(":", "", $timestamp) . "_" . $timezone . ".xlsx";

        if (!file_exists($report_path)) {
            mkdir($report_path, 0777, true);
        }

        $writer = IOFactory::createWriter($workbook, 'Xlsx');
        $writer->save("{$report_path}/{$report_filename}");

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

        $this->success('hospitals.generate_report_success', $hospital_report->id, $hospital_report->filename);
    }

    protected function queryContracts($agreement_data, $start_date, $end_date, $contract_type_id, $physicians)
    {
        $agreement = Agreement::findOrFail($agreement_data->id);

        $start_date = mysql_date($start_date);
        $end_date = mysql_date($end_date);
        //echo $agreement->start_date;die;
        $contract_month = months($agreement->start_date, 'now');
        $contract_term = months($agreement->start_date, $agreement->end_date);
        $log_range = months($start_date, $end_date);

        if ($contract_month > $contract_term) {
            $contract_month = $contract_term;
        }
        $i =0;
        $count_py = 0;
        $physicians1 = array();
        $practices = array();
        $py_data = DB::table('physician_practice_history')->select(
            DB::raw("practices.name as practice_name"),
            DB::raw("practices.id as practice_id"),
            DB::raw("physician_practice_history.physician_id as physician_id"))
            ->join('practices', 'practices.id', '=', 'physician_practice_history.practice_id')
            ->orderBy('practices.id', "asc")
            ->orderBy('physician_practice_history.physician_id', 'asc')
            ->whereIn("physician_practice_history.physician_id", $physicians)->get();
        //print_r($py_data);die;
        foreach ($py_data as $py_data1) {
            //echo $py_data1->physician_id;
            $physicians1[$count_py] = $py_data1->physician_id;
            $practices[$count_py] = $py_data1->practice_id;
            $count_py++;
            # code...
        }

        foreach ($physicians1 as $key=>$physician) {
            $physician_details = Physician::withTrashed()->findOrFail($physician);
            $period_query[$i] = DB::table('physician_logs')->select(
                DB::raw("practices.name as practice_name"),
                DB::raw("practices.id as practice_id"),
                DB::raw("physician_practice_history.physician_id as physician_id"),
                DB::raw("concat(physician_practice_history.last_name, ', ', physician_practice_history.first_name) as physician_name"),
                DB::raw("specialties.name as specialty_name"),
                DB::raw("contracts.id as contract_id"),
                DB::raw("contracts.contract_name_id as contract_name_id"),
                DB::raw("contracts.contract_type_id as contract_type_id"),
                DB::raw("contract_types.name as contract_name"),
                DB::raw("contracts.min_hours as min_hours"),
                DB::raw("contracts.max_hours as max_hours"),
                DB::raw("contracts.expected_hours as expected_hours"),
                DB::raw("contracts.rate as rate"),
                DB::raw("contracts.manual_contract_end_date as manual_contract_end_date"),
                DB::raw("sum(physician_logs.duration) as worked_hours"),
                DB::raw("'{$contract_month}' as contract_month"),
                DB::raw("'{$contract_term}' as contract_term"),
                DB::raw("'{$log_range}' as log_range"),
                DB::raw("contracts.annual_cap as annual_cap")
            )
                ->leftJoin('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                ->leftJoin('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                ->leftJoin('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                ->leftJoin('physician_practice_history', 'physician_practice_history.physician_id', '=', 'contracts.physician_id')
                ->leftJoin('specialties', 'specialties.id', '=', 'physician_practice_history.specialty_id')
                ->leftJoin("practices", function($join)
                {
                    $join->on("physician_practice_history.practice_id", "=", "practices.id")
                        ->on("practices.hospital_id", "=", "agreements.hospital_id");
                })
                ->where('contracts.agreement_id', '=', $agreement->id)
                ->where("physician_practice_history.physician_id", "=", $physician)
                ->where("practices.id", "=", $practices[$key])
                ->where("physician_logs.practice_id", "=", $practices[$key])
                /*->where("physician_practice_history.start_date", "<=", $end_date)*/
                ->whereBetween('physician_logs.date', array($start_date, $end_date))
                ->where("contract_types.id", "=", ContractType::MEDICAL_DIRECTORSHIP);
                if($physician_details->deleted_at == Null) {
                    $period_query[$i] = $period_query[$i]->where('physician_logs.deleted_at', '=', Null);
                }

                $period_query[$i] = $period_query[$i]->groupBy('physician_practice_history.physician_id', 'agreements.id', 'contracts.id')
                ->orderBy('contract_types.name', 'asc')
                ->orderBy('practices.name', 'asc')
                ->orderBy('physician_practice_history.last_name', 'asc')
                ->orderBy('physician_practice_history.first_name', 'asc');

            $period_query2[$i] = DB::table('physician_practice_history')->select(
                DB::raw("practices.name as practice_name"),
                DB::raw("practices.id as practice_id"),
                DB::raw("physician_practice_history.physician_id as physician_id"),
                DB::raw("concat(physician_practice_history.last_name, ', ', physician_practice_history.first_name) as physician_name"),
                DB::raw("specialties.name as specialty_name"),
                DB::raw("contracts.id as contract_id"),
                DB::raw("contracts.contract_name_id as contract_name_id"),
                DB::raw("contracts.contract_type_id as contract_type_id"),
                DB::raw("contract_types.name as contract_name"),
                DB::raw("contracts.min_hours as min_hours"),
                DB::raw("contracts.max_hours as max_hours"),
                DB::raw("contracts.expected_hours as expected_hours"),
                DB::raw("contracts.rate as rate"),
                DB::raw("contracts.manual_contract_end_date as manual_contract_end_date"),
                DB::raw("0 as worked_hours"),
                DB::raw("'{$contract_month}' as contract_month"),
                DB::raw("'{$contract_term}' as contract_term"),
                DB::raw("'{$log_range}' as log_range"),
                DB::raw("contracts.annual_cap as annual_cap")
            )
                ->leftJoin('contracts', 'contracts.physician_id', '=', 'physician_practice_history.physician_id')
                ->leftJoin('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                ->leftJoin('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                ->leftJoin('specialties', 'specialties.id', '=', 'physician_practice_history.specialty_id')
                ->leftJoin("practices", function($join)
                {
                    $join->on("physician_practice_history.practice_id", "=", "practices.id")
                        ->on("practices.hospital_id", "=", "agreements.hospital_id");
                })
                ->where('contracts.agreement_id', '=', $agreement->id)
                ->where("physician_practice_history.physician_id", "=", $physician)
                ->where("practices.id", "=", $practices[$key])
                /*->where("physician_practice_history.start_date", "<=", $end_date)*/
                ->where("contract_types.id", "=", ContractType::MEDICAL_DIRECTORSHIP)
                ->groupBy('physician_practice_history.physician_id', 'agreements.id', 'contracts.id')
                ->orderBy('contract_types.name', 'asc')
                ->orderBy('practices.name', 'asc')
                ->orderBy('physician_practice_history.last_name', 'asc')
                ->orderBy('physician_practice_history.first_name', 'asc');
            $i++;
        }


        $date_start_agreement = DB::table('agreements')->where("id","=",$agreement->id)->first();
        //print_r($date_start_agreement);die;
        $start_date_ytm = $date_start_agreement->start_date;
        if($end_date > $date_start_agreement->end_date)
        {
            $end_date = date('Y-m-d',strtotime($date_start_agreement->end_date));
        }

        $ts1 = strtotime($start_date_ytm);
        $ts2 = strtotime($end_date);

        $year1 = date('Y', $ts1);
        $year2 = date('Y', $ts2);

        $month1 = date('m', $ts1);
        $month2 = date('m', $ts2);

        $diff = (($year2 - $year1) * 12) + ($month2 - $month1) + 1;

        //$end_month = date('m',strtotime($end_date));

        //print_r($end_month);die;
        for($i=1;$i<=$diff;$i++)
        {
            //$start_month_date = $i."-".date('Y', strtotime($end_date));
            if($month1 > 12)
            {
                //echo "hi";
                $m = $month1 - (12*floor($month1/12));
                //echo $m;
                $y = $year1 + floor($month1/12);
            }
            else
            {
                $m = $month1;
                $y = $year1;
            }
            $month1++;

            //echo $month1."        ";
            $end_month_date = mysql_date(date('d-m-Y', strtotime("01-".$m."-".$y)));

            //$start_month_date = "01-".$i."-".date('Y', strtotime($end_date));
            //$first_date_month = mysql_date($start_month_date);
            //$first_date_month = mysql_date("01-".$end_month_date);
            //echo $end_month_date;
            $last_date_month = mysql_date(date('t-F-Y', strtotime($end_month_date)));
            //$contract_month = months($agreement->start_date, $last_date_month);
            if(date('Y', strtotime($last_date_month)) == date('Y', strtotime($agreement->start_date))) {
                $contract_month = date('m', strtotime($last_date_month))-date('m', strtotime($agreement->start_date))+1;
            }elseif(date('Y', strtotime($agreement->start_date)) < date('Y', strtotime($last_date_month))){
                $yearDiff = date('Y', strtotime($last_date_month)) - date('Y', strtotime($agreement->start_date));
                $contract_month = ((12*$yearDiff)-date('m', strtotime($agreement->start_date)))+date('m', strtotime($last_date_month)) + 1;
            }
            //echo $contract_month;
            //echo $last_date_month;die;
            $j=0;
            foreach ($physicians as $physician) {
                # code...
                $physician_details = Physician::withTrashed()->findOrFail($physician);
                $practices_history = PhysicianPracticeHistory::select('physician_practice_history.*')
                    ->join('practices', 'practices.id', '=', 'physician_practice_history.practice_id')
                    ->join('agreements', 'agreements.hospital_id', '=', 'practices.hospital_id')
                    ->where('agreements.id', '=', $agreement->id)
                    ->where('physician_practice_history.physician_id', '=', $physician)
                    ->orderBy('start_date', 'desc')->get();
                if(count($practices_history)>1) {
                    $count_practices=count($practices_history);
                    $count_practice_ytm=0;
                    foreach ($practices_history as $practice_present) {
                        $flag = 0;
                        $count_practice_ytm++;
                        if($count_practice_ytm == $count_practices){
                            $practice_present->start_date=$start_date_ytm;
                        }
                        if (mysql_date($practice_present->start_date) <= $end_month_date && mysql_date($practice_present->end_date) >= $last_date_month) {
                            $flag++;
                            $practice = $practice_present->practice_id;
                        } elseif (mysql_date($practice_present->start_date) <= $end_month_date && mysql_date($practice_present->end_date) < $last_date_month) {
                            if (mysql_date($practice_present->end_date) > $end_month_date) {
                                $flag++;
                                $practice = $practice_present->practice_id;
                            }
                        } elseif (mysql_date($practice_present->start_date) > $end_month_date && mysql_date($practice_present->end_date) < $last_date_month) {
                            $flag++;
                            $practice = $practice_present->practice_id;
                        } elseif (mysql_date($practice_present->start_date) > $end_month_date && mysql_date($practice_present->end_date) >= $last_date_month) {
                            if (mysql_date($practice_present->start_date) <= $last_date_month) {
                                $flag++;
                                $practice = $practice_present->practice_id;
                            }
                        }
                        if ($flag > 0) {
                            //$contract_month_change_practice1 = months($practice_present->start_date, $last_date_month);
                            if(date('Y', strtotime($last_date_month)) == date('Y', strtotime($practice_present->start_date))) {
                                $contract_month_change_practice1 = date('m', strtotime($last_date_month)) - date('m', strtotime($practice_present->start_date)) + 1;
                            }elseif(date('Y', strtotime($practice_present->start_date)) < date('Y', strtotime($last_date_month))){
                                $yearDiff = date('Y', strtotime($last_date_month)) - date('Y', strtotime($practice_present->start_date));
                                $contract_month_change_practice1 = ((12*$yearDiff)-date('m', strtotime($practice_present->start_date)))+date('m', strtotime($last_date_month)) + 1;
                            }
                            $year_to_month_query[$i][$j] = DB::table('physician_logs')->select(
                                DB::raw("practices.name as practice_name"),
                                DB::raw("practices.id as practice_id"),
                                DB::raw("physician_practice_history.physician_id as physician_id"),
                                DB::raw("concat(physician_practice_history.last_name, ', ', physician_practice_history.first_name) as physician_name"),
                                DB::raw("specialties.name as specialty_name"),
                                DB::raw("contracts.id as contract_id"),
                                DB::raw("contracts.contract_name_id as contract_name_id"),
                                DB::raw("contracts.contract_type_id as contract_type_id"),
                                DB::raw("contract_types.name as contract_name"),
                                DB::raw("contracts.min_hours as min_hours"),
                                DB::raw("contracts.max_hours as max_hours"),
                                DB::raw("contracts.expected_hours as expected_hours"),
                                DB::raw("contracts.rate as rate"),
                                DB::raw("contracts.manual_contract_end_date as manual_contract_end_date"),
                                DB::raw("sum(physician_logs.duration) as worked_hours"),
                                DB::raw("'{$contract_month_change_practice1}' as contract_month"),
                                DB::raw("'{$contract_month}' as contract_month1"),
                                DB::raw("'{$contract_term}' as contract_term"),
                                DB::raw("'{$contract_month_change_practice1}' as log_range"),
                                DB::raw("'{$end_month_date}' as start_date_check"),
                                DB::raw("'{$last_date_month}' as end_date_check"),
                                DB::raw("contracts.annual_cap as annual_cap")
                            )
                                ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                                ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                                ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                                ->join('physician_practice_history', 'physician_practice_history.physician_id', '=', 'contracts.physician_id')
                                ->join('specialties', 'specialties.id', '=', 'physician_practice_history.specialty_id')
                                ->join("practices", function ($join) {
                                    $join->on("physician_practice_history.practice_id", "=", "practices.id")
                                        ->on("practices.hospital_id", "=", "agreements.hospital_id");
                                })
                                ->where('contracts.agreement_id', '=', $agreement->id)
                                ->where("physician_practice_history.physician_id", "=", $physician)
                                ->where("practices.id", "=", $practice)
                                ->where("physician_logs.practice_id", "=", $practice)
                                ->whereBetween('physician_logs.date', array($end_month_date, $last_date_month))
                                ->where("contract_types.id", "=", ContractType::MEDICAL_DIRECTORSHIP);
                                if($physician_details->deleted_at == Null) {
                                    $year_to_month_query[$i][$j] = $year_to_month_query[$i][$j]->where('physician_logs.deleted_at', '=', Null);
                                }
                                $year_to_month_query[$i][$j] = $year_to_month_query[$i][$j]->groupBy('physician_practice_history.physician_id', 'agreements.id', 'contracts.id')
                                ->orderBy('contract_types.name', 'asc')
                                ->orderBy('practices.name', 'asc')
                                ->orderBy('physician_practice_history.last_name', 'asc')
                                ->orderBy('physician_practice_history.first_name', 'asc');

                            $year_to_month_query2[$i][$j] = DB::table('physician_practice_history')->select(
                                DB::raw("practices.name as practice_name"),
                                DB::raw("practices.id as practice_id"),
                                DB::raw("physician_practice_history.physician_id as physician_id"),
                                DB::raw("concat(physician_practice_history.last_name, ', ', physician_practice_history.first_name) as physician_name"),
                                DB::raw("specialties.name as specialty_name"),
                                DB::raw("contracts.id as contract_id"),
                                DB::raw("contracts.contract_name_id as contract_name_id"),
                                DB::raw("contracts.contract_type_id as contract_type_id"),
                                DB::raw("contract_types.name as contract_name"),
                                DB::raw("contracts.min_hours as min_hours"),
                                DB::raw("contracts.max_hours as max_hours"),
                                DB::raw("contracts.expected_hours as expected_hours"),
                                DB::raw("contracts.rate as rate"),
                                DB::raw("contracts.manual_contract_end_date as manual_contract_end_date"),
                                DB::raw("0 as worked_hours"),
                                DB::raw("'{$contract_month_change_practice1}' as contract_month"),
                                DB::raw("'{$contract_month}' as contract_month1"),
                                DB::raw("'{$contract_term}' as contract_term"),
                                DB::raw("'{$contract_month_change_practice1}' as log_range"),
                                DB::raw("'{$end_month_date}' as start_date_check"),
                                DB::raw("'{$last_date_month}' as end_date_check"),
                                DB::raw("contracts.annual_cap as annual_cap")
                            )
                                ->join('contracts', 'contracts.physician_id', '=', 'physician_practice_history.physician_id')
                                ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                                ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                                ->join('specialties', 'specialties.id', '=', 'physician_practice_history.specialty_id')
                                ->join("practices", function ($join) {
                                    $join->on("physician_practice_history.practice_id", "=", "practices.id")
                                        ->on("practices.hospital_id", "=", "agreements.hospital_id");
                                })
                                ->where('contracts.agreement_id', '=', $agreement->id)
                                ->where("physician_practice_history.physician_id", "=", $physician)
                                ->where("practices.id", "=", $practice)
                                ->where("contract_types.id", "=", ContractType::MEDICAL_DIRECTORSHIP)
                                ->groupBy('physician_practice_history.physician_id', 'agreements.id', 'contracts.id')
                                ->orderBy('contract_types.name', 'asc')
                                ->orderBy('practices.name', 'asc')
                                ->orderBy('physician_practice_history.last_name', 'asc')
                                ->orderBy('physician_practice_history.first_name', 'asc');
                            $j++;
                        }
                    }
                }else{
                    foreach ($practices_history as $practice_present) {
                        $practice = $practice_present->practice_id;
                    }
                    $year_to_month_query[$i][$j] = DB::table('physician_logs')->select(
                        DB::raw("practices.name as practice_name"),
                        DB::raw("practices.id as practice_id"),
                        DB::raw("physician_practice_history.physician_id as physician_id"),
                        DB::raw("concat(physician_practice_history.last_name, ', ', physician_practice_history.first_name) as physician_name"),
                        DB::raw("specialties.name as specialty_name"),
                        DB::raw("contracts.id as contract_id"),
                        DB::raw("contracts.contract_name_id as contract_name_id"),
                        DB::raw("contracts.contract_type_id as contract_type_id"),
                        DB::raw("contract_types.name as contract_name"),
                        DB::raw("contracts.min_hours as min_hours"),
                        DB::raw("contracts.max_hours as max_hours"),
                        DB::raw("contracts.expected_hours as expected_hours"),
                        DB::raw("contracts.rate as rate"),
                        DB::raw("contracts.manual_contract_end_date as manual_contract_end_date"),
                        DB::raw("sum(physician_logs.duration) as worked_hours"),
                        DB::raw("'{$contract_month}' as contract_month"),
                        DB::raw("'{$contract_month}' as contract_month1"),
                        DB::raw("'{$contract_term}' as contract_term"),
                        DB::raw("'{$contract_month}' as log_range"),
                        DB::raw("'{$end_month_date}' as start_date_check"),
                        DB::raw("'{$last_date_month}' as end_date_check"),
                        DB::raw("contracts.annual_cap as annual_cap")
                    )
                        ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                        ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                        ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                        ->join('physician_practice_history', 'physician_practice_history.physician_id', '=', 'contracts.physician_id')
                        ->join('specialties', 'specialties.id', '=', 'physician_practice_history.specialty_id')
                        ->join("practices", function ($join) {
                            $join->on("physician_practice_history.practice_id", "=", "practices.id")
                                ->on("practices.hospital_id", "=", "agreements.hospital_id");
                        })
                        ->where('contracts.agreement_id', '=', $agreement->id)
                        ->where("physician_practice_history.physician_id", "=", $physician)
                        ->where("practices.id", "=", $practice)
                        ->where("physician_logs.practice_id", "=", $practice)
                        ->whereBetween('physician_logs.date', array($end_month_date, $last_date_month))
                        ->where("contract_types.id", "=", ContractType::MEDICAL_DIRECTORSHIP);
                        if($physician_details->deleted_at == Null) {
                            $year_to_month_query[$i][$j] = $year_to_month_query[$i][$j]->where('physician_logs.deleted_at', '=', Null);
                        }
                        $year_to_month_query[$i][$j] = $year_to_month_query[$i][$j]->groupBy('physician_practice_history.physician_id', 'agreements.id', 'contracts.id')
                        ->orderBy('contract_types.name', 'asc')
                        ->orderBy('practices.name', 'asc')
                        ->orderBy('physician_practice_history.last_name', 'asc')
                        ->orderBy('physician_practice_history.first_name', 'asc');

                    $year_to_month_query2[$i][$j] = DB::table('physician_practice_history')->select(
                        DB::raw("practices.name as practice_name"),
                        DB::raw("practices.id as practice_id"),
                        DB::raw("physician_practice_history.physician_id as physician_id"),
                        DB::raw("concat(physician_practice_history.last_name, ', ', physician_practice_history.first_name) as physician_name"),
                        DB::raw("specialties.name as specialty_name"),
                        DB::raw("contracts.id as contract_id"),
                        DB::raw("contracts.contract_name_id as contract_name_id"),
                        DB::raw("contracts.contract_type_id as contract_type_id"),
                        DB::raw("contract_types.name as contract_name"),
                        DB::raw("contracts.min_hours as min_hours"),
                        DB::raw("contracts.max_hours as max_hours"),
                        DB::raw("contracts.expected_hours as expected_hours"),
                        DB::raw("contracts.rate as rate"),
                        DB::raw("contracts.manual_contract_end_date as manual_contract_end_date"),
                        DB::raw("0 as worked_hours"),
                        DB::raw("'{$contract_month}' as contract_month"),
                        DB::raw("'{$contract_month}' as contract_month1"),
                        DB::raw("'{$contract_term}' as contract_term"),
                        DB::raw("'{$contract_month}' as log_range"),
                        DB::raw("'{$end_month_date}' as start_date_check"),
                        DB::raw("'{$last_date_month}' as end_date_check"),
                        DB::raw("contracts.annual_cap as annual_cap")
                    )
                        ->join('contracts', 'contracts.physician_id', '=', 'physician_practice_history.physician_id')
                        ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                        ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                        ->join('specialties', 'specialties.id', '=', 'physician_practice_history.specialty_id')
                        ->join("practices", function ($join) {
                            $join->on("physician_practice_history.practice_id", "=", "practices.id")
                                ->on("practices.hospital_id", "=", "agreements.hospital_id");
                        })
                        ->where('contracts.agreement_id', '=', $agreement->id)
                        ->where("physician_practice_history.physician_id", "=", $physician)
                        ->where("practices.id", "=", $practice)
                        ->where("contract_types.id", "=", ContractType::MEDICAL_DIRECTORSHIP)
                        ->groupBy('physician_practice_history.physician_id', 'agreements.id', 'contracts.id')
                        ->orderBy('contract_types.name', 'asc')
                        ->orderBy('practices.name', 'asc')
                        ->orderBy('physician_practice_history.last_name', 'asc')
                        ->orderBy('physician_practice_history.first_name', 'asc');
                    $j++;
                }
            }
        }
        //print_r($year_to_month_query2[0][0]->get());die;

        $date_start_agreement = DB::table('agreements')->where("id","=",$agreement->id)->first();
        $start_date_ytm = $date_start_agreement->start_date;

        if($date_start_agreement->end_date > date('Y-m-t'))
        {
            $end_date = date('Y-m-t');
        }
        else
        {
            $end_date = mysql_date($date_start_agreement->end_date);
        }

        $ts1 = strtotime($start_date_ytm);
        $ts2 = strtotime($end_date);

        $year1 = date('Y', $ts1);
        $year2 = date('Y', $ts2);

        $month1 = date('m', $ts1);
        $month2 = date('m', $ts2);

        $diff = (($year2 - $year1) * 12) + ($month2 - $month1) + 1;

        //$end_month = date('m',strtotime($end_date));

        //print_r($end_month);die;
        $contract_month = months($agreement->start_date, $start_date) + 1;
        for($i=1;$i<=$diff;$i++)
        {
            //$start_month_date = $i."-".date('Y', strtotime($end_date));
            if($month1 > 12)
            {
                //echo "hi";
                $m = $month1 - (12*floor($month1/12));
                //echo $m;
                $y = $year1 + floor($month1/12);
            }
            else
            {
                $m = $month1;
                $y = $year1;
            }
            $month1++;

            //echo $month1."        ";
            $end_month_date = mysql_date(date('d-m-Y', strtotime("01-".$m."-".$y)));

            //$start_month_date = "01-".$i."-".date('Y', strtotime($end_date));
            //$first_date_month = mysql_date($start_month_date);
            //$first_date_month = mysql_date("01-".$end_month_date);
            //echo $end_month_date;die;
            $last_date_month = mysql_date(date('t-F-Y', strtotime($end_month_date)));
            $contract_month1 = months($agreement->start_date, $last_date_month);


            $j=0;
            foreach ($physicians as $physician) {
                # code...
                $physician_details = Physician::withTrashed()->findOrFail($physician);
                $year_to_date_query[$i][$j] = DB::table('physician_logs')->select(
                    DB::raw("practices.name as practice_name"),
                    DB::raw("physicians.id as physician_id"),
                    DB::raw("concat(physicians.last_name, ', ', physicians.first_name) as physician_name"),
                    DB::raw("specialties.name as specialty_name"),
                    DB::raw("contracts.id as contract_id"),
                    DB::raw("contracts.contract_name_id as contract_name_id"),
                    DB::raw("contracts.contract_type_id as contract_type_id"),
                    DB::raw("contract_types.name as contract_name"),
                    DB::raw("contracts.min_hours as min_hours"),
                    DB::raw("contracts.max_hours as max_hours"),
                    DB::raw("contracts.expected_hours as expected_hours"),
                    DB::raw("contracts.rate as rate"),
                    DB::raw("contracts.manual_contract_end_date as manual_contract_end_date"),
                    DB::raw("sum(physician_logs.duration) as worked_hours"),
                    DB::raw("'{$contract_month}' as contract_month1"),
                    DB::raw("'{$contract_month1}' as contract_month"),
                    DB::raw("'{$contract_term}' as contract_term"),
                    DB::raw("'{$contract_month}' as log_range"),
                    DB::raw("'{$end_month_date}' as start_date_check"),
                    DB::raw("'{$last_date_month}' as end_date_check"),
                    DB::raw("contracts.annual_cap as annual_cap")
                )
                    ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                    ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                    ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                    ->join('physicians', 'physicians.id', '=', 'contracts.physician_id')
                    ->join('specialties', 'specialties.id', '=', 'physicians.specialty_id')
                    ->join('practices', 'practices.id', '=', 'physicians.practice_id')
                    ->where('contracts.agreement_id', '=', $agreement->id)
                    ->where("physicians.id","=",$physician)
                    ->where("contract_types.id", "=", ContractType::MEDICAL_DIRECTORSHIP)
                    ->whereBetween('physician_logs.date',array($end_month_date,$last_date_month))
                    ->where("contract_types.id","=",ContractType::MEDICAL_DIRECTORSHIP);
                    if($physician_details->deleted_at == Null) {
                        $year_to_date_query[$i][$j] = $year_to_date_query[$i][$j]->where('physician_logs.deleted_at', '=', Null);
                    }
                    $year_to_date_query[$i][$j] = $year_to_date_query[$i][$j]->groupBy('physicians.id', 'agreements.id', 'contracts.id')
                    ->orderBy('contract_types.name', 'asc')
                    ->orderBy('practices.name', 'asc')
                    ->orderBy('physicians.last_name', 'asc')
                    ->orderBy('physicians.first_name', 'asc');

                $year_to_date_query2[$i][$j] = DB::table('physicians')->select(
                    DB::raw("practices.name as practice_name"),
                    DB::raw("physicians.id as physician_id"),
                    DB::raw("concat(physicians.last_name, ', ', physicians.first_name) as physician_name"),
                    DB::raw("specialties.name as specialty_name"),
                    DB::raw("contracts.id as contract_id"),
                    DB::raw("contracts.contract_name_id as contract_name_id"),
                    DB::raw("contracts.contract_type_id as contract_type_id"),
                    DB::raw("contract_types.name as contract_name"),
                    DB::raw("contracts.min_hours as min_hours"),
                    DB::raw("contracts.max_hours as max_hours"),
                    DB::raw("contracts.expected_hours as expected_hours"),
                    DB::raw("contracts.rate as rate"),
                    DB::raw("contracts.manual_contract_end_date as manual_contract_end_date"),
                    DB::raw("0 as worked_hours"),
                    DB::raw("'{$contract_month}' as contract_month1"),
                    DB::raw("'{$contract_month1}' as contract_month"),
                    DB::raw("'{$contract_term}' as contract_term"),
                    DB::raw("'{$contract_month}' as log_range"),
                    DB::raw("'{$end_month_date}' as start_date_check"),
                    DB::raw("'{$last_date_month}' as end_date_check"),
                    DB::raw("contracts.annual_cap as annual_cap")
                )
                    ->join('contracts', 'physician_id', '=', 'physicians.id')
                    ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                    ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                    ->join('specialties', 'specialties.id', '=', 'physicians.specialty_id')
                    ->join('practices', 'practices.id', '=', 'physicians.practice_id')
                    ->where('contracts.agreement_id', '=', $agreement->id)
                    ->where("physicians.id","=",$physician)
                    ->where("contract_types.id", "=", ContractType::MEDICAL_DIRECTORSHIP)
                    ->groupBy('physicians.id', 'agreements.id', 'contracts.id')
                    ->orderBy('contract_types.name', 'asc')
                    ->orderBy('practices.name', 'asc')
                    ->orderBy('physicians.last_name', 'asc')
                    ->orderBy('physicians.first_name', 'asc');
                $j++;
            }
        }
        //print_r(count($year_to_date_query));die;
        //$year_to_date_query->where('contracts.contract_type_id', '=', $contract_type_id);

        $results = new StdClass;
        $temp = 0;
        foreach ($period_query as $period_query1) {
            $results->period[$temp] = $period_query1->first();
            if(empty($results->period[$temp]))
            {
                $results->period[$temp] = $period_query2[$temp]->first();
            }
            $temp++;
        }
        $results->period = array_values(array_filter($results->period));

        $results -> agreement_start_date = date('m/d/Y',strtotime( $agreement->start_date));
        $results -> agreement_end_date = date('m/d/Y',strtotime( $agreement->end_date));
        //Log::info("Agreement_start_date: ".$agreement->start_date);
//echo "<pre>";
//print_r($results->period);die;
        //$results->year_to_month = $year_to_month_query->get();
        $results->year_to_month = array();
        $temp = 0;

        foreach($year_to_month_query as $key=>$year_to_date_query_arr)
        {
            $temp1 = 0;
            foreach ($year_to_date_query_arr as $key1=>$year_to_date_query_arr2) {
                $year_to_date_query_arr2_first = $year_to_date_query_arr2->first();
                if (!empty($year_to_date_query_arr2_first)) {
                    if($year_to_date_query_arr2_first->manual_contract_end_date == "0000-00-00" || strtotime($year_to_date_query_arr2_first->manual_contract_end_date) >= strtotime($year_to_date_query_arr2_first->start_date_check)) {
                        $results->year_to_month[$temp][$temp1] = $year_to_date_query_arr2_first;
                    }
                }else{
                    $year_to_month_query2_first = $year_to_month_query2[$key][$key1]->first();
                    if (!empty($year_to_month_query2_first)) {
                        if ($year_to_month_query2_first->manual_contract_end_date == "0000-00-00" || strtotime($year_to_month_query2_first->manual_contract_end_date) >= strtotime($year_to_month_query2_first->start_date_check)) {
                            $results->year_to_month[$temp][$temp1] = $year_to_month_query2_first;
                        }
                    }else{
                        $results->year_to_month[$temp][$temp1] = $year_to_month_query2_first;
                    }
                }
                $temp1++;
            }


            //$queries = DB::getQueryLog();
            //$last_query = end($queries);
            //print_r($last_query);
            //echo "<pre>";
            //print_r($results->year_to_month);
            $temp++;

        }
        $results->year_to_month = array_values(array_filter($results->year_to_month));
        $i = 0;
        foreach ($results->year_to_month as $result1) {
            $results->year_to_month[$i] = array_values(array_filter($result1));
            $i++;
        }
        $results->year_to_date = array();
        $temp = 0;
        foreach($year_to_date_query as $year_to_date_query_arr)
        {
            //$results->year_to_date[$temp] = $year_to_date_query_arr->get();
            //$temp++;
            $temp1 = 0;
            foreach ($year_to_date_query_arr as $year_to_date_query_arr2) {
                $year_to_date_query_arr2_first = $year_to_date_query_arr2->first();
                if (!empty($year_to_date_query_arr2_first)) {
                    if($year_to_date_query_arr2_first->manual_contract_end_date == "0000-00-00" || strtotime($year_to_date_query_arr2_first->manual_contract_end_date) >= strtotime($year_to_date_query_arr2_first->start_date_check)) {
                        $results->year_to_date[$temp][$temp1] = $year_to_date_query_arr2_first;
                    }
                }else{
                    $year_to_date_query2_first = $year_to_date_query2[$temp+1][$temp1]->first();
                    if (!empty($year_to_date_query2_first)) {
                        if ($year_to_date_query2_first->manual_contract_end_date == "0000-00-00" || strtotime($year_to_date_query2_first->manual_contract_end_date) >= strtotime($year_to_date_query2_first->start_date_check)) {
                            $results->year_to_date[$temp][$temp1] = $year_to_date_query2_first;
                        }
                    }else{
                        $results->year_to_date[$temp][$temp1] = $year_to_date_query2_first;
                    }
                }
                $temp1++;
            }
            $temp++;
        }
        $results->year_to_date = array_values(array_filter($results->year_to_date));
        $i = 0;
        foreach ($results->year_to_date as $result1) {
            $results->year_to_date[$i] = array_values(array_filter($result1));
            $i++;
        }
        //echo "<pre>";
        //print_r($results->year_to_date);die;
        foreach ($results->period as $result) {

            if (isset($result->contract_name_id)) {

                $result->contract_name = ContractName::findOrFail($result->contract_name_id)->name;


                $result->paid = DB::table("physician_payments")
                    ->where("physician_payments.physician_id", "=", $result->physician_id)
                    ->whereBetween("physician_payments.month", [$agreement_data->start_month, $agreement_data->end_month])
                    ->sum("amount");
            }
        }


        foreach ($results->year_to_month as $result1) {
            foreach ($result1 as $result) {
                if (isset($result->contract_name_id)) {

                    $result->contract_name = ContractName::findOrFail($result->contract_name_id)->name;


                    $result->paid = DB::table("physician_payments")
                        ->where("physician_payments.physician_id", "=", $result->physician_id)
                        ->where("physician_payments.agreement_id", "=", $agreement_data->id)
                        ->sum("amount");
                }
            }
        }

        foreach ($results->year_to_date as $result1) {
            foreach ($result1 as $result) {
                if (isset($result->contract_name_id)) {
                    $result->contract_name = ContractName::findOrFail($result->contract_name_id)->name;


                    $result->paid = DB::table("physician_payments")
                        ->where("physician_payments.physician_id", "=", $result->physician_id)
                        ->where("physician_payments.agreement_id", "=", $agreement_data->id)
                        ->sum("amount");
                }
            }
        }
        //Log::info("result::========", array($results->year_to_month));

        return $results;
    }

    protected function writeData($workbook, $sheetIndex, $index, $contracts,$startDate, $finalized, $end_date, $agreement_start_date, $agreement_end_date, $physicians, $agreement)
    {
        //Log::info("index =".$index);
        $contracts_count = count($contracts);

        $current_practice_name = null;
        $previous_practice_name = null;
        $previous_contract_name = null;
        $current_contract_name = null;
        $current_row = $index;

        $totals = new StdClass;
        $totals->contract_name = null;
        $totals->practice_name = null;
        $totals->practice_name_1 = null;
        $totals->expected_hours = 0.0;
        $totals->expected_hours_ytd = 0.0;
        $totals->worked_hours = 0.0;
        $totals->worked_hours_main = 0.0;
        $totals->days_on_call = 0.0;
        $totals->rate = 0.0;
        $totals->actual_rate = 0.0;
        //$totals->paid = 0.0;
        $totals->amount_paid = 0.0;
        $totals->amount_tobe_paid = 0.0;
        $totals->amount = 0.0;
        $totals->amount_main = 0.0;
        $totals->amount_monthly = 0.0;
        $totals->count = 0.0;
        $totals->has_override = false;
        $totals->worked_hours = 0;
        $totals->expected_payment = 0;
        $totals->max_hours = 0;
        $totals->pmt_status = 'Y';
        $totals->fmv = 0;
        $practice_first_row = 0;

        $contract_names = DB::table('physician_practice_history')->select(
            DB::raw("contract_names.name as contract_name"),
            DB::raw("contract_names.id as name_id"),
            DB::raw("physician_practice_history.physician_id as physician_id"))
            ->join('practices', 'practices.id', '=', 'physician_practice_history.practice_id')
            ->join('agreements', 'agreements.hospital_id', '=', 'practices.hospital_id')
            ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
            ->groupBy('contract_names.name')
            ->orderBy('practices.id', "asc")
            ->where("agreements.id", $agreement->id)
            ->where("contract_types.id", "=", ContractType::MEDICAL_DIRECTORSHIP)
            ->whereIn("physician_practice_history.physician_id", $physicians)->get();
        $is_practice_present = true;
        $first_practice_present = 0;
        $practice_row = 0;
        foreach ($contract_names as $contract_name_display) {
            foreach ($contracts as $index => $contract) {
                if($contract_name_display->contract_name === $contract->contract_name) {

                    $formula = $this->applyFormula($contract);

                    if ($formula->payment_override)
                        $totals->has_override = true;

                    if ($index == 0) {
                        $totals->contract_name = $contract->contract_name;
                    }
                    $new_current_row = $current_row;
                    // Write the contract header if this is the first row being inserted.
                    if ($index == 0 || $totals->contract_name != $contract->contract_name) {

                        //Log::info("current_row 718= ".$current_row);
                        if ($current_row != 4) {
                            $first_contract_name = true;
                        } else {
                            $first_contract_name = false;
                        }
                        $totals->contract_name = $contract->contract_name;
                        $current_row = $this->writeContractHeader($workbook, $sheetIndex, $current_row, $totals);
                        $current_row = $this->writePeriodHeader($workbook, $sheetIndex, $current_row, $agreement_start_date, $agreement_end_date);

                    }

                    if ($totals->practice_name != $contract->practice_name) {

                        $is_practice_present = false;
                        $first_practice_present++;

                        if ($practice_row > 0) {
                            if ($first_contract_name && $new_current_row != $current_row) {
                                $new_current_row = $current_row - 2;
                                $first_contract_name = false;
                            } else {
                                $new_current_row = $current_row;
                            }
                            $this->writePeriodPracticeMiddle($workbook, $sheetIndex, $practice_first_row, $new_current_row, $totals->practice_name, false);
                            if ($totals->count > 1) {
                                $this->writeTotals($workbook, $sheetIndex, $practice_row, $totals, 1);
                            } else {
                                $this->write_single_total($workbook, $sheetIndex, $practice_row, $totals);
                            }
                            $practice_row = 0;
                        }
                        $totals->practice_name = $contract->practice_name;
                        $totals->practice_name_1 = $contract->practice_name;
                    } else if ($index != 0) {
                        $totals->practice_name_1 = "";
                        if($practice_row == 0) {
                            $is_practice_present = false;
                        }
                    }
                    if (!$is_practice_present) {
                        //Log::info("is_practice_present :".$is_practice_present);
                        $is_practice_present = true;
                        $practice_row = $current_row;
                        $current_row = $this->writeTotals($workbook, $sheetIndex, $current_row, $totals, 0);
                        //Log::info("totals->count :".$totals->count);
                        $current_row++;
                        $practice_first_row = $current_row + 1;
                    }


                    if ($sheetIndex == 0) {
                        $current_row++;
                        $totals->worked_hours = $totals->worked_hours + $contract->worked_hours;
                        $totals->worked_hours_main += $contract->worked_hours;
                        $totals->amount = $totals->amount + $formula->amount;
                        $totals->amount_main += $totals->amount;
                        if ($contract->worked_hours != 0) {
                            $totals->actual_rate += ($totals->amount / $totals->worked_hours);
                        } else {
                            $totals->actual_rate = 0;
                        }
                        $month = mysql_date(date($startDate));
                        $monthArr = explode("-", $month);
                        $amount = DB::table('amount_paid')
                            ->where('start_date', '<=', $month)
                            ->where('end_date', '>=', $month)
                            ->where('physician_id', '=', $contract->physician_id)
                            ->where('contract_id', '=', $contract->contract_id)
                            ->where('practice_id', '=', $contract->practice_id)
                            ->orderBy('created_at', 'desc')
                            ->orderBy('id', 'desc')
                            ->first();

                        if (isset($amount->amountPaid)) {
                            $formula->amount_paid = $amount->amountPaid;
                        } else {
                            $formula->amount_paid = 0;
                        }

                        if ($contract->worked_hours != 0)
                            $pmt_status = (($formula->amount_paid) / $contract->worked_hours) <= $contract->rate;
                        else
                            $pmt_status = false;

                        $contract->expected_payment = $contract->expected_hours * $contract->rate;
                        if (!$pmt_status) {
                            $totals->pmt_status = 'N';
                        }
                        $total_to_be_paid = $contract->rate * $contract->worked_hours;

                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->setCellValue("C{$current_row}", $contract->physician_name)
                            ->setCellValue("D{$current_row}", $contract->expected_hours)
                            ->setCellValue("E{$current_row}", $contract->expected_payment)
                            ->setCellValue("F{$current_row}", $contract->worked_hours)
                            ->setCellValue("G{$current_row}", $formula->amount_paid)
                            ->setCellValue("H{$current_row}", $total_to_be_paid - $formula->amount_paid)
                            ->setCellValue("I{$current_row}", $contract->worked_hours !=0 ? $formula->amount_paid / $contract->worked_hours : "0")
                            ->setCellValue("J{$current_row}", $contract->rate);

                        if (count($contracts) - 1 == $index && $this->contract_length - 1 == $this->contract_count) {
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("B{$current_row}:J{$current_row}")->applyFromArray($this->cell_bottom_style);
                        } else {
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("C{$current_row}:I{$current_row}")->applyFromArray($this->period_cell_style);
                        }
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("F{$current_row}:J{$current_row}")->applyFromArray($this->shaded_style_fmv);

                        $workbook->setActiveSheetIndex($sheetIndex)
                            ->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);


                        $totals->expected_hours += $contract->expected_hours;
                       
                        // below check is added by akash to set addition or N/A assignment to totals.
                        if($formula->days_on_call != 'N/A'){
                            $totals->days_on_call += $formula->days_on_call;
                        } else {
                            $totals->days_on_call = $formula->days_on_call;
                        }

                        $totals->rate += $contract->rate;


                        $totals->amount_paid += $formula->amount_paid;
                        $totals->amount_tobe_paid += $total_to_be_paid - $formula->amount_paid;
                        $totals->expected_payment += $contract->expected_payment;
                        $totals->max_hours += $contract->max_hours;
                        $totals->fmv += $contract->rate;


                        $totals->count++;
                    }
                }
                if ($index == $contracts_count - 1 && $practice_row > 4) {
                    $this->writePeriodPracticeMiddle($workbook, $sheetIndex, $practice_first_row, $current_row, $totals->practice_name, true);
                    if ($totals->count > 1) {
                        $this->writeTotals($workbook, $sheetIndex, $practice_row, $totals, 1);
                    } else {
                        $this->write_single_total($workbook, $sheetIndex, $practice_row, $totals, 0);
                    }
                    $practice_row = 0;
                }
            }
        }
        if($sheetIndex == 0) {
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("B{$current_row}:J{$current_row}")->applyFromArray($this->bottom_style);
        }else{
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("B{$current_row}:L{$current_row}")->applyFromArray($this->bottom_style);
        }

        return $current_row;
    }
    private function writePeriodPracticeMiddle($workbook, $sheetIndex, $practice_first_row, $practice_last_row, $practice_name, $is_last_row){
        $practice_name_row = $practice_last_row - ($practice_last_row - $practice_first_row)/2;
        $practice_name_row = floor($practice_name_row);

      //  Log::info("practice_last_row: ".$practice_last_row);
    //    Log::info("practice_name_row: ".$practice_first_row);
      //  Log::info("practice_name_row: ".$practice_name_row);
      //  Log::info("practice_name: ".$practice_name);
    //    Log::info("is_last_row: ".$is_last_row);

        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$practice_first_row}:B{$practice_last_row}")->applyFromArray($this -> period_breakdown_practice_style);
        $workbook->setActiveSheetIndex($sheetIndex)-> setCellValue("B{$practice_name_row}", $practice_name);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$practice_name_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$practice_name_row}")->getAlignment()->setWrapText(true);

        if($is_last_row){
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("B{$practice_last_row}")->applyFromArray($this -> bottom_row_style);
        }
    }


    protected function writeDataCYTPM($workbook, $sheetIndex, $index, $contracts, $startDate, $physicians, $end_date, $agreement_start_date, $agreement_end_date,$agreement)
    {
        //Log::info("method writeDataYTD start: ".$index);
        //Log::info("Agreement start date: ".$agreement_start_date);
        $current_practice_name = null;
        $previous_practice_name = null;
        $previous_contract_name = null;
        $current_contract_name = null;
        $current_row = $index;
        $expected_hours=0.0;
        $max_hours=0.0;
        $annual_cap=0.0;

        $totals = new StdClass;
        $totals->contract_name = null;
        $totals->practice_name = null;
        $totals->practice_name_1 = null;
        $totals->physician_name = null;
        $totals->physician_name_1 = null;
        $totals->expected_hours = 0.0;
        $totals->expected_hours_ytd = 0.0;
        $totals->worked_hours = 0.0;
        $totals->worked_hours_main = 0.0;
        $totals->days_on_call = 0.0;
        $totals->rate = 0.0;
        $totals->actual_rate = 0.0;
        $totals->paid = 0.0;
        $totals->amount = 0.0;
        $totals->amount_paid = 0.0;
        $totals->amount_tobe_paid = 0.0;
        $totals->amount_main = 0.0;
        $totals->amount_monthly = 0.0;
        $totals->count = 0.0;
        $totals->has_override = false;
        $totals->fmv = 0.0;
        $totals->expected_payment = 0;
        $totals->worked_hours = 0;
        $totals->pmt_status = 'Y';
        $totals->fmv = 0.0;
        $totals->max_hours = 0.0;
        $totals->annual_cap = 0.0;

        $totals_ytm = new StdClass;
        $totals_ytm->contract_name = null;
        $totals_ytm->speciality_name = null;
        $totals_ytm->practice_name = null;
        $totals_ytm->practice_name_1 = null;
        $totals_ytm->expected_hours = 0.0;
        $totals_ytm->expected_hours_ytd = 0.0;
        $totals_ytm->worked_hours = 0.0;
        $totals_ytm->worked_hours_main = 0.0;
        $totals_ytm->days_on_call = 0.0;
        $totals_ytm->rate = 0.0;
        $totals_ytm->actual_rate = 0.0;
        $totals_ytm->paid = 0.0;
        $totals_ytm->amount = 0.0;
        $totals_ytm->amount_paid = 0.0;
        $totals_ytm->amount_tobe_paid = 0.0;
        $totals_ytm->amount_main = 0.0;
        $totals_ytm->amount_monthly = 0.0;
        $totals_ytm->count = 0.0;
        $totals_ytm->has_override = false;
        $totals_ytm->worked_hours = 0;
        $totals_ytm->expected_payment = 0;
        $totals_ytm->max_hours = 0;
        $totals_ytm->pmt_status = 'Y';
        $totals_ytm->contract_month = 0;
        $totals_ytm->contract_term = 0;
        $totals_ytm->fmv = 0.0;
        $totals_ytm->annual_cap = 0.0;

        $count_py = 0;
        $py_data = DB::table('physician_practice_history')->select(
            DB::raw("practices.name as practice_name"),
            DB::raw("practices.id as practice_id"),
            DB::raw("physician_practice_history.physician_id as physician_id"))
            ->join('practices', 'practices.id', '=', 'physician_practice_history.practice_id')
            ->join('agreements', 'agreements.hospital_id', '=', 'practices.hospital_id')
            ->orderBy('practices.id', "asc")
            ->where("agreements.id", $agreement->id)
            ->whereIn("physician_practice_history.physician_id", $physicians)->get();

        foreach ($py_data as $py_data1) {
            $physicians[$count_py] = $py_data1->physician_id;
            $practices[$count_py] = $py_data1->practice_id;
            $count_py++;
        }

        $contract_names = DB::table('physician_practice_history')->select(
            DB::raw("contract_names.name as contract_name"),
            DB::raw("contract_names.id as name_id"),
            DB::raw("physician_practice_history.physician_id as physician_id"))
            ->join('practices', 'practices.id', '=', 'physician_practice_history.practice_id')
            ->join('agreements', 'agreements.hospital_id', '=', 'practices.hospital_id')
            ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
            ->groupBy('contract_names.name')
            ->orderBy('practices.id', "asc")
            ->where("agreements.id", $agreement->id)
            ->where("contract_types.id", "=", ContractType::MEDICAL_DIRECTORSHIP)
            ->whereIn("physician_practice_history.physician_id", $physicians)->get();
        $year = date('Y',strtotime($startDate));
        $contract_start_month = date('n', strtotime($agreement_start_date));
        $practice_header_row = 0;
        $physician_header_row = 0;
        $is_last_row_written = false;
        $physician_first_row = 0;
        $contract_month =0;
        foreach ($contract_names as $contract_name_display) {
            $is_last_row_written = false;
            for ($i = 0; $i < count($physicians); $i++) {

                $physician_id = $physicians[$i];
                $practice_id = $practices[$i];
                $count1 = count($contracts);
                $is_practice_present = true;
                $is_physician_present = true;
                for ($j = $count1 - 1; $j >= 0; $j--) {
                    foreach ($contracts[$j] as $contract) {
                        if($contract_name_display->contract_name === $contract->contract_name) {
                            //Log::info("Practice name: ".$contract->practice_name);
                            if ($contract->physician_id == $physician_id) {
                                if ($contract->practice_id == $practice_id) {
                                    $formula = $this->applyFormula($contract);
                                    if ($formula->payment_override) {
                                        $totals->has_override = true;
                                        $totals_ytm->has_override = true;
                                    }

                                    if ($index == 0) {
                                        $totals->contract_name = $contract->contract_name;
                                        $totals->physician_name = $contract->physician_name;
                                        $totals->physician_name_1 = $contract->physician_name;
                                        $totals_ytm->contract_name = $contract->contract_name;
                                        $totals_ytm->physician_name = $contract->physician_name;
                                        $totals_ytm->physician_name_1 = $contract->physician_name;
                                    }


                                    // Write the contract header if this is the first row being inserted.
                                    if ($totals->physician_name != $contract->physician_name) {
                                        //echo "hello";
                                        if($contract_month != 0) {
                                            $totals->expected_hours_ytd = ($expected_hours * $contract_month);
                                        }else{
                                            $totals->expected_hours_ytd = $expected_hours;
                                        }
                                        $contract_month = 0;
                                        $totals_ytm->expected_hours_ytd += $totals->expected_hours_ytd;
                                        $is_physician_present = false;
                                        if ($physician_header_row > 0) {
                                            $totals->expected_hours = $expected_hours;
                                            $totals->max_hours = $max_hours;
                                            $totals->annual_cap = $annual_cap;
                                            $totals_ytm->expected_hours += $totals->expected_hours;
                                            $totals_ytm->max_hours += $totals->max_hours;
                                            $totals_ytm->annual_cap += $totals->annual_cap;
                                            $this->writeCYTPMPhysicianMiddle($workbook, $sheetIndex, $physician_first_row, $current_row, $totals, $expected_hours,$max_hours, $annual_cap, false);
                                            if ($totals->count > 1) {
                                                $this->writeTotals($workbook, $sheetIndex, $physician_header_row, $totals, 1);
                                            } else {
                                                $this->write_single_total($workbook, $sheetIndex, $physician_header_row, $totals);
                                            }
                                            $physician_header_row = 0;
                                        }

                                        $totals->physician_name = $contract->physician_name;
                                        $totals->physician_name_1 = $contract->physician_name;
                                        $totals_ytm->physician_name = $contract->physician_name;
                                        $totals_ytm->physician_name_1 = $contract->physician_name;
                                        if($contract->contract_month != 0){
                                            $totals->expected_hours_ytd = ($contract->expected_hours * $contract->contract_month);
                                        }else{
                                            $totals->expected_hours_ytd = $contract->expected_hours;
                                        }
                                    } elseif ($totals->physician_name === $contract->physician_name && $totals->practice_name != $contract->practice_name) {
                                        //echo "hello";
                                        if($contract_month != 0) {
                                            $totals->expected_hours_ytd = ($expected_hours * $contract_month);
                                        }else{
                                            $totals->expected_hours_ytd = $expected_hours;
                                        }
                                        $contract_month = 0;
                                        $totals_ytm->expected_hours_ytd += $totals->expected_hours_ytd;
                                        $is_physician_present = false;
                                        if ($physician_header_row > 0) {
                                            $totals->expected_hours = $expected_hours;
                                            $totals_ytm->expected_hours += $totals->expected_hours;
                                            $totals->max_hours = $max_hours;
                                            $totals->annual_cap = $annual_cap;
                                            $totals_ytm->max_hours += $totals->max_hours;
                                            $totals_ytm->annual_cap += $totals->annual_cap;
                                            $this->writeCYTPMPhysicianMiddle($workbook, $sheetIndex, $physician_first_row, $current_row, $totals, $expected_hours,$max_hours,$annual_cap, false);
                                            if ($totals->count > 1) {
                                                $this->writeTotals($workbook, $sheetIndex, $physician_header_row, $totals, 1);
                                            } else {
                                                $this->write_single_total($workbook, $sheetIndex, $physician_header_row, $totals);
                                            }
                                            $physician_header_row = 0;
                                        }
                                        //$this->writeTotalsYTM($workbook, $sheetIndex, $practice_header_row, $totals_ytm, false);

                                        $totals->physician_name = $contract->physician_name;
                                        $totals->physician_name_1 = $contract->physician_name;
                                        $totals_ytm->physician_name = $contract->physician_name;
                                        $totals_ytm->physician_name_1 = $contract->physician_name;
                                        if($contract->contract_month != 0){
                                            $totals->expected_hours_ytd = ($contract->expected_hours * $contract->contract_month);
                                        }else{
                                            $totals->expected_hours_ytd = $contract->expected_hours;
                                        }
                                    } else {
                                        $totals->practice_name_1 = "";
                                        $totals->physician_name_1 = "";
                                        $totals_ytm->practice_name_1 = "";
                                        $totals_ytm->physician_name_1 = "";
                                    }
                                    $expected_hours = $contract->expected_hours;
                                    $max_hours = $contract->max_hours;
                                    $annual_cap = $contract->annual_cap;
                                    $contract_month++;

                                    if ($index == 0 || $totals->contract_name != $contract->contract_name) {
                                        //Log::info("inside if if (index == 0 || totals->contract_name != contract->contract_name)");
                                        //Log::info("current_row: ".$current_row);
                                        $totals->contract_name = $contract->contract_name;
                                        $current_row = $this->writeContractHeader($workbook, $sheetIndex, $current_row, $totals);
                                        $current_row = $this->writePeriodHeader($workbook, $sheetIndex, $current_row, $agreement_start_date, $agreement_end_date);
                                    }

                                    if ($totals->practice_name != $contract->practice_name) {
                                        //Log::info("totals->practice_name != contract->practice_name");
                                        // Log::info("current_row: ".$current_row);
                                        // Log::info("practice_header_row :".$practice_header_row);
                                        $is_practice_present = false;

                                        if ($practice_header_row > 0) {
                                            //Log::info("totals_ytm->count :".$totals_ytm->count);

                                            // Log::info("expected_hours total_ytm: " . $totals_ytm->expected_hours);
                                            if ($totals_ytm->count > 1) {
                                                $this->writeTotalsYTM($workbook, $sheetIndex, $practice_header_row, $totals_ytm, false);
                                            } else {
                                                $this->write_single_totalYTM($workbook, $sheetIndex, $practice_header_row, $totals_ytm, true);
                                            }
                                            $practice_header_row = 0;
                                        }
                                        $totals->practice_name = $contract->practice_name;
                                        $totals->practice_name_1 = $contract->practice_name;
                                        $totals_ytm->practice_name = $contract->practice_name;
                                        $totals_ytm->practice_name_1 = $contract->practice_name;
                                    } else if (!$is_physician_present) {
                                        $totals->practice_name_1 = $contract->practice_name;
                                    } else {
                                        $totals->practice_name_1 = "";
                                        $totals_ytm->practice_name_1 = "";
                                    }


                                    $totals->worked_hours += $contract->worked_hours;
                                    $totals->worked_hours_main += $contract->worked_hours;
                                    $totals->amount = $totals->amount + $formula->amount;
                                    $totals->amount_main += $totals->amount;

                                    $totals_ytm->worked_hours += $contract->worked_hours;
                                    $totals_ytm->worked_hours_main += $contract->worked_hours;
                                    $totals_ytm->amount = $totals_ytm->amount + $formula->amount;
                                    $totals_ytm->amount_main += $totals_ytm->amount;
                                    $totals_ytm->speciality_name = $contract->specialty_name;
                                    $start_month_date = $contract->start_date_check;
                                    $end_month_date = $contract->end_date_check;
                                    $amount = DB::table('amount_paid')
                                        ->where('start_date', '<=', $start_month_date)
                                        ->where('end_date', '>=', $start_month_date)
                                        ->where('physician_id', '=', $contract->physician_id)
                                        ->where('contract_id', '=', $contract->contract_id)
                                        ->where('practice_id', '=', $contract->practice_id)
                                        ->orderBy('created_at', 'desc')
                                        ->orderBy('id', 'desc')
                                        ->first();

                                    if (isset($amount->amountPaid)) {
                                        $formula->amount_paid = $amount->amountPaid;
                                    } else {
                                        $formula->amount_paid = 0;
                                    }
                                    $expected_payment = $contract->expected_hours * $contract->rate;
                                    $totals->expected_payment += $expected_payment;
                                    if ($contract->worked_hours != 0)
                                        $pmt_status = (($formula->amount_paid) / $contract->worked_hours) <= $contract->rate;
                                    else
                                        $pmt_status = false;
                                    if (!$pmt_status) {
                                        $totals->pmt_status = 'N';
                                        $totals_ytm->pmt_status = 'N';
                                    }
                                    if (!$is_practice_present) {
                                        //Log::info("Inside practice false");
                                        //Log::info("practice_header_row: ".$is_practice_present);
                                        $practice_header_row = $current_row;
                                        $is_practice_present = true;
                                        $this->write_single_totalYTM($workbook, $sheetIndex, $current_row, $totals_ytm, false);
                                        $current_row++;
                                    }

                                    if (!$is_physician_present) {
                                        //Log::info("Inside physician false");
                                        //Log::info("physician_header_row: ".$is_practice_present);
                                        $physician_header_row = $current_row;
                                        $is_physician_present = true;
                                        $current_row = $this->writeTotals($workbook, $sheetIndex, $current_row, $totals, 0);
                                        //Log::info("practice_header_row: ".$physician_header_row);
                                        $current_row++;
                                        $physician_first_row = $current_row + 1;
                                    }
                                    if ($sheetIndex == 1) {
                                        //Log::info("current_row: ".$current_row);

                                        $current_row++;
                                        // Log::info("after increment current_row: ".$current_row);
                                        //echo $current_row;
                                        //Log::info("practice name: ". $totals->practice_name_1);
                                        //Log::info("physician name: ". $totals->physician_name_1);
                                        // month in three digit
                                        $month = ($contract->contract_month1 - 1) + $contract_start_month;
                                        if ($month > 12) {
                                            $month = $month % 12;
                                        }
                                        $month_string = number_to_month($month, "M");

                                        $total_to_be_paid = $contract->rate * $contract->worked_hours;

                                        $workbook->setActiveSheetIndex($sheetIndex)
                                            ->setCellValue("G{$current_row}", $expected_payment)
                                            ->setCellValue("H{$current_row}", $contract->contract_month != 0 ?  $contract->expected_hours * $contract->contract_month : $contract->expected_hours)
                                            ->setCellValue("I{$current_row}", $contract->worked_hours)
                                            ->setCellValue("J{$current_row}", $formula->amount_paid)
                                            ->setCellValue("K{$current_row}", $total_to_be_paid - $formula->amount_paid)
                                            ->setCellValue("L{$current_row}", $month_string);

                                        $workbook->setActiveSheetIndex($sheetIndex)
                                            ->getStyle("G{$current_row}:L{$current_row}")->applyFromArray($this->period_cell_style);
                                        $workbook->setActiveSheetIndex($sheetIndex)
                                            ->getStyle("I{$current_row}:L{$current_row}")->applyFromArray($this->shaded_style);
//                            $workbook->setActiveSheetIndex($sheetIndex)
//                                ->getStyle("J{$current_row}")->applyFromArray($this-> blank_cell_style);

                                        $workbook->setActiveSheetIndex($sheetIndex)
                                            ->getStyle("L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheetIndex)
                                            ->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheetIndex)
                                            ->getStyle("I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                        $workbook->setActiveSheetIndex($sheetIndex)
                                            ->getStyle("C{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                        $workbook->setActiveSheetIndex($sheetIndex)
                                            ->getStyle("C{$current_row}")->getAlignment()->setWrapText(true);

                                    }
                                    //$totals->expected_hours += $contract->expected_hours;
                                    //$totals->expected_payment += $expected_payment;
                                    $totals->rate += $contract->worked_hours != 0 ? $formula->amount_paid / $contract->worked_hours : 0;

                                    // $totals->actual_rate += $formula->actual_rate; // Commented by akash
                                    ($formula->actual_rate != 'N/A') ? $totals->actual_rate += $formula->actual_rate : $totals->actual_rate = $formula->actual_rate; // Added by akash
                                    $totals->amount_paid += $formula->amount_paid;
                                    $totals->amount_tobe_paid += $total_to_be_paid - $formula->amount_paid;
                                    if($contract->contract_month != 0){
                                        $totals->amount_monthly += ($totals_ytm->amount / $contract->contract_month);
                                    }else{
                                        $totals->amount_monthly += $totals_ytm->amount;
                                    }
                                    $totals->count++;
                                    $totals->fmv = $contract->rate;

                                    $totals_ytm->fmv = $contract->rate;
                                    //$totals_ytm->expected_hours = $contract->expected_hours;
                                    //$totals_ytm->expected_hours_ytd += ($contract->expected_hours * $contract->contract_month);
                                    if ($j == $count1 - 1) {
                                        if($contract->contract_month != 0){
                                            $totals->expected_hours_ytd = ($contract->expected_hours * $contract->contract_month);
                                        }else{
                                            $totals->expected_hours_ytd = $contract->expected_hours;
                                        }
                                    }/* else {
                                        $totals->expected_hours_ytd += ($contract->expected_hours * $contract->contract_month);
                                    }*/
                                    $totals_ytm->expected_payment += $expected_payment;
                                    $totals_ytm->rate += $contract->worked_hours != 0 ? $formula->amount_paid / $contract->worked_hours : 0;

                                    // $totals_ytm->actual_rate += $formula->actual_rate; // Commented by akash
                                    ($formula->actual_rate != 'N/A') ? $totals_ytm->actual_rate += $formula->actual_rate : $totals_ytm->actual_rate = $formula->actual_rate;
                                    $totals_ytm->amount_paid += $formula->amount_paid;
                                    $totals_ytm->amount_tobe_paid += $total_to_be_paid - $formula->amount_paid;
                                    if($contract->contract_month != 0){
                                        $totals_ytm->amount_monthly += ($totals_ytm->amount / $contract->contract_month);
                                    }else{
                                        $totals_ytm->amount_monthly += $totals_ytm->amount;
                                    }
                                    $totals_ytm->count++;
                                    $totals_ytm->contract_month = $contract->contract_month1;
                                    $totals_ytm->contract_term = $contract->contract_term;
                                }
                            }
                        }
                    }
                    if (0 == $j && count($physicians) - 1 == $i && !$is_last_row_written && $this->contract_length - 1 == $this->contract_count) {
                        //Log::info("inside ytd if ");
                        if($sheetIndex == 0) {
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("E{$current_row}:J{$current_row}")->applyFromArray($this->cell_bottom_style);
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("G{$current_row}:J{$current_row}")->applyFromArray($this->shaded_style);
                        }else{
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("G{$current_row}:L{$current_row}")->applyFromArray($this->cell_bottom_style);
                            $workbook->setActiveSheetIndex($sheetIndex)
                                ->getStyle("I{$current_row}:L{$current_row}")->applyFromArray($this->shaded_style);
                        }
                        $is_last_row_written = true;
                    }
                    //$totals_ytm->expected_hours_ytd = $totals->expected_hours_ytd;
                }

            }
        }

        $totals->expected_hours = $expected_hours;
        $totals->max_hours = $max_hours;
        $totals->annual_cap = $annual_cap;
        $totals_ytm -> expected_hours += $totals->expected_hours;
        $totals_ytm -> max_hours += $totals->max_hours;
        $totals_ytm -> annual_cap += $totals->annual_cap;
        $totals_ytm->expected_hours_ytd += $totals->expected_hours_ytd;


        if ($totals_ytm->count > 1){
            $this->writeTotalsYTM($workbook, $sheetIndex, $practice_header_row, $totals_ytm, false);
        }else{
            $this->write_single_totalYTM($workbook, $sheetIndex, $practice_header_row, $totals_ytm, true);
        }
        //$this->writeTotals($workbook, $sheetIndex, $physician_header_row, $totals, 0);
        if ($totals->count > 1){
            $this->writeTotals($workbook, $sheetIndex, $physician_header_row, $totals, 1);
        }else{
            $this->write_single_total($workbook, $sheetIndex, $physician_header_row, $totals);
        }
        $this -> writeCYTPMPhysicianMiddle($workbook, $sheetIndex, $physician_first_row, $current_row, $totals, $expected_hours,$max_hours, $annual_cap, true);
        return $current_row;
    }

    private function writeCYTPMPhysicianMiddle($workbook, $sheetIndex, $practice_first_row, $practice_last_row, $totals, $expected_hours,$max_hours,$annual_cap, $is_last_row){
        $practice_name_row = $practice_last_row - ($practice_last_row - $practice_first_row)/2;
        $practice_name_row = floor($practice_name_row);

        //Log::info("practice_last_row: ".$practice_last_row);
      //  Log::info("practice_name_row: ".$practice_first_row);
      //  Log::info("practice_name_row: ".$practice_name_row);

        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$practice_first_row}:B{$practice_last_row}")->applyFromArray($this -> period_breakdown_practice_style);
        $workbook->setActiveSheetIndex($sheetIndex)->mergeCells("B{$practice_name_row}:B{$practice_name_row}")->setCellValue("B{$practice_name_row}", $totals -> practice_name);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$practice_name_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("B{$practice_name_row}")->getAlignment()->setWrapText(true);

        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("C{$practice_first_row}:C{$practice_last_row}")->applyFromArray($this -> cytd_breakdown_practice_style);
        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("C{$practice_name_row}", $totals->physician_name);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("C{$practice_name_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("C{$practice_name_row}")->getAlignment()->setWrapText(true);

        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$practice_first_row}:D{$practice_last_row}")->applyFromArray($this -> cytd_breakdown_practice_style);
        $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("D{$practice_name_row}", $expected_hours);
        $workbook->setActiveSheetIndex($sheetIndex)->getStyle("D{$practice_name_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        if($sheetIndex == 1) {
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("E{$practice_first_row}:E{$practice_last_row}")->applyFromArray($this->cytd_breakdown_practice_style);
            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("E{$practice_name_row}", $max_hours);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("E{$practice_name_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("F{$practice_first_row}:F{$practice_last_row}")->applyFromArray($this->cytd_breakdown_practice_style);
            $workbook->setActiveSheetIndex($sheetIndex)->setCellValue("F{$practice_name_row}", $annual_cap);
            $workbook->setActiveSheetIndex($sheetIndex)->getStyle("F{$practice_name_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        if($is_last_row){
            $workbook->setActiveSheetIndex($sheetIndex)
                ->getStyle("B{$practice_last_row}:F{$practice_last_row}")->applyFromArray($this -> bottom_row_style);
        }

    }

    protected function writePeriodHeader($workbook, $sheet_index, $index, $start_date, $end_date){
        $current_row = $index;
        $report_header = "Contract Period: " . format_date($start_date) . " - " . format_date($end_date);
        if ($sheet_index == 0) {
            $current_row++;
            $workbook->setActiveSheetIndex($sheet_index)
                ->mergeCells("B{$current_row}:J{$current_row}")
                ->setCellValue("B{$current_row}", $report_header);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:J{$current_row}")->applyFromArray($this->period_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getRowDimension($current_row)->setRowHeight(-1);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:J{$current_row}")->getFont()->setBold(true);
        } else if ($sheet_index == 1) {
            $current_row++;

            $workbook->setActiveSheetIndex($sheet_index)
                ->mergeCells("B{$current_row}:L{$current_row}")
                ->setCellValue("B{$current_row}", $report_header);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:L{$current_row}")->applyFromArray($this->period_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:L{$current_row}")->applyFromArray($this->CYTPM_contract_period_bottom_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getRowDimension($current_row)->setRowHeight(-1);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:L{$current_row}")->getFont()->setBold(true);

        }

        //echo $current_row;
        return $current_row;

    }

    protected function writeContractHeader($workbook, $sheet_index, $index, $totals)
    {
        $current_row = $index;

        if ($sheet_index == 0) {
            $current_row++;
            $workbook->setActiveSheetIndex($sheet_index)
                ->mergeCells("B{$current_row}:J{$current_row}")
                ->setCellValue("B{$current_row}", $totals->contract_name);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:J{$current_row}")->applyFromArray($this->contract_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getRowDimension($current_row)->setRowHeight(-1);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:J{$current_row}")->getFont()->setBold(true);
            /*$workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}")->getFont()->setFontSize(14);*/
            //$workbook->setActiveSheetIndex($sheet_index)
            //    ->getRowDimension($current_row)->setRowHeight(-1);

        } else if ($sheet_index == 1) {
            $current_row++;

            $workbook->setActiveSheetIndex($sheet_index)
                ->mergeCells("B{$current_row}:L{$current_row}")
                ->setCellValue("B{$current_row}", $totals->contract_name);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:L{$current_row}")->applyFromArray($this->contract_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getRowDimension($current_row)->setRowHeight(-1);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:L{$current_row}")->getFont()->setBold(true);
            /* $workbook->setActiveSheetIndex($sheet_index)
                 ->getStyle("B{$current_row}")->getFont()->setFontSize(14);*/

            $this->resetTotals($totals);
        }

        //echo $current_row;
        return $current_row;
    }
    protected function write_single_totalYTM($workbook, $sheet_index, $index, $totals, $is_final_row){
        // Log::info("write_single_totalYTM");
        $current_row = $index;
        $current_row++;
        $hourly_rate = $totals->worked_hours && $totals->worked_hours!=0?$totals->amount_paid/$totals->worked_hours:0;
        $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")
            ->setCellValue("B{$current_row}", "{$totals-> practice_name} Totals")
            ->setCellValue("D{$current_row}", $totals->expected_hours)
            ->setCellValue("E{$current_row}", $totals->max_hours)
            ->setCellValue("F{$current_row}", $totals->annual_cap)
            ->setCellValue("G{$current_row}", $totals->expected_payment)
            ->setCellValue("H{$current_row}", $totals->expected_hours_ytd)
            ->setCellValue("I{$current_row}", $totals->worked_hours)
            ->setCellValue("J{$current_row}", $totals->amount_paid)
            ->setCellValue("K{$current_row}", $totals->amount_tobe_paid);

        $workbook->setActiveSheetIndex($sheet_index)
            ->getStyle("B{$current_row}:L{$current_row}")->getFont()->setBold(true);
        $workbook->setActiveSheetIndex($sheet_index)
            ->getStyle("B{$current_row}:L{$current_row}")->applyFromArray($this->contract_align);
        $workbook->setActiveSheetIndex($sheet_index)
            ->getStyle("I{$current_row}:L{$current_row}")->applyFromArray($this->contract_align);
        $workbook->setActiveSheetIndex($sheet_index)
            ->getStyle("N{$current_row}:L{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $workbook->setActiveSheetIndex($sheet_index)
            ->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $workbook->setActiveSheetIndex($sheet_index)
            ->getStyle("D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheet_index)
            ->getStyle("E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheet_index)
            ->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheet_index)
            ->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workbook->setActiveSheetIndex($sheet_index)
            ->getStyle("I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        if($is_final_row){
            $this -> resetTotalsYTM($totals);
        }
        return $current_row;
    }
    protected function write_single_total($workbook, $sheet_index, $index, $totals)
    {
        $current_row = $index;
        if ($sheet_index == 0){
            $current_row++;
            $hourly_rate = $totals->worked_hours && $totals->worked_hours!=0?$totals->amount_paid/$totals->worked_hours:0;
            $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")
                ->setCellValue("B{$current_row}", "{$totals->practice_name} Totals")
                // ->setCellValue("F{$current_row}", '1.00')
                // ->setCellValue("G{$current_row}", $totals->max_hours)
                ->setCellValue("D{$current_row}", $totals->expected_hours)
                ->setCellValue("E{$current_row}", $totals->expected_payment)
                ->setCellValue("F{$current_row}", $totals->worked_hours_main)
                ->setCellValue("G{$current_row}", $totals->amount_paid)
                ->setCellValue("H{$current_row}", $totals->amount_tobe_paid)
                ->setCellValue("I{$current_row}",$hourly_rate)
                ->setCellValue("J{$current_row}", $totals->fmv);


            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:J{$current_row}")->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:J{$current_row}")->applyFromArray($this->total_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("F{$current_row}:J{$current_row}")->applyFromArray($this-> cell_left_border_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }elseif($sheet_index == 1){
            $current_row++;
            $hourly_rate =  $totals->worked_hours!=0?$totals->amount_paid/$totals->worked_hours:0;
            $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")
                ->setCellValue("B{$current_row}", "{$totals->physician_name} Totals")
                ->setCellValue("D{$current_row}", $totals->expected_hours)
                ->setCellValue("E{$current_row}", $totals->max_hours)
                ->setCellValue("F{$current_row}", $totals->annual_cap)
                ->setCellValue("G{$current_row}", $totals->expected_payment)
                ->setCellValue("H{$current_row}", $totals->expected_hours_ytd)
                ->setCellValue("I{$current_row}", $totals->worked_hours)
                ->setCellValue("J{$current_row}", $totals->amount_paid)
                ->setCellValue("K{$current_row}", $totals->amount_tobe_paid);

            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:L{$current_row}")->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:L{$current_row}")->applyFromArray($this->total_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("I{$current_row}:L{$current_row}")->applyFromArray($this-> cell_left_border_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        }
        $this->resetTotals($totals);
        return $current_row;
    }


    protected function writeTotals($workbook, $sheet_index, $index, $totals, $practice_row)
    {
        // Log::info("writeTotals starts index:".$index);

        //echo $sheet_index;
        $current_row = $index;
        //echo $current_row;
        // Calculate the actual hourly rate for the practice.
        //$totals->actual_rate = $totals->amount_main / $totals->worked_hours_main;

        if ($totals->count > 1) {
            $average_fmv = ($totals->rate * 1.1) / $totals->count;
            $has_payment = $totals->has_override || $average_fmv >= $totals->actual_rate;
            //$pmt_status = (($totals->amount_paid + $totals->expected_payment)/$contract->worked_hours) < $contract->rate;
            if ($sheet_index == 0) {
                $current_row++;
                $hourly_rate = $totals->worked_hours && $totals->worked_hours!=0?$totals->amount_paid/$totals->worked_hours:0;
                $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")
                    ->setCellValue("B{$current_row}", "{$totals->practice_name} Totals")
                    // ->setCellValue("F{$current_row}", '1.00')
                    // ->setCellValue("G{$current_row}", $totals->max_hours)
                    ->setCellValue("D{$current_row}", $totals->expected_hours)
                    ->setCellValue("E{$current_row}", $totals->expected_payment)
                    ->setCellValue("F{$current_row}", $totals->worked_hours_main)
                    ->setCellValue("G{$current_row}", $totals->amount_paid)
                    ->setCellValue("H{$current_row}", $totals->amount_tobe_paid)
                    ->setCellValue("I{$current_row}",$hourly_rate)
                    ->setCellValue("J{$current_row}", $totals->fmv);


                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:J{$current_row}")->getFont()->setBold(true);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:J{$current_row}")->applyFromArray($this->total_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("F{$current_row}:J{$current_row}")->applyFromArray($this-> cell_left_border_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                //$workbook->setActiveSheetIndex($sheet_index)
                //    ->getStyle("I{$current_row}:K{$current_row}")->applyFromArray($this->shaded_style);
            } else if ($sheet_index == 1) {
                //echo "hey";
                $current_row++;

                $hourly_rate =  $totals->worked_hours!=0?$totals->amount_paid/$totals->worked_hours:0;
                $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")
                    ->setCellValue("B{$current_row}", "{$totals->physician_name} Totals")
                    ->setCellValue("D{$current_row}", $totals->expected_hours)
                    ->setCellValue("E{$current_row}", $totals->max_hours)
                    ->setCellValue("F{$current_row}", $totals->annual_cap)
                    ->setCellValue("G{$current_row}", $totals->expected_payment)
                    ->setCellValue("H{$current_row}", $totals->expected_hours_ytd)
                    ->setCellValue("I{$current_row}", $totals->worked_hours)
                    ->setCellValue("J{$current_row}", $totals->amount_paid)
                    ->setCellValue("K{$current_row}", $totals->amount_tobe_paid);

                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:L{$current_row}")->getFont()->setBold(true);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:L{$current_row}")->applyFromArray($this->total_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("I{$current_row}:L{$current_row}")->applyFromArray($this-> cell_left_border_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("E{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


            }
        }
        if($practice_row){
            $this->resetTotals($totals);
        }
        // Log::info("writeTotals ends");
        return $current_row;
    }

    protected function writeTotalsYTM($workbook, $sheet_index, $index, $totals, $is_final_row)
    {
        // Log::info("inside writeTotalsYTM totals->worked_hours :".$totals->worked_hours);

        $current_row = $index;
        $hourly_rate = $totals->worked_hours && $totals->worked_hours!=0?$totals->amount_paid/$totals->worked_hours:0;
        // Calculate the actual hourly rate for the practice.
        //$totals->actual_rate = $totals->amount_main / $totals->worked_hours_main;

        if ($totals->count > 1) {
            $average_fmv = ($totals->rate * 1.1) / $totals->count;
            $has_payment = $totals->has_override || $average_fmv >= $totals->actual_rate;

            if ($sheet_index == 0) {
                $current_row++;

                $workbook->setActiveSheetIndex($sheet_index)
                    ->setCellValue("B{$current_row}", $totals->fmv > $hourly_rate && $totals->worked_hours!=0?'Y':'N')
                    ->setCellValue("C{$current_row}", "{$totals->practice_name}")
                    ->setCellValue("D{$current_row}", "{$totals->physician_name}")
                    //->setCellValue("E{$current_row}", "{$totals->speciality_name}")

                    // ->setCellValue("F{$current_row}", '1.00')
                    // ->setCellValue("G{$current_row}", $totals->max_hours)
                    ->setCellValue("F{$current_row}", $totals->expected_hours)
                    ->setCellValue("G{$current_row}", $totals->expected_payment)
                    ->setCellValue("H{$current_row}", $totals->worked_hours_main)
                    ->setCellValue("I{$current_row}", $totals->amount_paid)
                    ->setCellValue("J{$current_row}", $totals->amount_tobe_paid)
                    ->setCellValue("K{$current_row}", $hourly_rate)
                    ->setCellValue("L{$current_row}", $totals->rate);

                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}")->applyFromArray(($totals->fmv > $hourly_rate && $totals->worked_hours!=0) ? $this->green_style : $this->red_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:K{$current_row}")->getFont()->setBold(true);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:K{$current_row}")->applyFromArray($this->cell_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("J{$current_row}:K{$current_row}")->applyFromArray($this->shaded_style);
            } else if ($sheet_index == 1) {
                $current_row++;
                $hourly_rate = $totals->worked_hours && $totals->worked_hours!=0?$totals->amount_paid/$totals->worked_hours:0;
                // Log::info("inside writeTotalsYTM sheet_index 1totals->worked_hours :".$totals->worked_hours);
                $workbook->setActiveSheetIndex($sheet_index)->mergeCells("B{$current_row}:C{$current_row}")
                    ->setCellValue("B{$current_row}", "{$totals-> practice_name} Totals")
                    ->setCellValue("D{$current_row}", $totals->expected_hours)
                    ->setCellValue("E{$current_row}", $totals->max_hours)
                    ->setCellValue("F{$current_row}", $totals->annual_cap)
                    ->setCellValue("G{$current_row}", $totals->expected_payment)
                    ->setCellValue("H{$current_row}", $totals->expected_hours_ytd)
                    ->setCellValue("I{$current_row}", $totals->worked_hours)
                    ->setCellValue("J{$current_row}", $totals->amount_paid)
                    ->setCellValue("K{$current_row}", $totals->amount_tobe_paid);

                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:N{$current_row}")->getFont()->setBold(true);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:L{$current_row}")->applyFromArray($this->contract_align);
                /*$workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("K{$current_row}:N{$current_row}")->applyFromArray($this->contract_align);*/
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("J{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            }
        } else {
            // Log::info("else totals->count:");
            //$workbook->setActiveSheetIndex($sheet_index)->getStyle("C{$current_row}:N{$current_row}")->getFont()->setBold(true);
            if($is_final_row){
                // Log::info("is final row: ".$is_final_row);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:J{$current_row}")->applyFromArray($this->cell_bottom_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("G{$current_row}:J{$current_row}")->applyFromArray($this-> cell_left_border_style);
            }
        }

        $this->resetTotalsYTM($totals);

        return $current_row;
    }

    protected function resetPhysicanAndPractice($totals){
        $totals -> physician_name = null;
        $totals -> practice_name = null;
    }
    protected function resetTotals($totals)
    {
        $totals->expected_hours = 0.0;
        $totals->expected_hours_ytd = 0.0;
        $totals->annual_cap = 0;
        //$totals->worked_hours_main = 0.0;change done for check calculations
        $totals->worked_hours_main = 0.0;
        $totals->worked_hours = 0.0;
        $totals->days_on_call = 0.0;
        $totals->rate = 0.0;
        $totals->actual_rate = 0.0;
        $totals->paid = 0.0;
        $totals->amount_paid = 0.0;
        $totals->amount_tobe_paid = 0.0;
        $totals->amount_main = 0.0;
        $totals->amount_monthly = 0.0;
        $totals->count = 0.0;
        $totals->has_override = false;
        $totals->expected_payment = 0;
        $totals->max_hours = 0;
        $totals->pmt_status = 'Y';
        $totals->fmv = 0;
    }

    protected function resetTotalsYTM($totals_ytm)
    {
        $totals_ytm->expected_hours = 0.0;
        $totals_ytm->expected_hours_ytd = 0.0;
        $totals_ytm->annual_cap = 0;
        $totals_ytm->worked_hours_main = 0.0;
        $totals_ytm->worked_hours = 0.0;
        $totals_ytm->days_on_call = 0.0;
        $totals_ytm->rate = 0.0;
        $totals_ytm->actual_rate = 0.0;
        $totals_ytm->paid = 0.0;
        $totals_ytm->amount_paid = 0.0;
        $totals_ytm->amount_tobe_paid = 0.0;
        $totals_ytm->amount_main = 0.0;
        $totals_ytm->amount_monthly = 0.0;
        $totals_ytm->count = 0.0;
        $totals_ytm->has_override = false;
        $totals_ytm->expected_payment = 0;
        $totals_ytm->max_hours = 0;
        $totals_ytm->pmt_status = 'Y';
        $totals_ytm->fmv = 0;
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
