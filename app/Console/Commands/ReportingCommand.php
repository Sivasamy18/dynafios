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
use App\Agreement;
use App\ContractType;
use App\Contract;

abstract class ReportingCommand extends Command
{
    public static $success = false;
    public static $message = null;
    public static $report_id = -1;
    public static $report_filename = null;

    protected function success($message, $report_id, $report_filename = null)
    {
        self::$success = true;
        self::$message = $message;
        self::$report_id = $report_id;
        self::$report_filename = $report_filename;
    }

    protected function failure($message)
    {
        self::$success = false;
        self::$message = $message;
        self::$report_id = -1;
    }

    protected function parseArguments()
    {
        $agreement_ids = explode(',', $this->argument('agreements'));
        $months = explode(',', $this->argument('months'));

        $results = [];
        foreach ($agreement_ids as $index => $agreement_id) {
            $agreement_data = Agreement::getAgreementData($agreement_id);

            $start_month = $months[$index * 2 + 0];
            $end_month = $months[$index * 2 + 1];
            $result              = new StdClass;
            $result->id          = $agreement_id;
            $result->start_date  = $agreement_data->months[$start_month]->start_date;
            if(isset($agreement_data->months[$end_month]->end_date))
                $result->end_date    = $agreement_data->months[$end_month]->end_date;
            else
               $result->end_date    = $agreement_data->months[$start_month]->end_date;
            $result->start_month = $start_month;
            $result->end_month   = $end_month;
            $result->month_range = 1 + abs($start_month - $end_month);

            $results[] = $result;
        }

        return $results;
    }

    protected function applyFormula($contract)
    {
        $formula = new StdClass;
        $formula->days_on_call = 'N/A';
        $formula->payment_override = false;
        if(isset($contract))
        {
            if ($contract->contract_type_id == ContractType::CO_MANAGEMENT) {
                //print_r($contract);
                if(isset($contract->log_range))
                    $amount = $contract->rate * $contract->expected_hours * $contract->log_range;
                else
                    die;
                $formula->amount = round($amount);
                if(isset($contract->worked_hours) && $contract->worked_hours != 0)
                    $formula->actual_rate = round($amount / $contract->worked_hours);
                else
                    $formula->actual_rate = 0;
                /*if ($contract->contract_month > 5) {
                    $fmv = ($formula->actual_rate * 100.0) / $contract->rate;
                    $formula->payment_status = $fmv < 110;
                } else {
                    $formula->payment_status = true;
                    $formula->payment_override = true;
                }*/
                $contract->id = $contract->contract_id;
                $remaining = Agreement::getRemainingAmount($contract);
                if ($contract->contract_month > Contract::CO_MANAGEMENT_MIN_MONTHS) {
                    if($remaining > 0){
                        $formula->payment_status = true;
                    }else{
                        $formula->payment_status = false;
                    }
                }else{
                    if($contract->min_hours <= $contract->worked_hours)
                    {
                        if($remaining > 0){
                            $formula->payment_status = true;
                            $formula->payment_override = true;
                        }else{
                            $formula->payment_status = false;
                            $formula->payment_override = true;
                        }
                    }
                    else{
                        $formula->payment_status = false;
                    }
                }
            } else {
                $amount = $contract->worked_hours * $contract->rate;

                $formula->amount = round($amount);
                $formula->actual_rate = 'N/A';
                $formula->payment_status = ($contract->expected_hours * $contract->log_range) <= $contract->worked_hours;

                if ($contract->contract_type_id == ContractType::ON_CALL) {
                    $formula->days_on_call = $contract->worked_hours / 24;
                }
            }
        }



        return $formula;
    }

    protected function loadTemplate($filename)
    {
        return $this->spreadsheet = IOFactory::load($this->templatePrefix($filename));
    }

    private function templatePrefix($filename)
    {
        return storage_path() . "/reports/templates/" . $filename;
    }

    protected function saveReport($report)
    {
        $filename = $this->reportFilename();

        $writer = IOFactory::createWriter($report, "Xlsx");
        $writer->save($this->reportPrefix($filename));

        return $filename;
    }

    protected function reportFilename()
    { 
        return "report_" . date("YmdHis") . ".xlsx";
    }

    protected function reportPrefix($filename)
    {
        return $filename;
    }
}
