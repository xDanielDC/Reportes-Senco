<?php

namespace App\Traits;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

trait PowerBITrait
{
    /**
     * @throws GuzzleException
     */
    protected function getUserAccessToken(): mixed
    {
        if (config('power-bi.username') === null && config('power-bi.password') === null){
	    return "";
	 }

        $client = new Client([
            'base_uri' => "https://login.windows.net/common/oauth2/token",
        ]);

        $response = $client->request('POST', "https://login.windows.net/common/oauth2/token", [
            'multipart' => [
                [
                    'name' => 'grant_type',
                    'contents' => config('power-bi.grant_type'),
                ],
                [
                    'name' => 'scope',
                    'contents' => 'openid'
                ],
                [
                    'name' => 'resource',
                    'contents' => config('power-bi.resource'),
                ],
                [
                    'name' => 'client_secret',
                    'contents' => config('power-bi.client_secret'),
                ],
                [
                    'name' => 'client_id',
                    'contents' => config('power-bi.client_id'),
                ],
                [
                    'name' => 'username',
                    'contents' => config('power-bi.username'),
                ],
                [
                    'name' => 'password',
                    'contents' => config('power-bi.password'),
                ],
            ],
        ]);

        return json_decode($response->getBody()->getContents())->access_token;
    }

    /**
     * @throws GuzzleException
     */
    protected function getReportsInGroup($group_id): array
    {
        $userAccessToken = $this->getUserAccessToken();

        $client = new Client([
            'base_uri' => 'https://api.powerbi.com/v1.0/myorg',
        ]);

        $headers = [
            'Authorization' => "Bearer $userAccessToken",
            'Content-type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $response = $client->request('GET', "https://api.powerbi.com/v1.0/myorg/groups/$group_id/reports", [
            'headers' => $headers,
        ]);

        return json_decode($response->getBody()->getContents())->value;
    }

    /**
     * @throws GuzzleException
     */
    protected function getReportAccessToken($userAccessToken, $report): object
    {
        try {
            $client = new Client([
                'base_uri' => 'https://api.powerbi.com',
            ]);

            $headers = [
                'Authorization' => "Bearer $userAccessToken",
                'Content-type' => 'application/json',
                'Accept' => 'application/json',
            ];

            $params = (object) [
                'accessLevel' => $report->access_level,
                'datasetId' => $report->dataset_id,
            ];

            $response = $client->request('POST', "https://api.powerbi.com/v1.0/myorg/groups/$report->group_id/reports/$report->report_id/GenerateToken", [
                'headers' => $headers,
                'json' => $params,
            ]);

            $resp = json_decode($response->getBody()->getContents());

            return (object) [
                'status' => 200,
                'tokenId' => $resp->tokenId,
                'token' => $resp->token,
                'expiration' => Carbon::parse($resp->expiration)->setTimezone('America/Bogota'),
            ];
        } catch (Exception $e) {
            return (object) [
                'status' => 500,
                'message' => $e->getMessage(),
            ];
        }
    }
}
