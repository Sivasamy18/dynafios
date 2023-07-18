<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Lang;
use App\AccessToken;
use App\ApiGraph;
use App\Services\ProductivMDService;
use App\Http\Controllers\BaseController;
use App\Physician;

class GraphApiController extends BaseController
{
    const STATUS_FAILURE = 0;
    const STATUS_SUCCESS = 1;

    public function getProviderByNPI(ProductivMDService $productivMDService)
    {
        $npi = Request::input("npi");
        
        if($npi == "" || $npi == null){
            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => Lang::get("Please enter npi.")
            ]);
        }

        $results = $productivMDService->getProviderByNPI($npi);

        return Response::json([
            "status" => self::STATUS_SUCCESS,
            "results" => $results
        ]);
    }

    public function getCompensationSummary(ProductivMDService $productivMDService)
    {
        $token = Request::input("token");
        $token = json_decode($token);
        $access_token = AccessToken::where("key", "=", $token)->first();
        $physician = $access_token->physician;

        $providerId = Request::header("providerId");   // GUID   // 377eb3dd-3525-4f9b-a237-a7f98bec894f
        $startDate = Request::input("startDate");     // 1/1/2022
        $endDate = Request::input("endDate");         // 12/31/2022
        $accrualInterval = Request::input("accrualInterval");     // annual / monthly
        
        if($providerId == "" || $providerId == null){
            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => Lang::get("Please enter provider id.")
            ]);
        } else if($startDate == "" || $startDate == null){
            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => Lang::get("Please enter start date.")
            ]);
        } else if($endDate == "" || $endDate == null){
            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => Lang::get("Please enter end date.")
            ]);
        } else if($accrualInterval == "" || $accrualInterval == null){
            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => Lang::get("Please enter accrual Interval.")
            ]);
        }

        $providerId = json_decode($providerId);
        $startDate = json_decode($startDate);
        $endDate = json_decode($endDate);
        $accrualInterval = json_decode($accrualInterval);
        $startDate = date('01/01/Y', strtotime($startDate));
        $endDate = date('m/t/Y', strtotime($endDate));          // comment line when we deploy on testing server
        // $endDate = date('m/t/Y', strtotime($startDate));     // Uncomment line when we deploy on testing server

        $results = $productivMDService->getCompensationSummary($providerId, $startDate, $endDate, $accrualInterval, $physician);

        return Response::json([
            "status" => self::STATUS_SUCCESS,
            "results" => $results
        ]);
    }

    public function getProductivityCompensation(ProductivMDService $productivMDService)
    {
        $token = Request::input("token");
        $token = json_decode($token);
        $access_token = AccessToken::where("key", "=", $token)->first();
        $physician = $access_token->physician;
        $providerId = Request::header("providerId");   // GUID   // 377eb3dd-3525-4f9b-a237-a7f98bec894f
        $startDate = Request::input("startDate");     // 1/1/2022
        $endDate = Request::input("endDate");         // 12/31/2022
        // $accrualInterval = Request::input("accrualInterval");     // annual / monthly 
        // $months = Request::input("month"); 
        
        if($providerId == "" || $providerId == null){
            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => Lang::get("Please enter provider id.")
            ]);
        } else if($startDate == "" || $startDate == null){
            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => Lang::get("Please enter start date.")
            ]);
        }

        $providerId = json_decode($providerId);
        $startDate = json_decode($startDate);
        $year = date('Y', strtotime($startDate));
        $month = 12;    // json_decode($months);

        $results = $productivMDService->getProductivityCompensation($providerId, $year, $month, $physician);

        return Response::json([
            "status" => self::STATUS_SUCCESS,
            "results" => $results
        ]);
    }

    public function getTtmProductivity(ProductivMDService $productivMDService)
    {
        $token = Request::input("token");
        $token = json_decode($token);
        $access_token = AccessToken::where("key", "=", $token)->first();
        $physician = $access_token->physician;
        $providerId = Request::header("providerId");   // GUID   // 377eb3dd-3525-4f9b-a237-a7f98bec894f
        $year = Request::input("year");     // 2022
        $month = Request::input("month");         // 12
        
        if($providerId == "" || $providerId == null){
            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => Lang::get("Please enter provider id.")
            ]);
        } else if($year == "" || $year == null){
            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => Lang::get("Please enter year.")
            ]);
        } else if($month == "" || $month == null){
            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => Lang::get("Please enter month.")
            ]);
        }
        
        $providerId = json_decode($providerId);
        $year = json_decode($year);
        $month = json_decode($month);

        $results = $productivMDService->getTtmProductivity($providerId, $year, $month, $physician);

        return Response::json([
            "status" => self::STATUS_SUCCESS,
            "results" => $results
        ]);
    }

    public function getCompensationSummaryGuage(ProductivMDService $productivMDService)
    {
        $token = Request::input("token");
        $token = json_decode($token);
        $access_token = AccessToken::where("key", "=", $token)->first();
        $physician = $access_token->physician;

        $startDate = Request::input("startDate");     // 1/1/2022
        $endDate = Request::input("endDate");         // 12/31/2022

        if($startDate == "" || $startDate == null){
            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => Lang::get("Please enter start date.")
            ]);
        } else if($endDate == "" || $endDate == null){
            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => Lang::get("Please enter end date.")
            ]);
        }

        $startDate = json_decode($startDate);
        $endDate = json_decode($endDate);
        $startDate = date('01/01/Y', strtotime($startDate));
        $endDate = date('m/t/Y', strtotime($endDate));          // comment line when we deploy on testing server
        // $endDate = date('m/t/Y', strtotime($startDate));     // Uncomment line when we deploy on testing server

        $results = $productivMDService->getCompensationSummaryGuage($startDate, $endDate, $physician);

        return Response::json([
            "status" => self::STATUS_SUCCESS,
            "results" => $results
        ]);
    }
}
