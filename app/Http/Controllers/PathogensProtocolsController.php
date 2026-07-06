<?php

namespace App\Http\Controllers;

use App\Models\Protocols;
use App\Services\ExperimentsService;
use Illuminate\Support\Facades\Validator;

class PathogensProtocolsController extends Controller
{
    protected $service;

    public function __construct(ExperimentsService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        return view('pathogens_protocols.create', $this->service->assign());
    }

    public function store()
    {

        $rules = [
            'protocol_ass' => 'nullable|string|max:100',
            'protocol_id' => 'nullable|integer|exists:protocols,id',
            'pathogen_ass' => 'required|array|min:1',
            'pathogen_ass.*' => 'required|integer|exists:pathogens,id',
        ];

        $validator = Validator::make(request()->all(), $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {

            $protocolId = request('protocol_id');
            $protocolName = request('protocol_ass');
            $protocol = $protocolId
                ? Protocols::find($protocolId)
                : Protocols::where('name', $protocolName)->first();

            if (! $protocol) {
                session()->flash('error', 'Protocol not found.');

                return back()->withInput();
            }

            $pathogenIds = collect((array) request('pathogen_ass'))
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();

            $protocol->pathogens()->syncWithoutDetaching($pathogenIds);

            // Flash success message to the session
            session()->flash('success', 'Protocol-Pathogen association registered successfully!');

            return back();
        } catch (\Exception $e) {
            // Handle any exceptions during registration
            session()->flash('error', 'An error occurred: '.$e->getMessage());

            return back()->withInput();
        }
    }

    public function detach()
    {
        $protocolName = request('protocol');
        $pathogenId = request('pathogen_id');

        if (! $protocolName || ! $pathogenId) {
            return response()->json(['message' => 'Invalid request.'], 400);
        }

        $protocol = Protocols::where('name', $protocolName)->first();
        if (! $protocol) {
            return response()->json(['message' => 'Protocol not found.'], 404);
        }

        try {
            $protocol->pathogens()->detach($pathogenId);

            return response()->json(['message' => 'Pathogen detached successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to detach pathogen: '.$e->getMessage()], 500);
        }
    }
}
