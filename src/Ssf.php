<?php

namespace CogentHealth\Ssf;

use App\Utils\Options;
use CogentHealth\Ssf\Claim\Claim;
use CogentHealth\Ssf\Services\AuthService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Ssf implements SsfInterface
{
    protected static $message;
    protected static $success = true;
    protected static $httpStatusCode;
    protected static $responseBody;

    private static $username;
    private static $password;
    private static $ssfClient;
    private static $clientOptions;
    private static $hostName;

    /**
     *
     * @var Singleton
     */
    private static $instance;

    public function __construct()
    {
        self::$username = AuthService::getUsername();
        self::$password = AuthService::getPassword();
        self::$hostName =env("SSF_API_URL")?env("SSF_API_URL"):Options::get('ssf_settings')['ssf_url']??'';

        self::$clientOptions = [
            'verify' => false,
            'auth' => [
                self::$username,
                self::$password
            ],
            'headers' => [
                'remote-user' => Options::get('ssf_settings')['ssf_remote_user']??''
            ],
            'base_uri' =>self::$hostName
        ];
        self::$ssfClient = new Client(self::$clientOptions);
    }

    public static function init()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function getPatientDetailById(int $patientId)
    {
        try {
            self::init();

            $response = self::$ssfClient->get(
                config('ssf_api_url.patient') . "?identifier=" . $patientId
            );

            self::$httpStatusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);
        } catch (\RequestException $e) {
            if ($e->hasResponse()) {
                $responseBody = json_decode($e->getResponse()->getBody()->getContents());
            }
            self::$success = false;
            self::$httpStatusCode = 500;
        } catch (\Exception $e) {
            $responseBody = '';
            self::$success = false;
            self::$message = $e->getMessage();
            self::$httpStatusCode = 500;
        }

        self::$responseBody = $responseBody;
        return self::apiResponse();
    }

    public static function eligibilityRequest(int $patientId)
    {
        try {
            self::init();
            $request_body = [
                "resourceType" =>  "CoverageEligibilityRequest",
                "patient" =>  [
                    "reference" =>  "Patient/" . $patientId
                ]
            ];

            $response = self::$ssfClient->post(
                config('ssf_api_url.eligibility_request'),
                [
                    'json' => $request_body
                ]
            );
            $responseBody = json_decode($response->getBody()->getContents());
            self::$httpStatusCode = 200;
            self::$message = "Request fetched successfully.";
        } catch (\RequestException $e) {
            if ($e->hasResponse()) {
                $responseBody = json_decode($e->getResponse()->getBody()->getContents());
            }

            self::$httpStatusCode = 500;
            self::$success = false;
        }

        self::$responseBody = $responseBody;
        return self::apiResponse();
    }

    public static function claimSubmission(Claim $claim)
    {
        try {
            self::init();

            $request_body = [
                "resourceType" => "Claim",
                "identifier" => [
                    [
                        "type" => [
                            "coding" => [
                                [
                                    "code" => "ACSN",
                                    "system" => "https://hl7.org/fhir/valueset-identifier-type.html"
                                ]
                            ]
                        ],
                        "use" => "usual",
                        "value" => "FD3BCF6F-8D16-4425-8628-5A7B79E50180"
                    ]
                ],
                "extension" => [
                    [
                        "url" => "schemeType",
                        "valueString" => "2"
                    ]
                ],
                "type" => [
                    "text" => "O"
                ],
                "patient" => [
                    "reference" => "Patient/" . $claim->patient_id
                ],
                "billablePeriod" => [
                    "start" => $claim->billable_period_start,
                    "end" => $claim->billable_period_end
                ],
                "created" => $claim->created_at,
                "enterer" => [
                    "reference" => "Practitioner/CE82B1BD-495F-42D2-B2D6-CA44F5D2B029"
                ],
                "facility" => [
                    "reference" => "Location/954EC1AC-7620-4824-9663-14189F8B9563"
                ],
                "diagnosis" => [
                    [
                        "diagnosisCodeableConcept" => [
                            "coding" => [
                                [
                                    "code" => "A01"
                                ]
                            ]
                        ],
                        "sequence" => 1,
                        "type" => [
                            [
                                "text" => "icd_0"
                            ]
                        ]
                    ]
                ],
                "item" => $claim->items,
                "total" => [
                    "value" => number_format($claim->total_amount, 2, '.', '')
                ]
            ];

            $response = self::$ssfClient->post(
                config('ssf_api_url.claim'),
                [
                    'json' => $request_body
                ]
            );
            $responseBody = json_decode($response->getBody()->getContents());
            self::$httpStatusCode = 200;
        } catch (RequestException $e) {
            // $responseBody = Psr7\Message::toString($e->getRequest());
            if ($e->hasResponse()) {
                $responseBody = json_decode($e->getResponse()->getBody()->getContents());
            }

            self::$httpStatusCode = 500;
            self::$success = false;
        }

        self::$responseBody = $responseBody;

        return self::apiResponse();
    }

    public static function apiResponse()
    {
        return response()->json([
            'data' => self::$responseBody,
            'message' => self::$message,
            'success' => self::$success
        ], self::$httpStatusCode);
    }
}
