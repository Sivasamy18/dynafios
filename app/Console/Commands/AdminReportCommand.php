<?php
namespace App\Console\Commands;

use Carbon\Carbon;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Support\Facades\DB;
use DateTime;
use StdClass;
use App\Agreement;
use App\ContractName;
use App\AdminReport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use function App\Start\admin_report_path;

class AdminReportCommand extends ReportingCommand
{
    protected $name = 'reports:admin';
    protected $description = 'Generates a DYNAFIOS administrative report.';
    private $cell_style = [
        'borders' => [
            'outline' => ['borderStyle'  => Border::BORDER_THIN],
            'inside' => ['borderStyle'  => Border::BORDER_THIN]
        ]
    ];

    private $red_style = [
        'fill' => ['fillType'  => Fill::FILL_SOLID, 'color' => ['rgb' => 'ffc7ce']],
        'font' => ['color' => ['rgb' => '9c0006'], 'bold' => true],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ];

    private $green_style = [
        'fill' => ['fillType'  => Fill::FILL_SOLID, 'color' => ['rgb' => 'c6efce']],
        'font' => ['color' => ['rgb' => '006100'], 'bold' => true],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ];


    private $shaded_style = [
        'fill' => [
            'fillType'  => Fill::FILL_SOLID,
            'color' => ['rgb' => 'eeece1']
        ],
        'borders' => [
            'left' => ['borderStyle'  => Border::BORDER_MEDIUM],
            'right' => ['borderStyle'  => Border::BORDER_MEDIUM]
        ]
    ];

    private $total_style = [
        'fill' => ['fillType'  => Fill::FILL_SOLID, 'color' => ['rgb' => 'dbeef4']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        'borders' => [
            'left' => ['borderStyle'  => Border::BORDER_THIN],
            'right' => ['borderStyle'  => Border::BORDER_MEDIUM],
            'outline' => ['borderStyle'  => Border::BORDER_THIN],
            'inside' => ['borderStyle'  => Border::BORDER_THIN]
        ]
    ];

    private $contract_style = [
        'fill' => ['fillType'  => Fill::FILL_SOLID, 'color' => ['rgb' => '000000']],
        'font' => ['color' => ['rgb' => 'FFFFFF']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        'borders' => [
            'allborders' => ['borderStyle'  => Border::BORDER_THICK,'color' => ['rgb' => '000000']],
            //'inside' => ['borderStyle'  => Border::BORDER_MEDIUM,'color' => ['rgb' => 'FF0000']]
        ]
    ];

    public function __invoke()
    {
        //echo "hey";die;
        $arguments = $this->parseArguments();
        //echo "hey";die;
        // $workbook = $this->loadTemplate('administrative.xlsx');

        $now = Carbon::now();
		$timezone = $now->timezone->getName();
		$timestamp = format_date((exec('time /T')), "h:i A");
        
        //Load template using phpSpreadsheet
        $reader = IOFactory::createReader("Xlsx");
		$workbook = $reader->load(storage_path()."/reports/templates/administrative.xlsx");

        $report_header = '';
        $report_header .= "DYNAFIOS DASHBOARD\n";
        $report_header .= "Period Report\n";
        $report_header .= "Run Date: " . with(new DateTime('now'))->format("m/d/Y");

        $report_header_ytm = '';
        $report_header_ytm .= "DYNAFIOS DASHBOARD\n";
        $report_header_ytm .= "Contract Year to Prior Month Report\n";
        $report_header_ytm .= "Run Date: " . with(new DateTime('now'))->format("m/d/Y");

        $report_header_ytd = '';
        $report_header_ytd .= "DYNAFIOS DASHBOARD\n";
        $report_header_ytd .= "Contract Year To Date Report\n";
        $report_header_ytd .= "Run Date: " . with(new DateTime('now'))->format("m/d/Y");

        $workbook->setActiveSheetIndex(0)->setCellValue('A1', $report_header);
        $workbook->setActiveSheetIndex(1)->setCellValue('A1', $report_header_ytm);
        $workbook->setActiveSheetIndex(2)->setCellValue('A1', $report_header_ytd);

        $period_index = 4;
        $ytm_index = 4;
        $ytd_index = 4;
//echo "<pre>";
//print_r($arguments);die;

        foreach ($arguments as $argument) {

            //echo $argument->end_date;die;
            $contracts = $this->queryContracts($argument->id, $argument->start_date, $argument->end_date);
            //echo "<pre>";
//print_r($contracts->period);die;
            $period_index = $this->writeData($workbook, 0, $period_index, $contracts->period,$argument->start_date, $argument->end_date);
            $ytm_index = $this->writeDataYTD($workbook, 1, $ytm_index, $contracts->year_to_month,$argument->start_date, $argument->end_date);
            $ytd_index = $this->writeDataYTD($workbook, 2, $ytd_index, $contracts->year_to_date,$argument->start_date, $argument->end_date);
        }

        $report_path = admin_report_path();
        // $report_filename = "report_" . date('mdYhis') . ".xlsx";
        $report_filename = "report_" . date('mdY') . "_" . str_replace(":", "", $timestamp) . "_" . $timezone . ".xlsx";
        
        if (!file_exists($report_path)) {
            mkdir($report_path, 0777, true);
        }

        $writer = IOFactory::createWriter($workbook, 'Xlsx');
        $writer->save("{$report_path}/{$report_filename}");

        $admin_report = new AdminReport;
        $admin_report->filename = $report_filename;
        $admin_report->save();

        $this->success('reports.generate_success', $admin_report->id);
    }

    protected function queryContracts($agreement_id, $start_date, $end_date)
    {
        $agreement = Agreement::findOrFail($agreement_id);

        $start_date = mysql_date($start_date);
        $end_date = mysql_date($end_date);

        $contract_month = months($agreement->start_date, 'now');
        $contract_term = months($agreement->start_date, $agreement->end_date);
        $log_range = months($start_date, $end_date);

        if ($contract_month > $contract_term)
            $contract_month = $contract_term;

        $results = new StdClass;
        $py_data = DB::table('agreements')->select(DB::raw("physician_practices.physician_id as physician_id"),DB::raw("practices.name as practice_name"))
            ->join('hospitals', 'hospitals.id', '=', 'agreements.hospital_id')
            ->join('practices', 'practices.hospital_id', '=', 'hospitals.id')

            //drop column practice_id from table 'physicians' changes by 1254
            // ->join("physicians", "physicians.practice_id", "=", "practices.id")
            ->join("physician_practices", "physician_practices.practice_id", "=", "practices.id")

            
            ->orderBy('practices.id',"asc")
            ->groupBy('physicians.id','practices.name')
            ->where('agreements.id', '=', $agreement_id)
            ->get();
        $count_py = 0;
        foreach ($py_data as $py_data1) {
            //echo $py_data1->physician_id;
            $physicians1[$count_py] = $py_data1->physician_id;
            $count_py++;
            # code...
        }
        //print_r($physicians1);die;
        $i = 0;
        foreach ($physicians1 as $physician) {
            $period[$i] = DB::table('physician_logs')->select(
                DB::raw("contracts.agreement_id as agreement_id"),
                DB::raw("hospitals.name as hospital_name"),
                DB::raw("hospitals.city as hospital_city"),
                DB::raw("states.name as hospital_state"),
                DB::raw("practices.name as practice_name"),
                DB::raw("practices.id as practice_id"),
                DB::raw("physicians.id as physician_id"),
                DB::raw("physicians.npi as physician_npi"),
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
                DB::raw("sum(physician_logs.duration) as worked_hours"),
                DB::raw("'{$contract_month}' as contract_month"),
                DB::raw("'{$contract_term}' as contract_term"),
                DB::raw("'{$log_range}' as log_range")
            )
                ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                ->join('physicians', 'physicians.id', '=', 'contracts.physician_id')
                ->join('specialties', 'specialties.id', '=', 'physicians.specialty_id')


                //drop column practice_id from table 'physicians' changes by 1254
                ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
                ->join('practices', 'practices.id', '=', 'physician_practices.practice_id')
                
                ->join('hospitals', 'hospitals.id', '=', 'practices.hospital_id')
                ->join('states', 'states.id', '=', 'hospitals.state_id')
                ->where('contracts.agreement_id', '=', $agreement_id)
                ->where( "contracts.end_date", "=", "0000-00-00 00:00:00")
                ->where('physicians.id',"=", $physician)
                ->whereBetween('physician_logs.date', [$start_date, $end_date])
                ->groupBy('physicians.id', 'agreements.id', 'contracts.id')
                ->orderBy('contract_types.name', 'asc')
                ->orderBy('practices.name', 'asc')
                ->orderBy('physicians.last_name', 'asc')
                ->orderBy('physicians.first_name', 'asc');

            $period2[$i] = DB::table('physicians')->select(
                DB::raw("contracts.agreement_id as agreement_id"),
                DB::raw("hospitals.name as hospital_name"),
                DB::raw("hospitals.city as hospital_city"),
                DB::raw("states.name as hospital_state"),
                DB::raw("practices.name as practice_name"),
                DB::raw("practices.id as practice_id"),
                DB::raw("physicians.id as physician_id"),
                DB::raw("physicians.npi as physician_npi"),
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
                DB::raw("0 as worked_hours"),
                DB::raw("'{$contract_month}' as contract_month"),
                DB::raw("'{$contract_term}' as contract_term"),
                DB::raw("'{$log_range}' as log_range")
            )
                ->join('contracts', 'contracts.physician_id', '=', 'physicians.id')
                ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                ->join('specialties', 'specialties.id', '=', 'physicians.specialty_id')

                //drop column practice_id from table 'physicians' changes by 1254
                ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
                ->join('practices', 'practices.id', '=', 'physician_practices.practice_id')

                ->join('hospitals', 'hospitals.id', '=', 'practices.hospital_id')
                ->join('states', 'states.id', '=', 'hospitals.state_id')
                ->where('contracts.agreement_id', '=', $agreement_id)
                ->where( "contracts.end_date", "=", "0000-00-00 00:00:00")
                ->where('physicians.id',"=", $physician)
                ->groupBy('physicians.id', 'agreements.id', 'contracts.id')
                ->orderBy('contract_types.name', 'asc')
                ->orderBy('practices.name', 'asc')
                ->orderBy('physicians.last_name', 'asc')
                ->orderBy('physicians.first_name', 'asc');
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
            $contract_month = months($agreement->start_date, $last_date_month);
            //echo $contract_month;
            //echo $last_date_month;die;
            $j=0;


            foreach ($physicians1 as $physician) {
                //echo $physician;
                $year_to_month[$i][$j] = DB::table('physician_logs')->select(
                    DB::raw("hospitals.name as hospital_name"),
                    DB::raw("hospitals.city as hospital_city"),
                    DB::raw("states.name as hospital_state"),
                    DB::raw("practices.name as practice_name"),
                    DB::raw("practices.id as practice_id"),
                    DB::raw("physicians.id as physician_id"),
                    DB::raw("physicians.npi as physician_npi"),
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
                    DB::raw("sum(physician_logs.duration) as worked_hours"),
                    DB::raw("'{$contract_month}' as contract_month"),
                    DB::raw("'{$contract_term}' as contract_term"),
                    DB::raw("'{$contract_month}' as log_range"),
                    DB::raw("'{$end_month_date}' as start_date_check"),
                    DB::raw("'{$last_date_month}' as end_date_check")
                )
                    ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                    ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                    ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                    ->join('physicians', 'physicians.id', '=', 'contracts.physician_id')
                    ->join('specialties', 'specialties.id', '=', 'physicians.specialty_id')
                    //drop column practice_id from table 'physicians' changes by 1254
                    ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
                    ->join('practices', 'practices.id', '=', 'physician_practices.practice_id')
                    ->join('hospitals', 'hospitals.id', '=', 'practices.hospital_id')
                    ->join('states', 'states.id', '=', 'hospitals.state_id')
                    ->where('contracts.agreement_id', '=', $agreement_id)
                    ->where( "contracts.end_date", "=", "0000-00-00 00:00:00")
                    ->where('physicians.id', '=', $physician)
                    ->whereBetween('physician_logs.date',array($end_month_date,$last_date_month))
                    ->groupBy('physicians.id', 'agreements.id', 'contracts.id')
                    ->orderBy('contract_types.name', 'asc')
                    ->orderBy('practices.name', 'asc')
                    ->orderBy('physicians.last_name', 'asc')
                    ->orderBy('physicians.first_name', 'asc');

                $year_to_month2[$i][$j] = DB::table('physicians')->select(
                    DB::raw("hospitals.name as hospital_name"),
                    DB::raw("hospitals.city as hospital_city"),
                    DB::raw("states.name as hospital_state"),
                    DB::raw("practices.name as practice_name"),
                    DB::raw("practices.id as practice_id"),
                    DB::raw("physicians.id as physician_id"),
                    DB::raw("physicians.npi as physician_npi"),
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
                    DB::raw("0 as worked_hours"),
                    DB::raw("'{$contract_month}' as contract_month"),
                    DB::raw("'{$contract_term}' as contract_term"),
                    DB::raw("'{$contract_month}' as log_range"),
                    DB::raw("'{$end_month_date}' as start_date_check"),
                    DB::raw("'{$last_date_month}' as end_date_check")
                )
                    ->join('contracts', 'contracts.physician_id', '=', 'physicians.id')
                    ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                    ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                    ->join('specialties', 'specialties.id', '=', 'physicians.specialty_id')

                    //drop column practice_id from table 'physicians' changes by 1254
                    ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
                    ->join('practices', 'practices.id', '=', 'physician_practices.practice_id')
                   
                    ->join('hospitals', 'hospitals.id', '=', 'practices.hospital_id')
                    ->join('states', 'states.id', '=', 'hospitals.state_id')
                    ->where('contracts.agreement_id', '=', $agreement_id)
                    ->where( "contracts.end_date", "=", "0000-00-00 00:00:00")
                    ->where('physicians.id', '=', $physician)
                    ->groupBy('physicians.id', 'agreements.id', 'contracts.id')
                    ->orderBy('contract_types.name', 'asc')
                    ->orderBy('practices.name', 'asc')
                    ->orderBy('physicians.last_name', 'asc')
                    ->orderBy('physicians.first_name', 'asc');

                $j++;
            }
        }

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
            $contract_month = months($agreement->start_date, $last_date_month);
            $j = 0;
            foreach ($physicians1 as $physician) {
                $year_to_date[$i][$j] = DB::table('physician_logs')->select(
                    DB::raw("hospitals.name as hospital_name"),
                    DB::raw("hospitals.city as hospital_city"),
                    DB::raw("states.name as hospital_state"),
                    DB::raw("practices.name as practice_name"),
                    DB::raw("practices.id as practice_id"),
                    DB::raw("physicians.id as physician_id"),
                    DB::raw("physicians.npi as physician_npi"),
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
                    DB::raw("sum(physician_logs.duration) as worked_hours"),
                    DB::raw("'{$contract_month}' as contract_month"),
                    DB::raw("'{$contract_term}' as contract_term"),
                    DB::raw("'{$contract_month}' as log_range"),
                    DB::raw("'{$end_month_date}' as start_date_check"),
                    DB::raw("'{$last_date_month}' as end_date_check")
                )
                    ->join('contracts', 'contracts.id', '=', 'physician_logs.contract_id')
                    ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                    ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                    ->join('physicians', 'physicians.id', '=', 'contracts.physician_id')
                    ->join('specialties', 'specialties.id', '=', 'physicians.specialty_id')

                    //drop column practice_id from table 'physicians' changes by 1254
                    ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
                    ->join('practices', 'practices.id', '=', 'physician_practices.practice_id')

                    ->join('hospitals', 'hospitals.id', '=', 'practices.hospital_id')
                    ->join('states', 'states.id', '=', 'hospitals.state_id')
                    ->where('contracts.agreement_id', '=', $agreement_id)
                    ->where( "contracts.end_date", "=", "0000-00-00 00:00:00")
                    ->whereBetween('physician_logs.date',array($end_month_date,$last_date_month))
                    ->where('physicians.id', '=', $physician)
                    ->groupBy('physicians.id', 'agreements.id', 'contracts.id')
                    ->orderBy('contract_types.name', 'asc')
                    ->orderBy('practices.name', 'asc')
                    ->orderBy('physicians.last_name', 'asc')
                    ->orderBy('physicians.first_name', 'asc');

                $year_to_date2[$i][$j] = DB::table('physicians')->select(
                    DB::raw("hospitals.name as hospital_name"),
                    DB::raw("hospitals.city as hospital_city"),
                    DB::raw("states.name as hospital_state"),
                    DB::raw("practices.name as practice_name"),
                    DB::raw("practices.id as practice_id"),
                    DB::raw("physicians.id as physician_id"),
                    DB::raw("physicians.npi as physician_npi"),
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
                    DB::raw("0 as worked_hours"),
                    DB::raw("'{$contract_month}' as contract_month"),
                    DB::raw("'{$contract_term}' as contract_term"),
                    DB::raw("'{$contract_month}' as log_range"),
                    DB::raw("'{$end_month_date}' as start_date_check"),
                    DB::raw("'{$last_date_month}' as end_date_check")
                )
                    ->join('contracts', 'contracts.physician_id', '=', 'physicians.id')
                    ->join('contract_types', 'contract_types.id', '=', 'contracts.contract_type_id')
                    ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
                    ->join('specialties', 'specialties.id', '=', 'physicians.specialty_id')

                    //drop column practice_id from table 'physicians' changes by 1254
                    ->join('physician_practices', 'physician_practices.physician_id', '=', 'physicians.id')
                    ->join('practices', 'practices.id', '=', 'physician_practices.practice_id')
                    
                    ->join('hospitals', 'hospitals.id', '=', 'practices.hospital_id')
                    ->join('states', 'states.id', '=', 'hospitals.state_id')
                    ->where('contracts.agreement_id', '=', $agreement_id)
                    ->where( "contracts.end_date", "=", "0000-00-00 00:00:00")
                    ->where('physicians.id', '=', $physician)
                    ->groupBy('physicians.id', 'agreements.id', 'contracts.id')
                    ->orderBy('contract_types.name', 'asc')
                    ->orderBy('practices.name', 'asc')
                    ->orderBy('physicians.last_name', 'asc')
                    ->orderBy('physicians.first_name', 'asc');

                $j++;
            }
        }

        //$results->period = $period->get();
        $temp = 0;
        foreach ($period as $period_query1) {
            //echo $temp;
            $results->period[$temp] = $period_query1->first();
            if(empty($results->period[$temp]))
            {
                //echo $temp;
                $results->period[$temp] = $period2[$temp]->first();
                //$queries = DB::getQueryLog();
                //$last_query = end($queries);
                //print_r($last_query);die;
            }
            $temp++;
        }
        $results->period = array_values(array_filter($results->period));
        //echo "<pre>";
        //print_r($results->period);die;
        //$results->year_to_month = $year_to_month_query->get();
        $results->year_to_month = array();

        $temp = 0;

        foreach($year_to_month as $year_to_date_query_arr)
        {
            $temp1 = 0;
            foreach ($year_to_date_query_arr as $year_to_date_query_arr2) {
                # code...
                $results->year_to_month[$temp][$temp1] = $year_to_date_query_arr2->first();
                if(empty($results->year_to_month[$temp][$temp1]))
                {
                    $results->year_to_month[$temp][$temp1] = $year_to_month2[$temp+1][$temp1]->first();
                }
                $temp1++;
            }
            $temp++;

        }

        $results->year_to_month = array_values(array_filter($results->year_to_month));
        //print_r($results->year_to_month);die;
        $results->year_to_date = array();

        $temp = 0;
        foreach($year_to_date as $year_to_date_query_arr)
        {
            //$results->year_to_date[$temp] = $year_to_date_query_arr->get();
            //$temp++;
            $temp1 = 0;
            foreach ($year_to_date_query_arr as $year_to_date_query_arr2) {
                # code...
                $results->year_to_date[$temp][$temp1] = $year_to_date_query_arr2->first();
                if(empty($results->year_to_date[$temp][$temp1]))
                {
                    $results->year_to_date[$temp][$temp1] = $year_to_date2[$temp+1][$temp1]->first();
                }
                $temp1++;
            }
            $temp++;
        }

        foreach ($results->period as $result) {
            if ($result->contract_name_id)
                $result->contract_name = ContractName::findOrFail($result->contract_name_id)->name;
        }
//print_r($results->period);die;
        /* foreach ($results->year_to_date as $result) {
            if ($result->contract_name_id)
                $result->contract_name = ContractName::findOrFail($result->contract_name_id)->name;
        } */
        //echo "<pre>";
        //print_r($results->year_to_month);
        $i = 0;
        foreach ($results->year_to_month as $result1) {
            $results->year_to_month[$i] = array_values(array_filter($result1));
            $i++;
        }
        foreach ($results->year_to_month as $result1) {
            //print_r($result1);die;
            //$result1 = array_values(array_filter($result1));
            //echo "<pre>";
            //print_r($result1);die;
            foreach ($result1 as $result) {
                if ($result->contract_name_id) {
                    $result->contract_name = ContractName::findOrFail($result->contract_name_id)->name;
                }
            }
        }
        //echo "<pre>";
        //print_r($results->year_to_month);die;
        $i = 0;
        foreach ($results->year_to_date as $result1) {
            $results->year_to_date[$i] = array_values(array_filter($result1));
            $i++;
        }
        foreach ($results->year_to_date as $result1) {
            foreach ($result1 as $result) {
                if ($result->contract_name_id) {
                    $result->contract_name = ContractName::findOrFail($result->contract_name_id)->name;
                }
            }
        }
        //echo "<pre>";
        //print_r($results->year_to_date);die;
        return $results;
    }

    protected function writeData($workbook, $sheet_index, $index, $contracts,$startDate,$end_date)
    {
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
        $totals->paid = 0.0;
        $totals->amount = 0.0;
        $totals->amount_paid = 0.0;
        $totals->amount_monthly = 0.0;
        $totals->count = 0.0;
        $totals->has_override = false;
        $totals->worked_hours = 0.0;
        $totals->expected_payment = 0.0;
        $totals->max_hours = 0.0;
        $totals->pmt_status = 'Y';
        $totals->fmv = 0.0;

        foreach ($contracts as $contract) {
            $contract->month_end_date = mysql_date(date($end_date));
            $amount = DB::table('amount_paid')
                ->where('start_date', '<=' , mysql_date(date($startDate)))
                ->where('end_date', '>=' , mysql_date(date($startDate)))
                ->where('physician_id','=',$contract->physician_id )
                ->where('contract_id','=',$contract->contract_id )
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->first();
            if (isset($amount->amountPaid)) {
                $contract->amount_paid = $amount->amountPaid;
            }else{
                $contract->amount_paid = 0;
            }
            $formula = $this->applyFormula($contract);
            $month = mysql_date(date($startDate));
            $monthArr = explode("-",$month);
            /*$amount = DB::table('amount_paid')
                ->where('start_date', '<=' , $month)
                ->where('end_date', '>=' , $month)
                ->where('physician_id','=',$contract->physician_id )
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->first();*/
            //$queries = DB::getQueryLog();
            //$last_query = end($queries);
            //print_r($last_query);die;

            if(isset($amount->amountPaid))
            {
                $formula->amount_paid = $amount->amountPaid;
            }
            else
            {
                $formula->amount_paid = 0;
            }
            $contract->expected_payment = $contract->expected_hours * $contract->rate;


            if($contract->worked_hours !=0)
                $pmt_status = (($formula->amount_paid)/$contract->worked_hours) <= $contract->rate;
            else
                $pmt_status = false;

            if ($sheet_index == 0) {
                //echo "hello";
                //echo $pmt_status;
                //echo $pmt_status ? 'Y' : 'N';
                $workbook->setActiveSheetIndex($sheet_index)
                    ->setCellValue("A{$current_row}", $pmt_status ? 'Y' : 'N')
                    ->setCellValue("B{$current_row}", $contract->hospital_name)
                    ->setCellValue("C{$current_row}", $contract->hospital_city)
                    ->setCellValue("D{$current_row}", $contract->hospital_state)
                    ->setCellValue("E{$current_row}", $contract->practice_name)
                    ->setCellValue("F{$current_row}", $contract->physician_npi)
                    ->setCellValue("G{$current_row}", $contract->physician_name)
                    ->setCellValue("H{$current_row}", $contract->specialty_name)
                    ->setCellValue("I{$current_row}", $contract->contract_name)
                    //->setCellValue("J{$current_row}", $contract->min_hours)
                    //->setCellValue("K{$current_row}", $contract->max_hours)
                    ->setCellValue("J{$current_row}", $contract->expected_hours)
                    ->setCellValue("K{$current_row}", $contract->expected_payment)
                    ->setCellValue("L{$current_row}", $contract->worked_hours)
                    ->setCellValue("M{$current_row}", $formula->amount_paid)
                    ->setCellValue("N{$current_row}", $contract->worked_hours?$formula->amount_paid/$contract->worked_hours:0)
                    ->setCellValue("O{$current_row}", $contract->rate)
                    ->setCellValue("P{$current_row}", $formula->days_on_call);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("A{$current_row}")
                    ->applyFromArray($pmt_status ? $this->green_style : $this->red_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("L{$current_row}:P{$current_row}")->applyFromArray($this->shaded_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("F{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("P{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                //$workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$current_row}")->getNumberFormat()->setFormatCode('0.00');
                //$workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$current_row}")->getNumberFormat()->setFormatCode('0.00');
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$current_row}")->getNumberFormat()->setFormatCode('0.00');
                $workbook->setActiveSheetIndex($sheet_index)->getStyle("L{$current_row}")->getNumberFormat()->setFormatCode('0.00');
                $totals->hospital_name = $contract->hospital_name;
                $totals->worked_hours += $contract->worked_hours;
                $totals->amount_monthly += $contract->worked_hours?$formula->amount_paid/$contract->worked_hours:0.00;
                $totals->expected_payment += $contract->expected_payment;
                $totals->days_on_call += ($formula->days_on_call != 'N/A') ? $formula->days_on_call : 0; //Changed by akash
                $totals->amount_paid += $formula->amount_paid;
                $totals->rate = $contract->rate;
                $current_row++;
            }
        }
//die;
        if(isset($totals->hospital_name))
        {
            if($totals->worked_hours !=0)
                $pmt_status = (($totals->amount_paid)/$totals->worked_hours) <= $totals->rate;
            else
                $pmt_status = false;
            $workbook->setActiveSheetIndex($sheet_index)
                ->mergeCells("B{$current_row}:D{$current_row}")
                ->setCellValue("A{$current_row}", $pmt_status ? 'Y' : 'N')
                ->setCellValue("B{$current_row}", $totals->hospital_name." Totals")
                ->setCellValue("E{$current_row}", "-")
                ->setCellValue("F{$current_row}", "-")
                ->setCellValue("G{$current_row}", "-")
                ->setCellValue("H{$current_row}", "-")
                ->setCellValue("I{$current_row}", "-")
                //->setCellValue("J{$current_row}", $contract->min_hours)
                //->setCellValue("K{$current_row}", $contract->max_hours)
                ->setCellValue("J{$current_row}", "-")
                ->setCellValue("K{$current_row}", $totals->expected_payment)
                ->setCellValue("L{$current_row}", $totals->worked_hours)
                ->setCellValue("M{$current_row}", $totals->amount_paid)
                ->setCellValue("N{$current_row}", $totals->amount_monthly)
                ->setCellValue("O{$current_row}", "-")
                ->setCellValue("P{$current_row}", $totals->days_on_call > 0 ?$totals->days_on_call:"N/A");
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:P{$current_row}")->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:P{$current_row}")->applyFromArray($this->total_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("A{$current_row}")
                ->applyFromArray($pmt_status ? $this->green_style : $this->red_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("E{$current_row}:P{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);


            $current_row++;
        }
        return $current_row;
    }

    protected function writeDataYTD($workbook, $sheet_index, $index, $contracts,$startDate,$end_date)
    {
        $current_row = $index;

        //$physician_id = $physicians[$i];
        $totals = new StdClass;
        $totals->hospital_name = null;
        $totals->hospital_name_1 = null;
        $totals->hospital_city = null;
        $totals->hospital_state = null;
        $totals->practice_name = null;
        $totals->practice_name_1 = null;
        $totals->physician_npi = null;
        $totals->physician_name = null;
        $totals->physician_name_1 = null;
        $totals->specialty_name = null;
        $totals->contract_name = null;
        $totals->expected_hours_ytd = 0.0;
        $totals->worked_hours = 0.0;
        $totals->days_on_call = 0.0;
        $totals->perhours = 0.0;
        $totals->amount_paid = 0.0;
        $totals->pmt_status = 'Y';
        $totals->expected_payment = 0.0;
        $count1 = count($contracts);

        $totals_phy = new StdClass;
        $totals_phy->hospital_name = null;
        $totals_phy->hospital_name_1 = null;
        $totals_phy->hospital_city = null;
        $totals_phy->hospital_state = null;
        $totals_phy->practice_name = null;
        $totals_phy->practice_name_1 = null;
        $totals_phy->physician_npi = null;
        $totals_phy->physician_name = null;
        $totals_phy->physician_name_1 = null;
        $totals_phy->specialty_name = null;
        $totals_phy->contract_name = null;
        $totals_phy->expected_hours_ytd = 0.0;
        $totals_phy->worked_hours = 0.0;
        $totals_phy->days_on_call = 0.0;
        $totals_phy->perhours = 0.0;
        $totals_phy->amount_paid = 0.0;
        $totals_phy->pmt_status = 'Y';
        $totals_phy->rate = 0.0;
        $totals_phy->expected_payment = 0.0;
        $totals_phy->physician_npi = "";
        $totals_phy->physician_practice = "";

        //echo "<pre>";print_r($contracts);die;
        $contracts1 = array();
        for($a = 0;$a<$count1;$a++)
        {
            for($b = 0;$b<count($contracts[$a]);$b++)
            {
                //print_r($contracts[$a][$b]);die;
                $contracts1[$b][$a] = $contracts[$a][$b];
            }
        }
        $contracts = $contracts1;
        $count1 = count($contracts);
        //echo "<pre>";
        //print_r($contracts);die;
        for($j=0;$j< $count1;$j++)
        {
            foreach ($contracts[$j] as $contract) {
                $contract->month_end_date = mysql_date(date($end_date));
                $start_month_date = $contract->start_date_check;
                $amount = DB::table('amount_paid')
                    ->where('start_date', '<=' , mysql_date(date($start_month_date)))
                    ->where('end_date', '>=' , mysql_date(date($start_month_date)))
                    ->where('physician_id','=',$contract->physician_id )
                    ->where('contract_id','=',$contract->contract_id )
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();
                if (isset($amount->amountPaid)) {
                    $contract->amount_paid = $amount->amountPaid;
                }else{
                    $contract->amount_paid = 0;
                }
                $formula = $this->applyFormula($contract);
                if ($index == 0) {
                    $totals->hospital_name = $contract->hospital_name;
                    $totals->hospital_name_1 = $contract->hospital_name;
                    $totals->hospital_city = $contract->hospital_city;
                    $totals->hospital_state = $contract->hospital_state;
                    $totals->practice_name = $contract->practice_name;
                    $totals->practice_name_1 = $contract->practice_name;
                    $totals->physician_npi = $contract->physician_npi;
                    $totals->physician_name = $contract->physician_name;
                    $totals->physician_name_1 = $contract->physician_name;
                }
                if ($totals->hospital_name != $contract->hospital_name) {
                    $totals->hospital_name = $contract->hospital_name;
                    $totals->hospital_name_1 = $contract->hospital_name;
                    $totals->hospital_city = $contract->hospital_city;
                    $totals->hospital_state = $contract->hospital_state;
                }
                else
                {
                    $totals->hospital_name_1 = "";
                    $totals->hospital_city = "";
                    $totals->hospital_state = "";
                }
                if($totals->physician_name != $contract->physician_name)
                {
                    //$totals->practice_name = $contract->practice_name;
                    $totals->physician_npi = $contract->physician_npi;
                    $totals->physician_name = $contract->physician_name;
                    $totals->physician_name_1 = $contract->physician_name;
                }
                else
                {
                    //$totals->practice_name = "";
                    $totals->physician_npi = "";
                    //$totals->physician_name = "";
                    $totals->physician_name_1 = "";
                }
                if($totals->practice_name != $contract->practice_name)
                {
                    $totals->practice_name = $contract->practice_name;
                    $totals->practice_name_1 = $contract->practice_name;
                }
                else
                {
                    $totals->practice_name_1 = "";
                }
                $start_month_date = $contract->start_date_check;
                $end_month_date = $contract->end_date_check;
                //$month = mysql_date(date($dateSql));
                /*$amount = DB::table('amount_paid')
                    ->where('start_date', '<=' , $start_month_date)
                    ->where('end_date', '>=' , $start_month_date)
                    ->where('physician_id','=',$contract->physician_id )
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();*/
                //$queries = DB::getQueryLog();
                //$last_query = end($queries);
                //print_r($last_query);die;

                if(isset($amount->amountPaid))
                {
                    $formula->amount_paid = $amount->amountPaid;
                }
                else
                {
                    $formula->amount_paid = 0;
                }
                $contract->expected_payment = $contract->expected_hours * $contract->rate;

                $pmt_status = $contract->worked_hours!=0?(($formula->amount_paid)/$contract->worked_hours) <= $contract->rate:0;

                if(!$pmt_status)
                {
                    $totals->pmt_status = 'N';
                }
                $totals->specialty_name = $contract->specialty_name;
                $totals->contract_name = $contract->contract_name;
                $totals->expected_hours_ytd += $contract->expected_hours;
                $totals->worked_hours += $contract->worked_hours;
                $totals->days_on_call += ($formula->days_on_call != 'N/A') ? $formula->days_on_call : 0; //Changed by akash
                $totals->perhours += $contract->worked_hours!=0?$formula->amount_paid/$contract->worked_hours:0;
                $totals->amount_paid += $formula->amount_paid;
                $totals->hospital_city = $contract->hospital_city;
                $totals->hospital_state = $contract->hospital_state;

                $totals_phy->specialty_name = $contract->specialty_name;
                $totals_phy->contract_name = $contract->contract_name;
                $totals_phy->expected_hours_ytd += $contract->expected_hours;
                $totals_phy->worked_hours += $contract->worked_hours;
                $totals->days_on_call += ($formula->days_on_call != 'N/A') ? $formula->days_on_call : 0; //Changed by akash
                $totals_phy->perhours += $contract->worked_hours!=0?$formula->amount_paid/$contract->worked_hours:0;
                $totals_phy->amount_paid += $formula->amount_paid;
                $totals_phy->hospital_city = $contract->hospital_city;
                $totals_phy->hospital_state = $contract->hospital_state;
                $totals_phy->rate = $contract->rate;
                $totals_phy->physician_name = $contract->physician_name;
                $totals_phy->hospital_name = $totals->hospital_name;
                $contract->expected_payment = $contract->expected_hours * $contract->rate;
                $totals_phy->expected_payment += $contract->expected_payment;
                $totals_phy->physician_npi = $contract->physician_npi;
                $totals_phy->physician_practice = $totals->practice_name;
                $totals->expected_payment += $contract->expected_payment;
                if($sheet_index== 1 )
                {

                    $workbook->setActiveSheetIndex($sheet_index)
                        ->setCellValue("A{$current_row}", $totals->pmt_status ? 'Y' : 'N')
                        ->setCellValue("B{$current_row}", $totals->hospital_name_1)
                        ->setCellValue("C{$current_row}", $totals->hospital_city)
                        ->setCellValue("D{$current_row}", $totals->hospital_state)
                        ->setCellValue("E{$current_row}", $totals->practice_name_1)
                        ->setCellValue("F{$current_row}", $totals->physician_npi)
                        ->setCellValue("G{$current_row}", $totals->physician_name_1)
                        ->setCellValue("H{$current_row}", $contract->specialty_name)
                        ->setCellValue("I{$current_row}", $contract->contract_name)
                        ->setCellValue("J{$current_row}", $contract->expected_hours)
                        ->setCellValue("K{$current_row}", $contract->expected_payment)
                        ->setCellValue("L{$current_row}", $contract->expected_hours * $contract->contract_month)
                        ->setCellValue("M{$current_row}", $contract->worked_hours)
                        ->setCellValue("N{$current_row}", $formula->amount_paid)
                        ->setCellValue("O{$current_row}", $contract->worked_hours!=0?$formula->amount_paid/$contract->worked_hours:0)
                        ->setCellValue("P{$current_row}", $contract->contract_month)
                        ->setCellValue("Q{$current_row}", $contract->contract_term)
                        ->setCellValue("R{$current_row}", $formula->days_on_call);

                    $workbook->setActiveSheetIndex($sheet_index)
                        ->getStyle("A{$current_row}")
                        ->applyFromArray($pmt_status ? $this->green_style : $this->red_style);
                    $workbook->setActiveSheetIndex($sheet_index)
                        ->getStyle("M{$current_row}:R{$current_row}")
                        ->applyFromArray($this->shaded_style);
                    // $workbook->setActiveSheetIndex($sheet_index)->getStyle("J{$current_row}")->getNumberFormat()->setFormatCode('0.00');
//                    $workbook->setActiveSheetIndex($sheet_index)->getStyle("K{$current_row}")->getNumberFormat()->setFormatCode('0.00');
                    $current_row++;
                }

            }

            $pmt_status = $totals_phy->worked_hours!=0?(($totals_phy->amount_paid)/$totals_phy->worked_hours) <= $totals_phy->rate:0;
            if($sheet_index == 1)
            {
                $workbook->setActiveSheetIndex($sheet_index)
                    ->mergeCells("B{$current_row}:D{$current_row}")
                    ->setCellValue("A{$current_row}", $pmt_status ? 'Y' : 'N')
                    ->setCellValue("B{$current_row}", $totals_phy->hospital_name)
                    ->setCellValue("E{$current_row}", "-")
                    ->setCellValue("F{$current_row}", "-")
                    ->setCellValue("G{$current_row}", $totals_phy->physician_name." totals")
                    ->setCellValue("H{$current_row}", "-")
                    ->setCellValue("I{$current_row}", "-")
                    ->setCellValue("J{$current_row}", "-")
                    ->setCellValue("K{$current_row}", $totals_phy->expected_payment)
                    ->setCellValue("L{$current_row}", $contract->expected_hours * $contract->contract_month)
                    ->setCellValue("M{$current_row}", $totals_phy->worked_hours)
                    ->setCellValue("N{$current_row}", $totals_phy->amount_paid)
                    ->setCellValue("O{$current_row}", $totals_phy->worked_hours!=0?$totals_phy->amount_paid/$totals_phy->worked_hours:0)
                    ->setCellValue("P{$current_row}", "-")
                    ->setCellValue("Q{$current_row}", "-")
                    ->setCellValue("R{$current_row}", $totals_phy->days_on_call > 0 ? $totals_phy->days_on_call:"N/A");

                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("A{$current_row}")
                    ->applyFromArray($pmt_status ? $this->green_style : $this->red_style);

                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:R{$current_row}")->getFont()->setBold(true);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:R{$current_row}")->applyFromArray($this->contract_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:R{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("G{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            }
            else
            {
                $workbook->setActiveSheetIndex($sheet_index)
                    ->setCellValue("A{$current_row}", $pmt_status ? 'Y' : 'N')
                    ->setCellValue("B{$current_row}", $totals_phy->hospital_name)
                    ->setCellValue("C{$current_row}", $totals_phy->hospital_city)
                    ->setCellValue("D{$current_row}", $totals_phy->hospital_state)
                    ->setCellValue("E{$current_row}", $totals_phy->physician_name)
                    ->setCellValue("F{$current_row}", $totals_phy->physician_npi)
                    ->setCellValue("G{$current_row}", $totals_phy->physician_name." totals")
                    ->setCellValue("H{$current_row}", $totals_phy->specialty_name)
                    ->setCellValue("I{$current_row}", $totals_phy->contract_name)
                    ->setCellValue("J{$current_row}", $contract->expected_hours)
                    ->setCellValue("K{$current_row}", $totals_phy->expected_payment)
                    ->setCellValue("L{$current_row}", $contract->expected_hours * $contract->contract_month)
                    ->setCellValue("M{$current_row}", $totals_phy->worked_hours)
                    ->setCellValue("N{$current_row}", $totals_phy->amount_paid)
                    ->setCellValue("O{$current_row}", $totals_phy->worked_hours!=0?$totals_phy->amount_paid/$totals_phy->worked_hours:0)
                    ->setCellValue("P{$current_row}", $contract->contract_term)
                    ->setCellValue("Q{$current_row}", $totals_phy->days_on_call > 0?$totals_phy->days_on_call:"N/A");

                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("A{$current_row}")
                    ->applyFromArray($pmt_status ? $this->green_style : $this->red_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("M{$current_row}:Q{$current_row}")
                    ->applyFromArray($this->shaded_style);

            }
            $current_row++;
            $totals_phy->hospital_name = null;
            $totals_phy->hospital_name_1 = null;
            $totals_phy->hospital_city = null;
            $totals_phy->hospital_state = null;
            $totals_phy->practice_name = null;
            $totals_phy->practice_name_1 = null;
            $totals_phy->physician_npi = null;
            $totals_phy->physician_name = null;
            $totals_phy->physician_name_1 = null;
            $totals_phy->specialty_name = null;
            $totals_phy->contract_name = null;
            $totals_phy->expected_hours_ytd = 0.0;
            $totals_phy->worked_hours = 0.0;
            $totals_phy->days_on_call = 0.0;
            $totals_phy->perhours = 0.0;
            $totals_phy->amount_paid = 0.0;
            $totals_phy->pmt_status = 'Y';
            $totals_phy->expected_payment = 0.0;

        }

        if($totals->hospital_name !="")
        {
            $workbook->setActiveSheetIndex($sheet_index)
                ->setCellValue("A{$current_row}", $totals->pmt_status)
                ->setCellValue("B{$current_row}", $totals->hospital_name." totals")
                ->setCellValue("C{$current_row}", $totals->hospital_city)
                ->setCellValue("D{$current_row}", $totals->hospital_state)
                ->setCellValue("E{$current_row}", '-')
                ->setCellValue("F{$current_row}", '-')
                ->setCellValue("G{$current_row}", '-')
                ->setCellValue("H{$current_row}", '-')
                ->setCellValue("I{$current_row}", $totals->contract_name)
                ->setCellValue("J{$current_row}", "-")
                ->setCellValue("K{$current_row}", $totals->expected_payment)
                ->setCellValue("L{$current_row}", $totals->expected_hours_ytd)
                ->setCellValue("M{$current_row}", $totals->worked_hours)
                ->setCellValue("N{$current_row}", $totals->amount_paid)
                ->setCellValue("O{$current_row}", $totals->perhours)
                ->setCellValue("P{$current_row}", '-')
                ->setCellValue("Q{$current_row}", $totals->days_on_call > 0 ?$totals->days_on_call:"N/A");
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("A{$current_row}")
                ->applyFromArray($totals->pmt_status=='Y' ? $this->green_style : $this->red_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:R{$current_row}")->getFont()->setBold(true);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:Q{$current_row}")->applyFromArray($this->total_style);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:R{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("B{$current_row}:D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("E{$current_row}:H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $workbook->setActiveSheetIndex($sheet_index)
                ->getStyle("I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);


            if($sheet_index ==1)
            {
                $workbook->setActiveSheetIndex($sheet_index)
                    ->setCellValue("A{$current_row}", $totals->pmt_status)
                    ->setCellValue("B{$current_row}", $totals->hospital_name." totals")
                    ->setCellValue("C{$current_row}", $totals->hospital_city)
                    ->setCellValue("D{$current_row}", $totals->hospital_state)
                    ->setCellValue("E{$current_row}", '-')
                    ->setCellValue("F{$current_row}", '-')
                    ->setCellValue("G{$current_row}", '-')
                    ->setCellValue("H{$current_row}", '-')
                    ->setCellValue("I{$current_row}", $totals->contract_name)
                    ->setCellValue("J{$current_row}", "-")
                    ->setCellValue("K{$current_row}", $totals->expected_payment)
                    ->setCellValue("L{$current_row}", $totals->expected_hours_ytd)
                    ->setCellValue("M{$current_row}", $totals->worked_hours)
                    ->setCellValue("N{$current_row}", $totals->amount_paid)
                    ->setCellValue("O{$current_row}", $totals->perhours)
                    ->setCellValue("P{$current_row}", '-')
                    ->setCellValue("Q{$current_row}", '-')
                    ->setCellValue("R{$current_row}", $totals->days_on_call>0?$totals->days_on_call:"N/A");
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("A{$current_row}")
                    ->applyFromArray($pmt_status ? $this->green_style : $this->red_style);

                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:R{$current_row}")->getFont()->setBold(true);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:R{$current_row}")->applyFromArray($this->total_style);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("B{$current_row}:D{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("E{$current_row}:H{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $workbook->setActiveSheetIndex($sheet_index)
                    ->getStyle("I{$current_row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            }
            $current_row++;
        }
        //echo $current_row;die;
        return $current_row;
    }

    protected function getArguments()
    {
        return [
            ["agreements", InputArgument::REQUIRED, "The agreement IDs."],
            ["months", InputArgument::REQUIRED, "The agreement months."]
        ];
    }

    protected function getOptions()
    {
        return [];
    }
}
