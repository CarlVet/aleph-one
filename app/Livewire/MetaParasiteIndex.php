<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithColumnSorting;
use App\Models\Countries;
use App\Models\MetaParasite;
use App\Models\ParasiteSampleTypes;
use App\Models\ParasiteSpecies;
use App\Models\Pathogens;
use App\Models\RiskFactors;
use App\Models\Studies;
use App\Models\Techniques;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class MetaParasiteIndex extends PlainComponent
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
            'project' => fn ($q, $dir) => $this->orderByRelation($q, ['projects'], 'code', $dir),
            'study' => fn ($q, $dir) => $this->orderByRelation($q, ['studies'], 'ref_key', $dir),
            'parasite_species' => fn ($q, $dir) => $this->orderByRelation($q, ['parasite_species'], 'name_scientific', $dir),
            'parasite_sample_type' => fn ($q, $dir) => $this->orderByRelation($q, ['parasite_sample_types'], 'name', $dir),
            'location' => 'location',
            'country' => fn ($q, $dir) => $this->orderByRelation($q, ['countries'], 'name', $dir),
            'date_sampling' => 'date_sampling',
            'pathogen' => fn ($q, $dir) => $this->orderByRelation($q, ['pathogens'], 'species', $dir),
            'technique' => fn ($q, $dir) => $this->orderByRelation($q, ['techniques'], 'name', $dir),
            'tested_n' => 'tested_n',
            'pos_n' => 'pos_n',
            'sub_project' => fn ($q, $dir) => $q->orderBy($this->subProjectCodeSortSubquery($q->getModel()), $dir),
            'scientist' => fn ($q, $dir) => $this->orderByRelation($q, ['people'], 'first_name', $dir),
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

    public function isGuestMode(): bool
    {
        return $this->projectId === null;
    }

    public $parasiteSpeciesFilter;

    public $parasiteSampleTypeFilter;

    public $samplingSiteFilter;

    public $collectedStartDate;

    public $collectedEndDate;

    public $studyFilter;

    public $countryFilter;

    public $pathogenFilter;

    public $techniqueFilter;

    public $testedNFilter;

    public $posNFilter;

    public $riskFactorFilter;

    public $scientistFilter;

    public $projectFilter;

    public $subProjectCodeFilter;

    public array $selectedMetaParasites = [];

    public function delete(MetaParasite $meta)
    {
        if (! $this->userCanMutateOwnedRecord((int) $meta->people_id, 'literature')) {
            return;
        }

        $meta->delete();
    }

    public function deleteSelected(): void
    {
        $selectedIds = collect($this->selectedMetaParasites)
            ->filter(fn ($checked): bool => (bool) $checked)
            ->keys()
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        if ($selectedIds->isEmpty()) {
            session()->flash('error', 'Please select at least one literature record.');

            return;
        }

        $metaRows = MetaParasite::query()->whereIn('id', $selectedIds->all())->get();
        $deleted = 0;
        foreach ($metaRows as $meta) {
            if (! $this->userCanMutateOwnedRecord((int) $meta->people_id, 'literature')) {
                continue;
            }

            $meta->delete();
            $deleted++;
        }

        $this->selectedMetaParasites = [];
        session()->flash(
            $deleted > 0 ? 'message' : 'error',
            $deleted > 0 ? "{$deleted} selected literature record(s) deleted successfully." : 'No selected literature records could be deleted.'
        );
    }

    public $isEditing = false;

    public function toggleEditMode()
    {
        if (! $this->userCanWriteModule('literature')) {
            return;
        }

        $this->isEditing = ! $this->isEditing;
    }

    public function updateField(int $metaParasiteId, string $field, mixed $value): void
    {
        if ($this->isGuestMode() || ! $this->userCanWriteModule('literature')) {
            return;
        }

        $allowed = [
            'studies_id',
            'parasite_species_id',
            'parasite_sample_types_id',
            'countries_id',
            'pathogens_id',
            'techniques_id',
            'location',
            'date_sampling',
            'tested_n',
            'pos_n',
        ];
        if (! in_array($field, $allowed, true)) {
            return;
        }

        $meta = MetaParasite::query()
            ->where('projects_id', (int) $this->projectId)
            ->findOrFail($metaParasiteId);
        if (! $this->userCanMutateOwnedRecord((int) $meta->people_id, 'literature')) {
            return;
        }

        $trimmed = is_string($value) ? trim($value) : $value;

        $rules = match ($field) {
            'studies_id' => ['required', 'integer', 'exists:studies,id'],
            'parasite_species_id' => ['required', 'integer', 'exists:parasite_species,id'],
            'parasite_sample_types_id' => ['required', 'integer', 'exists:parasite_sample_types,id'],
            'countries_id' => ['required', 'integer', 'exists:countries,id'],
            'pathogens_id' => ['required', 'integer', 'exists:pathogens,id'],
            'techniques_id' => ['required', 'integer', 'exists:techniques,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'date_sampling' => ['nullable', 'date'],
            'tested_n' => ['nullable', 'integer', 'min:0'],
            'pos_n' => ['nullable', 'integer', 'min:0'],
            default => ['nullable'],
        };

        $validated = validator(['value' => $trimmed], ['value' => $rules])->validate();

        $meta->{$field} = $validated['value'];
        $meta->save();
    }

    public string $selectedTable = 'meta_parasite_table';

    public function toggleTableMode()
    {
        $this->selectedTable = ! $this->selectedTable;
    }

    public function updating($field)
    {
        if (is_string($field) && str_starts_with($field, 'selectedMetaParasites')) {
            return;
        }

        $this->resetPage('articles-page');
    }

    protected function applyFilters($query)
    {
        if ($this->parasiteSpeciesFilter) {
            $query->whereHas('parasite_species', function ($q) {
                $q->where('name_scientific', 'like', '%'.$this->parasiteSpeciesFilter.'%');
            });
        }
        if ($this->parasiteSampleTypeFilter) {
            $query->whereHas('parasite_sample_types', function ($q) {
                $q->where('name', 'like', '%'.$this->parasiteSampleTypeFilter.'%');
            });
        }
        if ($this->samplingSiteFilter) {
            $query->where('location', 'like', '%'.$this->samplingSiteFilter.'%');
        }
        if ($this->collectedStartDate && $this->collectedEndDate) {
            $query->whereBetween('date_sampling', [$this->collectedStartDate, $this->collectedEndDate]);
        } elseif ($this->collectedStartDate) {
            $query->where('date_sampling', '>=', $this->collectedStartDate);
        } elseif ($this->collectedEndDate) {
            $query->where('date_sampling', '<=', $this->collectedEndDate);
        }
        if ($this->studyFilter) {
            $query->whereHas('studies', function ($q) {
                $q->where('ref_key', 'like', '%'.$this->studyFilter.'%');
            });
        }
        if ($this->countryFilter) {
            $query->whereHas('countries', function ($q) {
                $q->where('name', 'like', '%'.$this->countryFilter.'%');
            });
        }
        if ($this->pathogenFilter) {
            $query->whereHas('pathogens', function ($q) {
                $q->where('species', 'like', '%'.$this->pathogenFilter.'%');
            });
        }
        if ($this->techniqueFilter) {
            $query->whereHas('techniques', function ($q) {
                $q->where('name', 'like', '%'.$this->techniqueFilter.'%');
            });
        }
        if ($this->testedNFilter) {
            $query->where('tested_n', $this->testedNFilter);
        }
        if ($this->posNFilter) {
            $query->where('pos_n', $this->posNFilter);
        }
        if ($this->riskFactorFilter) {
            $query->whereHas('risk_factors', function ($q) {
                $q->where('name', 'like', '%'.$this->riskFactorFilter.'%');
            });
        }
        if ($this->scientistFilter) {
            $query->whereHas('people', function ($q) {
                $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%'.$this->scientistFilter.'%');
            });
        }
        if ($this->projectFilter) {
            $query->whereHas('projects', function ($q) {
                $q->where('code', 'like', '%'.$this->projectFilter.'%');
            });
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
        $fileName = 'meta_parasites.csv';

        $query = MetaParasite::with(
            'studies',
            'countries',
            'parasite_sample_types',
            'parasite_species',
            'pathogens',
            'techniques',
            'risk_factors',
            'people',
            'projects',
            'subProjectAssignment.subProject'
        );

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            // In guest mode, show only public meta data
            $query->where('is_private', false);
        } else {
            // In project mode, show meta data from the selected project
            $query->where('projects_id', $this->projectId);
        }

        $query = $this->applyFilters($query);
        $this->applySorting($query, $this->sortMap(), ['created_at', 'desc']);

        $meta_parasites = $query->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$fileName}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($meta_parasites) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Study', 'Parasite Species', 'Location', 'Country', 'Date Sampling', 'Sample Type', 'Pathogen', 'Technique', 'Tested N', 'Positive N', 'Risk Factor', 'Sub-project', 'Scientist']);

            foreach ($meta_parasites as $meta) {
                $riskFactors = $meta->risk_factors->pluck('name')->filter()->unique()->implode(', ');
                fputcsv($file, [
                    $meta->studies->ref_key,
                    $meta->parasite_species->name_scientific,
                    $meta->location,
                    $meta->countries->name,
                    $meta->date_sampling,
                    $meta->parasite_sample_types->name,
                    $meta->pathogens->species,
                    $meta->techniques->name,
                    $meta->tested_n,
                    $meta->pos_n,
                    $riskFactors !== '' ? $riskFactors : 'N/A',
                    data_get($meta, 'subProjectAssignment.subProject.code') ?? 'N/A',
                    $meta->people->first_name.' '.$meta->people->last_name,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        // Permission logic (copied from NucleicAcidsIndex)
        $project = null;
        $canEdit = true;
        if (! $this->isGuestMode()) {
            $user = Auth::user();
            if ($user && $user->people) {
                $project = $user->people->projects()
                    ->where('projects.id', $this->projectId)
                    ->withPivot('role', 'date_joined', 'permission')
                    ->first();
                if ($project && $project->pivot && $project->pivot->permission === 'viewer') {
                    $canEdit = false;
                }
            }
        }

        $query = MetaParasite::with(
            'studies',
            'countries',
            'parasite_sample_types',
            'parasite_species',
            'pathogens',
            'techniques',
            'risk_factors',
            'people',
            'projects',
            'subProjectAssignment.subProject'
        );

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            // In guest mode, show only public meta data
            $query->where('is_private', false);
        } else {
            // In project mode, show meta data from the selected project
            $query->where('projects_id', $this->projectId);
        }

        $query = $this->applyFilters($query);
        $this->applySorting($query, $this->sortMap(), ['created_at', 'desc']);

        $meta_parasites = $query->paginate($this->perPage, pageName: 'articles-page');

        return view('livewire.meta-parasite-index', [
            'meta_parasites' => $meta_parasites,
            'isEditing' => $this->isEditing,
            'selectedTable' => $this->selectedTable,
            'isGuestMode' => $this->isGuestMode(),
            'canEdit' => $canEdit,
            'studiesOptions' => Studies::query()->orderBy('ref_key')->get(['id', 'ref_key']),
            'parasiteSpeciesOptions' => ParasiteSpecies::query()->orderBy('name_scientific')->get(['id', 'name_scientific']),
            'parasiteSampleTypesOptions' => ParasiteSampleTypes::query()->orderBy('name')->get(['id', 'name']),
            'countriesOptions' => Countries::query()->orderBy('name')->get(['id', 'name']),
            'pathogensOptions' => Pathogens::query()->orderBy('species')->get(['id', 'species']),
            'techniquesOptions' => Techniques::query()->orderBy('name')->get(['id', 'name']),
            'riskFactorsOptions' => RiskFactors::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
