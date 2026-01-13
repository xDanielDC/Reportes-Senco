<?php

namespace App\Http\Controllers;

use App\Models\DesignPriority;
use App\Models\DesignRequest;
use App\Models\DesignState;
use App\Models\DesignTimeState;
use App\Models\PSLCustomer;
use App\Models\Seller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DesignRequestController extends Controller
{
    /**
     * @return Response
     */
    public function index()
    {
        $requests = DesignRequest::all();
        $priorities = DesignPriority::all();
        $time_states = DesignTimeState::all();
        $states = DesignState::all();
        $designers = User::where('type', '=', 'designer')->get();
        $sellers = Seller::all();

        $customers = DB::table('GBapp.dbo.users')
            ->where('type', '=', 'customer')
            ->select('document', 'name', DB::raw("'local' as type"));

        $customers = DB::connection('ssf')
            ->table('un_tercegener')
            ->where('eobcodigo ', '=', 'AC')
            ->where('tgeesclie', '=', 'S')
            ->select(DB::raw('RTRIM(LTRIM(tgecodigo)) as document'), DB::raw('RTRIM(LTRIM(tgenombcomp)) as name'), DB::raw("'external' as type"))
            ->distinct()
            ->union($customers)
            ->orderBy('name')
            ->get();

        $customers = $customers->groupBy('type');

        return Inertia::render('Design/Request', [
            'requests' => $requests,
            'priorities' => $priorities,
            'time_states' => $time_states,
            'states' => $states,
            'designers' => $designers,
            'customers' => $customers,
            'sellers' => $sellers,
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        try {
            DesignRequest::create([
                'priority_id' => $request->priority_id,
                'designer_id' => $request->designer_id,
                'seller_document' => $request->seller_document,
                'customer_id' => $request->customer_id,
                'comments' => $request->comments,
                'reception_date' => Carbon::parse($request->reception_date),
                'tentative_date' => Carbon::parse($request->tentative_date),
                'real_date' => Carbon::parse($request->real_date),
                'delivery_date' => Carbon::parse($request->delivery_date),
                'customer_approved_date' => Carbon::parse($request->customer_approved_date),
                'estimated_arrival_sherpa_date' => Carbon::parse($request->estimated_arrival_sherpa_date),
                'time_state_id' => $request->time_state_id,
                'state_id' => $request->state_id,
                'observations' => $request->observations,
            ]);

            $requests = DesignRequest::all();

            return response()->json($requests, 200);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $designRequest = DesignRequest::find($id);
            $designRequest->name = $request->name;
            $designRequest->update();

            $requests = DesignRequest::all();

            return response()->json($requests, 200);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try {
            DesignRequest::destroy($id);

            $requests = DesignRequest::all();

            return response()->json($requests, 200);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * @return Response
     */
    public function show($id)
    {
        $request = DesignRequest::find($id);
        $priorities = DesignPriority::all();
        $time_states = DesignTimeState::all();
        $states = DesignState::all();
        $designers = User::where('type', '=', 'designer')->get();
        $sellers = Seller::all();

        $customers = DB::table('GBapp.dbo.users')
            ->where('type', '=', 'customer')
            ->select('document', 'name', DB::raw("'local' as type"));

        $customers = DB::connection('ssf')
            ->table('un_tercegener')
            ->where('eobcodigo ', '=', 'AC')
            ->where('tgeesclie', '=', 'S')
            ->select(DB::raw('RTRIM(LTRIM(tgecodigo)) as document'), DB::raw('RTRIM(LTRIM(tgenombcomp)) as name'), DB::raw("'external' as type"))
            ->distinct()
            ->union($customers)
            ->orderBy('name')
            ->get();

        $customers = $customers->groupBy('type');


        return Inertia::render('Design/Show', [
            'design_request' => $request,
            'priorities' => $priorities,
            'time_states' => $time_states,
            'states' => $states,
            'designers' => $designers,
            'customers' => $customers,
            'sellers' => $sellers,
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function update_state(Request $request)
    {
        $design_request = DesignRequest::find($request->id);
        $design_request[$request->property] = $request->state_id;
        $design_request->save();

        $stored = DesignRequest::find($request->id);

        return response()->json($stored, 200);
    }
}
