<?php

namespace App\Http\Controllers;

use App\Traits\PowerBITrait;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use PowerBITrait;

    /**
     * @return Response
     *
     * @throws GuzzleException
     */
    public function index()
    {
        $userAccessToken = $this->getUserAccessToken();

        $reports = auth()->user()->reports()->wherePivot('show', true)
            ->get()->map(function ($row) use ($userAccessToken) {
                if ($row->token === null || Carbon::now() >= $row->expiration_date) {
                    $token = $this->getReportAccessToken($userAccessToken, $row);
                    $row->token = $token->token;
                    $row->expiration_date = $token->expiration;
                    $row->save();
                }
                $row->userAccessToken = $userAccessToken;
                $row->embedUrl = "https://app.powerbi.com/reportEmbed?reportId=$row->reportId&groupId=$row->groupId";

                return $row;
            });

        return Inertia::render('Dashboard', [
            'reports' => $reports,
        ]);
    }
}
