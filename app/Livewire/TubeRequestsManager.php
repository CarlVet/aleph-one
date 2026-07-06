<?php

namespace App\Livewire;

use App\Models\TubeRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class TubeRequestsManager extends PlainComponent
{
    use WithPagination;

    public $responseMessage = '';

    public $selectedRequestId;

    public $activeTab = 'incoming'; // 'incoming' or 'outgoing'

    protected $rules = [
        'responseMessage' => 'nullable|string|max:500',
    ];

    public function mount()
    {
        // Set default tab based on user role
        $user = Auth::user();
        if ($user && $user->people) {
            // Check if user is a PI of any project
            $isPI = $user->people->projects()
                ->wherePivot('role', 'Principal Investigator')
                ->exists();

            $this->activeTab = $isPI ? 'incoming' : 'outgoing';
        }
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function approveRequest($requestId)
    {
        $this->selectedRequestId = $requestId;
        $this->validate();

        $request = TubeRequests::with(['tube', 'targetProject', 'sourceProject.people'])->find($requestId);

        if (! $request) {
            session()->flash('error', 'Request not found.');

            return;
        }

        $user = Auth::user();
        if (! $user || ! $user->people) {
            session()->flash('error', 'You are not authorized to approve this request.');

            return;
        }

        // Get the PI for the source project
        $pi = $request->sourceProject->people()->wherePivot('role', 'Principal Investigator')->first();
        if (! $pi || $user->people->id !== $pi->id) {
            session()->flash('error', 'You are not authorized to approve this request.');

            return;
        }

        try {
            $request->update([
                'status' => 'approved',
                'response_message' => $this->responseMessage,
                'responded_at' => now(),
            ]);

            // Update the tube's project assignment
            $tube = $request->tube;
            $tube->projects_id = $request->target_project_id;
            $tube->is_private = 1;
            $tube->save();

            session()->flash('success', 'Request approved and tube transferred successfully.');
            $this->reset(['responseMessage', 'selectedRequestId']);

            // Redirect to refresh the page and show updated data
            return redirect()->route('tube-requests');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to approve request: '.$e->getMessage());
        }
    }

    public function rejectRequest($requestId)
    {
        $this->selectedRequestId = $requestId;
        $this->validate();

        $request = TubeRequests::with(['sourceProject.people'])->find($requestId);

        if (! $request) {
            session()->flash('error', 'Request not found.');

            return;
        }

        $user = Auth::user();
        if (! $user || ! $user->people) {
            session()->flash('error', 'You are not authorized to reject this request.');

            return;
        }

        // Get the PI for the source project
        $pi = $request->sourceProject->people()->wherePivot('role', 'Principal Investigator')->first();
        if (! $pi || $user->people->id !== $pi->id) {
            session()->flash('error', 'You are not authorized to reject this request.');

            return;
        }

        try {
            $request->update([
                'status' => 'rejected',
                'response_message' => $this->responseMessage,
                'responded_at' => now(),
            ]);

            session()->flash('success', 'Request rejected successfully.');
            $this->reset(['responseMessage', 'selectedRequestId']);

            // Redirect to refresh the page and show updated data
            return redirect()->route('tube-requests');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to reject request. Please try again.');
        }
    }

    public function render()
    {
        $user = Auth::user();
        $incomingRequests = collect();
        $outgoingRequests = collect();
        $isPI = false;

        if ($user && $user->people) {
            // Check if user is a PI of any project
            $isPI = $user->people->projects()->wherePivot('role', 'Principal Investigator')->exists();

            if ($isPI) {
                $allRequests = TubeRequests::with([
                    'tube',
                    'requester',
                    'sourceProject.people',
                    'targetProject',
                ])->get()->filter(function ($request) use ($user) {
                    $pi = $request->sourceProject->people()->wherePivot('role', 'Principal Investigator')->first();

                    return $pi && $user->people->id === $pi->id;
                });
                // Manual pagination
                $page = request()->get('page', 1);
                $perPage = 10;
                $incomingRequests = new LengthAwarePaginator(
                    $allRequests->forPage($page, $perPage)->values(),
                    $allRequests->count(),
                    $perPage,
                    $page,
                    ['path' => request()->url(), 'query' => request()->query()]
                );
            }

            $outgoingRequests = TubeRequests::with([
                'tube',
                'sourceProject.people',
                'targetProject',
            ])->where('requester_id', $user->people->id)
                ->orderBy('created_at', 'desc')
                ->paginate(10, pageName: 'outgoing-page');
        }

        return view('livewire.tube-requests-manager', [
            'incomingRequests' => $isPI ? $incomingRequests : collect(),
            'outgoingRequests' => $outgoingRequests,
            'isPI' => $isPI,
        ]);
    }
}
