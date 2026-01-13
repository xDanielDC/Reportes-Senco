<?php

namespace App\Http\Controllers;

use App\Models\ReportFilter;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportFilterController extends Controller
{
    /**
     * @return Response
     */
    public function index()
    {
        $filters = ReportFilter::all();

        return Inertia::render('Report/Filter', [
            'filters' => $filters,
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        try {
            ReportFilter::create($request->all());

            $filters = ReportFilter::all();

            return response()->json($filters, 200);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            ReportFilter::find($id)->update($request->except('id'));

            $filters = ReportFilter::all();

            return response()->json($filters, 200);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try {
            ReportFilter::destroy($id);

            $filters = ReportFilter::all();

            return response()->json($filters, 200);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
}
