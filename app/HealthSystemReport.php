<?php

namespace App;

use App\Console\Commands\HealthSystemActiveContractsReportCommand;
use App\Console\Commands\HealthSystemContractsExpiringReportCommand;
use App\Console\Commands\HealthSystemProviderProfileReportCommand;
use App\Console\Commands\HealthSystemSpendYTDEffectivenessReportCommand;
use App\Models\Files\File;
use Artisan;
use DateTime;
use DateTimeZone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Lang;
use Redirect;
use Request;

class HealthSystemReport extends Model
{
    protected $table = 'health_system_reports';

    const ACTIVE_CONTRACTS_REPORTS = 1;
    const SPEND_YTD_EFFECTIVENESS_REPORTS = 2;
    const CONTRACTS_EXPIRING_REPORTS = 3;
    const PROVIDER_PROFILE_REPORTS = 4;

    public function file()
    {
        return $this->morphOne(File::class, 'fileable');
    }

    public static function getReportData($group_id)
    {

        $user_id = Auth::user()->id;
        $region = Request::input('region');
        $facility = Request::input('facility');
        $agreement_ids = Request::input('agreements');
        $physician_ids = Request::input('physicians');

        if ($agreement_ids == null || $physician_ids == null) {
            return Redirect::back()->with([
                'error' => Lang::get('breakdowns.selection_error')
            ]);
        }

        $contracts = [];
        foreach ($agreement_ids as $agreement_id) {
            $agreement = Agreement::findOrFail($agreement_id);
            $months_start = Request::input("start_{$agreement_id}_start_month");
            $months_end = Request::input("end_{$agreement_id}_start_month");

            $contract_list = Contract::select("contracts.*", DB::raw('CONCAT(physicians.first_name, " ", physicians.last_name) AS physician_name'), "physicians.email as physician_email", "contract_types.name as contract_type",
                "contract_names.name as contract_name", "agreements.id as agreement_id", "agreements.approval_process as approval_process", "agreements.start_date as agreement_start_date",
                "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date", "hospitals.id as hospital_id", "hospitals.name as hospital_name", "health_system_regions.region_name as region_name")
                ->join('physicians', 'physicians.id', '=', 'contracts.physician_id')
                ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
                ->join("contract_types", "contract_types.id", "=", "contracts.contract_type_id")
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                ->join("region_hospitals", "region_hospitals.hospital_id", "=", "agreements.hospital_id")
                ->join("health_system_regions", "health_system_regions.id", "=", "region_hospitals.region_id");

            if ($group_id == Group::HEALTH_SYSTEM_USER) {
                //find health system id
                $system_user = HealthSystemUsers::where('user_id', '=', Auth::user()->id)->first();
                $health_system_id = $system_user->health_system_id;
                $health_system = HealthSystem:: where('id', '=', $health_system_id)->first();
                $filter_healthsystem = $health_system->health_system_name;
                $health_system_region_id = 0;

                $contract_list = $contract_list->join("health_system_users", "health_system_users.health_system_id", "=", "health_system_regions.health_system_id")
                    ->where("health_system_users.user_id", "=", $user_id);
            } else {
                $contract_list = $contract_list->join("health_system_region_users", "health_system_region_users.health_system_region_id", "=", "region_hospitals.region_id")
                    ->where("health_system_region_users.user_id", "=", $user_id);
                //find health system id & health system region id
                $region_user = HealthSystemRegionUsers::where('user_id', '=', Auth::user()->id)->first();
                $health_system_region_id = $region_user->health_system_region_id;
                $health_system_region = HealthSystemRegion::findOrfail($region_user->health_system_region_id);
                $health_system_id = $health_system_region->health_system_id;
                $health_system = HealthSystem:: where('id', '=', $health_system_id)->first();
                $filter_healthsystem = $health_system->health_system_name;
            }

            if ($region != 0) {
                $contract_list = $contract_list->where("region_hospitals.region_id", "=", $region);
            }

            if ($facility != 0) {
                $contract_list = $contract_list->where("hospitals.id", "=", $facility);
            }

            $contract_list = $contract_list->whereRaw("agreements.is_deleted=0")
                ->whereRaw("agreements.start_date <= now()")
                ->where('agreements.start_date', '>=', date('Y-m-d', strtotime($months_start)))
                ->whereRaw("agreements.end_date >= now()")
                // ->whereRaw("contracts.manual_contract_end_date >= now()")
                ->where('contracts.manual_contract_end_date', '<=', date('Y-m-d', strtotime($months_end)))
                ->whereIn('contracts.physician_id', $physician_ids)
                ->where('agreements.id', '=', $agreement_id)
                ->whereNull('region_hospitals.deleted_at')
                ->whereNull('contracts.deleted_at');

            $contract_list = $contract_list->orderBy('region_name')->orderBy('hospital_name')->orderBy('agreement_start_date')->distinct()->get();

            if (count($contract_list) > 0) {
                foreach ($contract_list as $contract_list) {
                    $contracts[] = $contract_list;
                }
            }
        }
        $contracts = collect($contracts)->sortBy('region_name')->sortBy('hospital_name')->sortBy('agreement_start_date');
        $data = array();
        $last_date_of_prev_month = date('m/d/Y', strtotime('last day of previous month'));

        $filter_region = '';
        $filter_facility = '';
        if (count($contracts) > 0) {
            foreach ($contracts as $contract) {

                $startEndDatesForYear = $contract->getContractStartDateForYear();

                if (strtotime($last_date_of_prev_month) <= strtotime($contract->manual_contract_end_date)) {
                    $end_date = $last_date_of_prev_month;
                } elseif (strtotime($contract->manual_contract_end_date) <= strtotime($startEndDatesForYear['year_end_date']->format('Y-m-d'))) {
                    $end_date = date('m/d/Y', strtotime($contract->manual_contract_end_date));
                } else {
                    $end_date = $startEndDatesForYear['year_end_date']->format('m/d/Y');
                }

                //$months = months($contract->agreement_start_date, $contract->manual_contract_end_date);
                $months = months($startEndDatesForYear['year_start_date']->format('m/d/Y'), $end_date);
                //$total_expected_payment = ($months * $contract->expected_hours) * $contract->rate;
                $total_expected_payment = ContractRate::findTotalExpectedPayment($contract->id, $startEndDatesForYear['year_start_date'], $months);

                $total_amount_paid = Amount_paid::select(DB::raw("SUM(amountPaid) as paid"))
                    ->where("contract_id", "=", $contract->id)
                    ->where("start_date", ">=", mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')))->first();
                $total_amount_paid = $total_amount_paid = (($contract->payment_type_id == PaymentType::HOURLY) && ($contract->prior_start_date != '0000-00-00')) ? $total_amount_paid->paid + $contract->prior_amount_paid : $total_amount_paid->paid;

                $hospital_id = $contract->hospital_id;
                $subquery = "select concat(last_name, ', ' ,first_name)
					as approver from users where id =
					(select user_id from agreement_approval_managers_info where is_deleted = '0' and contract_id !=0
					and contract_id = $contract->id and agreement_id = $contract->agreement_id and level=";

                $subquery_email = "select email as email from users 
					where id =
						(select user_id from agreement_approval_managers_info where is_deleted = '0' and contract_id !=0
						and contract_id = $contract->id and agreement_id = $contract->agreement_id and level=";

                $subquery_agreement = "select concat(last_name, ', ' ,first_name)
					as approver from users where id =
					(select user_id from agreement_approval_managers_info where is_deleted = '0' and contract_id = 0
					and agreement_id = $contract->agreement_id and level=";

                $subquery_agreement_email = "select email as email from users 
					where id =
						(select user_id from agreement_approval_managers_info where is_deleted = '0' and contract_id = 0
						and agreement_id = $contract->agreement_id and level=";

                $approval_levels = self::queryWithUnion('Contract', function ($query) use ($hospital_id, $subquery, $subquery_agreement, $subquery_email, $subquery_agreement_email) {
                    return $query->select('contract_names.name as contract_name', DB::raw("concat(physicians.last_name, ', ', physicians.first_name) as physician_name"), 'contracts.id as contract_id', DB::raw('(' . $subquery . '1 limit 1)) as approval_level1'), DB::raw('(' . $subquery . '2 limit 1)) as approval_level2'),
                        DB::raw('(' . $subquery . '3 limit 1)) as approval_level3'), DB::raw('(' . $subquery . '4 limit 1)) as approval_level4'),
                        DB::raw('(' . $subquery . '5 limit 1)) as approval_level5'), DB::raw('(' . $subquery . '6 limit 1)) as approval_level6'),
                        DB::raw('(' . $subquery_email . '1 limit 1)) as approval_level_email1'), DB::raw('(' . $subquery_email . '2 limit 1)) as approval_level_email2'),
                        DB::raw('(' . $subquery_email . '3 limit 1)) as approval_level_email3'), DB::raw('(' . $subquery_email . '4 limit 1)) as approval_level_email4'),
                        DB::raw('(' . $subquery_email . '5 limit 1)) as approval_level_email5'), DB::raw('(' . $subquery_email . '6 limit 1)) as approval_level_email6'))
                        ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
                        ->join("physicians", "physicians.id", "=", "contracts.physician_id")
                        ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                        ->where('agreements.approval_process', '=', '1')
                        ->where('contracts.default_to_agreement', '=', '0')
                        ->whereRaw("agreements.is_deleted=0")
                        ->whereRaw("agreements.start_date <= now()")
                        ->whereRaw("agreements.end_date >= now()")
                        ->whereRaw("contracts.manual_contract_end_date >= now()")
                        ->where('agreements.hospital_id', '=', $hospital_id)
                        ->whereNull("contracts.deleted_at")
                        ->union(DB::table('contracts')->select('contract_names.name as contract_name', DB::raw("concat(physicians.last_name, ', ', physicians.first_name) as physician_name"), 'contracts.id as contract_id', DB::raw('(' . $subquery_agreement . '1 limit 1)) as approval_level1'),
                            DB::raw('(' . $subquery_agreement . '2 limit 1)) as approval_level2'),
                            DB::raw('(' . $subquery_agreement . '3 limit 1)) as approval_level3'), DB::raw('(' . $subquery_agreement . '4 limit 1)) as approval_level4'),
                            DB::raw('(' . $subquery_agreement . '5 limit 1)) as approval_level5'), DB::raw('(' . $subquery_agreement . '6 limit 1)) as approval_level6'),
                            DB::raw('(' . $subquery_agreement_email . '1 limit 1)) as approval_level_email1'), DB::raw('(' . $subquery_agreement_email . '2 limit 1)) as approval_level_email2'),
                            DB::raw('(' . $subquery_agreement_email . '3 limit 1)) as approval_level_email3'), DB::raw('(' . $subquery_agreement_email . '4 limit 1)) as approval_level_email4'),
                            DB::raw('(' . $subquery_agreement_email . '5 limit 1)) as approval_level_email5'), DB::raw('(' . $subquery_agreement_email . '6 limit 1)) as approval_level_email6'))
                            ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
                            ->join("physicians", "physicians.id", "=", "contracts.physician_id")
                            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                            ->where('agreements.approval_process', '=', '1')
                            ->where('contracts.default_to_agreement', '=', '1')
                            ->whereRaw("agreements.is_deleted=0")
                            ->whereRaw("agreements.start_date <= now()")
                            ->whereRaw("agreements.end_date >= now()")
                            ->whereRaw("contracts.manual_contract_end_date >= now()")
                            ->where('agreements.hospital_id', '=', $hospital_id)
                            ->whereNull("contracts.deleted_at")
                            ->distinct())
                        ->distinct();
                });

                foreach ($approval_levels as $approval_level) {
                    $approval_level1 = $approval_level->approval_level1;
                    $approval_level2 = $approval_level->approval_level2;
                    $approval_level3 = $approval_level->approval_level3;
                    $approval_level4 = $approval_level->approval_level4;
                    $approval_level5 = $approval_level->approval_level5;
                    $approval_level6 = $approval_level->approval_level6;

                    $approval_level_email1 = $approval_level->approval_level_email1;
                    $approval_level_email2 = $approval_level->approval_level_email2;
                    $approval_level_email3 = $approval_level->approval_level_email3;
                    $approval_level_email4 = $approval_level->approval_level_email4;
                    $approval_level_email5 = $approval_level->approval_level_email5;
                    $approval_level_email6 = $approval_level->approval_level_email6;
                }

                $data[] = [
                    "contract_name" => $contract->contract_name,
                    "contract_type" => $contract->contract_type,
                    "hospital_name" => $contract->hospital_name,
                    "region_name" => $contract->region_name,
                    "physician_name" => $contract->physician_name,
                    "physician_email" => $contract->physician_email,
                    "agreement_start_date" => (($contract->payment_type_id == PaymentType::HOURLY) && ($contract->prior_start_date != '0000-00-00')) ? format_date($contract->prior_start_date) : format_date($contract->agreement_start_date), //changes done for prior start date of contract
                    "agreement_end_date" => format_date($contract->agreement_end_date),
                    "manual_contract_end_date" => format_date($contract->manual_contract_end_date),
                    "agreement_valid_upto_date" => format_date($contract->agreement_valid_upto_date),
                    "amount" => $total_amount_paid != null ? $total_amount_paid : 0,
                    "expected_spend" => $total_expected_payment != null ? $total_expected_payment : 0,
                    "approval_level1" => $approval_level1 != null ? $approval_level1 : "NA",
                    "approval_level2" => $approval_level2 != null ? $approval_level2 : "NA",
                    "approval_level3" => $approval_level3 != null ? $approval_level3 : "NA",
                    "approval_level4" => $approval_level4 != null ? $approval_level4 : "NA",
                    "approval_level5" => $approval_level5 != null ? $approval_level5 : "NA",
                    "approval_level6" => $approval_level6 != null ? $approval_level6 : "NA",

                    "approval_level_email1" => $approval_level_email1 != null ? $approval_level_email1 : "",
                    "approval_level_email2" => $approval_level_email2 != null ? $approval_level_email2 : "",
                    "approval_level_email3" => $approval_level_email3 != null ? $approval_level_email3 : "",
                    "approval_level_email4" => $approval_level_email4 != null ? $approval_level_email4 : "",
                    "approval_level_email5" => $approval_level_email5 != null ? $approval_level_email5 : "",
                    "approval_level_email6" => $approval_level_email6 != null ? $approval_level_email6 : ""
                ];
                $filter_region = $contract->region_name;
                $filter_facility = $contract->hospital_name;
            }
        }

        $filter_region = $region == 0 ? 'All Divisions' : $filter_region;
        $filter_facility = $facility == 0 ? 'All Facilities' : $filter_facility;

        $timestamp = Request::input("current_timestamp");
        $timeZone = Request::input("current_zoneName");
        //log::info("timestamp",array($timestamp));

        if ($timestamp != null && $timeZone != null && $timeZone != '' && $timestamp != '') {
            if (!strtotime($timestamp)) {
                $zone = new DateTime(strtotime($timestamp));
            } else {
                $zone = new DateTime(false);
            }
            $zone->setTimezone(new DateTimeZone($timeZone));
            $localtimeZone = $zone->format('m/d/Y h:i A T');
        } else {
            $localtimeZone = '';
        }
        // log::info("health system  localtimeZone",array($localtimeZone));

        Artisan::call("reports:HealthSystemActiveContractsReport", [
            "data" => $data,
            "user_id" => $user_id,
            "health_system_id" => $health_system_id,
            "health_system_region_id" => $health_system_region_id,
            "filter_region" => $filter_region,
            "filter_facility" => $filter_facility,
            "filter_healthsystem" => $filter_healthsystem,
            "localtimeZone" => $localtimeZone
        ]);

        if (!HealthSystemActiveContractsReportCommand::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(HealthSystemActiveContractsReportCommand::$message)
            ]);
        }

        $report_id = HealthSystemActiveContractsReportCommand::$report_id;
        $report_filename = HealthSystemActiveContractsReportCommand::$report_filename;

        return Redirect::back()->with([
            'success' => Lang::get(HealthSystemActiveContractsReportCommand::$message),
            'report_id' => $report_id,
            'report_filename' => $report_filename
        ]);
    }

    public static function getReportData_old($group_id)
    {

        $user_id = Auth::user()->id;
        //$group_id= Auth::user()->group_id;


        $region = Request::input('region');
        $facility = Request::input('facility');


        /*Contracts data finding*/
        $contract_list = Contract::select("contracts.*", DB::raw('CONCAT(physicians.first_name, " ", physicians.last_name) AS physician_name'), "physicians.email as physician_email", "contract_types.name as contract_type",
            "contract_names.name as contract_name", "agreements.id as agreement_id", "agreements.approval_process as approval_process", "agreements.start_date as agreement_start_date",
            "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date", "hospitals.id as hospital_id", "hospitals.name as hospital_name", "health_system_regions.region_name as region_name")
            ->join('physicians', 'physicians.id', '=', 'contracts.physician_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join("contract_types", "contract_types.id", "=", "contracts.contract_type_id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
            ->join("region_hospitals", "region_hospitals.hospital_id", "=", "agreements.hospital_id")
            ->join("health_system_regions", "health_system_regions.id", "=", "region_hospitals.region_id");

        if ($group_id == Group::HEALTH_SYSTEM_USER) {
            //find health system id
            $system_user = HealthSystemUsers::where('user_id', '=', Auth::user()->id)->first();
            $health_system_id = $system_user->health_system_id;
            $health_system = HealthSystem:: where('id', '=', $health_system_id)->first();
            $filter_healthsystem = $health_system->health_system_name;
            $health_system_region_id = 0;

            $contract_list = $contract_list->join("health_system_users", "health_system_users.health_system_id", "=", "health_system_regions.health_system_id")
                ->where("health_system_users.user_id", "=", $user_id);

        } else {
            $contract_list = $contract_list->join("health_system_region_users", "health_system_region_users.health_system_region_id", "=", "region_hospitals.region_id")
                ->where("health_system_region_users.user_id", "=", $user_id);
            //find health system id & health system region id
            $region_user = HealthSystemRegionUsers::where('user_id', '=', Auth::user()->id)->first();
            $health_system_region_id = $region_user->health_system_region_id;
            $health_system_region = HealthSystemRegion::findOrfail($region_user->health_system_region_id);
            $health_system_id = $health_system_region->health_system_id;
            $health_system = HealthSystem:: where('id', '=', $health_system_id)->first();
            $filter_healthsystem = $health_system->health_system_name;
        }

        if ($region != 0) {
            $contract_list = $contract_list->where("region_hospitals.region_id", "=", $region);
        }

        if ($facility != 0) {
            $contract_list = $contract_list->where("hospitals.id", "=", $facility);
        }

        $contract_list = $contract_list->whereRaw("agreements.is_deleted=0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("agreements.end_date >= now()")
            ->whereRaw("contracts.manual_contract_end_date >= now()")
            ->whereNull('region_hospitals.deleted_at')
            ->whereNull('contracts.deleted_at');

        $contract_list = $contract_list->orderBy('region_name')->orderBy('hospital_name')->orderBy('agreement_start_date')->distinct()->get();

        $data = array();

        $last_date_of_prev_month = date('m/d/Y', strtotime('last day of previous month'));


        $filter_region = '';
        $filter_facility = '';
        foreach ($contract_list as $contract) {
            $startEndDatesForYear = $contract->getContractStartDateForYear();
            if (strtotime($last_date_of_prev_month) <= strtotime($contract->manual_contract_end_date)) {
                $end_date = $last_date_of_prev_month;
            } elseif (strtotime($contract->manual_contract_end_date) <= strtotime($startEndDatesForYear['year_end_date'])) {
                $end_date = date('m/d/Y', strtotime($contract->manual_contract_end_date));
            } else {
                $end_date = $startEndDatesForYear['year_end_date']->format('m/d/Y');
            }
            //$months = months($contract->agreement_start_date, $contract->manual_contract_end_date);
            $months = months($startEndDatesForYear['year_start_date']->format('m/d/Y'), $end_date);
            //$total_expected_payment = ($months * $contract->expected_hours) * $contract->rate;
            $total_expected_payment = ContractRate::findTotalExpectedPayment($contract->id, $startEndDatesForYear['year_start_date'], $months);

            $total_amount_paid = Amount_paid::select(DB::raw("SUM(amountPaid) as paid"))
                ->where("contract_id", "=", $contract->id)
                ->where("start_date", ">=", mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')))->first();
            $total_amount_paid = $total_amount_paid = (($contract->payment_type_id == PaymentType::HOURLY) && ($contract->prior_start_date != '0000-00-00')) ? $total_amount_paid->paid + $contract->prior_amount_paid : $total_amount_paid->paid;

            $hospital_id = $contract->hospital_id;
            $subquery = "select concat(last_name, ', ' ,first_name)
				as approver from users where id =
				(select user_id from agreement_approval_managers_info where is_deleted = '0' and contract_id !=0
				and contract_id = $contract->id and agreement_id = $contract->agreement_id and level=";

            $subquery_email = "select email as email from users 
				where id =
					(select user_id from agreement_approval_managers_info where is_deleted = '0' and contract_id !=0
					and contract_id = $contract->id and agreement_id = $contract->agreement_id and level=";

            $subquery_agreement = "select concat(last_name, ', ' ,first_name)
				as approver from users where id =
				(select user_id from agreement_approval_managers_info where is_deleted = '0' and contract_id = 0
				and agreement_id = $contract->agreement_id and level=";

            $subquery_agreement_email = "select email as email from users 
				where id =
					(select user_id from agreement_approval_managers_info where is_deleted = '0' and contract_id = 0
					and agreement_id = $contract->agreement_id and level=";

            $approval_levels = self::queryWithUnion('Contract', function ($query) use ($hospital_id, $subquery, $subquery_agreement, $subquery_email, $subquery_agreement_email) {
                return $query->select('contract_names.name as contract_name', DB::raw("concat(physicians.last_name, ', ', physicians.first_name) as physician_name"), 'contracts.id as contract_id', DB::raw('(' . $subquery . '1 limit 1)) as approval_level1'), DB::raw('(' . $subquery . '2 limit 1)) as approval_level2'),
                    DB::raw('(' . $subquery . '3 limit 1)) as approval_level3'), DB::raw('(' . $subquery . '4 limit 1)) as approval_level4'),
                    DB::raw('(' . $subquery . '5 limit 1)) as approval_level5'), DB::raw('(' . $subquery . '6 limit 1)) as approval_level6'),
                    DB::raw('(' . $subquery_email . '1 limit 1)) as approval_level_email1'), DB::raw('(' . $subquery_email . '2 limit 1)) as approval_level_email2'),
                    DB::raw('(' . $subquery_email . '3 limit 1)) as approval_level_email3'), DB::raw('(' . $subquery_email . '4 limit 1)) as approval_level_email4'),
                    DB::raw('(' . $subquery_email . '5 limit 1)) as approval_level_email5'), DB::raw('(' . $subquery_email . '6 limit 1)) as approval_level_email6'))
                    ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
                    ->join("physicians", "physicians.id", "=", "contracts.physician_id")
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->where('agreements.approval_process', '=', '1')
                    ->where('contracts.default_to_agreement', '=', '0')
                    ->whereRaw("agreements.is_deleted=0")
                    ->whereRaw("agreements.start_date <= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    ->whereRaw("contracts.manual_contract_end_date >= now()")
                    ->where('agreements.hospital_id', '=', $hospital_id)
                    ->whereNull("contracts.deleted_at")
                    ->union(DB::table('contracts')->select('contract_names.name as contract_name', DB::raw("concat(physicians.last_name, ', ', physicians.first_name) as physician_name"), 'contracts.id as contract_id', DB::raw('(' . $subquery_agreement . '1 limit 1)) as approval_level1'),
                        DB::raw('(' . $subquery_agreement . '2 limit 1)) as approval_level2'),
                        DB::raw('(' . $subquery_agreement . '3 limit 1)) as approval_level3'), DB::raw('(' . $subquery_agreement . '4 limit 1)) as approval_level4'),
                        DB::raw('(' . $subquery_agreement . '5 limit 1)) as approval_level5'), DB::raw('(' . $subquery_agreement . '6 limit 1)) as approval_level6'),
                        DB::raw('(' . $subquery_agreement_email . '1 limit 1)) as approval_level_email1'), DB::raw('(' . $subquery_agreement_email . '2 limit 1)) as approval_level_email2'),
                        DB::raw('(' . $subquery_agreement_email . '3 limit 1)) as approval_level_email3'), DB::raw('(' . $subquery_agreement_email . '4 limit 1)) as approval_level_email4'),
                        DB::raw('(' . $subquery_agreement_email . '5 limit 1)) as approval_level_email5'), DB::raw('(' . $subquery_agreement_email . '6 limit 1)) as approval_level_email6'))
                        ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
                        ->join("physicians", "physicians.id", "=", "contracts.physician_id")
                        ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                        ->where('agreements.approval_process', '=', '1')
                        ->where('contracts.default_to_agreement', '=', '1')
                        ->whereRaw("agreements.is_deleted=0")
                        ->whereRaw("agreements.start_date <= now()")
                        ->whereRaw("agreements.end_date >= now()")
                        ->whereRaw("contracts.manual_contract_end_date >= now()")
                        ->where('agreements.hospital_id', '=', $hospital_id)
                        ->whereNull("contracts.deleted_at")
                        ->distinct())
                    ->distinct();
            });

            foreach ($approval_levels as $approval_level) {
                $approval_level1 = $approval_level->approval_level1;
                $approval_level2 = $approval_level->approval_level2;
                $approval_level3 = $approval_level->approval_level3;
                $approval_level4 = $approval_level->approval_level4;
                $approval_level5 = $approval_level->approval_level5;
                $approval_level6 = $approval_level->approval_level6;

                $approval_level_email1 = $approval_level->approval_level_email1;
                $approval_level_email2 = $approval_level->approval_level_email2;
                $approval_level_email3 = $approval_level->approval_level_email3;
                $approval_level_email4 = $approval_level->approval_level_email4;
                $approval_level_email5 = $approval_level->approval_level_email5;
                $approval_level_email6 = $approval_level->approval_level_email6;
            }

            $data[] = [
                "contract_name" => $contract->contract_name,
                "contract_type" => $contract->contract_type,
                "hospital_name" => $contract->hospital_name,
                "region_name" => $contract->region_name,
                "physician_name" => $contract->physician_name,
                "physician_email" => $contract->physician_email,
                "agreement_start_date" => (($contract->payment_type_id == PaymentType::HOURLY) && ($contract->prior_start_date != '0000-00-00')) ? format_date($contract->prior_start_date) : format_date($contract->agreement_start_date), //changes done for prior start date of contract
                "agreement_end_date" => format_date($contract->agreement_end_date),
                "manual_contract_end_date" => format_date($contract->manual_contract_end_date),
                "agreement_valid_upto_date" => format_date($contract->agreement_valid_upto_date),
                "amount" => $total_amount_paid != null ? $total_amount_paid : 0,
                "expected_spend" => $total_expected_payment != null ? $total_expected_payment : 0,
                "approval_level1" => $approval_level1 != null ? $approval_level1 : "NA",
                "approval_level2" => $approval_level2 != null ? $approval_level2 : "NA",
                "approval_level3" => $approval_level3 != null ? $approval_level3 : "NA",
                "approval_level4" => $approval_level4 != null ? $approval_level4 : "NA",
                "approval_level5" => $approval_level5 != null ? $approval_level5 : "NA",
                "approval_level6" => $approval_level6 != null ? $approval_level6 : "NA",

                "approval_level_email1" => $approval_level_email1 != null ? $approval_level_email1 : "",
                "approval_level_email2" => $approval_level_email2 != null ? $approval_level_email2 : "",
                "approval_level_email3" => $approval_level_email3 != null ? $approval_level_email3 : "",
                "approval_level_email4" => $approval_level_email4 != null ? $approval_level_email4 : "",
                "approval_level_email5" => $approval_level_email5 != null ? $approval_level_email5 : "",
                "approval_level_email6" => $approval_level_email6 != null ? $approval_level_email6 : ""
            ];
            $filter_region = $contract->region_name;
            $filter_facility = $contract->hospital_name;
        }

        $filter_region = $region == 0 ? 'All Divisions' : $filter_region;
        $filter_facility = $facility == 0 ? 'All Facilities' : $filter_facility;

        $timestamp = Request::input("current_timestamp");
        $timeZone = Request::input("current_zoneName");
        //log::info("timestamp",array($timestamp));

        if ($timestamp != null && $timeZone != null && $timeZone != '' && $timestamp != '') {
            if (!strtotime($timestamp)) {
                $zone = new DateTime(strtotime($timestamp));
            } else {
                $zone = new DateTime(false);
            }
            $zone->setTimezone(new DateTimeZone($timeZone));
            $localtimeZone = $zone->format('m/d/Y h:i A T');
        } else {
            $localtimeZone = '';
        }
        // log::info("health system  localtimeZone",array($localtimeZone));


        Artisan::call("reports:HealthSystemActiveContractsReport", [
            "data" => $data,
            "user_id" => $user_id,
            "health_system_id" => $health_system_id,
            "health_system_region_id" => $health_system_region_id,
            "filter_region" => $filter_region,
            "filter_facility" => $filter_facility,
            "filter_healthsystem" => $filter_healthsystem,
            "localtimeZone" => $localtimeZone
        ]);

        if (!HealthSystemActiveContractsReportCommand::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(HealthSystemActiveContractsReportCommand::$message)
            ]);
        }

        $report_id = HealthSystemActiveContractsReportCommand::$report_id;
        $report_filename = HealthSystemActiveContractsReportCommand::$report_filename;

        return Redirect::back()->with([
            'success' => Lang::get(HealthSystemActiveContractsReportCommand::$message),
            'report_id' => $report_id,
            'report_filename' => $report_filename
        ]);
    }

    /*Function for health system user contract expiring report*/
    public static function getContractExpiringReportData($group_id)
    {
        $user_id = Auth::user()->id;

        $region = Request::input('region');
        $facility = Request::input('facility');
        $expiring_in = Request::input('expiring_in');
        $agreement_ids = Request::input('agreements');
        $physician_ids = Request::input('physicians');

        if ($expiring_in > 365 || $expiring_in < 1) {
            return Redirect::back()->with(['error' => Lang::get('health_system_region.expiring_in_error')]);
        }

        if ($agreement_ids == null || $physician_ids == null) {
            return Redirect::back()->with([
                'error' => Lang::get('breakdowns.selection_error')
            ]);
        }

        $filter_region = '';
        $filter_facility = '';

        $day_after_expiring_days = date('Y-m-d', strtotime("+" . $expiring_in . " days"));
        $today = date('Y-m-d');

        $contracts = [];
        foreach ($agreement_ids as $agreement_id) {
            $agreement = Agreement::findOrFail($agreement_id);
            $months_start = Request::input("start_{$agreement_id}_start_month");
            $months_end = Request::input("end_{$agreement_id}_start_month");

            /*Contracts data finding*/
            $contract_list = Contract::select("contracts.*", DB::raw('CONCAT(physicians.first_name, " ", physicians.last_name) AS physician_name'),
                "contract_names.name as contract_name", "agreements.id as agreement_id", "agreements.approval_process as approval_process", "agreements.start_date as agreement_start_date",
                "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date", "hospitals.name as hospital_name", "health_system_regions.region_name as region_name")
                ->join('physicians', 'physicians.id', '=', 'contracts.physician_id')
                ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                ->join("region_hospitals", "region_hospitals.hospital_id", "=", "agreements.hospital_id")
                ->join("health_system_regions", "health_system_regions.id", "=", "region_hospitals.region_id");

            if ($group_id == Group::HEALTH_SYSTEM_USER) {

                $contract_list = $contract_list->join("health_system_users", "health_system_users.health_system_id", "=", "health_system_regions.health_system_id")
                    ->where("health_system_users.user_id", "=", $user_id);
                //find health system id
                $system_user = HealthSystemUsers::where('user_id', '=', Auth::user()->id)->first();
                $health_system_id = $system_user->health_system_id;
                $health_system_region_id = 0;
                $health_system = HealthSystem:: where('id', '=', $health_system_id)->first();
                $filter_healthsystem = $health_system->health_system_name;

            } else {
                $contract_list = $contract_list->join("health_system_region_users", "health_system_region_users.health_system_region_id", "=", "region_hospitals.region_id")
                    ->where("health_system_region_users.user_id", "=", $user_id);
                //find health system id & health system region id

                $region_user = HealthSystemRegionUsers::where('user_id', '=', Auth::user()->id)->first();
                $health_system_region_id = $region_user->health_system_region_id;
                $health_system_region = HealthSystemRegion::findOrfail($region_user->health_system_region_id);
                $health_system_id = $health_system_region->health_system_id;
                $health_system = HealthSystem:: where('id', '=', $health_system_id)->first();
                $filter_healthsystem = $health_system->health_system_name;
            }

            if ($region != 0) {
                $contract_list = $contract_list->where("region_hospitals.region_id", "=", $region);
            }

            if ($facility != 0) {
                $contract_list = $contract_list->where("hospitals.id", "=", $facility);
            }

            $contract_list = $contract_list->whereRaw("agreements.is_deleted=0")
                ->whereRaw("agreements.start_date <= now()")
                ->where('agreements.start_date', '>=', date('Y-m-d', strtotime($months_start)))
                ->whereRaw("agreements.end_date >= now()")
                // ->whereRaw("contracts.manual_contract_end_date >= now()")
                ->where('contracts.manual_contract_end_date', '<=', date('Y-m-d', strtotime($months_end)))
                ->whereIn('contracts.physician_id', $physician_ids)
                ->where('agreements.id', '=', $agreement_id)
                //->whereRaw("agreements.end_date <= '".$day_after_expiring_days."'")
                ->whereRaw("contracts.manual_contract_end_date <= '" . $day_after_expiring_days . "'");
            $contract_list = $contract_list->whereNull('region_hospitals.deleted_at')
                ->whereNull('contracts.deleted_at')->distinct()->get();

            if (count($contract_list) > 0) {
                foreach ($contract_list as $contract_list) {
                    $contracts[] = $contract_list;
                }
            }
        }

        $data = array();

        $last_date_of_prev_month = date('m/d/Y', strtotime('last day of previous month'));
        if (count($contracts) > 0) {
            foreach ($contracts as $contract) {
                $startEndDatesForYear = Agreement::getAgreementStartDateForYear($contract->agreement_id);
                if (strtotime($last_date_of_prev_month) <= strtotime($contract->manual_contract_end_date)) {
                    $end_date = $last_date_of_prev_month;
                } elseif (strtotime($contract->manual_contract_end_date) <= strtotime($startEndDatesForYear['year_end_date']->format('Y-m-d'))) {
                    $end_date = date('m/d/Y', strtotime($contract->manual_contract_end_date));
                } else {
                    $end_date = $startEndDatesForYear['year_end_date']->format('m/d/Y');
                }
                //$months = months($contract->agreement_start_date, $contract->manual_contract_end_date);
                $months = months($startEndDatesForYear['year_start_date']->format('m/d/Y'), $end_date);
                //$total_expected_payment = ($months * $contract->expected_hours) * $contract->rate;
                $total_expected_payment = ContractRate::findTotalExpectedPayment($contract->id, $startEndDatesForYear['year_start_date'], $months);

                $total_amount_paid = Amount_paid::select(DB::raw("SUM(amountPaid) as paid"))
                    ->where("contract_id", "=", $contract->id)
                    ->where("start_date", ">=", mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')))->first();

                $data[] = [
                    "contract_name" => $contract->contract_name,
                    "hospital_name" => $contract->hospital_name,
                    "region_name" => $contract->region_name,
                    "physician_name" => $contract->physician_name,
                    "agreement_start_date" => (($contract->payment_type_id == PaymentType::HOURLY) && ($contract->prior_start_date != '0000-00-00')) ? format_date($contract->prior_start_date) : format_date($contract->agreement_start_date), //changes done for prior start date of contract
                    "agreement_end_date" => format_date($contract->agreement_end_date),
                    "manual_contract_end_date" => format_date($contract->manual_contract_end_date),
                    "agreement_valid_upto_date" => format_date($contract->agreement_valid_upto_date)
                ];
                $filter_region = $contract->region_name;
                $filter_facility = $contract->hospital_name;
            }
        }

        $filter_region = $region == 0 ? 'All Divisions' : $filter_region;
        $filter_facility = $facility == 0 ? 'All Facilities' : $filter_facility;

        $timestamp = Request::input("current_timestamp");
        $timeZone = Request::input("current_zoneName");
        //log::info("timestamp",array($timestamp));

        if ($timestamp != null && $timeZone != null && $timeZone != '' && $timestamp != '') {
            if (!strtotime($timestamp)) {
                $zone = new DateTime(strtotime($timestamp));
            } else {
                $zone = new DateTime(false);
            }
            $zone->setTimezone(new DateTimeZone($timeZone));
            $localtimeZone = $zone->format('m/d/Y h:i A T');
        } else {
            $localtimeZone = '';
        }

        Artisan::call("reports:HealthSystemContractsExpiringReport", [
            "data" => $data,
            "user_id" => $user_id,
            "health_system_id" => $health_system_id,
            "health_system_region_id" => $health_system_region_id,
            "expiring_in" => $expiring_in,
            "filter_region" => $filter_region,
            "filter_facility" => $filter_facility,
            "filter_healthsystem" => $filter_healthsystem,
            "localtimeZone" => $localtimeZone
        ]);

        if (!HealthSystemContractsExpiringReportCommand::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(HealthSystemContractsExpiringReportCommand::$message)
            ]);
        }

        $report_id = HealthSystemContractsExpiringReportCommand::$report_id;
        $report_filename = HealthSystemContractsExpiringReportCommand::$report_filename;

        return Redirect::back()->with([
            'success' => Lang::get(HealthSystemContractsExpiringReportCommand::$message),
            'report_id' => $report_id,
            'report_filename' => $report_filename
        ]);
    }

    /*Function for health system user contract expiring report*/
    public static function getContractExpiringReportDataOld($group_id)
    {


        $user_id = Auth::user()->id;
        //$group_id= Auth::user()->group_id;

        $region = Request::input('region');
        $facility = Request::input('facility');
        $expiring_in = Request::input('expiring_in');

        if ($expiring_in > 365 || $expiring_in < 1) {
            return Redirect::back()->with(['error' => Lang::get('health_system_region.expiring_in_error')]);
        }

        $filter_region = '';
        $filter_facility = '';

        $day_after_expiring_days = date('Y-m-d', strtotime("+" . $expiring_in . " days"));
        $today = date('Y-m-d');

        /*Contracts data finding*/
        $contract_list = Contract::select("contracts.*", DB::raw('CONCAT(physicians.first_name, " ", physicians.last_name) AS physician_name'),
            "contract_names.name as contract_name", "agreements.id as agreement_id", "agreements.approval_process as approval_process", "agreements.start_date as agreement_start_date",
            "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date", "hospitals.name as hospital_name", "health_system_regions.region_name as region_name")
            ->join('physicians', 'physicians.id', '=', 'contracts.physician_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
            ->join("region_hospitals", "region_hospitals.hospital_id", "=", "agreements.hospital_id")
            ->join("health_system_regions", "health_system_regions.id", "=", "region_hospitals.region_id");

        if ($group_id == Group::HEALTH_SYSTEM_USER) {

            $contract_list = $contract_list->join("health_system_users", "health_system_users.health_system_id", "=", "health_system_regions.health_system_id")
                ->where("health_system_users.user_id", "=", $user_id);
            //find health system id
            $system_user = HealthSystemUsers::where('user_id', '=', Auth::user()->id)->first();
            $health_system_id = $system_user->health_system_id;
            $health_system_region_id = 0;
            $health_system = HealthSystem:: where('id', '=', $health_system_id)->first();
            $filter_healthsystem = $health_system->health_system_name;

        } else {
            $contract_list = $contract_list->join("health_system_region_users", "health_system_region_users.health_system_region_id", "=", "region_hospitals.region_id")
                ->where("health_system_region_users.user_id", "=", $user_id);
            //find health system id & health system region id

            $region_user = HealthSystemRegionUsers::where('user_id', '=', Auth::user()->id)->first();
            $health_system_region_id = $region_user->health_system_region_id;
            $health_system_region = HealthSystemRegion::findOrfail($region_user->health_system_region_id);
            $health_system_id = $health_system_region->health_system_id;
            $health_system = HealthSystem:: where('id', '=', $health_system_id)->first();
            $filter_healthsystem = $health_system->health_system_name;

        }

        if ($region != 0) {
            $contract_list = $contract_list->where("region_hospitals.region_id", "=", $region);
        }

        if ($facility != 0) {
            $contract_list = $contract_list->where("hospitals.id", "=", $facility);
        }

        $contract_list = $contract_list->whereRaw("agreements.is_deleted=0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("agreements.end_date >= now()")
            ->whereRaw("contracts.manual_contract_end_date >= now()")
            //->whereRaw("agreements.end_date <= '".$day_after_expiring_days."'")
            ->whereRaw("contracts.manual_contract_end_date <= '" . $day_after_expiring_days . "'");
        $contract_list = $contract_list->whereNull('region_hospitals.deleted_at')
            ->whereNull('contracts.deleted_at')->distinct()->get();

        $data = array();

        $last_date_of_prev_month = date('m/d/Y', strtotime('last day of previous month'));

        foreach ($contract_list as $contract) {
            $startEndDatesForYear = Agreement::getAgreementStartDateForYear($contract->agreement_id);
            if (strtotime($last_date_of_prev_month) <= strtotime($contract->manual_contract_end_date)) {
                $end_date = $last_date_of_prev_month;
            } elseif (strtotime($contract->manual_contract_end_date) <= strtotime($startEndDatesForYear['year_end_date'])) {
                $end_date = date('m/d/Y', strtotime($contract->manual_contract_end_date));
            } else {
                $end_date = $startEndDatesForYear['year_end_date']->format('m/d/Y');
            }
            //$months = months($contract->agreement_start_date, $contract->manual_contract_end_date);
            $months = months($startEndDatesForYear['year_start_date']->format('m/d/Y'), $end_date);
            //$total_expected_payment = ($months * $contract->expected_hours) * $contract->rate;
            $total_expected_payment = ContractRate::findTotalExpectedPayment($contract->id, $startEndDatesForYear['year_start_date'], $months);

            $total_amount_paid = Amount_paid::select(DB::raw("SUM(amountPaid) as paid"))
                ->where("contract_id", "=", $contract->id)
                ->where("start_date", ">=", mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')))->first();

            $data[] = [
                "contract_name" => $contract->contract_name,
                "hospital_name" => $contract->hospital_name,
                "region_name" => $contract->region_name,
                "physician_name" => $contract->physician_name,
                "agreement_start_date" => (($contract->payment_type_id == PaymentType::HOURLY) && ($contract->prior_start_date != '0000-00-00')) ? format_date($contract->prior_start_date) : format_date($contract->agreement_start_date), //changes done for prior start date of contract
                "agreement_end_date" => format_date($contract->agreement_end_date),
                "manual_contract_end_date" => format_date($contract->manual_contract_end_date),
                "agreement_valid_upto_date" => format_date($contract->agreement_valid_upto_date)
            ];
            $filter_region = $contract->region_name;
            $filter_facility = $contract->hospital_name;
        }

        $filter_region = $region == 0 ? 'All Divisions' : $filter_region;
        $filter_facility = $facility == 0 ? 'All Facilities' : $filter_facility;

        $timestamp = Request::input("current_timestamp");
        $timeZone = Request::input("current_zoneName");
        //log::info("timestamp",array($timestamp));

        if ($timestamp != null && $timeZone != null && $timeZone != '' && $timestamp != '') {
            if (!strtotime($timestamp)) {
                $zone = new DateTime(strtotime($timestamp));
            } else {
                $zone = new DateTime(false);
            }
            $zone->setTimezone(new DateTimeZone($timeZone));
            $localtimeZone = $zone->format('m/d/Y h:i A T');
        } else {
            $localtimeZone = '';
        }

        Artisan::call("reports:HealthSystemContractsExpiringReport", [
            "data" => $data,
            "user_id" => $user_id,
            "health_system_id" => $health_system_id,
            "health_system_region_id" => $health_system_region_id,
            "expiring_in" => $expiring_in,
            "filter_region" => $filter_region,
            "filter_facility" => $filter_facility,
            "filter_healthsystem" => $filter_healthsystem,
            "localtimeZone" => $localtimeZone
        ]);

        if (!HealthSystemContractsExpiringReportCommand::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(HealthSystemContractsExpiringReportCommand::$message)
            ]);
        }

        $report_id = HealthSystemContractsExpiringReportCommand::$report_id;
        $report_filename = HealthSystemContractsExpiringReportCommand::$report_filename;

        return Redirect::back()->with([
            'success' => Lang::get(HealthSystemContractsExpiringReportCommand::$message),
            'report_id' => $report_id,
            'report_filename' => $report_filename
        ]);
    }

    /*Function for Spend YTD & effectiveness*/
    public static function getContractSpendYTDEffectivenessReportData($group_id)
    {
        set_time_limit(0);
        $user_id = Auth::user()->id;
        //$group_id= Auth::user()->group_id;

        $region = Request::input('region');
        $facility = Request::input('facility');
        $payment_type = Request::input('payment_type');
        $contract_type = Request::input('contract_type');
        $agreement_ids = Request::input('agreements');
        $physician_ids = Request::input('physicians');

        if ($agreement_ids == null || $physician_ids == null) {
            return Redirect::back()->with([
                'error' => Lang::get('breakdowns.selection_error')
            ]);
        }

        if ($group_id == Group::HEALTH_SYSTEM_USER) {
            //find health system id
            $system_user = HealthSystemUsers::where('user_id', '=', Auth::user()->id)->first();
            $health_system_id = $system_user->health_system_id;
            $health_system_region_id = 0;
            $health_system = HealthSystem:: where('id', '=', $health_system_id)->first();
            $filter_healthsystem = $health_system->health_system_name;
        } else {
            //find health system id & health system region id
            $region_user = HealthSystemRegionUsers::where('user_id', '=', Auth::user()->id)->first();
            $health_system_region_id = $region_user->health_system_region_id;
            $health_system_region = HealthSystemRegion::findOrfail($region_user->health_system_region_id);
            $health_system_id = $health_system_region->health_system_id;
            $health_system = HealthSystem:: where('id', '=', $health_system_id)->first();
            $filter_healthsystem = $health_system->health_system_name;
        }
        /*find data for health system*/
        $data['system'] = HealthSystem::findOrFail($health_system_id);
        $system_contract_count = 0;
        $system_expected_hours_total = 0;
        $system_worked_hours_total = 0;
        $system_expected_amount_total = 0;
        $system_paid_amount_total = 0;

        $filter_region = '';
        $filter_facility = '';

        $data['system_regions'] = [];//create array for region data
        $system_regions = HealthSystemRegion::where('health_system_id', '=', $health_system_id);
        $system_regions = $region != 0 ? $system_regions->where('id', '=', $region)->orderBy('region_name')->get() : $system_regions->orderBy('region_name')->get();

        foreach ($system_regions as $system_region) {
            $region_data['name'] = $system_region->region_name;
            $filter_region = $system_region->region_name;
            $region_contract_count = 0;
            $region_expected_hours_total = 0;
            $region_worked_hours_total = 0;
            $region_expected_amount_total = 0;
            $region_paid_amount_total = 0;

            $region_data['region_hospitals'] = RegionHospitals::select('region_hospitals.*', 'hospitals.name as name')
                ->join('hospitals', 'hospitals.id', '=', 'region_hospitals.hospital_id');

            /*Find that region's hospitals*/
            $region_data['region_hospitals'] = $region != 0 ? $region_data['region_hospitals']->where('region_hospitals.region_id', '=', $region) : $region_data['region_hospitals']->where('region_hospitals.region_id', '=', $system_region->id);
            /*Find that hospitals as per selected filter value */
            $region_data['region_hospitals'] = $facility != 0 ? $region_data['region_hospitals']->where('region_hospitals.hospital_id', '=', $facility)->get() : $region_data['region_hospitals']->get();

            foreach ($region_data['region_hospitals'] as $hospital) {//go through each hospital to find it's cpntract data
                $hospital_contract_count = 0;
                $hospital_expected_hours_total = 0;
                $hospital_worked_hours_total = 0;
                $hospital_expected_amount_total = 0;
                $hospital_paid_amount_total = 0;
                $contracts = [];

                foreach ($agreement_ids as $agreement_id) {
                    $agreement = Agreement::findOrFail($agreement_id);
                    $months_start = Request::input("start_{$agreement_id}_start_month");
                    $months_end = Request::input("end_{$agreement_id}_start_month");

                    // code for finding contracts of that hospital...
                    $contract_list = Contract::select("contracts.*")
                        ->join('physicians', 'physicians.id', '=', 'contracts.physician_id')
                        ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
                        ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                        ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id");

                    $contract_list = $contract_list->whereRaw("agreements.is_deleted=0")
                        ->whereRaw("agreements.start_date <= now()")
                        ->where('agreements.start_date', '>=', date('Y-m-d', strtotime($months_start)))
                        ->whereRaw("agreements.end_date >= now()")
                        // ->whereRaw("contracts.manual_contract_end_date >= now()")
                        ->where('contracts.manual_contract_end_date', '<=', date('Y-m-d', strtotime($months_end)))
                        ->whereIn('contracts.physician_id', $physician_ids)
                        ->where('agreements.id', '=', $agreement_id)
                        ->whereNull('contracts.deleted_at');

                    if ($contract_type != 0) {
                        $contract_list = $contract_list->where("contracts.contract_type_id", "=", $contract_type);
                    }
                    if ($payment_type != 0) {
                        $contract_list = $contract_list->where("contracts.payment_type_id", "=", $payment_type);
                    }
                    $contract_list = $contract_list->where("contracts.payment_type_id", "<>", PaymentType::PER_DIEM)//exclude per diem payment type contracts
                    ->where("hospitals.id", "=", $hospital->hospital_id)
                        ->get();

                    if (count($contract_list) > 0) {
                        foreach ($contract_list as $contract_list) {
                            $contracts[] = $contract_list;
                        }
                    }
                }
                if (count($contracts) > 0) {
                    foreach ($contracts as $contract) {
                        $startEndDatesForYear = $contract->getContractStartDateForYear();
                        $last_date_of_prev_month = date('m/d/Y', strtotime('last day of previous month'));

                        if (strtotime($last_date_of_prev_month) <= strtotime($contract->manual_contract_end_date)) {
                            $end_date = $last_date_of_prev_month;
                        } elseif (strtotime($contract->manual_contract_end_date) <= strtotime($startEndDatesForYear['year_end_date']->format('m/d/Y'))) {
                            $end_date = date('m/d/Y', strtotime($contract->manual_contract_end_date));
                        } else {
                            $end_date = $startEndDatesForYear['year_end_date']->format('m/d/Y');
                        }

                        $months = months($startEndDatesForYear['year_start_date']->format('m/d/Y'), $end_date);
                        $total_expected_hrs = $months * $contract->expected_hours;

                        $total_worked = PhysicianLog::select(DB::raw("sum(duration) as hours"))
                            ->whereBetween('date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($end_date)])
                            ->where('contract_id', '=', $contract->id)->first();
                        /*changes for adding contract prior worked hours values */
                        $year_start_date_formatted = date_format($startEndDatesForYear['year_start_date'], "Y-m-d");
                        $contract_total_worked_hours = strtotime($year_start_date_formatted) == strtotime($contract->prior_start_date) ? $total_worked->hours + $contract->prior_worked_hours : $total_worked->hours;
                        $total_worked_hrs = $contract_total_worked_hours;

                        //$total_worked_hrs = $total_worked->hours;

                        //$total_expected_payment = ($months * $contract->expected_hours) * $contract->rate;
                        $total_expected_payment = ContractRate::findTotalExpectedPayment($contract->id, $startEndDatesForYear['year_start_date'], $months);
                        $contract_ids[] = $contract->id;
                        $total_spend = Amount_paid::select(DB::raw("sum(amountPaid) as paid"))
                            ->where("contract_id", "=", $contract->id)
                            ->where("start_date", ">=", mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')))->first();
                        //$total_spend_amount = $total_spend->paid;
                        /*changes for adding contract prior amount paid values */
                        $year_start_date_formatted = date_format($startEndDatesForYear['year_start_date'], "Y-m-d");
                        $contract_total_spend_amount = strtotime($year_start_date_formatted) == strtotime($contract->prior_start_date) ? $total_spend->paid + $contract->prior_amount_paid : $total_spend->paid;
                        $total_spend_amount = $contract_total_spend_amount;

                        $total_expected_hrs = $total_expected_hrs != null ? $total_expected_hrs : 0;
                        $total_worked_hrs = $total_worked_hrs != null ? $total_worked_hrs : 0;
                        $total_expected_payment = $total_expected_payment != null ? $total_expected_payment : 0;
                        $total_spend_amount = $total_spend_amount != null ? $total_spend_amount : 0;

                        $hospital_contract_count++;
                        $hospital_expected_hours_total += $total_expected_hrs;
                        $hospital_worked_hours_total += $total_worked_hrs;
                        $hospital_expected_amount_total += $total_expected_payment;
                        $hospital_paid_amount_total += $total_spend_amount;
                    }
                }

                //find hospital work effectiveness
                if ($hospital_expected_hours_total > 0) {
                    $hospital_work_effectiveness = ($hospital_worked_hours_total / $hospital_expected_hours_total) * 100;
                } elseif ($hospital_worked_hours_total > 0) {
                    $hospital_work_effectiveness = 100;
                } else {
                    $hospital_work_effectiveness = 0;
                }

                //find hospital amount effectiveness
                if ($hospital_expected_amount_total > 0) {
                    $hospital_amount_effectiveness = ($hospital_paid_amount_total / $hospital_expected_amount_total) * 100;
                } elseif ($hospital_paid_amount_total > 0) {
                    $hospital_amount_effectiveness = 100;
                } else {
                    $hospital_amount_effectiveness = 0;
                }

                $hospital->contract_count = $hospital_contract_count;
                $hospital->expected_hours_total = $hospital_expected_hours_total;
                $hospital->worked_hours_total = $hospital_worked_hours_total;
                $hospital->work_effectiveness = $hospital_work_effectiveness;
                $hospital->expected_amount_total = $hospital_expected_amount_total;
                $hospital->paid_amount_total = $hospital_paid_amount_total;
                $hospital->amount_effectiveness = $hospital_amount_effectiveness;

                $region_contract_count += $hospital_contract_count;
                $region_expected_hours_total += $hospital_expected_hours_total;
                $region_worked_hours_total += $hospital_worked_hours_total;
                $region_expected_amount_total += $hospital_expected_amount_total;
                $region_paid_amount_total += $hospital_paid_amount_total;

                $filter_facility = $hospital->name;
            }

            //find region work effectiveness
            if ($region_expected_hours_total > 0) {
                $region_work_effectiveness = ($region_worked_hours_total / $region_expected_hours_total) * 100;
            } elseif ($region_worked_hours_total > 0) {
                $region_work_effectiveness = 100;
            } else {
                $region_work_effectiveness = 0;
            }
            //find region amount effectiveness
            if ($region_expected_amount_total > 0) {
                $region_amount_effectiveness = ($region_paid_amount_total / $region_expected_amount_total) * 100;
            } elseif ($region_paid_amount_total > 0) {
                $region_amount_effectiveness = 100;
            } else {
                $region_amount_effectiveness = 0;
            }

            $region_data['region_contract_count'] = $region_contract_count;
            $region_data['region_expected_hours_total'] = $region_expected_hours_total;
            $region_data['region_worked_hours_total'] = $region_worked_hours_total;
            $region_data['region_work_effectiveness'] = $region_work_effectiveness;
            $region_data['region_expected_amount_total'] = $region_expected_amount_total;
            $region_data['region_paid_amount_total'] = $region_paid_amount_total;
            $region_data['region_amount_effectiveness'] = $region_amount_effectiveness;

            $system_contract_count += $region_contract_count;
            $system_expected_hours_total += $region_expected_hours_total;
            $system_worked_hours_total += $region_worked_hours_total;
            $system_expected_amount_total += $region_expected_amount_total;
            $system_paid_amount_total += $region_paid_amount_total;

            $data['system_regions'][] = $region_data;
        }

        //find system work effectiveness
        if ($system_expected_hours_total > 0) {
            $system_work_effectiveness = ($system_worked_hours_total / $system_expected_hours_total) * 100;
        } elseif ($system_worked_hours_total > 0) {
            $system_work_effectiveness = 100;
        } else {
            $system_work_effectiveness = 0;
        }
        //find system amount effectiveness
        if ($system_expected_amount_total > 0) {
            $system_amount_effectiveness = ($system_paid_amount_total / $system_expected_amount_total) * 100;
        } elseif ($system_paid_amount_total > 0) {
            $system_amount_effectiveness = 100;
        } else {
            $system_amount_effectiveness = 0;
        }

        $data['system_contract_count'] = $system_contract_count;
        $data['system_expected_hours_total'] = $system_expected_hours_total;
        $data['system_worked_hours_total'] = $system_worked_hours_total;
        $data['system_work_effectiveness'] = $system_work_effectiveness;
        $data['system_expected_amount_total'] = $system_expected_amount_total;
        $data['system_paid_amount_total'] = $system_paid_amount_total;
        $data['system_amount_effectiveness'] = $system_amount_effectiveness;

        $filter_region = $region == 0 ? 'All Divisions' : $filter_region;
        $filter_facility = $facility == 0 ? 'All Facilities' : $filter_facility;

        $timestamp = Request::input("current_timestamp");
        $timeZone = Request::input("current_zoneName");
        //log::info("timestamp",array($timestamp));

        if ($timestamp != null && $timeZone != null && $timeZone != '' && $timestamp != '') {
            if (!strtotime($timestamp)) {
                $zone = new DateTime(strtotime($timestamp));
            } else {
                $zone = new DateTime(false);
            }
            $zone->setTimezone(new DateTimeZone($timeZone));
            $localtimeZone = $zone->format('m/d/Y h:i A T');
        } else {
            $localtimeZone = '';
        }

        Artisan::call("reports:HealthSystemSpendYTDEffectivenessReport", [
            "data" => $data,
            "user_id" => $user_id,
            "health_system_id" => $health_system_id,
            "health_system_region_id" => $health_system_region_id,
            "filter_region" => $filter_region,
            "filter_facility" => $filter_facility,
            "filter_healthsystem" => $filter_healthsystem,
            "localtimeZone" => $localtimeZone
        ]);

        if (!HealthSystemSpendYTDEffectivenessReportCommand::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(HealthSystemSpendYTDEffectivenessReportCommand::$message)
            ]);
        }

        $report_id = HealthSystemSpendYTDEffectivenessReportCommand::$report_id;
        $report_filename = HealthSystemSpendYTDEffectivenessReportCommand::$report_filename;

        return Redirect::back()->with([
            'success' => Lang::get(HealthSystemSpendYTDEffectivenessReportCommand::$message),
            'report_id' => $report_id,
            'report_filename' => $report_filename
        ]);
    }

    /*Function for Spend YTD & effectiveness*/
    public static function getContractSpendYTDEffectivenessReportDataOld($group_id)
    {

        $user_id = Auth::user()->id;
        //$group_id= Auth::user()->group_id;


        $region = Request::input('region');
        $facility = Request::input('facility');
        $payment_type = Request::input('payment_type');
        $contract_type = Request::input('contract_type');


        if ($group_id == Group::HEALTH_SYSTEM_USER) {
            //find health system id
            $system_user = HealthSystemUsers::where('user_id', '=', Auth::user()->id)->first();
            $health_system_id = $system_user->health_system_id;
            $health_system_region_id = 0;
            $health_system = HealthSystem:: where('id', '=', $health_system_id)->first();
            $filter_healthsystem = $health_system->health_system_name;


        } else {
            //find health system id & health system region id

            $region_user = HealthSystemRegionUsers::where('user_id', '=', Auth::user()->id)->first();
            $health_system_region_id = $region_user->health_system_region_id;
            $health_system_region = HealthSystemRegion::findOrfail($region_user->health_system_region_id);
            $health_system_id = $health_system_region->health_system_id;
            $health_system = HealthSystem:: where('id', '=', $health_system_id)->first();
            $filter_healthsystem = $health_system->health_system_name;


        }
        /*find data for health system*/
        $data['system'] = HealthSystem::findOrFail($health_system_id);
        $system_contract_count = 0;
        $system_expected_hours_total = 0;
        $system_worked_hours_total = 0;
        $system_expected_amount_total = 0;
        $system_paid_amount_total = 0;

        $filter_region = '';
        $filter_facility = '';

        $data['system_regions'] = [];//create array for region data
        $system_regions = HealthSystemRegion::where('health_system_id', '=', $health_system_id);
        $system_regions = $region != 0 ? $system_regions->where('id', '=', $region)->orderBy('region_name')->get() : $system_regions->orderBy('region_name')->get();

        foreach ($system_regions as $system_region) {
            $region_data['name'] = $system_region->region_name;
            $filter_region = $system_region->region_name;
            $region_contract_count = 0;
            $region_expected_hours_total = 0;
            $region_worked_hours_total = 0;
            $region_expected_amount_total = 0;
            $region_paid_amount_total = 0;

            $region_data['region_hospitals'] = RegionHospitals::select('region_hospitals.*', 'hospitals.name as name')
                ->join('hospitals', 'hospitals.id', '=', 'region_hospitals.hospital_id');

            /*Find that region's hospitals*/
            $region_data['region_hospitals'] = $region != 0 ? $region_data['region_hospitals']->where('region_hospitals.region_id', '=', $region) : $region_data['region_hospitals']->where('region_hospitals.region_id', '=', $system_region->id);
            /*Find that hospitals as per selected filter value */
            $region_data['region_hospitals'] = $facility != 0 ? $region_data['region_hospitals']->where('region_hospitals.hospital_id', '=', $facility)->get() : $region_data['region_hospitals']->get();

            foreach ($region_data['region_hospitals'] as $hospital) {//go through each hospital to find it's cpntract data

                $hospital_contract_count = 0;
                $hospital_expected_hours_total = 0;
                $hospital_worked_hours_total = 0;
                $hospital_expected_amount_total = 0;
                $hospital_paid_amount_total = 0;
                // code for finding contracts of that hospital...
                $contract_list = Contract::select("contracts.*")
                    ->join('physicians', 'physicians.id', '=', 'contracts.physician_id')
                    ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id");
                $contract_list = $contract_list->whereRaw("agreements.is_deleted=0")
                    ->whereRaw("agreements.start_date <= now()")
                    ->whereRaw("agreements.end_date >= now()")
                    ->whereRaw("contracts.manual_contract_end_date >= now()")
                    ->whereNull('contracts.deleted_at');
                if ($contract_type != 0) {
                    $contract_list = $contract_list->where("contracts.contract_type_id", "=", $contract_type);
                }
                if ($payment_type != 0) {
                    $contract_list = $contract_list->where("contracts.payment_type_id", "=", $payment_type);
                }
                $contract_list = $contract_list->where("contracts.payment_type_id", "<>", PaymentType::PER_DIEM)//exclude per diem payment type contracts
                ->where("hospitals.id", "=", $hospital->hospital_id)
                    ->get();

                foreach ($contract_list as $contract) {
                    $startEndDatesForYear = $contract->getContractStartDateForYear();
                    $last_date_of_prev_month = date('m/d/Y', strtotime('last day of previous month'));

                    if (strtotime($last_date_of_prev_month) <= strtotime($contract->manual_contract_end_date)) {
                        $end_date = $last_date_of_prev_month;
                    } elseif (strtotime($contract->manual_contract_end_date) <= strtotime($startEndDatesForYear['year_end_date']->format('m/d/Y'))) {
                        $end_date = date('m/d/Y', strtotime($contract->manual_contract_end_date));
                    } else {
                        $end_date = $startEndDatesForYear['year_end_date']->format('m/d/Y');
                    }

                    $months = months($startEndDatesForYear['year_start_date']->format('m/d/Y'), $end_date);
                    $total_expected_hrs = $months * $contract->expected_hours;

                    $total_worked = PhysicianLog::select(DB::raw("sum(duration) as hours"))
                        ->whereBetween('date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($end_date)])
                        ->where('contract_id', '=', $contract->id)->first();
                    /*changes for adding contract prior worked hours values */
                    $year_start_date_formatted = date_format($startEndDatesForYear['year_start_date'], "Y-m-d");
                    $contract_total_worked_hours = strtotime($year_start_date_formatted) == strtotime($contract->prior_start_date) ? $total_worked->hours + $contract->prior_worked_hours : $total_worked->hours;
                    $total_worked_hrs = $contract_total_worked_hours;

                    //$total_worked_hrs = $total_worked->hours;

                    //$total_expected_payment = ($months * $contract->expected_hours) * $contract->rate;
                    $total_expected_payment = ContractRate::findTotalExpectedPayment($contract->id, $startEndDatesForYear['year_start_date'], $months);
                    $contract_ids[] = $contract->id;
                    $total_spend = Amount_paid::select(DB::raw("sum(amountPaid) as paid"))
                        ->where("contract_id", "=", $contract->id)
                        ->where("start_date", ">=", mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')))->first();
                    //$total_spend_amount = $total_spend->paid;
                    /*changes for adding contract prior amount paid values */
                    $year_start_date_formatted = date_format($startEndDatesForYear['year_start_date'], "Y-m-d");
                    $contract_total_spend_amount = strtotime($year_start_date_formatted) == strtotime($contract->prior_start_date) ? $total_spend->paid + $contract->prior_amount_paid : $total_spend->paid;
                    $total_spend_amount = $contract_total_spend_amount;

                    $total_expected_hrs = $total_expected_hrs != null ? $total_expected_hrs : 0;
                    $total_worked_hrs = $total_worked_hrs != null ? $total_worked_hrs : 0;
                    $total_expected_payment = $total_expected_payment != null ? $total_expected_payment : 0;
                    $total_spend_amount = $total_spend_amount != null ? $total_spend_amount : 0;

                    $hospital_contract_count++;
                    $hospital_expected_hours_total += $total_expected_hrs;
                    $hospital_worked_hours_total += $total_worked_hrs;
                    $hospital_expected_amount_total += $total_expected_payment;
                    $hospital_paid_amount_total += $total_spend_amount;


                }
                //find hospital work effectiveness
                if ($hospital_expected_hours_total > 0) {
                    $hospital_work_effectiveness = ($hospital_worked_hours_total / $hospital_expected_hours_total) * 100;
                } elseif ($hospital_worked_hours_total > 0) {
                    $hospital_work_effectiveness = 100;
                } else {
                    $hospital_work_effectiveness = 0;
                }
                //find hospital amount effectiveness
                if ($hospital_expected_amount_total > 0) {
                    $hospital_amount_effectiveness = ($hospital_paid_amount_total / $hospital_expected_amount_total) * 100;
                } elseif ($hospital_paid_amount_total > 0) {
                    $hospital_amount_effectiveness = 100;
                } else {
                    $hospital_amount_effectiveness = 0;
                }

                $hospital->contract_count = $hospital_contract_count;
                $hospital->expected_hours_total = $hospital_expected_hours_total;
                $hospital->worked_hours_total = $hospital_worked_hours_total;
                $hospital->work_effectiveness = $hospital_work_effectiveness;
                $hospital->expected_amount_total = $hospital_expected_amount_total;
                $hospital->paid_amount_total = $hospital_paid_amount_total;
                $hospital->amount_effectiveness = $hospital_amount_effectiveness;

                $region_contract_count += $hospital_contract_count;
                $region_expected_hours_total += $hospital_expected_hours_total;
                $region_worked_hours_total += $hospital_worked_hours_total;
                $region_expected_amount_total += $hospital_expected_amount_total;
                $region_paid_amount_total += $hospital_paid_amount_total;

                $filter_facility = $hospital->name;
            }

            //find region work effectiveness
            if ($region_expected_hours_total > 0) {
                $region_work_effectiveness = ($region_worked_hours_total / $region_expected_hours_total) * 100;
            } elseif ($region_worked_hours_total > 0) {
                $region_work_effectiveness = 100;
            } else {
                $region_work_effectiveness = 0;
            }
            //find region amount effectiveness
            if ($region_expected_amount_total > 0) {
                $region_amount_effectiveness = ($region_paid_amount_total / $region_expected_amount_total) * 100;
            } elseif ($region_paid_amount_total > 0) {
                $region_amount_effectiveness = 100;
            } else {
                $region_amount_effectiveness = 0;
            }

            $region_data['region_contract_count'] = $region_contract_count;
            $region_data['region_expected_hours_total'] = $region_expected_hours_total;
            $region_data['region_worked_hours_total'] = $region_worked_hours_total;
            $region_data['region_work_effectiveness'] = $region_work_effectiveness;
            $region_data['region_expected_amount_total'] = $region_expected_amount_total;
            $region_data['region_paid_amount_total'] = $region_paid_amount_total;
            $region_data['region_amount_effectiveness'] = $region_amount_effectiveness;


            $system_contract_count += $region_contract_count;
            $system_expected_hours_total += $region_expected_hours_total;
            $system_worked_hours_total += $region_worked_hours_total;
            $system_expected_amount_total += $region_expected_amount_total;
            $system_paid_amount_total += $region_paid_amount_total;

            $data['system_regions'][] = $region_data;

        }

        //find system work effectiveness
        if ($system_expected_hours_total > 0) {
            $system_work_effectiveness = ($system_worked_hours_total / $system_expected_hours_total) * 100;
        } elseif ($system_worked_hours_total > 0) {
            $system_work_effectiveness = 100;
        } else {
            $system_work_effectiveness = 0;
        }
        //find system amount effectiveness
        if ($system_expected_amount_total > 0) {
            $system_amount_effectiveness = ($system_paid_amount_total / $system_expected_amount_total) * 100;
        } elseif ($system_paid_amount_total > 0) {
            $system_amount_effectiveness = 100;
        } else {
            $system_amount_effectiveness = 0;
        }

        $data['system_contract_count'] = $system_contract_count;
        $data['system_expected_hours_total'] = $system_expected_hours_total;
        $data['system_worked_hours_total'] = $system_worked_hours_total;
        $data['system_work_effectiveness'] = $system_work_effectiveness;
        $data['system_expected_amount_total'] = $system_expected_amount_total;
        $data['system_paid_amount_total'] = $system_paid_amount_total;
        $data['system_amount_effectiveness'] = $system_amount_effectiveness;

        $filter_region = $region == 0 ? 'All Divisions' : $filter_region;
        $filter_facility = $facility == 0 ? 'All Facilities' : $filter_facility;

        $timestamp = Request::input("current_timestamp");
        $timeZone = Request::input("current_zoneName");
        //log::info("timestamp",array($timestamp));

        if ($timestamp != null && $timeZone != null && $timeZone != '' && $timestamp != '') {
            if (!strtotime($timestamp)) {
                $zone = new DateTime(strtotime($timestamp));
            } else {
                $zone = new DateTime(false);
            }
            $zone->setTimezone(new DateTimeZone($timeZone));
            $localtimeZone = $zone->format('m/d/Y h:i A T');
        } else {
            $localtimeZone = '';
        }

        Artisan::call("reports:HealthSystemSpendYTDEffectivenessReport", [
            "data" => $data,
            "user_id" => $user_id,
            "health_system_id" => $health_system_id,
            "health_system_region_id" => $health_system_region_id,
            "filter_region" => $filter_region,
            "filter_facility" => $filter_facility,
            "filter_healthsystem" => $filter_healthsystem,
            "localtimeZone" => $localtimeZone
        ]);

        if (!HealthSystemSpendYTDEffectivenessReportCommand::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(HealthSystemSpendYTDEffectivenessReportCommand::$message)
            ]);
        }

        $report_id = HealthSystemSpendYTDEffectivenessReportCommand::$report_id;
        $report_filename = HealthSystemSpendYTDEffectivenessReportCommand::$report_filename;

        return Redirect::back()->with([
            'success' => Lang::get(HealthSystemSpendYTDEffectivenessReportCommand::$message),
            'report_id' => $report_id,
            'report_filename' => $report_filename
        ]);
    }

    /*Provider profile report */
    public static function getProviderProfileReportData($group_id)
    {
        $user_id = Auth::user()->id;
        //$group_id= Auth::user()->group_id;


        $region = Request::input('region');
        $facility = Request::input('facility');
        $sort_by = Request::input('sortBy');
        $agreement_ids = Request::input('agreements');
        $physician_ids = Request::input('physicians');

        if ($agreement_ids == null || $physician_ids == null) {
            return Redirect::back()->with([
                'error' => Lang::get('breakdowns.selection_error')
            ]);
        }

        //$sort_by='';
        //$sort_by='FMV_RATE';
        //$sort_by='HOURS_WORKED';
        //$sort_by='AMOUNT_PAID';

        $contracts = [];
        foreach ($agreement_ids as $agreement_id) {
            $agreement = Agreement::findOrFail($agreement_id);
            $months_start = Request::input("start_{$agreement_id}_start_month");
            $months_end = Request::input("end_{$agreement_id}_start_month");

            /*Contracts data finding*/
            $contract_list = Contract::select("contracts.*", DB::raw('CONCAT(physicians.first_name, " ", physicians.last_name) AS physician_name'),
                "contract_names.name as contract_name", "agreements.id as agreement_id", "agreements.approval_process as approval_process", "agreements.start_date as agreement_start_date",
                "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date", "hospitals.name as hospital_name", "health_system_regions.region_name as region_name")
                ->join('physicians', 'physicians.id', '=', 'contracts.physician_id')
                ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                ->join("region_hospitals", "region_hospitals.hospital_id", "=", "agreements.hospital_id")
                ->join("health_system_regions", "health_system_regions.id", "=", "region_hospitals.region_id");

            if ($group_id == Group::HEALTH_SYSTEM_USER) {
                //find health system id
                $system_user = HealthSystemUsers::where('user_id', '=', Auth::user()->id)->first();
                $health_system_id = $system_user->health_system_id;
                $health_system_region_id = 0;
                $health_system = HealthSystem:: where('id', '=', $health_system_id)->first();
                $filter_healthsystem = $health_system->health_system_name;
                $contract_list = $contract_list->join("health_system_users", "health_system_users.health_system_id", "=", "health_system_regions.health_system_id")
                    ->where("health_system_users.user_id", "=", $user_id);
            } else {
                $contract_list = $contract_list->join("health_system_region_users", "health_system_region_users.health_system_region_id", "=", "region_hospitals.region_id")
                    ->where("health_system_region_users.user_id", "=", $user_id);
                //find health system id & health system region id
                $region_user = HealthSystemRegionUsers::where('user_id', '=', Auth::user()->id)->first();
                $health_system_region_id = $region_user->health_system_region_id;
                $health_system_region = HealthSystemRegion::findOrfail($region_user->health_system_region_id);
                $health_system_id = $health_system_region->health_system_id;
                $health_system = HealthSystem:: where('id', '=', $health_system_id)->first();
                $filter_healthsystem = $health_system->health_system_name;
            }

            if ($region != 0) {
                $contract_list = $contract_list->where("region_hospitals.region_id", "=", $region);
            }

            if ($facility != 0) {
                $contract_list = $contract_list->where("hospitals.id", "=", $facility);
            }

            $contract_list = $contract_list->whereRaw("agreements.is_deleted=0")
                ->whereRaw("agreements.start_date <= now()")
                ->where('agreements.start_date', '>=', date('Y-m-d', strtotime($months_start)))
                ->whereRaw("agreements.end_date >= now()")
                // ->whereRaw("contracts.manual_contract_end_date >= now()")
                ->where('contracts.manual_contract_end_date', '<=', date('Y-m-d', strtotime($months_end)))
                ->whereIn('contracts.physician_id', $physician_ids)
                ->where('agreements.id', '=', $agreement_id)
                ->whereNull('region_hospitals.deleted_at')
                ->whereNull('contracts.deleted_at');

            $contract_list = $contract_list->orderBy('region_name')->orderBy('hospital_name')->orderBy('agreement_start_date')->distinct()->get();

            if (count($contract_list) > 0) {
                foreach ($contract_list as $contract_list) {
                    $contracts[] = $contract_list;
                }
            }
        }

        $contracts = collect($contracts)->sortBy('region_name')->sortBy('hospital_name')->sortBy('agreement_start_date');

        $data = array();

        $last_date_of_prev_month = date('m/d/Y', strtotime('last day of previous month'));
        $filter_region = '';
        $filter_facility = '';

        if (count($contracts) > 0) {
            foreach ($contracts as $contract) {
                $startEndDatesForYear = $contract->getContractStartDateForYear();
                if (strtotime($last_date_of_prev_month) <= strtotime($contract->manual_contract_end_date)) {
                    $end_date = $last_date_of_prev_month;
                } elseif (strtotime($contract->manual_contract_end_date) <= strtotime($startEndDatesForYear['year_end_date']->format('Y-m-d'))) {
                    $end_date = date('m/d/Y', strtotime($contract->manual_contract_end_date));
                } else {
                    $end_date = $startEndDatesForYear['year_end_date']->format('m/d/Y');
                }
                $months = months($startEndDatesForYear['year_start_date']->format('m/d/Y'), $end_date);
                //$total_expected_payment = ($months * $contract->expected_hours) * $contract->rate;
                $total_expected_payment = ContractRate::findTotalExpectedPayment($contract->id, $startEndDatesForYear['year_start_date'], $months);

                $total_amount_paid = Amount_paid::select(DB::raw("SUM(amountPaid) as paid"))
                    ->where("contract_id", "=", $contract->id)
                    ->where("start_date", ">=", mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')))->first();

                /*changes for adding contract prior amount paid values */
                $year_start_date_formatted = date_format($startEndDatesForYear['year_start_date'], "Y-m-d");
                $contract_total_spend_amount = strtotime($year_start_date_formatted) == strtotime($contract->prior_start_date) ? $total_amount_paid->paid + $contract->prior_amount_paid : $total_amount_paid->paid;
                $total_amount_paid = $contract_total_spend_amount;

                $total_worked = PhysicianLog::select(DB::raw("sum(duration) as hours"))
                    ->whereBetween('date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($end_date)])
                    ->where('contract_id', '=', $contract->id)->first();
                //$total_worked_hrs = $total_worked->hours;
                /*changes for adding contract prior worked hours values */
                $year_start_date_formatted = date_format($startEndDatesForYear['year_start_date'], "Y-m-d");
                $contract_total_worked_hours = strtotime($year_start_date_formatted) == strtotime($contract->prior_start_date) ? $total_worked->hours + $contract->prior_worked_hours : $total_worked->hours;
                $total_worked_hrs = $contract_total_worked_hours;

                $perDiemContract = 0;
                if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                    $perDiemContract = 1;
                }
                if ($sort_by == 'FMV_RATE') //create array with with key of FMV Rate
                {
                    $data[] = [
                        "fmv_rate" => $contract->rate,
                        "worked_hrs" => $total_worked_hrs != null ? $total_worked_hrs : 0,
                        "amount" => $total_amount_paid != null ? $total_amount_paid : 0,
                        "contract_name" => $contract->contract_name,
                        "hospital_name" => $contract->hospital_name,
                        "region_name" => $contract->region_name,
                        "physician_name" => $contract->physician_name,
                        "agreement_start_date" => (($contract->payment_type_id == PaymentType::HOURLY) && ($contract->prior_start_date != '0000-00-00')) ? format_date($contract->prior_start_date) : format_date($contract->agreement_start_date), //changes done for prior start date of contract
                        "agreement_end_date" => format_date($contract->agreement_end_date),
                        "manual_contract_end_date" => format_date($contract->manual_contract_end_date),
                        "agreement_valid_upto_date" => format_date($contract->agreement_valid_upto_date),
                        "expected_spend" => $total_expected_payment != null ? $total_expected_payment : 0,
                        "isPerDiem" => $perDiemContract

                    ];
                } elseif ($sort_by == 'HOURS_WORKED') //create array with with key of Worked Hours
                {
                    $data[] = [
                        "worked_hrs" => $total_worked_hrs != null ? $total_worked_hrs : 0,
                        "fmv_rate" => $contract->rate,
                        "amount" => $total_amount_paid != null ? $total_amount_paid : 0,
                        "contract_name" => $contract->contract_name,
                        "hospital_name" => $contract->hospital_name,
                        "region_name" => $contract->region_name,
                        "physician_name" => $contract->physician_name,
                        "agreement_start_date" => (($contract->payment_type_id == PaymentType::HOURLY) && ($contract->prior_start_date != '0000-00-00')) ? format_date($contract->prior_start_date) : format_date($contract->agreement_start_date), //changes done for prior start date of contract
                        "agreement_end_date" => format_date($contract->agreement_end_date),
                        "manual_contract_end_date" => format_date($contract->manual_contract_end_date),
                        "agreement_valid_upto_date" => format_date($contract->agreement_valid_upto_date),
                        "expected_spend" => $total_expected_payment != null ? $total_expected_payment : 0,
                        "isPerDiem" => $perDiemContract
                    ];
                } else //create array with with key of amount paid
                {
                    $data[] = [
                        "amount" => $total_amount_paid != null ? $total_amount_paid : 0,
                        "worked_hrs" => $total_worked_hrs != null ? $total_worked_hrs : 0,
                        "fmv_rate" => $contract->rate,
                        "contract_name" => $contract->contract_name,
                        "hospital_name" => $contract->hospital_name,
                        "region_name" => $contract->region_name,
                        "physician_name" => $contract->physician_name,
                        "agreement_start_date" => (($contract->payment_type_id == PaymentType::HOURLY) && ($contract->prior_start_date != '0000-00-00')) ? format_date($contract->prior_start_date) : format_date($contract->agreement_start_date), //changes done for prior start date of contract
                        "agreement_end_date" => format_date($contract->agreement_end_date),
                        "manual_contract_end_date" => format_date($contract->manual_contract_end_date),
                        "agreement_valid_upto_date" => format_date($contract->agreement_valid_upto_date),
                        "expected_spend" => $total_expected_payment != null ? $total_expected_payment : 0,
                        "isPerDiem" => $perDiemContract
                    ];
                }

                $filter_region = $contract->region_name;
                $filter_facility = $contract->hospital_name;

            }
        }


        if (($sort_by == 'FMV_RATE') || ($sort_by == 'HOURS_WORKED') || ($sort_by == 'AMOUNT_PAID')) {
            arsort($data);
        }

        $filter_region = $region == 0 ? 'All Divisions' : $filter_region;
        $filter_facility = $facility == 0 ? 'All Facilities' : $filter_facility;

        $timestamp = Request::input("current_timestamp");
        $timeZone = Request::input("current_zoneName");
        //log::info("timestamp",array($timestamp));

        if ($timestamp != null && $timeZone != null && $timeZone != '' && $timestamp != '') {
            if (!strtotime($timestamp)) {
                $zone = new DateTime(strtotime($timestamp));
            } else {
                $zone = new DateTime(false);
            }
            $zone->setTimezone(new DateTimeZone($timeZone));
            $localtimeZone = $zone->format('m/d/Y h:i A T');
        } else {
            $localtimeZone = '';
        }

        Artisan::call("reports:HealthSystemProviderProfileReport", [
            "data" => $data,
            "user_id" => $user_id,
            "health_system_id" => $health_system_id,
            "health_system_region_id" => $health_system_region_id,
            "filter_region" => $filter_region,
            "filter_facility" => $filter_facility,
            "filter_healthsystem" => $filter_healthsystem,
            "localtimeZone" => $localtimeZone
        ]);

        if (!HealthSystemProviderProfileReportCommand::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(HealthSystemProviderProfileReportCommand::$message)
            ]);
        }

        $report_id = HealthSystemProviderProfileReportCommand::$report_id;
        $report_filename = HealthSystemProviderProfileReportCommand::$report_filename;

        return Redirect::back()->with([
            'success' => Lang::get(HealthSystemProviderProfileReportCommand::$message),
            'report_id' => $report_id,
            'report_filename' => $report_filename
        ]);
    }

    /*Provider profile report */
    public static function getProviderProfileReportDataOld($group_id)
    {

        $user_id = Auth::user()->id;
        //$group_id= Auth::user()->group_id;


        $region = Request::input('region');
        $facility = Request::input('facility');
        $sort_by = Request::input('sortBy');

        //$sort_by='';
        //$sort_by='FMV_RATE';
        //$sort_by='HOURS_WORKED';
        //$sort_by='AMOUNT_PAID';

        /*Contracts data finding*/
        $contract_list = Contract::select("contracts.*", DB::raw('CONCAT(physicians.first_name, " ", physicians.last_name) AS physician_name'),
            "contract_names.name as contract_name", "agreements.id as agreement_id", "agreements.approval_process as approval_process", "agreements.start_date as agreement_start_date",
            "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date", "hospitals.name as hospital_name", "health_system_regions.region_name as region_name")
            ->join('physicians', 'physicians.id', '=', 'contracts.physician_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
            ->join("region_hospitals", "region_hospitals.hospital_id", "=", "agreements.hospital_id")
            ->join("health_system_regions", "health_system_regions.id", "=", "region_hospitals.region_id");

        if ($group_id == Group::HEALTH_SYSTEM_USER) {
            //find health system id
            $system_user = HealthSystemUsers::where('user_id', '=', Auth::user()->id)->first();
            $health_system_id = $system_user->health_system_id;
            $health_system_region_id = 0;
            $health_system = HealthSystem:: where('id', '=', $health_system_id)->first();
            $filter_healthsystem = $health_system->health_system_name;
            $contract_list = $contract_list->join("health_system_users", "health_system_users.health_system_id", "=", "health_system_regions.health_system_id")
                ->where("health_system_users.user_id", "=", $user_id);
        } else {
            $contract_list = $contract_list->join("health_system_region_users", "health_system_region_users.health_system_region_id", "=", "region_hospitals.region_id")
                ->where("health_system_region_users.user_id", "=", $user_id);
            //find health system id & health system region id
            $region_user = HealthSystemRegionUsers::where('user_id', '=', Auth::user()->id)->first();
            $health_system_region_id = $region_user->health_system_region_id;
            $health_system_region = HealthSystemRegion::findOrfail($region_user->health_system_region_id);
            $health_system_id = $health_system_region->health_system_id;
            $health_system = HealthSystem:: where('id', '=', $health_system_id)->first();
            $filter_healthsystem = $health_system->health_system_name;
        }

        if ($region != 0) {
            $contract_list = $contract_list->where("region_hospitals.region_id", "=", $region);
        }

        if ($facility != 0) {
            $contract_list = $contract_list->where("hospitals.id", "=", $facility);
        }

        $contract_list = $contract_list->whereRaw("agreements.is_deleted=0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("agreements.end_date >= now()")
            ->whereRaw("contracts.manual_contract_end_date >= now()")
            ->whereNull('region_hospitals.deleted_at')
            ->whereNull('contracts.deleted_at');

        $contract_list = $contract_list->orderBy('region_name')->orderBy('hospital_name')->orderBy('agreement_start_date')->distinct()->get();

        $data = array();

        $last_date_of_prev_month = date('m/d/Y', strtotime('last day of previous month'));
        $filter_region = '';
        $filter_facility = '';
        foreach ($contract_list as $contract) {
            $startEndDatesForYear = $contract->getContractStartDateForYear();
            if (strtotime($last_date_of_prev_month) <= strtotime($contract->manual_contract_end_date)) {
                $end_date = $last_date_of_prev_month;
            } elseif (strtotime($contract->manual_contract_end_date) <= strtotime($startEndDatesForYear['year_end_date'])) {
                $end_date = date('m/d/Y', strtotime($contract->manual_contract_end_date));
            } else {
                $end_date = $startEndDatesForYear['year_end_date']->format('m/d/Y');
            }
            $months = months($startEndDatesForYear['year_start_date']->format('m/d/Y'), $end_date);
            //$total_expected_payment = ($months * $contract->expected_hours) * $contract->rate;
            $total_expected_payment = ContractRate::findTotalExpectedPayment($contract->id, $startEndDatesForYear['year_start_date'], $months);

            $total_amount_paid = Amount_paid::select(DB::raw("SUM(amountPaid) as paid"))
                ->where("contract_id", "=", $contract->id)
                ->where("start_date", ">=", mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')))->first();

            /*changes for adding contract prior amount paid values */
            $year_start_date_formatted = date_format($startEndDatesForYear['year_start_date'], "Y-m-d");
            $contract_total_spend_amount = strtotime($year_start_date_formatted) == strtotime($contract->prior_start_date) ? $total_amount_paid->paid + $contract->prior_amount_paid : $total_amount_paid->paid;
            $total_amount_paid = $contract_total_spend_amount;

            $total_worked = PhysicianLog::select(DB::raw("sum(duration) as hours"))
                ->whereBetween('date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($end_date)])
                ->where('contract_id', '=', $contract->id)->first();
            //$total_worked_hrs = $total_worked->hours;
            /*changes for adding contract prior worked hours values */
            $year_start_date_formatted = date_format($startEndDatesForYear['year_start_date'], "Y-m-d");
            $contract_total_worked_hours = strtotime($year_start_date_formatted) == strtotime($contract->prior_start_date) ? $total_worked->hours + $contract->prior_worked_hours : $total_worked->hours;
            $total_worked_hrs = $contract_total_worked_hours;

            $perDiemContract = 0;
            if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                $perDiemContract = 1;
            }
            if ($sort_by == 'FMV_RATE') //create array with with key of FMV Rate
            {
                $data[] = [
                    "fmv_rate" => $contract->rate,
                    "worked_hrs" => $total_worked_hrs != null ? $total_worked_hrs : 0,
                    "amount" => $total_amount_paid != null ? $total_amount_paid : 0,
                    "contract_name" => $contract->contract_name,
                    "hospital_name" => $contract->hospital_name,
                    "region_name" => $contract->region_name,
                    "physician_name" => $contract->physician_name,
                    "agreement_start_date" => (($contract->payment_type_id == PaymentType::HOURLY) && ($contract->prior_start_date != '0000-00-00')) ? format_date($contract->prior_start_date) : format_date($contract->agreement_start_date), //changes done for prior start date of contract
                    "agreement_end_date" => format_date($contract->agreement_end_date),
                    "manual_contract_end_date" => format_date($contract->manual_contract_end_date),
                    "agreement_valid_upto_date" => format_date($contract->agreement_valid_upto_date),
                    "expected_spend" => $total_expected_payment != null ? $total_expected_payment : 0,
                    "isPerDiem" => $perDiemContract

                ];
            } elseif ($sort_by == 'HOURS_WORKED') //create array with with key of Worked Hours
            {
                $data[] = [
                    "worked_hrs" => $total_worked_hrs != null ? $total_worked_hrs : 0,
                    "fmv_rate" => $contract->rate,
                    "amount" => $total_amount_paid != null ? $total_amount_paid : 0,
                    "contract_name" => $contract->contract_name,
                    "hospital_name" => $contract->hospital_name,
                    "region_name" => $contract->region_name,
                    "physician_name" => $contract->physician_name,
                    "agreement_start_date" => (($contract->payment_type_id == PaymentType::HOURLY) && ($contract->prior_start_date != '0000-00-00')) ? format_date($contract->prior_start_date) : format_date($contract->agreement_start_date), //changes done for prior start date of contract
                    "agreement_end_date" => format_date($contract->agreement_end_date),
                    "manual_contract_end_date" => format_date($contract->manual_contract_end_date),
                    "agreement_valid_upto_date" => format_date($contract->agreement_valid_upto_date),
                    "expected_spend" => $total_expected_payment != null ? $total_expected_payment : 0,
                    "isPerDiem" => $perDiemContract
                ];
            } else //create array with with key of amount paid
            {
                $data[] = [
                    "amount" => $total_amount_paid != null ? $total_amount_paid : 0,
                    "worked_hrs" => $total_worked_hrs != null ? $total_worked_hrs : 0,
                    "fmv_rate" => $contract->rate,
                    "contract_name" => $contract->contract_name,
                    "hospital_name" => $contract->hospital_name,
                    "region_name" => $contract->region_name,
                    "physician_name" => $contract->physician_name,
                    "agreement_start_date" => (($contract->payment_type_id == PaymentType::HOURLY) && ($contract->prior_start_date != '0000-00-00')) ? format_date($contract->prior_start_date) : format_date($contract->agreement_start_date), //changes done for prior start date of contract
                    "agreement_end_date" => format_date($contract->agreement_end_date),
                    "manual_contract_end_date" => format_date($contract->manual_contract_end_date),
                    "agreement_valid_upto_date" => format_date($contract->agreement_valid_upto_date),
                    "expected_spend" => $total_expected_payment != null ? $total_expected_payment : 0,
                    "isPerDiem" => $perDiemContract
                ];
            }

            $filter_region = $contract->region_name;
            $filter_facility = $contract->hospital_name;

        }

        if (($sort_by == 'FMV_RATE') || ($sort_by == 'HOURS_WORKED') || ($sort_by == 'AMOUNT_PAID')) {
            arsort($data);
        }

        $filter_region = $region == 0 ? 'All Divisions' : $filter_region;
        $filter_facility = $facility == 0 ? 'All Facilities' : $filter_facility;

        $timestamp = Request::input("current_timestamp");
        $timeZone = Request::input("current_zoneName");
        //log::info("timestamp",array($timestamp));

        if ($timestamp != null && $timeZone != null && $timeZone != '' && $timestamp != '') {
            if (!strtotime($timestamp)) {
                $zone = new DateTime(strtotime($timestamp));
            } else {
                $zone = new DateTime(false);
            }
            $zone->setTimezone(new DateTimeZone($timeZone));
            $localtimeZone = $zone->format('m/d/Y h:i A T');
        } else {
            $localtimeZone = '';
        }

        Artisan::call("reports:HealthSystemProviderProfileReport", [
            "data" => $data,
            "user_id" => $user_id,
            "health_system_id" => $health_system_id,
            "health_system_region_id" => $health_system_region_id,
            "filter_region" => $filter_region,
            "filter_facility" => $filter_facility,
            "filter_healthsystem" => $filter_healthsystem,
            "localtimeZone" => $localtimeZone
        ]);

        if (!HealthSystemProviderProfileReportCommand::$success) {
            return Redirect::back()->with([
                'error' => Lang::get(HealthSystemProviderProfileReportCommand::$message)
            ]);
        }

        $report_id = HealthSystemProviderProfileReportCommand::$report_id;
        $report_filename = HealthSystemProviderProfileReportCommand::$report_filename;

        return Redirect::back()->with([
            'success' => Lang::get(HealthSystemProviderProfileReportCommand::$message),
            'report_id' => $report_id,
            'report_filename' => $report_filename
        ]);
    }

    public static function queryWithUnion($model, callable $filter = null)
    {
        $model = 'App\\' . $model;

        $query = new $model;
        if ($filter != null) {
            $query = $filter($query);
        }

        $results = $query->get();
        return $results;
    }
}
