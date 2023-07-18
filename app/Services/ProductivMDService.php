<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\ApiGraph;

use GuzzleHttp\Client;

class ProductivMDService
{
    private static $AZURE_CLIENT_INSTANCE = "";
    private static $AZURE_TENANT_ID = "";
    private static $AZURE_CLIENT_ID = "";
    private static $AZURE_CLIENT_SECRET = "";
    private static $base_url ="";
    private static $oAuth2BaseUri = "";
    private static $authConfig = [];

    private Client $httpClient;

    public function __construct() {
        self::$AZURE_CLIENT_INSTANCE = env('AZURE_CLIENT_INSTANCE');
        self::$AZURE_TENANT_ID = env('AZURE_TENANT_ID');
        self::$AZURE_CLIENT_ID = env('AZURE_CLIENT_ID');
        self::$AZURE_CLIENT_SECRET = env('AZURE_CLIENT_SECRET');

        self::$base_url = "https://report-api.dynafios.productivmd.com/";
        self::$oAuth2BaseUri = self::$AZURE_CLIENT_INSTANCE . "/" . self::$AZURE_TENANT_ID . "/oauth2/v2.0/token";
        self::$authConfig = [
            'client_id' => self::$AZURE_CLIENT_ID,
            'client_secret' => self::$AZURE_CLIENT_SECRET,
            'grant_type' => 'client_credentials',
            'scope' => 'https://graph.microsoft.com/.default'
        ];

        $this->httpClient = GuzzleOAuth2Service::getGuzzleOAuth2Client(self::$oAuth2BaseUri, self::$authConfig);
    }

    public function getProviderByNPI($npi) {
        try{
            $request = $this->httpClient->get("https://org-api.dynafios.productivmd.com/getproviderbynpi/". $npi,
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ]
            );
            $results = json_decode(stripslashes($request->getBody()->getContents()));   // don't add logs other wise data will get null
            return $results;
        }catch (\Exception $ex){
            return "";
        }
    }

    public function getCompensationSummary($providerId, $startDate, $endDate, $accrualInterval, $physician) {
        try{
            $request = $this->httpClient->get(self::$base_url . "compensationsummary?providerId=". $providerId . "&startDate=". $startDate . "&endDate=". $endDate . "&accrualInterval=" .  $accrualInterval,
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ]
            );

            $response = json_decode($request->getBody()->getContents());
            $results = ApiGraph::getCompensationSummary($response, $startDate, $endDate, $accrualInterval, $physician);

            return $results;
        }catch (\Exception $ex){
            return "";
        }
    }

    public function getProductivityCompensation($providerId, $year, $month, $physician) {
        try{
            $request = $this->httpClient->get(self::$base_url . "ttmproductivity?providerId=". $providerId . "&year=". $year . "&month=". $month,
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ]
            );
            $response = json_decode($request->getBody()->getContents());
            $results = ApiGraph::getProductivityCompensation($response, $year, $physician);

            return $results;
        }catch (\Exception $ex){
            return "";
        }
    }

    public function getTtmProductivity($providerId, $year, $month, $physician) {
        try{
            $request = $this->httpClient->get(self::$base_url . "ttmproductivity?providerId=". $providerId . "&year=". $year . "&month=". $month,
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ]
            );
            $response = json_decode($request->getBody()->getContents());
            $results = ApiGraph::getTtmProductivity($response, $physician);

            return $results;
        }catch (\Exception $ex){
            return "";
        }
    }

    public function getCompensationSummaryGuage($startDate, $endDate, $physician) {
        try{
            $results = ApiGraph::getCompensationSummaryGuage($startDate, $endDate, $physician);

            return $results;
        }catch (\Exception $ex){
            Log::error(array($ex));
            return "";
        }
    }
}
