<?php

namespace App\Http\Controllers;

use App\Models\Fundings;
use App\Models\Projects;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FundingController extends Controller
{
    public function store(Request $request, Projects $project)
    {
        $validator = Validator::make($request->all(), [
            'source' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'reference' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $funding = Fundings::create([
                'source' => $request->source,
                'amount' => $request->amount,
                'currency' => $request->currency,
                'reference' => $request->reference,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);

            return back()->with('success', 'Funding details updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update funding details: '.$e->getMessage());
        }
    }

    public function update(Request $request, Projects $project)
    {
        $validator = Validator::make($request->all(), [
            'source' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'reference' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $funding = $project->funding;

            if (! $funding) {
                $funding = new Fundings(['projects_id' => $project->id]);
            }

            $funding->fill([
                'source' => $request->source,
                'amount' => $request->amount,
                'currency' => $request->currency,
                'reference' => $request->reference,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);

            $funding->save();

            return back()->with('success', 'Funding details updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update funding details: '.$e->getMessage());
        }
    }
}
