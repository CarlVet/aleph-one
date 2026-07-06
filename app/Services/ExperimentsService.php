<?php

namespace App\Services;

use App\Models\AnimalSamples;
use App\Models\Countries;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\Experiments;
use App\Models\HumanSamples;
use App\Models\Laboratories;
use App\Models\NucleicAcids;
use App\Models\Organizations;
use App\Models\ParasiteSamples;
use App\Models\Pathogens;
use App\Models\People;
use App\Models\Pools;
use App\Models\Protocols;
use App\Models\Studies;
use App\Models\Techniques;
use App\Models\Tubes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ExperimentsService
{
    private const EXCLUDED_PROTOCOL_TECHNIQUE_TYPES = [
        'Nucleic Acids Extraction and Purification',
        'Microplastics identification',
    ];

    protected $projectId;

    public function __construct()
    {
        $this->projectId = session('selected_project_id');
    }

    public function getProjectId()
    {
        return $this->projectId;
    }

    public function isGuestMode()
    {
        return $this->projectId === null;
    }

    public function check_or_create($model, $conditions, $attributes = [])
    {
        $existing_value = $model::where($conditions)->first();

        if (! $existing_value) {
            $model::create(array_merge($conditions, $attributes));

            return $model::where($conditions)->first()->id;
        } else {
            return $existing_value->id;
        }
    }

    public function laboratories_by_country()
    {
        $laboratories = Laboratories::with('countries')->get();

        $labs_by_country = [];

        foreach ($laboratories as $lab) {
            $country = $lab->countries->name ?? 'Unknown';
            $labs_by_country[$country][] = [
                'id' => $lab->id,
                'name' => $lab->name,
                'type' => 'laboratory',
                'country' => $country,
            ];
        }

        return $labs_by_country;
    }

    /**
     * Lightweight data for the Experiments list Livewire page.
     *
     * This intentionally avoids the very heavy `assign()` payload (tubes + multiple
     * experiment collections) which can exhaust PHP memory when rendering.
     *
     * @return array{exp_protocols:Collection<int, Protocols>, pathogens:Collection<int, Pathogens>, laboratories_by_country:array<string, array<int, array{id:int, name:string, type:string, country:string}>>, people:Collection<int, People>}
     */
    public function listsForExperimentsIndex(): array
    {
        $exp_protocols = $this->experimentProtocolsQuery([
            'techniques',
            'studies',
            'pathogens',
        ])->get();

        $people = $this->isGuestMode()
            ? collect()
            : People::whereHas('projects', function ($query) {
                $query->where('projects.id', $this->projectId);
            })
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

        return [
            'exp_protocols' => $exp_protocols,
            'pathogens' => Pathogens::all(),
            'laboratories_by_country' => $this->laboratories_by_country(),
            'people' => $people,
        ];
    }

    /**
     * Data for the `/samples/pools/create` Blade form.
     *
     * Keep this minimal: that page only needs people + labs list.
     *
     * @return array{laboratories_by_country:array<string, array<int, array{id:int, name:string, type:string, country:string}>>, people:Collection<int, People>}
     */
    public function dataForPoolsCreate(): array
    {
        $people = People::whereHas('projects', function ($query) {
            $query->where('projects.id', $this->projectId);
        })->get();

        return [
            'laboratories_by_country' => $this->laboratories_by_country(),
            'people' => $people,
        ];
    }

    /**
     * Data for the `/experiments/create` Blade form.
     *
     * This intentionally avoids `assign()` because that method loads many full
     * experiment datasets + full tube lists and can exhaust memory.
     *
     * @return array<string, mixed>
     */
    public function dataForCreate(): array
    {
        $tubes_service = app(TubesService::class);
        $tubes_data = $tubes_service->paginateForExperimentsCreate(10);

        $exp_protocols = $this->experimentProtocolsQuery([
            'techniques',
            'studies',
            'pathogens',
        ])->get();

        $protocol_lookup_rows = $this->experimentProtocolsQuery([
            'techniques',
            'pathogens',
            'experiments.projects',
            'microplastics.projects',
        ])->get()
            ->map(function (Protocols $protocol): array {
                return [
                    'code' => (string) ($protocol->code ?? ''),
                    'name' => (string) ($protocol->name ?? ''),
                    'technique_name' => (string) ($protocol->techniques->name ?? ''),
                    'technique_type' => (string) ($protocol->techniques->type ?? ''),
                    'target_pathogens' => $protocol->pathogens
                        ->pluck('species')
                        ->filter()
                        ->unique()
                        ->values()
                        ->all(),
                    'project_codes' => $protocol->experiments
                        ->pluck('projects.code')
                        ->merge($protocol->microplastics->pluck('projects.code'))
                        ->filter()
                        ->unique()
                        ->values()
                        ->all(),
                ];
            })
            ->values();

        $techniques = Techniques::all();
        $pathogens = Pathogens::all();
        $laboratories_by_country = $this->laboratories_by_country();
        $laboratory_lookup_rows = Laboratories::query()
            ->with('countries')
            ->orderBy('name')
            ->get()
            ->map(function (Laboratories $laboratory): array {
                return [
                    'name' => (string) ($laboratory->name ?? ''),
                    'lab_type' => (string) ($laboratory->lab_type ?? ''),
                    'country' => (string) ($laboratory->countries?->name ?? ''),
                    'city' => (string) ($laboratory->city ?? ''),
                    'address' => (string) ($laboratory->address ?? ''),
                    'latitude' => $laboratory->latitude,
                    'longitude' => $laboratory->longitude,
                ];
            })
            ->values();

        $organizations = Organizations::with('countries')->get();
        $countries = Countries::all();
        $organizations_by_country = $organizations
            ->sortBy([
                fn ($organization) => mb_strtolower((string) optional($organization->countries)->name),
                fn ($organization) => mb_strtolower((string) $organization->name),
            ])
            ->groupBy(function ($organization): string {
                $country = optional($organization->countries)->name;

                return is_string($country) && trim($country) !== '' ? trim($country) : 'Unassigned Country';
            })
            ->sortKeysUsing('strcasecmp');
        $lab_types = collect([
            'Academic',
            'Research',
            'Diagnostic',
            'Commercial',
            'Government',
        ])
            ->merge(Laboratories::query()
                ->whereNotNull('lab_type')
                ->where('lab_type', '!=', '')
                ->distinct()
                ->pluck('lab_type'))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique(fn ($value) => mb_strtolower($value))
            ->sortBy(fn ($value) => mb_strtolower($value))
            ->values();

        $protocol_pathogen_map = $this->experimentProtocolsQuery(['pathogens'])->get()->mapWithKeys(function ($protocol) {
            return [
                $protocol->name => $protocol->pathogens->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'species' => $p->species,
                    ];
                })->toArray(),
            ];
        });

        $people = People::whereHas('projects', function ($query) {
            $query->where('projects.id', $this->projectId);
        })->get();

        $outcomeQualDefaults = [
            'Strong positive',
            'Positive',
            'Suspect',
            'Negative',
            'Inconclusive',
            'Unsuccesfull',
        ];
        $outcomeQualDataset = Experiments::query()
            ->where('projects_id', $this->projectId)
            ->whereNotNull('outcome_discrete')
            ->distinct()
            ->orderBy('outcome_discrete')
            ->pluck('outcome_discrete')
            ->filter(fn ($v) => filled($v))
            ->values()
            ->all();
        $outcome_qual_options = array_values(array_unique(array_merge($outcomeQualDefaults, $outcomeQualDataset)));

        $organization_types = [
            'Government' => 'Government',
            'Non-Governmental Organization' => 'Non-Governmental Organization',
            'Private Company' => 'Private Company',
            'University' => 'University',
            'Research Institute' => 'Research Institute',
            'Research Station' => 'Research Station',
            'Wildlife Center' => 'Wildlife Center',
            'Clinic' => 'Veterinary Clinic',
            'Field Station' => 'Field Station',
        ];

        $location_types = [
            'Stand-up freezer' => 'Stand-up freezer',
            'Chest freezer' => 'Chest freezer',
            'Refrigerator' => 'Refrigerator',
            'Walk-in refrigerator' => 'Walk-in refrigerator',
            'Walk-in freezer' => 'Walk-in freezer',
            'Shelf' => 'Shelf',
            'Cabinet' => 'Cabinet',
            'Drawer' => 'Drawer',
        ];

        // If validation failed previously, pre-populate Selectize with only selected tubes.
        $selected_human_tubes = collect();
        $selected_animal_tubes = collect();
        $selected_environment_tubes = collect();
        $selected_parasite_tubes = collect();
        $selected_nucleic_tubes = collect();
        $selected_culture_tubes = collect();
        $selected_pool_tubes = collect();

        $old_human_ids = array_filter((array) session()->getOldInput('human_tube_id', []));
        $old_animal_ids = array_filter((array) session()->getOldInput('animal_tube_id', []));
        $old_environment_ids = array_filter((array) session()->getOldInput('environment_tube_id', []));
        $old_parasite_ids = array_filter((array) session()->getOldInput('parasite_tube_id', []));
        $old_nucleic_ids = array_filter((array) session()->getOldInput('nucleic_tube_id', []));
        $old_culture_ids = array_filter((array) session()->getOldInput('culture_tube_id', []));
        $old_pool_ids = array_filter((array) session()->getOldInput('pool_tube_id', []));

        if ($old_human_ids) {
            $selected_human_tubes = Tubes::query()->whereIn('id', $old_human_ids)->get();
        }
        if ($old_animal_ids) {
            $selected_animal_tubes = Tubes::query()->whereIn('id', $old_animal_ids)->get();
        }
        if ($old_environment_ids) {
            $selected_environment_tubes = Tubes::query()->whereIn('id', $old_environment_ids)->get();
        }
        if ($old_parasite_ids) {
            $selected_parasite_tubes = Tubes::query()->whereIn('id', $old_parasite_ids)->get();
        }
        if ($old_nucleic_ids) {
            $selected_nucleic_tubes = Tubes::query()->whereIn('id', $old_nucleic_ids)->get();
        }
        if ($old_culture_ids) {
            $selected_culture_tubes = Tubes::query()->whereIn('id', $old_culture_ids)->get();
        }
        if ($old_pool_ids) {
            $selected_pool_tubes = Tubes::query()->whereIn('id', $old_pool_ids)->get();
        }

        return array_merge($tubes_data, [
            'exp_protocols' => $exp_protocols,
            'protocol_lookup_rows' => $protocol_lookup_rows,
            'pathogens' => $pathogens,
            'studies' => Studies::all(),
            'techniques' => $techniques,
            'protocol_pathogen_map' => $protocol_pathogen_map,
            'laboratories_by_country' => $laboratories_by_country,
            'laboratory_lookup_rows' => $laboratory_lookup_rows,
            'organizations' => $organizations,
            'organizations_by_country' => $organizations_by_country,
            'countries' => $countries,
            'lab_types' => $lab_types,
            'people' => $people,
            'organization_types' => $organization_types,
            'location_types' => $location_types,
            'outcome_qual_options' => $outcome_qual_options,
            'selected_human_tubes' => $selected_human_tubes,
            'selected_animal_tubes' => $selected_animal_tubes,
            'selected_environment_tubes' => $selected_environment_tubes,
            'selected_parasite_tubes' => $selected_parasite_tubes,
            'selected_nucleic_tubes' => $selected_nucleic_tubes,
            'selected_culture_tubes' => $selected_culture_tubes,
            'selected_pool_tubes' => $selected_pool_tubes,
        ]);
    }

    public function assign()
    {
        $tubes_service = app(TubesService::class);

        $tubes_data = $tubes_service->assign();

        // Get people based on project or guest mode
        if ($this->isGuestMode()) {
            $people = collect(); // Empty collection in guest mode
        } else {
            $people = People::whereHas('projects', function ($query) {
                $query->where('projects.id', $this->projectId);
            })->get();
        }

        // Get experiments with appropriate filtering
        $experiments = Experiments::with(
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
        )->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_nucleic = Experiments::whereHas('experiments_content', function ($query) {
            $query->where('experiments_content_type', NucleicAcids::class);
        })->with(
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.nucleic_content',
        )->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_nucleic_animal = Experiments::whereHasMorph(
            'experiments_content',
            [NucleicAcids::class],
            function ($query) {
                $query->whereHasMorph(
                    'nucleic_content',
                    [AnimalSamples::class]
                );
            }
        )->with([
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.nucleic_content',
            'experiments_content.nucleic_content.animals',
            'experiments_content.nucleic_content.animals.animal_species',
            'experiments_content.nucleic_content.sample_types',
            'experiments_content.nucleic_content.sampling_sites',
            'experiments_content.nucleic_content.sampling_sites.countries',
            'experiments_content.protocols',
            'experiments_content.people',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_nucleic_human = Experiments::whereHasMorph(
            'experiments_content',
            [NucleicAcids::class],
            function ($query) {
                $query->whereHasMorph(
                    'nucleic_content',
                    [HumanSamples::class]
                );
            }
        )->with([
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.nucleic_content',
            'experiments_content.nucleic_content.humans',
            'experiments_content.nucleic_content.sample_types',
            'experiments_content.nucleic_content.sampling_sites',
            'experiments_content.nucleic_content.sampling_sites.countries',
            'experiments_content.protocols',
            'experiments_content.people',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_nucleic_environment = Experiments::whereHasMorph(
            'experiments_content',
            [NucleicAcids::class],
            function ($query) {
                $query->whereHasMorph(
                    'nucleic_content',
                    [EnvironmentSamples::class]
                );
            }
        )->with([
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.nucleic_content',
            'experiments_content.nucleic_content.environment_sample_types',
            'experiments_content.nucleic_content.sampling_sites',
            'experiments_content.nucleic_content.sampling_sites.countries',
            'experiments_content.protocols',
            'experiments_content.people',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_nucleic_parasite = Experiments::whereHasMorph(
            'experiments_content',
            [NucleicAcids::class],
            function ($query) {
                $query->whereHasMorph(
                    'nucleic_content',
                    [ParasiteSamples::class]
                );
            }
        )->with([
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.nucleic_content',
            'experiments_content.nucleic_content.parasites',
            'experiments_content.nucleic_content.parasites.parasite_species',
            'experiments_content.nucleic_content.parasite_sample_types',
            'experiments_content.protocols',
            'experiments_content.people',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_nucleic_culture = Experiments::whereHasMorph(
            'experiments_content',
            [NucleicAcids::class],
            function ($query) {
                $query->whereHasMorph(
                    'nucleic_content',
                    [Cultures::class]
                );
            }
        )->with([
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.nucleic_content',
            'experiments_content.nucleic_content.laboratories',
            'experiments_content.protocols',
            'experiments_content.people',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_nucleic_pool = Experiments::whereHasMorph(
            'experiments_content',
            [NucleicAcids::class],
            function ($query) {
                $query->whereHasMorph(
                    'nucleic_content',
                    [Pools::class]
                );
            }
        )->with([
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.nucleic_content',
            'experiments_content.protocols',
            'experiments_content.people',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_animals = Experiments::whereHas('experiments_content', function ($query) {
            $query->where('experiments_content_type', AnimalSamples::class);
        })->with(
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.animals',
            'experiments_content.animals.animal_species',
            'experiments_content.sample_types',
            'experiments_content.sampling_sites',
            'experiments_content.sampling_sites.countries',
        )->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_humans = Experiments::whereHas('experiments_content', function ($query) {
            $query->where('experiments_content_type', HumanSamples::class);
        })->with(
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.humans',
            'experiments_content.sample_types',
            'experiments_content.sampling_sites',
            'experiments_content.sampling_sites.countries',
        )->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_environments = Experiments::whereHas('experiments_content', function ($query) {
            $query->where('experiments_content_type', EnvironmentSamples::class);
        })->with(
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.environment_sample_types',
            'experiments_content.sampling_sites',
            'experiments_content.sampling_sites.countries',
        )->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_parasites = Experiments::whereHas('experiments_content', function ($query) {
            $query->where('experiments_content_type', ParasiteSamples::class);
        })->with([
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.parasites',
            'experiments_content.parasites.parasite_species',
            'experiments_content.parasites.locations',
            'experiments_content.parasite_sample_types',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_parasite_human = Experiments::whereHasMorph(
            'experiments_content',
            [ParasiteSamples::class],
            function ($query) {
                $query->whereHas(
                    'parasites',
                    function ($q) {
                        $q->whereHasMorph('parasites_origin', [HumanSamples::class]);
                    }
                );
            }
        )->with([
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.parasites',
            'experiments_content.parasites.parasite_species',
            'experiments_content.parasites.locations',
            'experiments_content.parasite_sample_types',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_parasite_animal = Experiments::whereHasMorph(
            'experiments_content',
            [ParasiteSamples::class],
            function ($query) {
                $query->whereHas(
                    'parasites',
                    function ($q) {
                        $q->whereHasMorph('parasites_origin', [AnimalSamples::class]);
                    }
                );
            }
        )->with([
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.parasites',
            'experiments_content.parasites.parasite_species',
            'experiments_content.parasites.locations',
            'experiments_content.parasite_sample_types',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_parasite_environment = Experiments::whereHasMorph(
            'experiments_content',
            [ParasiteSamples::class],
            function ($query) {
                $query->whereHas(
                    'parasites',
                    function ($q) {
                        $q->whereHasMorph('parasites_origin', [EnvironmentSamples::class]);
                    }
                );
            }
        )->with([
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.parasites',
            'experiments_content.parasites.parasite_species',
            'experiments_content.parasites.locations',
            'experiments_content.parasite_sample_types',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_culture = Experiments::whereHas('experiments_content', function ($query) {
            $query->where('experiments_content_type', Cultures::class);
        })->with(
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.cultures_content',
            'experiments_content.laboratories',
        )->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_culture_human = Experiments::whereHasMorph(
            'experiments_content',
            [Cultures::class],
            function ($query) {
                $query->whereHasMorph(
                    'cultures_content',
                    [HumanSamples::class]
                );
            }
        )->with([
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.cultures_content',
            'experiments_content.cultures_content.humans',
            'experiments_content.laboratories',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_culture_animal = Experiments::whereHasMorph(
            'experiments_content',
            [Cultures::class],
            function ($query) {
                $query->whereHasMorph(
                    'cultures_content',
                    [AnimalSamples::class]
                );
            }
        )->with([
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.cultures_content',
            'experiments_content.cultures_content.animals',
            'experiments_content.cultures_content.animals.animal_species',
            'experiments_content.laboratories',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_culture_environment = Experiments::whereHasMorph(
            'experiments_content',
            [Cultures::class],
            function ($query) {
                $query->whereHasMorph(
                    'cultures_content',
                    [EnvironmentSamples::class]
                );
            }
        )->with([
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.cultures_content',
            'experiments_content.cultures_content.environment_sample_types',
            'experiments_content.laboratories',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_culture_parasite = Experiments::whereHasMorph(
            'experiments_content',
            [Cultures::class],
            function ($query) {
                $query->whereHasMorph(
                    'cultures_content',
                    [ParasiteSamples::class]
                );
            }
        )->with([
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.cultures_content',
            'experiments_content.cultures_content.parasites',
            'experiments_content.cultures_content.parasites.parasite_species',
            'experiments_content.cultures_content.parasites.locations',
            'experiments_content.cultures_content.parasite_sample_types',
            'experiments_content.laboratories',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_culture_pool = Experiments::whereHasMorph(
            'experiments_content',
            [Cultures::class],
            function ($query) {
                $query->whereHasMorph(
                    'cultures_content',
                    [Pools::class]
                );
            }
        )->with([
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.laboratories',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_pool = Experiments::whereHas('experiments_content', function ($query) {
            $query->where('experiments_content_type', Pools::class);
        })->with(
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.pool_contents',
            'experiments_content.laboratories',
        )->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_pool_human = Experiments::whereHasMorph(
            'experiments_content',
            [Pools::class],
            function ($query) {
                $query->whereHas(
                    'pool_contents',
                    function ($q) {
                        $q->whereHasMorph('samples', [HumanSamples::class]);
                    }
                );
            }
        )->with([
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.pool_contents',
            'experiments_content.laboratories',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_pool_animal = Experiments::whereHasMorph(
            'experiments_content',
            [Pools::class],
            function ($query) {
                $query->whereHas(
                    'pool_contents',
                    function ($q) {
                        $q->whereHasMorph('samples', [AnimalSamples::class]);
                    }
                );
            }
        )->with([
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.pool_contents',
            'experiments_content.laboratories',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_pool_environment = Experiments::whereHasMorph(
            'experiments_content',
            [Pools::class],
            function ($query) {
                $query->whereHas(
                    'pool_contents',
                    function ($q) {
                        $q->whereHasMorph('samples', [EnvironmentSamples::class]);
                    }
                );
            }
        )->with([
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.pool_contents',
            'experiments_content.laboratories',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_pool_parasite = Experiments::whereHasMorph(
            'experiments_content',
            [Pools::class],
            function ($query) {
                $query->whereHas(
                    'pool_contents',
                    function ($q) {
                        $q->whereHasMorph('samples', [ParasiteSamples::class]);
                    }
                );
            }
        )->with([
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.pool_contents',
            'experiments_content.laboratories',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $experiments_pool_nucleic = Experiments::whereHasMorph(
            'experiments_content',
            [Pools::class],
            function ($query) {
                $query->whereHas(
                    'pool_contents',
                    function ($q) {
                        $q->whereHasMorph('samples', [NucleicAcids::class]);
                    }
                );
            }
        )->with([
            'protocols',
            'protocols.techniques',
            'pathogens',
            'people',
            'laboratories',
            'laboratories.countries',
            'projects',
            'experiments_content',
            'experiments_content.pool_contents',
            'experiments_content.laboratories',
        ])->when(! $this->isGuestMode(), function ($query) {
            $query->where('projects_id', $this->projectId);
        })->when($this->isGuestMode(), function ($query) {
            $query->where('is_private', false);
        })->get();

        $exp_protocols = $this->experimentProtocolsQuery([
            'techniques',
            'studies',
            'pathogens',
        ])->get();

        $techniques = Techniques::all();
        $pathogens = Pathogens::all();
        $laboratories_by_country = $this->laboratories_by_country();

        $organizations = Organizations::with('countries')->get();
        $countries = Countries::all();

        $protocol_pathogen_map = $this->experimentProtocolsQuery(['pathogens'])->get()->mapWithKeys(function ($protocol) {
            return [
                $protocol->name => $protocol->pathogens->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'species' => $p->species,
                    ];
                })->toArray(),
            ];
        });

        $organization_types = [
            'Government' => 'Government',
            'Non-Governmental Organization' => 'Non-Governmental Organization',
            'Private Company' => 'Private Company',
            'University' => 'University',
            'Research Institute' => 'Research Institute',
            'Research Station' => 'Research Station',
            'Wildlife Center' => 'Wildlife Center',
            'Clinic' => 'Veterinary Clinic',
            'Field Station' => 'Field Station',
        ];

        $location_types = [
            'Stand-up freezer' => 'Stand-up freezer',
            'Chest freezer' => 'Chest freezer',
            'Refrigerator' => 'Refrigerator',
            'Walk-in refrigerator' => 'Walk-in refrigerator',
            'Walk-in freezer' => 'Walk-in freezer',
            'Shelf' => 'Shelf',
            'Cabinet' => 'Cabinet',
            'Drawer' => 'Drawer',
        ];

        return array_merge($tubes_data, [
            'experiments' => $experiments,
            'experiments_nucleic' => $experiments_nucleic,
            'experiments_nucleic_animal' => $experiments_nucleic_animal,
            'experiments_nucleic_human' => $experiments_nucleic_human,
            'experiments_nucleic_environment' => $experiments_nucleic_environment,
            'experiments_nucleic_parasite' => $experiments_nucleic_parasite,
            'experiments_nucleic_culture' => $experiments_nucleic_culture,
            'experiments_nucleic_pool' => $experiments_nucleic_pool,
            'experiments_animals' => $experiments_animals,
            'experiments_humans' => $experiments_humans,
            'experiments_environments' => $experiments_environments,
            'experiments_parasites' => $experiments_parasites,
            'experiments_parasite_human' => $experiments_parasite_human,
            'experiments_parasite_animal' => $experiments_parasite_animal,
            'experiments_parasite_environment' => $experiments_parasite_environment,
            'experiments_culture' => $experiments_culture,
            'experiments_culture_human' => $experiments_culture_human,
            'experiments_culture_animal' => $experiments_culture_animal,
            'experiments_culture_environment' => $experiments_culture_environment,
            'experiments_culture_parasite' => $experiments_culture_parasite,
            'experiments_culture_pool' => $experiments_culture_pool,
            'experiments_pool' => $experiments_pool,
            'experiments_pool_human' => $experiments_pool_human,
            'experiments_pool_animal' => $experiments_pool_animal,
            'experiments_pool_environment' => $experiments_pool_environment,
            'experiments_pool_parasite' => $experiments_pool_parasite,
            'experiments_pool_nucleic' => $experiments_pool_nucleic,
            'exp_protocols' => $exp_protocols,
            'pathogens' => $pathogens,
            'studies' => Studies::all(),
            'techniques' => $techniques,
            'protocol_pathogen_map' => $protocol_pathogen_map,
            'laboratories_by_country' => $laboratories_by_country,
            'organizations' => $organizations,
            'countries' => $countries,
            'people' => $people,
            'organization_types' => $organization_types,
            'location_types' => $location_types,
        ]);
    }

    protected function experimentProtocolsQuery(array $with = []): Builder
    {
        return Protocols::query()
            ->with($with)
            ->whereHas('techniques', function ($query) {
                $query->whereNotIn('type', self::EXCLUDED_PROTOCOL_TECHNIQUE_TYPES);
            });
    }
}
