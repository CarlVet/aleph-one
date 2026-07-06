<?php

namespace App\Livewire;

use App\Livewire\Concerns\ExportsTable;
use App\Models\AnimalSamples;
use App\Models\Boxes;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use App\Models\Projects;
use App\Models\TubePositions;
use App\Models\Tubes;
use Illuminate\Support\Facades\Auth;

class BoxContents extends PlainComponent
{
    use ExportsTable;

    public $box;

    public $boxId;

    public $tubePositions = [];

    public $nRows;

    public $nColumns;

    public $canView = false;

    public $canEdit = true;

    public $unauthorizedMessage = '';

    public string $tubeCodeDisplay = 'tube';

    // Modal and editing properties
    public $showTubeModal = false;

    public $selectedPosition = '';

    public $selectedTubeType = '';

    public $selectedTubeId = '';

    public $selectedTubeCode = '';

    public $availableTubes = [];

    public $currentTube = null;

    public $tubeToDeleteId = null;

    public function mount(string $boxId): void
    {
        $resolvedBoxId = $this->resolveBoxId($boxId);

        if ($resolvedBoxId === null) {
            $this->canView = false;
            $this->canEdit = false;
            $this->unauthorizedMessage = 'Box not found.';

            return;
        }

        $this->boxId = $resolvedBoxId;
        $this->checkAuthorization();

        if (! $this->canView) {
            return;
        }

        $this->loadBox();
    }

    private function resolveBoxId(string $boxId): ?int
    {
        $value = trim($boxId);

        if ($value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            return (int) $value;
        }

        return Boxes::query()->where('code', $value)->value('id');
    }

    private function checkAuthorization()
    {
        // Get the selected project ID
        $selectedProjectId = $this->selectedProjectId();

        if (! $selectedProjectId) {
            $this->canView = false;
            $this->unauthorizedMessage = 'No project selected. Please select a project to view this box contents.';

            return;
        }

        // Load the box to check if it belongs to the selected project
        $box = Boxes::find($this->boxId);

        if (! $box) {
            $this->canView = false;
            $this->unauthorizedMessage = 'Box not found.';

            return;
        }

        // Check if the box belongs to the selected project
        if ($box->projects_id != $selectedProjectId) {
            $this->canView = false;
            $this->unauthorizedMessage = 'You are not authorized to view the contents of this box because it does not belong to your selected project.';

            return;
        }

        // Check user permissions for the project
        $this->canEdit = $this->userCanEditSelectedProject();

        $this->canView = true;
    }

    private function loadBox()
    {
        $this->box = Boxes::with([
            'latest_box_position',
            'latest_box_position.locations',
            'latest_box_position.locations.laboratories',
            'latest_box_position.locations.laboratories.countries',
            'latest_box_position.people',
        ])->findOrFail($this->boxId);
        $this->nRows = $this->box->n_rows;
        $this->nColumns = $this->box->n_columns;
        $this->loadTubePositions();
    }

    protected function loadTubePositions()
    {
        // Get the LATEST position for each tube across ALL boxes
        $globalLatestPositions = TubePositions::select('tubes_id', 'boxes_id', 'position_x', 'position_y', 'date_moved')
            ->orderBy('date_moved', 'desc')
            ->get()
            ->groupBy('tubes_id')
            ->map(function ($positions) {
                return $positions->first(); // Get the most recent position for each tube globally
            });

        // Only show tubes whose LATEST position is in THIS box
        $tubesInThisBox = $globalLatestPositions->filter(function ($position) {
            return $position->boxes_id == $this->boxId;
        });

        // Load tube details for tubes that belong in this box
        $tubeIds = $tubesInThisBox->pluck('tubes_id')->toArray();
        $tubes = Tubes::whereIn('id', $tubeIds)->get()->keyBy('id');

        $this->tubePositions = [];
        foreach ($tubesInThisBox as $position) {
            $tube = $tubes->get($position->tubes_id);
            if ($tube) {
                $tubeType = $this->getTubeTypeFromContentType($tube->tubes_content_type);
                $this->tubePositions["{$position->position_x},{$position->position_y}"] = [
                    'code' => $tube->code,
                    'alias_code' => $tube->alias_code,
                    'tube_id' => $tube->id,
                    'type' => $tubeType,
                    'color' => $this->getTubeTypeColor($tubeType),
                ];
            }
        }
    }

    public function selectPosition($position)
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to edit tube positions in this box.');

            return;
        }

        $this->selectedPosition = $position;
        $this->selectedTubeType = '';
        $this->selectedTubeId = '';
        $this->selectedTubeCode = '';
        $this->availableTubes = [];

        // Check if there's already a tube at this position (using global latest position logic)
        $position = explode(',', $position);
        $x = $position[0];
        $y = $position[1];

        // Get the LATEST position for each tube across ALL boxes
        $globalLatestPositions = TubePositions::select('tubes_id', 'boxes_id', 'position_x', 'position_y', 'date_moved')
            ->orderBy('date_moved', 'desc')
            ->get()
            ->groupBy('tubes_id')
            ->map(function ($positions) {
                return $positions->first();
            });

        // Check if any tube has its latest position at this spot in this box
        $existingPosition = $globalLatestPositions->filter(function ($pos) use ($x, $y) {
            return $pos->boxes_id == $this->boxId &&
                   $pos->position_x == $x &&
                   $pos->position_y == $y;
        })->first();

        if ($existingPosition) {
            // Get the tube details
            $tube = Tubes::find($existingPosition->tubes_id);
            $this->currentTube = $tube;
        } else {
            $this->currentTube = null;
        }

        $this->showTubeModal = true;
    }

    public function updatedSelectedTubeType()
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to edit tube positions in this box.');

            return;
        }

        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);
        $project_code = $project->code;

        $this->selectedTubeId = '';
        $this->selectedTubeCode = '';
        $this->availableTubes = [];

        if (! $this->selectedTubeType) {
            return;
        }

        // Load all available tubes based on selected type (including those already positioned elsewhere)
        switch ($this->selectedTubeType) {
            case 'human':
                $this->availableTubes = Tubes::where('tubes_content_type', HumanSamples::class)
                    ->where('projects_id', $projectId)
                    ->get();
                break;
            case 'animal':
                $this->availableTubes = Tubes::where('tubes_content_type', AnimalSamples::class)
                    ->where('projects_id', $projectId)
                    ->get();
                break;
            case 'environment':
                $this->availableTubes = Tubes::where('tubes_content_type', EnvironmentSamples::class)
                    ->where('projects_id', $projectId)
                    ->get();
                break;
            case 'parasite':
                $this->availableTubes = Tubes::where('tubes_content_type', ParasiteSamples::class)
                    ->where('projects_id', $projectId)
                    ->get();
                break;
            case 'nucleic':
                $this->availableTubes = Tubes::where('tubes_content_type', NucleicAcids::class)
                    ->where('projects_id', $projectId)
                    ->get();
                break;
            case 'culture':
                $this->availableTubes = Tubes::where('tubes_content_type', Cultures::class)
                    ->where('projects_id', $projectId)
                    ->get();
                break;
            case 'pool':
                $this->availableTubes = Tubes::where('tubes_content_type', Pools::class)
                    ->where('projects_id', $projectId)
                    ->get();
                break;
        }
    }

    public function updatedSelectedTubeCode()
    {
        if (! $this->selectedTubeCode || ! $this->selectedTubeType || ! $this->availableTubes) {
            $this->selectedTubeId = '';

            return;
        }

        $tube = $this->resolveSelectedTubeFromAvailable();

        if ($tube) {
            $this->selectedTubeId = $tube->id;
        } else {
            $this->selectedTubeId = '';
        }
    }

    public function updateTubePosition()
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to edit tube positions in this box.');

            return;
        }

        if (! $this->selectedTubeCode || ! $this->selectedPosition) {
            return;
        }

        $tube = $this->resolveSelectedTubeFromAvailable();

        if (! $tube) {
            $this->dispatch('show-message', ['type' => 'error', 'message' => 'Invalid tube code selected.']);

            return;
        }

        $this->selectedTubeId = $tube->id;

        $position = explode(',', $this->selectedPosition);
        $x = $position[0];
        $y = $position[1];

        // Check if position is within box bounds
        if ($x > $this->nColumns || $y > $this->nRows) {
            $this->dispatch('show-message', ['type' => 'error', 'message' => 'Position is outside box bounds.']);

            return;
        }

        // Check if another tube is already at this position (using global latest position logic)
        $globalLatestPositions = TubePositions::select('tubes_id', 'boxes_id', 'position_x', 'position_y', 'date_moved')
            ->orderBy('date_moved', 'desc')
            ->get()
            ->groupBy('tubes_id')
            ->map(function ($positions) {
                return $positions->first();
            });

        // Check if any tube (other than the one we're moving) has its latest position at this spot
        $conflictingTube = $globalLatestPositions->filter(function ($position) use ($x, $y) {
            return $position->boxes_id == $this->boxId &&
                   $position->position_x == $x &&
                   $position->position_y == $y &&
                   $position->tubes_id != $this->selectedTubeId;
        })->first();

        if ($conflictingTube) {
            $this->dispatch('show-message', ['type' => 'error', 'message' => 'Another tube is already at this position.']);

            return;
        }

        try {
            // Create a new tube position record (don't delete previous ones to maintain history)
            TubePositions::create([
                'boxes_id' => $this->boxId,
                'position_x' => $x,
                'position_y' => $y,
                'tubes_id' => $this->selectedTubeId,
                'date_moved' => now(),
                'people_id' => Auth::user()->people->id,
                'reason' => 'Manual position update',
            ]);

            // Reload tube positions
            $this->loadTubePositions();

            $this->dispatch('show-message', ['type' => 'success', 'message' => 'Tube position updated successfully.']);
            $this->closeTubeModal();

        } catch (\Exception $e) {
            $this->dispatch('show-message', ['type' => 'error', 'message' => 'Error updating tube position: '.$e->getMessage()]);
        }
    }

    private function resolveSelectedTubeFromAvailable(): ?Tubes
    {
        $selected = trim((string) $this->selectedTubeCode);
        if ($selected === '') {
            return null;
        }

        $isAliasMode = $this->tubeCodeDisplay === 'alias';

        return collect($this->availableTubes)->first(function ($tube) use ($selected, $isAliasMode) {
            if ($isAliasMode) {
                return ($tube->alias_code && $tube->alias_code === $selected) || $tube->code === $selected;
            }

            return $tube->code === $selected;
        });
    }

    public function removeTubeFromPosition()
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to remove tube positions in this box.');

            return;
        }

        if (! $this->selectedPosition) {
            return;
        }

        $position = explode(',', $this->selectedPosition);
        $x = $position[0];
        $y = $position[1];

        try {
            // Remove the tube from this position
            TubePositions::where('boxes_id', $this->boxId)
                ->where('position_x', $x)
                ->where('position_y', $y)
                ->delete();

            // Reload tube positions
            $this->loadTubePositions();

            $this->dispatch('show-message', ['type' => 'success', 'message' => 'Tube removed from position successfully.']);
            $this->closeTubeModal();

        } catch (\Exception $e) {
            $this->dispatch('show-message', ['type' => 'error', 'message' => 'Error removing tube: '.$e->getMessage()]);
        }
    }

    public function deleteTube()
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to delete tubes in this box.');

            return;
        }

        if (! $this->currentTube) {
            return;
        }

        if ($this->currentTube->is_private === false) {
            $this->dispatch('show-message', ['type' => 'error', 'message' => 'This tube is public and cannot be deleted.']);

            return;
        }

        try {
            // Delete the tube and all its positions
            TubePositions::where('tubes_id', $this->currentTube->id)->delete();
            Tubes::where('id', $this->currentTube->id)->delete();

            // Reload tube positions
            $this->loadTubePositions();

            $this->dispatch('show-message', ['type' => 'success', 'message' => 'Tube deleted successfully.']);
            $this->closeTubeModal();

        } catch (\Exception $e) {
            $this->dispatch('show-message', ['type' => 'error', 'message' => 'Error deleting tube: '.$e->getMessage()]);
        }
    }

    public function closeTubeModal()
    {
        $this->showTubeModal = false;
        $this->selectedPosition = '';
        $this->selectedTubeType = '';
        $this->selectedTubeId = '';
        $this->selectedTubeCode = '';
        $this->availableTubes = [];
        $this->currentTube = null;
    }

    public function export(string $format = 'csv')
    {
        if (! $this->canView) {
            session()->flash('error', 'You do not have permission to export data from this box.');

            return;
        }

        // Write header row with column numbers
        $headers = ['Position'];
        for ($x = 1; $x <= $this->nColumns; $x++) {
            $headers[] = "Column {$x}";
        }

        // Write data rows
        $rows = [];
        for ($y = 1; $y <= $this->nRows; $y++) {
            $row = ["Row {$y}"];
            for ($x = 1; $x <= $this->nColumns; $x++) {
                $key = "{$x},{$y}";
                $tubeData = $this->tubePositions[$key] ?? null;
                if (! $tubeData) {
                    $row[] = '-';

                    continue;
                }

                if ($this->tubeCodeDisplay === 'alias') {
                    $row[] = filled($tubeData['alias_code'] ?? null)
                        ? (string) $tubeData['alias_code']
                        : ((string) ($tubeData['code'] ?? '-') ?: '-');
                } else {
                    $row[] = isset($tubeData['code']) ? $tubeData['code'] : '-';
                }
            }
            $rows[] = $row;
        }

        return $this->exportTable("box_{$this->box->code}_grid", $headers, $rows, $format);
    }

    protected function getTubeTypeColor($tubeType)
    {
        switch ($tubeType) {
            case 'human':
                return 'from-pink-500 to-pink-600';
            case 'animal':
                return 'from-yellow-500 to-yellow-600';
            case 'environment':
                return 'from-green-500 to-green-600';
            case 'parasite':
                return 'from-purple-500 to-purple-600';
            case 'nucleic':
                return 'from-blue-500 to-blue-600';
            case 'culture':
                return 'from-orange-500 to-orange-600';
            case 'pool':
                return 'from-cyan-500 to-cyan-600';
            default:
                return 'from-gray-500 to-gray-600';
        }
    }

    protected function getTubeTypeFromContentType($contentType)
    {
        switch ($contentType) {
            case HumanSamples::class:
                return 'human';
            case AnimalSamples::class:
                return 'animal';
            case EnvironmentSamples::class:
                return 'environment';
            case ParasiteSamples::class:
                return 'parasite';
            case NucleicAcids::class:
                return 'nucleic';
            case Cultures::class:
                return 'culture';
            case Pools::class:
                return 'pool';
            default:
                return 'unknown';
        }
    }

    public function getBoxContentTypeProperty()
    {
        // Get the latest position for each tube globally
        $globalLatestPositions = TubePositions::select('tubes_id', 'boxes_id', 'date_moved')
            ->orderBy('date_moved', 'desc')
            ->get()
            ->groupBy('tubes_id')
            ->map(function ($positions) {
                return $positions->first();
            });

        // Only tubes whose latest position is in this box
        $tubesInThisBox = $globalLatestPositions->filter(function ($position) {
            return $position->boxes_id == $this->boxId;
        });

        $tubeIds = $tubesInThisBox->pluck('tubes_id')->toArray();

        if (empty($tubeIds)) {
            return 'Empty';
        }

        $tubes = Tubes::whereIn('id', $tubeIds)->get();
        $types = $tubes->pluck('tubes_content_type')->unique();

        if ($types->count() === 1) {
            switch ($types->first()) {
                case HumanSamples::class:
                    return 'Human samples';
                case AnimalSamples::class:
                    return 'Animal samples';
                case EnvironmentSamples::class:
                    return 'Environmental samples';
                case ParasiteSamples::class:
                    return 'Parasite samples';
                case NucleicAcids::class:
                    return 'Nucleic acids';
                case Cultures::class:
                    return 'Cultures';
                case Pools::class:
                    return 'Pools';
                default:
                    return 'Unknown';
            }
        } elseif ($types->count() > 1) {
            return 'Miscellaneous';
        } else {
            return 'Empty';
        }
    }

    public function deleteBox()
    {
        if (! $this->canEdit) {
            session()->flash('error', 'You do not have permission to delete this box.');

            return;
        }

        try {
            // Delete all tube positions in this box
            TubePositions::where('boxes_id', $this->boxId)->delete();
            // Delete the box itself
            Boxes::where('id', $this->boxId)->delete();

            // Redirect to the tube positions list with a success message
            return redirect('/bank/tubes/list')->with('success', 'Box deleted successfully.');
        } catch (\Exception $e) {
            $this->dispatch('show-message', ['type' => 'error', 'message' => 'Error deleting box: '.$e->getMessage()]);
        }
    }

    public function render()
    {
        if (! $this->canView) {
            return view('livewire.box-contents', [
                'box' => null,
                'tubePositions' => [],
                'nRows' => 0,
                'nColumns' => 0,
                'showTubeModal' => false,
                'selectedPosition' => '',
                'selectedTubeType' => '',
                'selectedTubeId' => '',
                'selectedTubeCode' => '',
                'availableTubes' => [],
                'currentTube' => null,
                'canView' => false,
                'canEdit' => false,
                'unauthorizedMessage' => $this->unauthorizedMessage,
            ]);
        }

        return view('livewire.box-contents', [
            'box' => $this->box,
            'tubePositions' => $this->tubePositions,
            'nRows' => $this->nRows,
            'nColumns' => $this->nColumns,
            'showTubeModal' => $this->showTubeModal,
            'selectedPosition' => $this->selectedPosition,
            'selectedTubeType' => $this->selectedTubeType,
            'selectedTubeId' => $this->selectedTubeId,
            'selectedTubeCode' => $this->selectedTubeCode,
            'availableTubes' => $this->availableTubes,
            'currentTube' => $this->currentTube,
            'canView' => $this->canView,
            'canEdit' => $this->canEdit,
            'unauthorizedMessage' => $this->unauthorizedMessage,
        ]);
    }
}
