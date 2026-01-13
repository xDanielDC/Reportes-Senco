<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Traits\PowerBITrait;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;

class ImportReportController extends Controller
{
    use PowerBITrait;

    /**
     * @return Response
     */
    public function index()
    {
        $current_reports = DB::table('reports')
            ->pluck('report_id')
            ->toArray();

        return Inertia::render('Report/Import', [
            'current_reports' => $current_reports,
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function get_reports(Request $request)
    {
        try {
            $result = $this->getReportsInGroup($request->group_id);

            return response()->json($result);
        } catch (Exception|GuzzleException $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            foreach ($request->selected as $item) {
                Report::create([
                    'name' => $item['name'],
                    'group_id' => $item['datasetWorkspaceId'],
                    'report_id' => $item['id'],
                    'access_level' => 'View',
                    'dataset_id' => $item['datasetId'],
                    'user_id' => Auth::id(),
                ]);
            }

            DB::commit();

            return response()->json('success', 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json($e->getMessage(), 500);
        }
    }
}
