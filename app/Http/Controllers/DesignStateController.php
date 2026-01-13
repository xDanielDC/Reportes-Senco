<?php

namespace App\Http\Controllers;

use App\Models\DesignState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DesignStateController extends Controller
{
    /**
     * @return Response
     */
    public function index()
    {
        $states = DesignState::all();

        return Inertia::render('Design/State', [
            'states' => $states,
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        try {
            DesignState::create($request->except('id'));

            $states = DesignState::all();

            return response()->json($states, 200);
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
            $designState = DesignState::find($id);
            $designState->name = $request->name;
            $designState->update();

            $states = DesignState::all();

            return response()->json($states, 200);
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
            DesignState::destroy($id);

            $states = DesignState::all();

            return response()->json($states, 200);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
}
