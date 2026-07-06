<?php

namespace App\Livewire;

use App\Models\AnimalSamples;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\Microplastics;
use App\Models\ParasiteSamples;
use App\Models\Pools;

class MicroplasticProfile extends PlainComponent
{
    public $microplastic;

    public string $code;

    public bool $canView = false;

    public bool $canEdit = false;

    public string $unauthorizedMessage = '';

    public function mount(string $code): void
    {
        $this->code = $code;
        $this->loadRecord();
    }

    private function loadRecord(): void
    {
        $projectId = $this->selectedProjectId();
        if (! $projectId) {
            $this->unauthorizedMessage = 'No project selected. Please select a project to view this microplastics profile.';

            return;
        }

        $record = Microplastics::query()
            ->with([
                'mps_types',
                'protocols.techniques',
                'laboratories.countries',
                'people',
                'projects',
                'microplastics_content',
                'tubes.tube_positions.boxes',
                'experiments.protocols',
                'experiments.pathogens',
                'experiments.people',
                'experiments.laboratories',
            ])
            ->where('projects_id', $projectId)
            ->where('code', $this->code)
            ->first();

        if (! $record) {
            $this->unauthorizedMessage = 'Microplastics record not found in the selected project.';

            return;
        }

        $this->microplastic = $record;
        $this->canView = true;
        $this->canEdit = $this->userCanMutateOwnedRecord((int) ($record->people_id ?? 0), 'microplastics');
    }

    public function deleteRecord()
    {
        if (! $this->canEdit || ! $this->microplastic) {
            session()->flash('error', 'You do not have permission to delete this record.');

            return;
        }

        $this->microplastic->delete();
        session()->flash('message', 'Microplastics record deleted successfully!');

        return redirect('/samples/microplastics/list');
    }

    public function sourceProfileUrl(): ?string
    {
        $sourceCode = $this->microplastic?->microplastics_content?->code;
        if (! $sourceCode) {
            return null;
        }

        return match ($this->microplastic?->microplastics_content_type) {
            HumanSamples::class => '/samples/humans/'.$sourceCode,
            AnimalSamples::class => '/samples/animals/'.$sourceCode,
            EnvironmentSamples::class => '/samples/environment/'.$sourceCode,
            ParasiteSamples::class => '/samples/parasites/'.$sourceCode,
            Pools::class => '/samples/pools/'.$sourceCode,
            default => null,
        };
    }

    public function render()
    {
        return view('livewire.microplastic-profile', [
            'microplastic' => $this->microplastic,
            'canView' => $this->canView,
            'canEdit' => $this->canEdit,
            'unauthorizedMessage' => $this->unauthorizedMessage,
            'sourceProfileUrl' => $this->sourceProfileUrl(),
        ]);
    }
}
