<?php

namespace App\Http\Controllers;

use App\Models\DesignTask;
use Illuminate\Http\Request;

class DesignTaskController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DesignTask::create([
            'design_request_id' => $request->design_request_id,
            'description' => $request->description,
        ]);

        $design_tasks = DesignTask::where('design_request_id', '=', $request->design_request_id)->get();

        return response()->json($design_tasks, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $design_task = DesignTask::find($id);
        $design_task->description = $request->description;
        $design_task->save();

        $design_tasks = DesignTask::where('design_request_id', '=', $request->design_request_id)->get();

        return response()->json($design_tasks, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($design_request_id, $id)
    {
        DesignTask::destroy($id);

        $design_tasks = DesignTask::where('design_request_id', '=', $design_request_id)->get();

        return response()->json($design_tasks, 200);
    }
}
