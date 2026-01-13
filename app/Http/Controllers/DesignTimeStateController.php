<?php

namespace App\Http\Controllers;

use App\Models\DesignTimeState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DesignTimeStateController extends Controller
{
    /**
     * @return Response
     */
    public function index()
    {
        $time_states = DesignTimeState::all();

        return Inertia::render('Design/TimeState', [
            'time_states' => $time_states,
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        try {
            DesignTimeState::create($request->except('id'));

            $time_states = DesignTimeState::all();

            return response()->json($time_states, 200);
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
            $designState = DesignTimeState::find($id);
            $designState->name = $request->name;
            $designState->update();

            $time_states = DesignTimeState::all();

            return response()->json($time_states, 200);
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
            DesignTimeState::destroy($id);

            $time_states = DesignTimeState::all();

            return response()->json($time_states, 200);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
}
