<?php

namespace App\Livewire;

use App\Livewire\Concerns\ExportsTable;
use App\Livewire\Concerns\WithColumnSorting;
use App\Livewire\Forms\SequencesForm;
use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\Experiments;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use App\Models\Sequences;
use App\Services\SequencesService;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

#[Title('Sequences Index')]
class SequencesIndex extends PlainComponent
{
    use ExportsTable;
    use WithColumnSorting;
    use WithPagination;

    public SequencesForm $form;

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
            'sequence_code' => 'code',
            'accession_number' => 'accession_number',
            'sub_project' => fn ($q, $dir) => $q->orderBy($this->subProjectCodeSortSubquery($q->getModel()), $dir),
            'length' => 'length',
            'method' => 'method',
            'instrument' => 'instrument',
            'date_sequenced' => 'date_sequenced',
            'sequenced_by' => fn ($q, $dir) => $this->orderByRelation($q, ['people'], 'first_name', $dir),
            'sequenced_at' => fn ($q, $dir) => $this->orderByRelation($q, ['laboratories'], 'name', $dir),
        ];
    }

    public $codeFilter;

    public $accessionFilter;

    public $experimentFilter;

    public $originalContentFilter;

    public $startLength;

    public $endLength;

    public $pathogenFilter;

    public $methodFilter;

    public $instrumentFilter;

    public $startDate;

    public $endDate;

    public $peopleFilter;

    public $laboratoriesFilter;

    public $projectsFilter;

    public $subProjectCodeFilter;

    // Human original-sample filters
    public $patientCodeFilter;

    public $humanSampleTypeFilter;

    public $humanOccupationFilter;

    public $humanSexFilter;

    public $humanCountryFilter;

    public $humanEthnicityFilter;

    public $humanMinAge;

    public $humanMaxAge;

    // Animal original-sample filters
    public $animalCodeFilter;

    public $animalSpeciesFilter;

    public $animalSampleTypeFilter;

    public $animalSexFilter;

    public $animalAgeFilter;

    // Environment original-sample filters
    public $environmentSampleTypeFilter;

    // Parasite original-sample filters
    public $parasiteSpeciesFilter;

    public $parasiteStageFilter;

    public $parasiteSexFilter;

    public $parasiteStateFilter;

    public $parasiteSampleTypeFilter;

    // Culture original-sample filters
    public $cultureCodeFilter;

    public $cultureMediumFilter;

    public $cultureTypeFilter;

    // Pool original-sample filters
    public $poolCodeFilter;

    public array $selectedSequences = [];

    protected $projectId;

    public function mount(?string $type = null): void
    {
        if ($type === null) {
            return;
        }

        $this->selectedTable = match ($type) {
            'human' => 'human_sequences_table',
            'animal' => 'animal_sequences_table',
            'environment' => 'environment_sequences_table',
            'parasite' => 'parasite_sequences_table',
            'culture' => 'culture_sequences_table',
            'pool' => 'pool_sequences_table',
            default => 'sequences_table',
        };
    }

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

    public function updateField($sequenceId, $field, $value)
    {
        $sequence = Sequences::find($sequenceId);
        if (! $sequence || ! $this->userCanMutateOwnedRecord((int) $sequence->people_id, 'nucleic_acids')) {
            $this->dispatch('show-error', message: 'You are not allowed to edit this sequence.');

            return;
        }

        try {
            $this->form->updateField($sequenceId, $field, $value);
            $fieldLabel = ucwords(str_replace('_', ' ', (string) $field));
            $this->dispatch('show-success', message: $fieldLabel.' updated successfully.');
        } catch (\Throwable $e) {
            $this->dispatch('show-error', message: 'Update failed: '.$e->getMessage());
        }
    }

    public function delete(Sequences $sequence)
    {
        if (! $this->userCanMutateOwnedRecord((int) $sequence->people_id, 'nucleic_acids')) {
            $this->dispatch('show-error', message: 'You are not allowed to delete this sequence.');

            return;
        }

        // Delete the FASTA file if it exists
        if ($sequence->fasta_path && Storage::disk('local')->exists($sequence->fasta_path)) {
            Storage::disk('local')->delete($sequence->fasta_path);
        }

        $sequence->delete();
        $this->form->refreshData();
        $this->dispatch('show-success', message: 'Sequence deleted successfully.');
    }

    public function deleteSelected(): void
    {
        $selectedIds = collect($this->selectedSequences)
            ->filter(fn ($checked): bool => (bool) $checked)
            ->keys()
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        if ($selectedIds->isEmpty()) {
            session()->flash('error', 'Please select at least one sequence.');

            return;
        }

        $sequences = Sequences::query()->whereIn('id', $selectedIds->all())->get();
        $deleted = 0;

        foreach ($sequences as $sequence) {
            if (! $this->userCanMutateOwnedRecord((int) $sequence->people_id, 'nucleic_acids')) {
                continue;
            }

            if ($sequence->fasta_path && Storage::disk('local')->exists($sequence->fasta_path)) {
                Storage::disk('local')->delete($sequence->fasta_path);
            }

            $sequence->delete();
            $deleted++;
        }

        $this->selectedSequences = [];

        session()->flash(
            $deleted > 0 ? 'message' : 'error',
            $deleted > 0 ? "{$deleted} selected sequence(s) deleted successfully." : 'No selected sequences could be deleted.'
        );
    }

    public $isEditing = false;

    public function toggleEditMode()
    {
        if (! $this->userCanWriteModule('nucleic_acids')) {
            return;
        }

        $this->isEditing = ! $this->isEditing;
    }

    public string $selectedTable = 'sequences_table';

    public function toggleTableMode()
    {
        $this->selectedTable = ! $this->selectedTable;
    }

    public function updating($field)
    {
        if (is_string($field) && str_starts_with($field, 'selectedSequences')) {
            return;
        }

        $this->resetPage('articles-page');
    }

    protected function applyFilters($query)
    {
        if ($this->codeFilter) {
            $query->where('code', 'like', '%'.$this->codeFilter.'%');
        }
        if ($this->accessionFilter) {
            $query->where('accession_number', 'like', '%'.$this->accessionFilter.'%');
        }
        if ($this->experimentFilter) {
            $query->whereHas('nucleic_acids', function ($q) {
                $q->whereHasMorph('nucleic_content', [Experiments::class], function ($Q) {
                    $Q->where('code', 'like', '%'.$this->experimentFilter.'%');
                });
            });
        }
        if ($this->originalContentFilter) {
            $value = '%'.$this->originalContentFilter.'%';
            $sourceClasses = [
                HumanSamples::class,
                AnimalSamples::class,
                EnvironmentSamples::class,
                ParasiteSamples::class,
                Cultures::class,
                Pools::class,
            ];

            $query->whereHas('nucleic_acids', function ($naQuery) use ($value, $sourceClasses): void {
                $naQuery->where(function ($sourceQuery) use ($value, $sourceClasses): void {
                    $sourceQuery
                        ->whereHasMorph('nucleic_content', $sourceClasses, function ($directSourceQuery) use ($value): void {
                            $directSourceQuery->where('code', 'like', $value);
                        })
                        ->orWhereHas('tubes', function ($tubeQuery) use ($value): void {
                            $tubeQuery->where('alias_code', 'like', $value);
                        })
                        ->orWhereHasMorph('nucleic_content', [Experiments::class], function ($experimentQuery) use ($value, $sourceClasses): void {
                            $experimentQuery->whereHasMorph('experiments_content', [NucleicAcids::class], function ($originalNucleicQuery) use ($value, $sourceClasses): void {
                                $originalNucleicQuery
                                    ->where('code', 'like', $value)
                                    ->orWhereHas('tubes', function ($tubeQuery) use ($value): void {
                                        $tubeQuery->where('alias_code', 'like', $value);
                                    })
                                    ->orWhereHasMorph('nucleic_content', $sourceClasses, function ($originSourceQuery) use ($value): void {
                                        $originSourceQuery->where('code', 'like', $value);
                                    });
                            });
                        });
                });
            });
        }
        if ($this->startLength && $this->endLength) {
            $query->whereBetween('length', [$this->startLength, $this->endLength]);
        } elseif ($this->startLength) {
            $query->where('length', '>=', $this->startLength);
        } elseif ($this->endLength) {
            $query->where('length', '<=', $this->endLength);
        }
        if ($this->pathogenFilter) {
            $query->whereHas('nucleic_acids', function ($q) {
                $q->whereHasMorph('nucleic_content', [Experiments::class], function ($Q) {
                    $Q->whereHas('pathogens', function ($qu) {
                        $qu->where('species', 'like', '%'.$this->pathogenFilter.'%');
                    });
                });
            });
        }
        if ($this->methodFilter) {
            $query->where('method', 'like', '%'.$this->methodFilter.'%');
        }
        if ($this->instrumentFilter) {
            $query->where('instrument', 'like', '%'.$this->instrumentFilter.'%');
        }
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('date_sequenced', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('date_sequenced', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->where('date_sequenced', '<=', $this->endDate);
        }
        if ($this->peopleFilter) {
            $query->whereHas('people', function ($q) {
                $value = '%'.$this->peopleFilter.'%';
                $q->where('first_name', 'like', $value)
                    ->orWhere('last_name', 'like', $value);
            });
        }
        if ($this->laboratoriesFilter) {
            $query->whereHas('laboratories', function ($q) {
                $q->where('name', 'like', '%'.$this->laboratoriesFilter.'%');
            });
        }
        if ($this->projectsFilter) {
            $query->whereHas('projects', function ($q) {
                $q->where('code', 'like', '%'.$this->projectsFilter.'%');
            });
        }
        if ($this->subProjectCodeFilter) {
            $query->whereHas('subProjectAssignment.subProject', function ($q) {
                $q->where('code', 'like', '%'.$this->subProjectCodeFilter.'%');
            });
        }

        // Original-sample-specific filters (only apply when on that tab)
        if ($this->selectedTable === 'human_sequences_table') {
            $this->applyHumanOriginalSampleFilters($query);
        } elseif ($this->selectedTable === 'animal_sequences_table') {
            $this->applyAnimalOriginalSampleFilters($query);
        } elseif ($this->selectedTable === 'environment_sequences_table') {
            $this->applyEnvironmentOriginalSampleFilters($query);
        } elseif ($this->selectedTable === 'parasite_sequences_table') {
            $this->applyParasiteOriginalSampleFilters($query);
        } elseif ($this->selectedTable === 'culture_sequences_table') {
            $this->applyCultureOriginalSampleFilters($query);
        } elseif ($this->selectedTable === 'pool_sequences_table') {
            $this->applyPoolOriginalSampleFilters($query);
        }

        return $query;
    }

    /**
     * Apply constraints to the original sample, regardless of whether the sequenced nucleic acid
     * points directly to the sample, or to an Experiment -> (original nucleic acid) -> sample.
     */
    private function whereOriginalSample(Builder $query, string $sampleClass, Closure $constraints): Builder
    {
        return $query->whereHas('nucleic_acids', function ($na) use ($sampleClass, $constraints) {
            $na->where(function ($q) use ($sampleClass, $constraints) {
                // Case A: nucleic acid extracted directly from the sample
                $q->whereHasMorph('nucleic_content', [$sampleClass], function ($sample) use ($constraints) {
                    $constraints($sample);
                })
                    // Case B: nucleic acid extracted from an experiment run on an "original" nucleic acid
                    ->orWhereHasMorph('nucleic_content', [Experiments::class], function ($exp) use ($sampleClass, $constraints) {
                        $exp->whereHasMorph('experiments_content', [NucleicAcids::class], function ($originalNucleic) use ($sampleClass, $constraints) {
                            $originalNucleic->whereHasMorph('nucleic_content', [$sampleClass], function ($sample) use ($constraints) {
                                $constraints($sample);
                            });
                        });
                    });
            });
        });
    }

    private function applyHumanOriginalSampleFilters(Builder $query): void
    {
        $this->whereOriginalSample($query, HumanSamples::class, function ($sample) {
            if ($this->patientCodeFilter) {
                $sample->whereHas('humans', fn ($q) => $q->where('code', 'like', '%'.$this->patientCodeFilter.'%'));
            }
            if ($this->humanSampleTypeFilter) {
                $sample->whereHas('sample_types', fn ($q) => $q->where('name', 'like', '%'.$this->humanSampleTypeFilter.'%'));
            }
            if ($this->humanOccupationFilter) {
                $sample->whereHas('humans', fn ($q) => $q->where('occupation', 'like', '%'.$this->humanOccupationFilter.'%'));
            }
            if ($this->humanSexFilter) {
                $sample->whereHas('humans', fn ($q) => $q->where('sex', 'like', '%'.$this->humanSexFilter.'%'));
            }
            if ($this->humanEthnicityFilter) {
                $sample->whereHas('humans', fn ($q) => $q->where('ethnicity', 'like', '%'.$this->humanEthnicityFilter.'%'));
            }
            if ($this->humanCountryFilter) {
                $sample->whereHas('humans.countries', fn ($q) => $q->where('name', 'like', '%'.$this->humanCountryFilter.'%'));
            }
            if ($this->humanMinAge || $this->humanMaxAge) {
                $today = now();
                $minAge = is_numeric($this->humanMinAge) ? (int) $this->humanMinAge : null;
                $maxAge = is_numeric($this->humanMaxAge) ? (int) $this->humanMaxAge : null;
                $sample->whereHas('humans', function ($q) use ($today, $minAge, $maxAge) {
                    if ($minAge !== null) {
                        $q->whereDate('date_of_birth', '<=', $today->copy()->subYears($minAge));
                    }
                    if ($maxAge !== null) {
                        $q->whereDate('date_of_birth', '>=', $today->copy()->subYears($maxAge));
                    }
                });
            }
        });
    }

    private function applyAnimalOriginalSampleFilters(Builder $query): void
    {
        $this->whereOriginalSample($query, AnimalSamples::class, function ($sample) {
            if ($this->animalCodeFilter) {
                $sample->whereHas('animals', fn ($q) => $q->where('code', 'like', '%'.$this->animalCodeFilter.'%'));
            }
            if ($this->animalSpeciesFilter) {
                $sample->whereHas('animals.animal_species', fn ($q) => $q->where('name_common', 'like', '%'.$this->animalSpeciesFilter.'%'));
            }
            if ($this->animalSampleTypeFilter) {
                $sample->whereHas('sample_types', fn ($q) => $q->where('name', 'like', '%'.$this->animalSampleTypeFilter.'%'));
            }
            if ($this->animalSexFilter) {
                $sample->whereHas('animals', fn ($q) => $q->where('sex', 'like', '%'.$this->animalSexFilter.'%'));
            }
            if ($this->animalAgeFilter) {
                $sample->whereHas('animals', fn ($q) => $q->where('age', 'like', '%'.$this->animalAgeFilter.'%'));
            }
        });
    }

    private function applyEnvironmentOriginalSampleFilters(Builder $query): void
    {
        $this->whereOriginalSample($query, EnvironmentSamples::class, function ($sample) {
            if ($this->environmentSampleTypeFilter) {
                $sample->whereHas('environment_sample_types', fn ($q) => $q->where('name', 'like', '%'.$this->environmentSampleTypeFilter.'%'));
            }
        });
    }

    private function applyParasiteOriginalSampleFilters(Builder $query): void
    {
        $this->whereOriginalSample($query, ParasiteSamples::class, function ($sample) {
            if ($this->parasiteSampleTypeFilter) {
                $sample->whereHas('parasite_sample_types', fn ($q) => $q->where('name', 'like', '%'.$this->parasiteSampleTypeFilter.'%'));
            }
            if ($this->parasiteSpeciesFilter) {
                $sample->whereHas('parasites.parasite_species', fn ($q) => $q->where('name_scientific', 'like', '%'.$this->parasiteSpeciesFilter.'%'));
            }
            if ($this->parasiteStageFilter) {
                $sample->whereHas('parasites', fn ($q) => $q->where('stage', 'like', '%'.$this->parasiteStageFilter.'%'));
            }
            if ($this->parasiteSexFilter) {
                $sample->whereHas('parasites', fn ($q) => $q->where('sex', 'like', '%'.$this->parasiteSexFilter.'%'));
            }
            if ($this->parasiteStateFilter) {
                $sample->whereHas('parasites', fn ($q) => $q->where('state', 'like', '%'.$this->parasiteStateFilter.'%'));
            }
        });
    }

    private function applyCultureOriginalSampleFilters(Builder $query): void
    {
        $this->whereOriginalSample($query, Cultures::class, function ($sample) {
            if ($this->cultureCodeFilter) {
                $sample->where('code', 'like', '%'.$this->cultureCodeFilter.'%');
            }
            if ($this->cultureMediumFilter) {
                $sample->where('medium', 'like', '%'.$this->cultureMediumFilter.'%');
            }
            if ($this->cultureTypeFilter) {
                $sample->where('type', 'like', '%'.$this->cultureTypeFilter.'%');
            }
        });
    }

    private function applyPoolOriginalSampleFilters(Builder $query): void
    {
        $this->whereOriginalSample($query, Pools::class, function ($sample) {
            if ($this->poolCodeFilter) {
                $sample->where('code', 'like', '%'.$this->poolCodeFilter.'%');
            }
        });
    }

    protected function applySelectedContentTypeConstraint(Builder $query): Builder
    {
        $sourceClass = match ($this->selectedTable) {
            'human_sequences_table' => HumanSamples::class,
            'animal_sequences_table' => AnimalSamples::class,
            'environment_sequences_table' => EnvironmentSamples::class,
            'parasite_sequences_table' => ParasiteSamples::class,
            'culture_sequences_table' => Cultures::class,
            'pool_sequences_table' => Pools::class,
            default => null,
        };

        if ($sourceClass === null) {
            return $query;
        }

        return $query->whereHas('nucleic_acids', function ($na) use ($sourceClass) {
            $na->where(function ($q) use ($sourceClass) {
                // Case A: sample -> nucleic acid -> sequence
                $q->whereHasMorph('nucleic_content', [$sourceClass]);

                // Case B: sample -> nucleic acid -> experiment -> nucleic acid -> sequence
                $q->orWhereHasMorph('nucleic_content', [Experiments::class], function ($exp) use ($sourceClass) {
                    $exp->whereHasMorph('experiments_content', [NucleicAcids::class], function ($originalNucleic) use ($sourceClass) {
                        $originalNucleic->whereHasMorph('nucleic_content', [$sourceClass]);
                    });
                });
            });
        });
    }

    public function export(string $format = 'csv')
    {
        $fileName = match ($this->selectedTable) {
            'human_sequences_table' => 'sequences_human.csv',
            'animal_sequences_table' => 'sequences_animal.csv',
            'environment_sequences_table' => 'sequences_environment.csv',
            'parasite_sequences_table' => 'sequences_parasite.csv',
            'culture_sequences_table' => 'sequences_culture.csv',
            'pool_sequences_table' => 'sequences_pool.csv',
            default => 'sequences_all.csv',
        };

        $query = Sequences::query()->with([
            'nucleic_acids.nucleic_content' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    Experiments::class => [
                        'pathogens',
                        'experiments_content' => function (MorphTo $morphTo): void {
                            $morphTo->morphWith([
                                NucleicAcids::class => [
                                    'tubes',
                                    'nucleic_content' => function (MorphTo $morphTo): void {
                                        $morphTo->morphWith([
                                            HumanSamples::class => [
                                                'humans',
                                                'sample_types',
                                                'sampling_sites',
                                            ],
                                            AnimalSamples::class => [
                                                'animals.animal_species',
                                                'sample_types',
                                                'sampling_sites',
                                            ],
                                            EnvironmentSamples::class => [
                                                'environment_sample_types',
                                                'sampling_sites',
                                            ],
                                            ParasiteSamples::class => [
                                                'parasites.parasite_species',
                                                'parasite_sample_types',
                                            ],
                                            Cultures::class => [
                                                'cultures_content',
                                            ],
                                            Pools::class => [
                                                'pool_contents',
                                            ],
                                        ]);
                                    },
                                ],
                            ]);
                        },
                    ],
                    HumanSamples::class => [
                        'humans.countries',
                        'sample_types',
                        'sampling_sites',
                    ],
                    AnimalSamples::class => [
                        'animals.animal_species',
                        'sample_types',
                        'sampling_sites',
                    ],
                    EnvironmentSamples::class => [
                        'environment_sample_types',
                        'sampling_sites',
                    ],
                    ParasiteSamples::class => [
                        'parasites.parasite_species',
                        'parasite_sample_types',
                    ],
                    Cultures::class => [
                        'cultures_content',
                    ],
                    Pools::class => [
                        'pool_contents',
                    ],
                ]);
            },
            'nucleic_acids.tubes',
            'people',
            'laboratories',
            'projects',
            'subProjectAssignment.subProject',
        ]);

        $query = $this->applySelectedContentTypeConstraint($query);

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            // In guest mode, show only public sequences
            $query->where('is_private', false);
        } else {
            // Project mode - show sequences from the selected project
            $query->where('projects_id', $this->projectId);
        }

        $query = $this->applyFilters($query);
        $this->applySorting($query, $this->sortMap(), ['created_at', 'desc']);

        $sequences = $query->get();

        $baseHeader = ['Sequence code', 'Accession number', 'Experiment code', 'Original content type', 'Original content code', 'Sub-project'];
        $detailsHeader = match ($this->selectedTable) {
            'human_sequences_table' => ['Patient code', 'Sample type', 'Occupation', 'Sex', 'Age', 'Country', 'Ethnicity'],
            'animal_sequences_table' => ['Animal code', 'Animal species', 'Sample type', 'Sex', 'Age'],
            'environment_sequences_table' => ['Sample type'],
            'parasite_sequences_table' => ['Parasite species', 'Stage', 'Sex', 'State', 'Sample type'],
            'culture_sequences_table' => ['Culture code', 'Medium', 'Culture type'],
            'pool_sequences_table' => ['Pool code', 'Nr pooled'],
            default => ['Original content details'],
        };

        $tailHeader = ['Length (nt)', 'Target pathogen', 'Method', 'Instrument', 'Date sequenced', 'Sequenced by', 'Sequenced at', 'Project'];

        $headers = array_merge($baseHeader, $detailsHeader, $tailHeader);

        $rows = $sequences->map(function ($sequence) {
            $nucleic = $sequence->nucleic_acids;
            $experiment = ($nucleic && $nucleic->nucleic_content instanceof Experiments) ? $nucleic->nucleic_content : null;

            $originalNucleic = ($experiment && $experiment->experiments_content instanceof NucleicAcids) ? $experiment->experiments_content : null;
            $source = $originalNucleic?->nucleic_content ?? $nucleic?->nucleic_content;

            $sourceType = $source ? class_basename($source) : 'N/A';
            $sourceCode = $source->code ?? 'N/A';

            $detailCells = match ($this->selectedTable) {
                'human_sequences_table' => (function () use ($source) {
                    $human = $source instanceof HumanSamples ? $source->humans : null;
                    $age = $human?->date_of_birth ? Carbon::parse($human->date_of_birth)->age : null;

                    return [
                        $human?->code ?? 'N/A',
                        $source instanceof HumanSamples ? ($source->sample_types?->name ?? 'N/A') : 'N/A',
                        $human?->occupation ?? 'N/A',
                        $human?->sex ?? 'N/A',
                        $age !== null ? (string) $age : 'N/A',
                        $human?->countries?->name ?? 'N/A',
                        $human?->ethnicity ?? 'N/A',
                    ];
                })(),
                'animal_sequences_table' => (function () use ($source) {
                    $animal = $source instanceof AnimalSamples ? $source->animals : null;

                    return [
                        $animal?->code ?? 'N/A',
                        $animal?->animal_species?->name_common ?? 'N/A',
                        $source instanceof AnimalSamples ? ($source->sample_types?->name ?? 'N/A') : 'N/A',
                        $animal?->sex ?? 'N/A',
                        $animal?->age ?? 'N/A',
                    ];
                })(),
                'environment_sequences_table' => [
                    $source instanceof EnvironmentSamples ? ($source->environment_sample_types?->name ?? 'N/A') : 'N/A',
                ],
                'parasite_sequences_table' => (function () use ($source) {
                    $parasite = $source instanceof ParasiteSamples ? $source->parasites : null;

                    return [
                        $parasite?->parasite_species?->name_scientific ?? 'N/A',
                        $parasite?->stage ?? 'N/A',
                        $parasite?->sex ?? 'N/A',
                        $parasite?->state ?? 'N/A',
                        $source instanceof ParasiteSamples ? ($source->parasite_sample_types?->name ?? 'N/A') : 'N/A',
                    ];
                })(),
                'culture_sequences_table' => [
                    $source instanceof Cultures ? ($source->code ?? 'N/A') : 'N/A',
                    $source instanceof Cultures ? ($source->medium ?? 'N/A') : 'N/A',
                    $source instanceof Cultures ? ($source->type ?? 'N/A') : 'N/A',
                ],
                'pool_sequences_table' => [
                    $source instanceof Pools ? ($source->code ?? 'N/A') : 'N/A',
                    $source instanceof Pools ? (string) ($source->nr_pooled ?? 'N/A') : 'N/A',
                ],
                default => (function () use ($source) {
                    if (! $source) {
                        return ['N/A'];
                    }

                    $details = match (class_basename($source)) {
                        'HumanSamples' => trim(implode(' | ', array_filter([
                            $source->humans?->code ?? null,
                            $source->sample_types?->name ?? null,
                        ]))),
                        'AnimalSamples' => trim(implode(' | ', array_filter([
                            $source->animals?->code ?? null,
                            $source->animals?->animal_species?->name_common ?? null,
                            $source->sample_types?->name ?? null,
                        ]))),
                        'EnvironmentSamples' => $source->environment_sample_types?->name ?? '',
                        'ParasiteSamples' => trim(implode(' | ', array_filter([
                            $source->parasites?->parasite_species?->name_scientific ?? null,
                            $source->parasites?->stage ?? null,
                            $source->parasites?->sex ?? null,
                        ]))),
                        'Cultures' => trim(implode(' | ', array_filter([
                            $source->code ?? null,
                            $source->medium ?? null,
                            $source->type ?? null,
                        ]))),
                        'Pools' => trim(implode(' | ', array_filter([
                            $source->code ?? null,
                            $source->nr_pooled ?? null,
                        ]))),
                        default => '',
                    };

                    return [$details ?: 'N/A'];
                })(),
            };

            $row = array_merge(
                [
                    $sequence->code,
                    $sequence->accession_number,
                    $experiment?->code ?? 'N/A',
                    $sourceType,
                    $sourceCode,
                    data_get($sequence, 'subProjectAssignment.subProject.code') ?? 'N/A',
                ],
                $detailCells,
                [
                    $sequence->length,
                    $experiment?->pathogens?->species ?? 'N/A',
                    $sequence->method,
                    $sequence->instrument,
                    $sequence->date_sequenced,
                    trim(($sequence->people->first_name ?? '').' '.($sequence->people->last_name ?? '')) ?: 'N/A',
                    $sequence->laboratories->name ?? 'N/A',
                    $sequence->projects->code ?? 'N/A',
                ]
            );

            return $row;
        });

        return $this->exportTable(Str::replaceLast('.csv', '', $fileName), $headers, $rows, $format);
    }

    public function render()
    {
        $service = app(SequencesService::class);

        $additionalData = $service->assign();

        $query = Sequences::query()->with([
            'nucleic_acids.nucleic_content' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    Experiments::class => [
                        'pathogens',
                        'experiments_content' => function (MorphTo $morphTo): void {
                            $morphTo->morphWith([
                                NucleicAcids::class => [
                                    'tubes',
                                    'nucleic_content' => function (MorphTo $morphTo): void {
                                        $morphTo->morphWith([
                                            HumanSamples::class => [
                                                'humans',
                                                'sample_types',
                                                'sampling_sites',
                                            ],
                                            AnimalSamples::class => [
                                                'animals.animal_species',
                                                'sample_types',
                                                'sampling_sites',
                                            ],
                                            EnvironmentSamples::class => [
                                                'environment_sample_types',
                                                'sampling_sites',
                                            ],
                                            ParasiteSamples::class => [
                                                'parasites.parasite_species',
                                                'parasite_sample_types',
                                            ],
                                            Cultures::class => [
                                                'cultures_content',
                                            ],
                                            Pools::class => [
                                                'pool_contents',
                                            ],
                                        ]);
                                    },
                                ],
                            ]);
                        },
                    ],
                    HumanSamples::class => [
                        'humans.countries',
                        'sample_types',
                        'sampling_sites',
                    ],
                    AnimalSamples::class => [
                        'animals.animal_species',
                        'sample_types',
                        'sampling_sites',
                    ],
                    EnvironmentSamples::class => [
                        'environment_sample_types',
                        'sampling_sites',
                    ],
                    ParasiteSamples::class => [
                        'parasites.parasite_species',
                        'parasite_sample_types',
                    ],
                    Cultures::class => [
                        'cultures_content',
                    ],
                    Pools::class => [
                        'pool_contents',
                    ],
                ]);
            },
            'nucleic_acids.tubes',
            'people',
            'laboratories',
            'projects',
            'subProjectAssignment.subProject',
        ]);

        $query = $this->applySelectedContentTypeConstraint($query);

        // Handle guest mode vs project mode
        if ($this->isGuestMode()) {
            // In guest mode, show only public sequences
            $query->where('is_private', false);
        } else {
            // Project mode - show sequences from the selected project
            $query->where('projects_id', $this->projectId);
        }

        $query = $this->applyFilters($query);
        $this->applySorting($query, $this->sortMap(), ['created_at', 'desc']);

        $sequences = $query->paginate($this->perPage, pageName: 'articles-page');

        $canEdit = ! $this->isGuestMode() && $this->userCanWriteModule('nucleic_acids');

        $viewData = array_merge($additionalData, [
            'sequences' => $sequences,
            'isEditing' => $this->isEditing,
            'selectedTable' => $this->selectedTable,
            'canEdit' => $canEdit,
        ]);

        return view('livewire.sequences-index', $viewData);
    }
}
