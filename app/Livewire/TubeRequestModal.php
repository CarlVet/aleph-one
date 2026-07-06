<?php

namespace App\Livewire;

use App\Models\TubeRequests;
use App\Models\Tubes;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TubeRequestModal extends Component
{
    public $showModal = false;

    public $tubeId;

    public $tube;

    public $targetProjectId;

    public $requestMessage = '';

    public $userProjects = [];

    public $sourceProject;

    public $principalInvestigator;

    protected $rules = [
        'targetProjectId' => 'required|exists:projects,id',
        'requestMessage' => 'nullable|string|max:500',
    ];

    protected $listeners = ['openTubeRequestModal' => 'openModal'];

    public function mount()
    {
        $this->loadUserProjects();
    }

    public function loadUserProjects()
    {
        $user = Auth::user();
        if ($user && $user->people) {
            $this->userProjects = $user->people->projects()->get();
        }
    }

    public function openModal($tubeId)
    {
        $this->tubeId = $tubeId;
        $this->tube = Tubes::with(['tubes_content', 'projects'])->find($tubeId);

        if ($this->tube) {
            $this->sourceProject = $this->tube->projects;

            // Get the principal investigator from the projects_people table
            $this->principalInvestigator = $this->sourceProject->people()
                ->wherePivot('role', 'Principal Investigator')
                ->first();
        }

        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['tubeId', 'tube', 'targetProjectId', 'requestMessage', 'sourceProject', 'principalInvestigator']);
    }

    public function submitRequest()
    {
        $this->validate();

        $user = Auth::user();
        if (! $user || ! $user->people) {
            session()->flash('error', 'User not found.');

            return;
        }

        // Check if there's already a pending request for this tube by this user
        $existingRequest = TubeRequests::where('tubes_id', $this->tubeId)
            ->where('requester_id', $user->people->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            session()->flash('error', 'You already have a pending request for this tube.');

            return;
        }

        // Get the principal investigator from the projects_people table
        $principalInvestigator = $this->sourceProject->people()
            ->wherePivot('role', 'Principal Investigator')
            ->first();

        if (! $principalInvestigator) {
            session()->flash('error', 'No Principal Investigator found for this project.');

            return;
        }

        try {
            TubeRequests::create([
                'tubes_id' => $this->tubeId,
                'requester_id' => $user->people->id,
                'source_project_id' => $this->sourceProject->id,
                'target_project_id' => $this->targetProjectId,
                'principal_investigator_id' => $principalInvestigator->id,
                'status' => 'pending',
                'request_message' => $this->requestMessage,
            ]);

            session()->flash('success', 'Tube request submitted successfully! The principal investigator will be notified.');
            $this->closeModal();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to submit request. Please try again.');
        }
    }

    public function render()
    {
        return view('livewire.tube-request-modal');
    }
}
