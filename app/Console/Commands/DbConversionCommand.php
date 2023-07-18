<?php
namespace App\Console\Commands;

use App\Agreement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use App\Contract;

class DbConversionCommand extends Command
{
    protected $name = 'db:conversion';
    protected $description = 'Converts the TRACE database to the new agreements format.';

    public function __construct()
    {
        parent::__construct();
    }

    public function __invoke()
    {
        $this->createAgreements();
        $this->associateAgreements();
    }

    private function createAgreements()
    {
        $agreements = DB::table('contracts')->select(
            'hospitals.id as hospital_id',
            'contracts.start_date as start_date',
            'contracts.end_date as end_date',
            'contracts.archived as archived'
        )
            ->join('physicians', 'physicians.id', '=', 'contracts.physician_id')
            ->join('practices', 'practices.id', '=', 'physicians.practice_id')
            ->join('hospitals', 'hospitals.id', '=', 'practices.hospital_id')
            ->groupBy('hospitals.id')
            ->groupBy('contracts.start_date')
            ->groupBy('contracts.end_date')
            ->groupBy('contracts.archived')
            ->get();

        foreach ($agreements as $agreement) {
            if (!$this->option('simulate')) {
                $model = new Agreement;
                $model->hospital_id = $agreement->hospital_id;
                $model->start_date = $agreement->start_date;
                $model->end_date = $agreement->end_date;
                $model->archived = $agreement->archived;
                $model->save();
            }
        }
    }

    private function associateAgreements()
    {
        $contracts = DB::table('contracts')->select(
            'hospitals.id as hospital_id',
            'contracts.id as contract_id',
            'contracts.start_date as start_date',
            'contracts.end_date as end_date'
        )
            ->join('physicians', 'physicians.id', '=', 'contracts.physician_id')
            ->join('practices', 'practices.id', '=', 'physicians.practice_id')
            ->join('hospitals', 'hospitals.id', '=', 'practices.hospital_id')
            ->where('contracts.archived', '=', '0')
            ->get();

        foreach ($contracts as $contract) {
            $agreement = DB::table('agreements')
                ->where('agreements.hospital_id', '=', $contract->hospital_id)
                ->where('agreements.start_date', '=', $contract->start_date)
                ->where('agreements.end_date', '=', $contract->end_date)
                ->pluck('id');

            if (!$this->option('simulate')) {
                $model = Contract::findOrFail($contract->contract_id);
                $model->agreement_id = $agreement;
                $model->save();
            }
        }
    }

    protected function getArguments()
    {
        return [];
    }

    protected function getOptions()
    {
        return [
            ['simulate', null, InputOption::VALUE_NONE, 'Simulates the conversion.']
        ];
    }
}