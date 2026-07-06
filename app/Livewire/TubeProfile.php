<?php

namespace App\Livewire;

use App\Models\TubePositions;
use App\Models\Tubes;
use Illuminate\Validation\ValidationException;

class TubeProfile extends PlainComponent
{
    public $tube;

    public $code;

    public $canView = false;

    public $canEdit = true;

    public $unauthorizedMessage = '';

    public $editingValues = [
        'alias_code' => '',
        'tube_type' => '',
        'preservant' => '',
        'purpose' => '',
        'date_processed' => '',
    ];

    public function mount($code)
    {
        $this->code = $code;
        $this->checkAuthorization();

        if (! $this->canView) {
            return;
        }

        $this->loadTube();
    }

    private function checkAuthorization()
    {
        // Get the selected project ID
        $selectedProjectId = $this->selectedProjectId();

        if (! $selectedProjectId) {
            $this->canView = false;
            $this->unauthorizedMessage = 'No project selected. Please select a project to view this tube profile.';

            return;
        }

        // Load the tube to check if it belongs to the selected project
        $tube = Tubes::where('code', $this->code)->first();

        if (! $tube) {
            $this->canView = false;
            $this->unauthorizedMessage = 'Tube not found.';

            return;
        }

        // Check if the tube belongs to the selected project
        if ($tube->projects_id != $selectedProjectId) {
            $this->canView = false;
            $this->unauthorizedMessage = 'You are not authorized to view the profile of this tube because it does not belong to your selected project.';

            return;
        }

        $this->canEdit = $this->userCanMutateOwnedRecord((int) ($tube->people_id ?? 0), 'tube_positions');
        $this->canView = true;
    }

    private function loadTube()
    {
        $this->tube = Tubes::with([
            'tubes_content',
            'tube_positions',
            'tube_positions.boxes',
            'tube_positions.people',
            'tube_positions.boxes.box_positions',
            'tube_positions.boxes.box_positions.locations',
            'tube_positions.boxes.box_positions.locations.laboratories',
        ])->where('code', $this->code)->firstOrFail();
    }

    // Inline editing methods (click-to-edit)
    public function startEdit($field)
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to edit this tube.');

            return;
        }

        $this->loadTube();
        if ($field === 'date_processed') {
            $this->editingValues[$field] = $this->tube->date_processed?->format('Y-m-d') ?? '';
        } else {
            $this->editingValues[$field] = (string) ($this->tube->$field ?? '');
        }
        $this->dispatch('start-edit', field: $field);
    }

    public function saveEdit($field)
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to edit this tube.');

            return;
        }

        try {
            $this->validate([
                'editingValues.alias_code' => 'nullable|string|max:255',
                'editingValues.tube_type' => 'nullable|string|max:255',
                'editingValues.preservant' => 'nullable|string|max:255',
                'editingValues.purpose' => 'nullable|string|max:255',
                'editingValues.date_processed' => 'nullable|date',
            ]);

            $this->tube->update([$field => $this->editingValues[$field] ?: null]);

            $this->loadTube();

            $this->editingValues[$field] = '';
            $this->dispatch('save-edit', field: $field);
            $this->dispatch('show-success', message: ucfirst(str_replace('_', ' ', $field)).' updated successfully!');
            session()->flash('message', ucfirst(str_replace('_', ' ', $field)).' updated successfully!');
        } catch (ValidationException $e) {
            $messages = collect($e->errors())->flatten()->toArray();
            $errorMessage = implode("\n", $messages);
            $this->dispatch('show-error', message: $errorMessage);
            session()->flash('error', $errorMessage);
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: 'Failed to update '.str_replace('_', ' ', $field).': '.$e->getMessage());
            session()->flash('error', 'Failed to update '.str_replace('_', ' ', $field).': '.$e->getMessage());
        }
    }

    public function cancelEdit($field)
    {
        $this->loadTube();
        $this->editingValues[$field] = '';
        $this->dispatch('cancel-edit', field: $field);
    }

    public function deleteTube()
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to delete this tube.');

            return;
        }

        try {
            $tube = Tubes::where('code', $this->code)->firstOrFail();
            TubePositions::where('tubes_id', $tube->id)->delete();
            $tube->delete();

            session()->flash('message', 'Tube deleted successfully!');

            return redirect('/bank/tubes/list');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete tube: '.$e->getMessage());
        }
    }

    public function render()
    {
        if (! $this->canView) {
            return view('livewire.tube-profile', [
                'tube' => null,
                'canView' => false,
                'canEdit' => false,
                'unauthorizedMessage' => $this->unauthorizedMessage,
            ]);
        }

        return view('livewire.tube-profile', [
            'tube' => $this->tube,
            'canView' => $this->canView,
            'canEdit' => $this->canEdit,
            'unauthorizedMessage' => $this->unauthorizedMessage,
        ]);
    }
}
