<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithColumnSorting;
use App\Models\Boxes;
use App\Models\BoxPositions;
use App\Models\Locations;
use App\Services\BoxesService;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class BoxPositionsList extends PlainComponent
{
    use WithColumnSorting;
    use WithPagination;

    protected function sortingPageName(): ?string
    {
        return 'articles-page';
    }

    /**
     * @return array<string, string|callable>
     */
    protected function sortMap(): array
    {
        return [
            'box_code' => fn ($q, $dir) => $this->orderByRelation($q, ['boxes'], 'code', $dir),
            'box_alias_code' => fn ($q, $dir) => $this->orderByRelation($q, ['boxes'], 'alias_code', $dir),
            'content_type' => fn ($q, $dir) => $this->orderByRelation($q, ['boxes'], 'content_type', $dir),
            'date_moved' => 'date_moved',
            'location' => fn ($q, $dir) => $this->orderByRelation($q, ['locations'], 'name', $dir),
            'sublocation' => 'sublocation',
            'facility' => fn ($q, $dir) => $this->orderByRelation($q, ['locations', 'laboratories'], 'name', $dir),
            'moved_by' => fn ($q, $dir) => $this->orderByRelation($q, ['people'], 'first_name', $dir),
            'reason' => 'reason',
            'sub_project' => fn ($q, $dir) => $q->orderBy($this->subProjectCodeSortSubquery($q->getModel()), $dir),
        ];
    }

    protected $projectId;

    public function __construct()
    {
        $this->projectId = session('selected_project_id');
    }

    public function getProjectId()
    {
        return $this->projectId;
    }

    public $boxCodeFilter;

    public $boxAliasCodeFilter;

    public $contentTypeFilter;

    public $startDate;

    public $endDate;

    public $locationFilter;

    public $subLocationFilter;

    public $facilityFilter;

    public $scientistFilter;

    public $reasonFilter;

    public $subProjectCodeFilter;

    public $selectedContentType = 'box-positions'; // Default content type

    public function updateField($sampleId, $field, $value)
    {
        $box_position = BoxPositions::find($sampleId);
        if (! $box_position || ! $this->userCanMutateOwnedRecord((int) $box_position->people_id, 'box_positions')) {
            session()->flash('error', 'You can only edit records you registered.');

            return;
        }

        if ($box_position) {
            switch ($field) {
                case 'box':
                    $box = Boxes::where('code', $value)->first();
                    $box_position->update(['boxes_id' => $box->id]);
                    break;
                case 'box_alias_code':
                    $box_position->boxes()->update(['alias_code' => $value]);
                    break;
                case 'content_type':
                    $box_position->boxes()->update(['content_type' => $value]);
                    break;
                case 'location':
                    $location = Locations::where('name', $value)->first();
                    $box_position->update(['locations_id' => $location->id]);
                    break;
                case 'sublocation':
                    $box_position->update(['sublocation' => $value]);
                    break;
                case 'date_moved':
                    $box_position->update(['date_moved' => $value]);
                    break;
                case 'reason':
                    $box_position->update(['reason' => $value]);
                    break;
            }

            session()->flash('success', 'Box position edited successfully!');
        }
    }

    public function delete(BoxPositions $box_position)
    {
        if (! $this->userCanMutateOwnedRecord((int) $box_position->people_id, 'box_positions')) {
            session()->flash('error', 'You can only delete records you registered.');

            return;
        }

        $box_position->delete();
    }

    public $isEditing = false;

    public string $selectedTable = 'box_positions_table'; // Default table view

    public array $selectedBoxPositions = [];

    public function toggleEditMode()
    {
        if (! $this->userCanWriteModule('box_positions')) {
            session()->flash('error', 'You do not have permission to edit box positions.');

            return;
        }

        $this->isEditing = ! $this->isEditing;
    }

    public function deleteSelected(): void
    {
        $selectedIds = collect($this->selectedBoxPositions)
            ->filter(fn ($checked): bool => (bool) $checked)
            ->keys()
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        if ($selectedIds->isEmpty()) {
            session()->flash('error', 'Please select at least one box position.');

            return;
        }

        $positions = BoxPositions::query()
            ->whereIn('id', $selectedIds->all())
            ->get();

        $deleted = 0;

        foreach ($positions as $position) {
            if (! $this->userCanMutateOwnedRecord((int) $position->people_id, 'box_positions')) {
                continue;
            }

            $position->delete();
            $deleted++;
        }

        $this->selectedBoxPositions = [];

        if ($deleted > 0) {
            session()->flash('success', "{$deleted} selected box position(s) deleted successfully.");
        } else {
            session()->flash('error', 'No selected box positions could be deleted.');
        }
    }

    public function updating($field)
    {
        if (is_string($field) && str_starts_with($field, 'selectedBoxPositions')) {
            return;
        }

        $this->resetPage('articles-page');
    }

    protected function applyFilters($query)
    {
        if ($this->boxCodeFilter) {
            $query->whereHas('boxes', function ($q) {
                $q->where('code', 'like', '%'.$this->boxCodeFilter.'%');
            });
        }
        if ($this->boxAliasCodeFilter) {
            $query->whereHas('boxes', function ($q) {
                $q->where('alias_code', 'like', '%'.$this->boxAliasCodeFilter.'%');
            });
        }
        if ($this->contentTypeFilter) {
            $query->whereHas('boxes', function ($q) {
                $q->where('content_type', 'like', '%'.$this->contentTypeFilter.'%');
            });
        }
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('date_moved', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('date_moved', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->where('date_moved', '<=', $this->endDate);
        }
        if ($this->locationFilter) {
            $query->whereHas('locations', function ($q) {
                $q->where('name', 'like', '%'.$this->locationFilter.'%');
            });
        }
        if ($this->subLocationFilter) {
            $query->where('sublocation', 'like', '%'.$this->subLocationFilter.'%');
        }
        if ($this->facilityFilter) {
            $query->whereHas('locations.laboratories', function ($q) {
                $q->where('name', 'like', '%'.$this->facilityFilter.'%');
            });
        }
        if ($this->scientistFilter) {
            $query->whereHas('people', function ($q) {
                $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%'.$this->scientistFilter.'%');
            });
        }
        if ($this->reasonFilter) {
            $query->where('reason', 'like', '%'.$this->reasonFilter.'%');
        }
        if ($this->subProjectCodeFilter) {
            $query->whereHas('subProjectAssignment.subProject', function ($q) {
                $q->where('code', 'like', '%'.$this->subProjectCodeFilter.'%');
            });
        }

        return $query;
    }

    public function export()
    {
        $fileName = 'box_positions.csv';

        $query = BoxPositions::with(
            'boxes',
            'boxes.projects',
            'locations',
            'locations.laboratories',
            'locations.laboratories.countries',
            'people',
            'subProjectAssignment.subProject',
        )->whereHas('boxes', function ($query) {
            $query->where('projects_id', $this->projectId);
        });

        $query = $this->applyFilters($query);
        $this->applySorting($query, $this->sortMap(), ['created_at', 'desc']);

        $box_positions = $query->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$fileName}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($box_positions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Box code', 'Box alias code', 'Content type', 'Sub-project', 'Date moved', 'Location (room)', 'Sub-location', 'Facility (country)', 'Moved by', 'Reason moved']);

            foreach ($box_positions as $box_position) {
                $location = $box_position->locations;
                $laboratory = $location?->laboratories;
                $country = $laboratory?->countries;

                fputcsv($file, [
                    $box_position->boxes?->code,
                    $box_position->boxes?->alias_code,
                    $box_position->boxes?->content_type,
                    data_get($box_position, 'subProjectAssignment.subProject.code') ?? 'N/A',
                    $box_position->date_moved,
                    $location?->name ? $location->name.($location?->room ? ' ('.$location->room.')' : '') : null,
                    $box_position->sublocation,
                    $laboratory?->name ? $laboratory->name.($country?->name ? ' ('.$country->name.')' : '') : null,
                    $box_position->people?->title ? $box_position->people->title.' '.$box_position->people->first_name.' '.$box_position->people->last_name : null,
                    $box_position->reason,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $boxes_service = app(BoxesService::class);

        $query = BoxPositions::with(
            'boxes',
            'boxes.projects',
            'locations',
            'locations.laboratories',
            'locations.laboratories.countries',
            'people',
            'subProjectAssignment.subProject',
        )->whereHas('boxes', function ($query) {
            $query->where('projects_id', $this->projectId);
        });

        $query = $this->applyFilters($query);
        $this->applySorting($query, $this->sortMap(), ['created_at', 'desc']);

        $box_positions = $query->paginate($this->perPage, pageName: 'articles-page');

        // Permission logic (copied from NucleicAcidsIndex)
        $project = null;
        $canEdit = $this->userCanWriteModule('box_positions');

        $viewData = array_merge($boxes_service->assign(), [
            'box_positions' => $box_positions,
            'isEditing' => $this->isEditing,
            'canEdit' => $canEdit,
            'isGuestMode' => $this->isGuestMode(),
            'currentPeopleId' => (int) ($this->currentPeopleId() ?? 0),
            'showBulkActions' => $canEdit && ! $this->isGuestMode(),
        ]);

        return view('livewire.box-positions-list', $viewData);
    }
}
