<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class Specialty extends Model
{
    protected $table = 'specialties';

    public static function getSpecialtiesForPerformansDashboard($user_id, $region, $facility, $practice_type, $group_id, $contract_type)
    {
        $default = [0 => 'All'];

        $specialties = self::select("specialties.id as id", "specialties.name as name")
            ->join("physicians", "physicians.specialty_id", "=", "specialties.id")
            ->join("physician_contracts", "physician_contracts.physician_id", "=", "physicians.id")
            ->join("contracts", "contracts.id", "=", "physician_contracts.contract_id")
            ->join("practices", "practices.id", "=", "physician_contracts.practice_id")
            ->join("hospitals", "hospitals.id", "=", "practices.hospital_id")
            ->join("hospital_user", "hospital_user.hospital_id", "=", "hospitals.id");

        if ($group_id == Group::HEALTH_SYSTEM_USER) {
            $specialties = $specialties->join("region_hospitals", "region_hospitals.hospital_id", "=", "hospitals.id");
            $specialties = $specialties->whereNull("region_hospitals.deleted_at");
        }

        $specialties = $specialties->where('hospital_user.user_id', '=', $user_id)
            ->whereNull("contracts.deleted_at");

        if ($region != 0) {
            $specialties = $specialties->where('region_hospitals.region_id', '=', $region);
        }

        if ($facility != 0) {
            $specialties = $specialties->where("hospitals.id", "=", $facility);
        } else {
            $hospitals = Hospital::select('hospitals.id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->get();

            $hospital_list = array();
            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('performance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital->id;
                }
            }

            $specialties = $specialties->whereIn("hospitals.id", $hospital_list);
        }

        if ($practice_type != 0) {
            if ($practice_type == 2) {
                $specialties = $specialties->whereBetween('practices.practice_type_id', array(1, 3));
            } elseif ($practice_type == 3) {
                $specialties = $specialties->whereBetween('practices.practice_type_id', array(4, 6));
            } else {
                $specialties = $specialties->whereBetween('practices.practice_type_id', array(1, 6));
            }
        }

        if ($contract_type != 0) {
            $specialties = $specialties->where("contracts.contract_type_id", "=", $contract_type);
        }

        $specialties = $specialties
            ->whereNotIn('contracts.payment_type_id', array(3, 5, 8))
            ->orderBy('name', 'asc')
            ->distinct()
            ->pluck('name', 'id')->toArray();

        return $default + $specialties;
    }

    public static function getManagementSpecialtyChart($user_id, $region, $facility, $practice_type, $specialty, $group_id, $contract_type)
    {
        $return_data = array();

        // $contracts = Contract::select('contracts.*', 'physician_contracts.physician_id as physician_ids')
        $contracts = Contract::select('contracts.id', 'contracts.agreement_id', 'physician_contracts.physician_id as physician_id')
            ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
            ->join("physicians", "physicians.id", "=", "physician_contracts.physician_id")
            ->join("practices", "practices.id", "=", "physician_contracts.practice_id")
            ->join("hospitals", "hospitals.id", "=", "practices.hospital_id")
            ->join("hospital_user", "hospital_user.hospital_id", "=", "hospitals.id");

        if ($group_id == Group::HEALTH_SYSTEM_USER) {
            $contracts = $contracts->join("region_hospitals", "region_hospitals.hospital_id", "=", "hospitals.id");
            $contracts = $contracts->whereNull("region_hospitals.deleted_at");
        }

        if ($region != 0) {
            $contracts = $contracts->where('region_hospitals.region_id', '=', $region);
        }

        if ($facility != 0) {
            $contracts = $contracts->where("hospitals.id", "=", $facility);
        } else {
            $hospitals = Hospital::select('hospitals.id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->get();

            $hospital_list = array();
            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('performance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital->id;
                }
            }

            $contracts = $contracts->whereIn("hospitals.id", $hospital_list);
        }

        if ($practice_type != 0) {
            if ($practice_type == 2) {
                $contracts = $contracts->whereBetween('practices.practice_type_id', array(1, 3));
            } elseif ($practice_type == 3) {
                $contracts = $contracts->whereBetween('practices.practice_type_id', array(4, 6));
            } else {
                $contracts = $contracts->whereBetween('practices.practice_type_id', array(1, 6));
            }
        }

        if ($contract_type != 0) {
            $contracts = $contracts->where("contracts.contract_type_id", "=", $contract_type);
        }

        if ($specialty != 0) {
            $contracts = $contracts->where("physicians.specialty_id", "=", $specialty);
        }

        $contracts = $contracts
            ->where('hospital_user.user_id', '=', $user_id)
            ->whereNotIn("contracts.payment_type_id", array(3, 5, 8))
            ->whereNull("contracts.deleted_at")
            ->distinct()->get();

        foreach ($contracts as $contract) {
            $startEndDatesForYear = Agreement::getAgreementStartDateForYear($contract->agreement_id);

            $total_durations = PhysicianLog::
            select(DB::raw("action_categories.id as category_id, action_categories.name as category_name, SUM(physician_logs.duration) as total_duration"))
                ->join("physicians", "physicians.id", "=", "physician_logs.physician_id")
                ->join("actions", "actions.id", "=", "physician_logs.action_id")
                ->join("action_categories", "action_categories.id", "=", "actions.category_id");

            if ($specialty != 0) {
                $total_durations = $total_durations->where("physicians.specialty_id", "=", $specialty);
            }

            $total_durations = $total_durations->where("physician_logs.contract_id", "=", $contract->id)
                ->where('physician_logs.physician_id', '=', $contract->physician_id)
                ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))])
                ->whereNull("physician_logs.deleted_at")
                // ->groupBy('action_categories.id')
                ->orderBy('action_categories.id', 'asc')
                ->distinct()->get();

            if (count($total_durations) > 0) {
                foreach ($total_durations as $logs_duration) {
                    if ($logs_duration->category_id) {
                        $collection = collect($return_data);
                        $check_exist = $collection->contains('category_id', $logs_duration->category_id);

                        if ($check_exist) {
                            $data = collect($return_data)->where('category_id', $logs_duration->category_id)->all();
                            foreach ($data as $data) {
                                $total_duration = $data["total_duration"] + $logs_duration->total_duration;

                                $category_id = $data["category_id"];

                                foreach ($return_data as $key => $value) {
                                    if ($value["category_id"] == $category_id) {
                                        unset($return_data[$key]);
                                    }
                                }

                                $return_data[] = [
                                    "category_id" => $logs_duration->category_id,
                                    "category_name" => $logs_duration->category_name,
                                    "total_duration" => formatNumber($total_duration) . ""
                                ];
                            }
                        } else {
                            $return_data[] = [
                                "category_id" => $logs_duration->category_id,
                                "category_name" => $logs_duration->category_name,
                                "total_duration" => formatNumber($logs_duration->total_duration) . ""
                            ];
                        }
                    }
                }
            }
        }
        $data = collect($return_data)->sortBy('category_id')->toArray();
        return $data;
    }

    public static function getActualToExpectedTimeSpecialtyChart($user_id, $region, $facility, $practice_type, $specialty, $group_id, $contract_type)
    {
        $return_data = array();

        // $contracts = Contract::select('contracts.*', 'physician_contracts.physician_id as physician_ids')
        $contracts = Contract::select('contracts.id', 'contracts.agreement_id', 'contracts.expected_hours', 'physician_contracts.physician_id as physician_id')
            ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
            ->join("physicians", "physicians.id", "=", "physician_contracts.physician_id")
            ->join("practices", "practices.id", "=", "physician_contracts.practice_id")
            ->join("hospitals", "hospitals.id", "=", "practices.hospital_id")
            ->join("hospital_user", "hospital_user.hospital_id", "=", "hospitals.id");

        if ($group_id == Group::HEALTH_SYSTEM_USER) {
            $contracts = $contracts->join("region_hospitals", "region_hospitals.hospital_id", "=", "hospitals.id");
            $contracts = $contracts->whereNull("region_hospitals.deleted_at");
        }

        if ($region != 0) {
            $contracts = $contracts->where('region_hospitals.region_id', '=', $region);
        }

        if ($facility != 0) {
            $contracts = $contracts->where("hospitals.id", "=", $facility);
        } else {
            $hospitals = Hospital::select('hospitals.id')
                ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
                ->where('hospital_user.user_id', '=', $user_id)
                ->where('hospitals.archived', '=', 0)
                ->get();

            $hospital_list = array();
            foreach ($hospitals as $hospital) {
                $compliance_on_off = DB::table('hospital_feature_details')->where("hospital_id", "=", $hospital->id)->orderBy('updated_at', 'desc')->pluck('performance_on_off')->first();
                if ($compliance_on_off == 1) {
                    $hospital_list[] = $hospital->id;
                }
            }

            $contracts = $contracts->whereIn("hospitals.id", $hospital_list);
        }

        if ($practice_type != 0) {
            if ($practice_type == 2) {
                $contracts = $contracts->whereBetween('practices.practice_type_id', array(1, 3));
            } elseif ($practice_type == 3) {
                $contracts = $contracts->whereBetween('practices.practice_type_id', array(4, 6));
            } else {
                $contracts = $contracts->whereBetween('practices.practice_type_id', array(1, 6));
            }
        }

        if ($contract_type != 0) {
            $contracts = $contracts->where("contracts.contract_type_id", "=", $contract_type);
        }

        if ($specialty != 0) {
            $contracts = $contracts->where("physicians.specialty_id", "=", $specialty);
        }

        $contracts = $contracts
            ->where('hospital_user.user_id', '=', $user_id)
            ->whereNotIn("contracts.payment_type_id", array(3, 5, 8))
            ->whereNull("contracts.deleted_at")
            ->distinct()->get();

        $total_expected_hours = 0;
        $total_actual_hours = 0;
        $total_remaining_hours = 0;
        $contract_ids = [];

        foreach ($contracts as $contract) {
            $startEndDatesForYear = Agreement::getAgreementStartDateForYear($contract->agreement_id);

            $expected_hours = $contract->expected_hours;

            $start_date = $startEndDatesForYear['year_start_date']->format('Y-m-d');
            $end_date = date("Y-n-j", strtotime("last day of previous month"));

            $start_date = strtotime($start_date);
            $end_date = strtotime($end_date);

            $year_start_date = date('Y', $start_date);
            $year_end_date = date('Y', $end_date);

            $month_start_date = date('m', $start_date);
            $month_end_date = date('m', $end_date);

            $months_diff = (($year_end_date - $year_start_date) * 12) + ($month_end_date - $month_start_date);
            $prior_periods = $months_diff + 1;

            $total_expected_hours = $prior_periods * $expected_hours;

            $total_durations = PhysicianLog::select(DB::raw("SUM(physician_logs.duration) as total_durations"))
                ->join("log_approval", "log_approval.log_id", "=", "physician_logs.id")
                ->join('physicians', 'physicians.id', '=', 'physician_logs.physician_id')
                ->whereNull("physician_logs.deleted_at")
                ->where('physician_logs.contract_id', '=', $contract->id)
                ->where('physician_logs.physician_id', '=', $contract->physician_id);

            // if($specialty != 0){
            //     $total_durations = $total_durations->where("physicians.specialty_id", "=", $specialty);
            // }

            $total_durations = $total_durations->where("log_approval.approval_managers_level", ">", 0)
                ->where("log_approval.approval_status", "=", "1")
                ->whereBetween('physician_logs.date', [mysql_date($startEndDatesForYear['year_start_date']->format('m/d/Y')), mysql_date($startEndDatesForYear['year_end_date']->format('m/d/Y'))])
                ->first();

            if ($total_durations->total_durations) {
                $total_durations = $total_durations->total_durations;
                $actual_hours = $total_durations;
                $remaining_hours = $total_durations > $total_expected_hours ? 0 : $total_expected_hours - $total_durations;

                $total_actual_hours += $actual_hours;
                if (!in_array($contract->id, $contract_ids)) {
                    array_push($contract_ids, $contract->id);
                    $total_remaining_hours += $remaining_hours;
                }
            }
        }

        if (count($contracts) > 0) {
            if ($total_actual_hours > 0 || $total_remaining_hours > 0) {
                $return_data[] = [
                    "type_id" => '1001',
                    "type_name" => 'Actual Hours',
                    "total_hours" => formatNumber($total_actual_hours) . ""
                ];

                $return_data[] = [
                    "type_id" => '1002',
                    "type_name" => 'Remaining Expected',
                    "total_hours" => formatNumber($total_remaining_hours) . ""
                ];
            }
        }

        return $return_data;
    }

    public function practices()
    {
        return $this->hasMany('App\Practice');
    }

    public function physicians()
    {
        return $this->hasMany('App\Physician');
    }
}
