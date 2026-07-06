<?php

namespace App\Http\Controllers;

use App\Models\Projects;
use Illuminate\Http\Request;

class ProjectSelectionController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'selected_project' => 'required',
        ]);

        if ($request->selected_project === 'guest') {
            // Clear any selected project to enter guest mode
            session()->forget('selected_project_id');

            return response()->json([
                'success' => true,
                'message' => '👁️ Guest mode activated! You can now explore public experiments and samples.',
            ]);
        }

        // Validate that the project exists
        $request->validate([
            'selected_project' => 'exists:projects,id',
        ]);

        $project = Projects::findOrFail($request->selected_project);
        session(['selected_project_id' => $project->id]);

        // Return a JSON response that will trigger our JavaScript
        return response()->json([
            'success' => true,
            'message' => "🚀 Project '{$project->code}' selected! Ready to explore your research data.",
        ]);
    }
}
