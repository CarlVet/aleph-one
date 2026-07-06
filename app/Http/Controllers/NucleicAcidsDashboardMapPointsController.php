<?php

namespace App\Http\Controllers;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\PoolContents;
use App\Models\Pools;
use App\Models\SamplingSites;
use App\Services\PrimarySampleReachability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NucleicAcidsDashboardMapPointsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $projectId = session('selected_project_id');
        $isGuestMode = $projectId === null;

        $cursor = (int) $request->query('cursor', 0);
        $limit = (int) $request->query('limit', 1500);
        $limit = max(100, min($limit, 2000));

        $rows = $this->filteredQuery($request, $isGuestMode, $projectId)
            ->select([
                'nucleic_acids.id',
                'nucleic_acids.code',
                'nucleic_acids.type',
                'nucleic_acids.nucleic_content_type',
                'nucleic_acids.nucleic_content_id',
                'protocols.name as protocol',
                DB::raw($this->peopleNameSql().' as extracted_by'),
                'content_humans.ethnicity as human_ethnicity',
                'content_humans.occupation as human_occupation',
                'content_countries.name as human_country',
                'content_animal_species.name_common as animal_species',
                'content_animals.sex as animal_sex',
                'content_animals.age as animal_age',
                DB::raw('COALESCE(content_parasite_species.name_scientific, pool_parasite_attrs.pool_parasite_species) as parasite_species'),
                DB::raw('COALESCE(content_parasites.stage, pool_parasite_attrs.pool_parasite_stage) as parasite_stage'),
                DB::raw('COALESCE(content_parasites.sex, pool_parasite_attrs.pool_parasite_sex) as parasite_sex'),
                'content_cultures.type as culture_type',
                'content_cultures.medium as culture_medium',
                'content_pools.nr_pooled as pool_nr_pooled',
            ])
            ->where('nucleic_acids.id', '>', $cursor)
            ->orderBy('nucleic_acids.id')
            ->limit($limit)
            ->get();

        $samplingSites = SamplingSites::query()
            ->get(['id', 'name', 'latitude', 'longitude'])
            ->keyBy('id');

        $points = $this->rowsToPoints($rows, $samplingSites);

        $nextCursor = null;
        if ($rows->count() === $limit) {
            $nextCursor = (int) $rows->last()->id;
        }

        return response()->json([
            'points' => $points,
            'next_cursor' => $nextCursor,
        ]);
    }

    public function filteredQuery(Request $request, bool $isGuestMode, ?int $projectId)
    {
        $poolParasiteAttributesSub = DB::table('pool_contents as map_pool_contents')
            ->join('parasite_samples as map_parasite_samples', 'map_parasite_samples.id', '=', 'map_pool_contents.samples_id')
            ->join('parasites as map_parasites', 'map_parasites.id', '=', 'map_parasite_samples.parasites_id')
            ->leftJoin('parasite_species as map_parasite_species', 'map_parasite_species.id', '=', 'map_parasites.parasite_species_id')
            ->where(function ($q) {
                $q->whereIn('map_pool_contents.samples_type', $this->typeVariants(ParasiteSamples::class))
                    ->orWhereRaw('LOWER(map_pool_contents.samples_type) LIKE ?', ['%parasite%']);
            })
            ->select(
                'map_pool_contents.pools_id',
                DB::raw('MIN(map_parasite_species.name_scientific) as pool_parasite_species'),
                DB::raw('MIN(map_parasites.stage) as pool_parasite_stage'),
                DB::raw('MIN(map_parasites.sex) as pool_parasite_sex'),
            )
            ->groupBy('map_pool_contents.pools_id');

        $query = NucleicAcids::query()
            ->leftJoin('protocols', 'nucleic_acids.protocols_id', '=', 'protocols.id')
            ->leftJoin('laboratories', 'nucleic_acids.laboratories_id', '=', 'laboratories.id')
            ->leftJoin('people', 'nucleic_acids.people_id', '=', 'people.id')
            ->leftJoin('human_samples as content_human_samples', function ($join) {
                $join->on('content_human_samples.id', '=', 'nucleic_acids.nucleic_content_id')
                    ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(HumanSamples::class));
            })
            ->leftJoin('humans as content_humans', 'content_humans.id', '=', 'content_human_samples.humans_id')
            ->leftJoin('countries as content_countries', 'content_countries.id', '=', 'content_humans.countries_id')
            ->leftJoin('animal_samples as content_animal_samples', function ($join) {
                $join->on('content_animal_samples.id', '=', 'nucleic_acids.nucleic_content_id')
                    ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(AnimalSamples::class));
            })
            ->leftJoin('animals as content_animals', 'content_animals.id', '=', 'content_animal_samples.animals_id')
            ->leftJoin('animal_species as content_animal_species', 'content_animal_species.id', '=', 'content_animals.animal_species_id')
            ->leftJoin('parasite_samples as content_parasite_samples', function ($join) {
                $join->on('content_parasite_samples.id', '=', 'nucleic_acids.nucleic_content_id')
                    ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class));
            })
            ->leftJoin('parasites as content_parasites', 'content_parasites.id', '=', 'content_parasite_samples.parasites_id')
            ->leftJoin('parasite_species as content_parasite_species', 'content_parasite_species.id', '=', 'content_parasites.parasite_species_id')
            ->leftJoinSub($poolParasiteAttributesSub, 'pool_parasite_attrs', function ($join) {
                $join->on('pool_parasite_attrs.pools_id', '=', 'nucleic_acids.nucleic_content_id')
                    ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Pools::class));
            })
            ->leftJoin('cultures as content_cultures', function ($join) {
                $join->on('content_cultures.id', '=', 'nucleic_acids.nucleic_content_id')
                    ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class));
            })
            ->leftJoin('pools as content_pools', function ($join) {
                $join->on('content_pools.id', '=', 'nucleic_acids.nucleic_content_id')
                    ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Pools::class));
            });

        if ($isGuestMode) {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'nucleic_acids.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants(NucleicAcids::class))
                    ->where('tubes.is_private', false);
            });
        } else {
            $query->where(function ($w) use ($projectId) {
                $w->where('nucleic_acids.projects_id', $projectId)
                    ->orWhereExists(function ($sub) use ($projectId) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', 'nucleic_acids.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants(NucleicAcids::class))
                            ->where('tubes.projects_id', $projectId);
                    });
            });
        }

        $nucleicTypeFilter = (string) $request->query('nucleicTypeFilter', '');
        if ($nucleicTypeFilter !== '') {
            $query->where('nucleic_acids.type', $nucleicTypeFilter);
        }

        $sourceTypeFilter = (string) $request->query('sourceTypeFilter', 'all');
        if ($sourceTypeFilter !== '' && $sourceTypeFilter !== 'all') {
            $sourceType = match ($sourceTypeFilter) {
                'human' => HumanSamples::class,
                'animal' => AnimalSamples::class,
                'environment' => EnvironmentSamples::class,
                'parasite' => ParasiteSamples::class,
                'culture' => Cultures::class,
                'pool' => Pools::class,
                default => null,
            };

            if ($sourceType) {
                $query->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants($sourceType));
            }
        }

        $subProjectFilter = (string) $request->query('subProjectFilter', '');
        if ($subProjectFilter !== '') {
            $query->whereExists(function ($sub) use ($subProjectFilter) {
                $sub->select(DB::raw(1))
                    ->from('sub_project_assignments')
                    ->join('sub_projects', 'sub_projects.id', '=', 'sub_project_assignments.sub_project_id')
                    ->whereColumn('sub_project_assignments.assignable_id', 'nucleic_acids.id')
                    ->whereIn('sub_project_assignments.assignable_type', $this->typeVariants(NucleicAcids::class))
                    ->where('sub_projects.code', $subProjectFilter);
            });
        }

        $animalSpeciesFilter = (string) $request->query('animalSpeciesFilter', '');
        if ($sourceTypeFilter === 'animal' && $animalSpeciesFilter !== '') {
            $query->whereExists(function ($sub) use ($animalSpeciesFilter) {
                $sub->select(DB::raw(1))
                    ->from('animal_samples')
                    ->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
                    ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
                    ->whereColumn('animal_samples.id', 'nucleic_acids.nucleic_content_id')
                    ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(AnimalSamples::class))
                    ->where('animal_species.name_common', $animalSpeciesFilter);
            });
        }

        $parasiteSpeciesFilter = (string) $request->query('parasiteSpeciesFilter', '');
        $parasiteOriginTypeFilter = (string) $request->query('parasiteOriginTypeFilter', 'all');
        $parasiteOriginAnimalSpeciesFilter = (string) $request->query('parasiteOriginAnimalSpeciesFilter', '');
        if ($sourceTypeFilter === 'parasite' && ($parasiteSpeciesFilter !== '' || $parasiteOriginTypeFilter !== 'all' || $parasiteOriginAnimalSpeciesFilter !== '')) {
            $originType = match ($parasiteOriginTypeFilter) {
                'human' => HumanSamples::class,
                'animal' => AnimalSamples::class,
                'environment' => EnvironmentSamples::class,
                'parasite' => ParasiteSamples::class,
                'culture' => Cultures::class,
                'pool' => Pools::class,
                'nucleic' => NucleicAcids::class,
                default => null,
            };

            $query->whereExists(function ($sub) use ($originType, $parasiteSpeciesFilter, $parasiteOriginAnimalSpeciesFilter) {
                $sub->select(DB::raw(1))
                    ->from('parasite_samples')
                    ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                    ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
                    ->leftJoin('animal_samples', function ($join) {
                        $join->on('parasites.parasites_origin_id', '=', 'animal_samples.id')
                            ->whereIn('parasites.parasites_origin_type', $this->typeVariants(AnimalSamples::class));
                    })
                    ->leftJoin('animals', 'animal_samples.animals_id', '=', 'animals.id')
                    ->leftJoin('animal_species as origin_animal_species', 'animals.animal_species_id', '=', 'origin_animal_species.id')
                    ->whereColumn('parasite_samples.id', 'nucleic_acids.nucleic_content_id')
                    ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class));

                if ($parasiteSpeciesFilter !== '') {
                    $sub->where('parasite_species.name_scientific', $parasiteSpeciesFilter);
                }

                if ($originType) {
                    $sub->whereIn('parasites.parasites_origin_type', $this->typeVariants($originType));
                }

                if ($parasiteOriginAnimalSpeciesFilter !== '') {
                    $sub->whereIn('parasites.parasites_origin_type', $this->typeVariants(AnimalSamples::class))
                        ->where('origin_animal_species.name_common', $parasiteOriginAnimalSpeciesFilter);
                }
            });
        }

        $tracePrimaryTypeFilter = (string) $request->query('tracePrimaryTypeFilter', 'all');
        $tracePrimaryAnimalSpeciesFilter = (string) $request->query('tracePrimaryAnimalSpeciesFilter', '');
        $tracePrimaryAnimalSexFilter = (string) $request->query('tracePrimaryAnimalSexFilter', '');
        $tracePrimaryAnimalAgeFilter = (string) $request->query('tracePrimaryAnimalAgeFilter', '');
        $tracePrimaryHumanEthnicityFilter = (string) $request->query('tracePrimaryHumanEthnicityFilter', '');
        $tracePrimaryHumanOccupationFilter = (string) $request->query('tracePrimaryHumanOccupationFilter', '');
        $tracePrimaryHumanCountryFilter = (string) $request->query('tracePrimaryHumanCountryFilter', '');
        $tracePrimaryParasiteSpeciesFilter = (string) $request->query('tracePrimaryParasiteSpeciesFilter', '');
        $tracePrimaryCultureTypeFilter = (string) $request->query('tracePrimaryCultureTypeFilter', '');
        $tracePrimaryCultureMediumFilter = (string) $request->query('tracePrimaryCultureMediumFilter', '');
        $tracePrimaryNucleicTypeFilter = (string) $request->query('tracePrimaryNucleicTypeFilter', '');
        $tracePrimaryPoolMinNrPooled = $request->query('tracePrimaryPoolMinNrPooled');
        $tracePrimaryPoolMaxNrPooled = $request->query('tracePrimaryPoolMaxNrPooled');
        $traceDeepPrimaryTypeFilter = (string) $request->query('traceDeepPrimaryTypeFilter', 'all');
        $traceDeepAnimalSpeciesFilter = (string) $request->query('traceDeepAnimalSpeciesFilter', '');
        $traceDeepAnimalSexFilter = (string) $request->query('traceDeepAnimalSexFilter', '');
        $traceDeepAnimalAgeFilter = (string) $request->query('traceDeepAnimalAgeFilter', '');
        $traceDeepHumanEthnicityFilter = (string) $request->query('traceDeepHumanEthnicityFilter', '');
        $traceDeepHumanOccupationFilter = (string) $request->query('traceDeepHumanOccupationFilter', '');
        $traceDeepHumanCountryFilter = (string) $request->query('traceDeepHumanCountryFilter', '');

        $hasDeepTrace = $traceDeepPrimaryTypeFilter !== 'all'
            || $traceDeepAnimalSpeciesFilter !== ''
            || $traceDeepAnimalSexFilter !== ''
            || $traceDeepAnimalAgeFilter !== ''
            || $traceDeepHumanEthnicityFilter !== ''
            || $traceDeepHumanOccupationFilter !== ''
            || $traceDeepHumanCountryFilter !== '';

        $hasAnyTrace = $tracePrimaryTypeFilter !== 'all'
            || $tracePrimaryAnimalSpeciesFilter !== ''
            || $tracePrimaryAnimalSexFilter !== ''
            || $tracePrimaryAnimalAgeFilter !== ''
            || $tracePrimaryHumanEthnicityFilter !== ''
            || $tracePrimaryHumanOccupationFilter !== ''
            || $tracePrimaryHumanCountryFilter !== ''
            || $tracePrimaryParasiteSpeciesFilter !== ''
            || $tracePrimaryCultureTypeFilter !== ''
            || $tracePrimaryCultureMediumFilter !== ''
            || $tracePrimaryNucleicTypeFilter !== ''
            || $tracePrimaryPoolMinNrPooled !== null
            || $tracePrimaryPoolMaxNrPooled !== null
            || $hasDeepTrace;

        if ($hasAnyTrace) {
            $handledSpecialTrace = false;

            if (
                $sourceTypeFilter === 'parasite'
                && ! $hasDeepTrace
                && in_array($tracePrimaryTypeFilter, ['human', 'animal', 'environment'], true)
            ) {
                $originType = match ($tracePrimaryTypeFilter) {
                    'human' => HumanSamples::class,
                    'animal' => AnimalSamples::class,
                    'environment' => EnvironmentSamples::class,
                };

                $query->whereExists(function ($sub) use (
                    $originType,
                    $tracePrimaryAnimalSpeciesFilter,
                    $tracePrimaryAnimalSexFilter,
                    $tracePrimaryAnimalAgeFilter,
                    $tracePrimaryHumanEthnicityFilter,
                    $tracePrimaryHumanOccupationFilter,
                    $tracePrimaryHumanCountryFilter
                ) {
                    $sub->select(DB::raw(1))
                        ->from('parasite_samples')
                        ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                        ->whereColumn('parasite_samples.id', 'nucleic_acids.nucleic_content_id')
                        ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class))
                        ->whereIn('parasites.parasites_origin_type', $this->typeVariants($originType));

                    if ($originType === AnimalSamples::class) {
                        $sub->leftJoin('animal_samples as trace_animal_samples', 'trace_animal_samples.id', '=', 'parasites.parasites_origin_id')
                            ->leftJoin('animals as trace_animals', 'trace_animals.id', '=', 'trace_animal_samples.animals_id')
                            ->leftJoin('animal_species as trace_animal_species', 'trace_animal_species.id', '=', 'trace_animals.animal_species_id');

                        if ($tracePrimaryAnimalSpeciesFilter !== '') {
                            $sub->where('trace_animal_species.name_common', $tracePrimaryAnimalSpeciesFilter);
                        }
                        if ($tracePrimaryAnimalSexFilter !== '') {
                            $sub->where('trace_animals.sex', $tracePrimaryAnimalSexFilter);
                        }
                        if ($tracePrimaryAnimalAgeFilter !== '') {
                            $sub->where('trace_animals.age', $tracePrimaryAnimalAgeFilter);
                        }
                    }

                    if ($originType === HumanSamples::class) {
                        $sub->leftJoin('human_samples as trace_human_samples', 'trace_human_samples.id', '=', 'parasites.parasites_origin_id')
                            ->leftJoin('humans as trace_humans', 'trace_humans.id', '=', 'trace_human_samples.humans_id')
                            ->leftJoin('countries as trace_countries', 'trace_countries.id', '=', 'trace_humans.countries_id');

                        if ($tracePrimaryHumanEthnicityFilter !== '') {
                            $sub->where('trace_humans.ethnicity', $tracePrimaryHumanEthnicityFilter);
                        }
                        if ($tracePrimaryHumanOccupationFilter !== '') {
                            $sub->where('trace_humans.occupation', $tracePrimaryHumanOccupationFilter);
                        }
                        if ($tracePrimaryHumanCountryFilter !== '') {
                            $sub->where('trace_countries.name', $tracePrimaryHumanCountryFilter);
                        }
                    }
                });

                $handledSpecialTrace = true;
            }

            if (
                ! $handledSpecialTrace
                && $sourceTypeFilter === 'culture'
                && ! $hasDeepTrace
                && in_array($tracePrimaryTypeFilter, ['human', 'animal', 'environment', 'parasite', 'pool'], true)
            ) {
                $traceType = match ($tracePrimaryTypeFilter) {
                    'human' => HumanSamples::class,
                    'animal' => AnimalSamples::class,
                    'environment' => EnvironmentSamples::class,
                    'parasite' => ParasiteSamples::class,
                    'pool' => Pools::class,
                };

                $query->whereExists(function ($sub) use (
                    $traceType,
                    $tracePrimaryParasiteSpeciesFilter,
                    $tracePrimaryPoolMinNrPooled,
                    $tracePrimaryPoolMaxNrPooled
                ) {
                    $sub->select(DB::raw(1))
                        ->from('cultures')
                        ->whereColumn('cultures.id', 'nucleic_acids.nucleic_content_id')
                        ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                        ->whereIn('cultures.cultures_content_type', $this->typeVariants($traceType));

                    if ($traceType === ParasiteSamples::class && $tracePrimaryParasiteSpeciesFilter !== '') {
                        $sub->join('parasite_samples as trace_parasite_samples', 'trace_parasite_samples.id', '=', 'cultures.cultures_content_id')
                            ->join('parasites as trace_parasites', 'trace_parasites.id', '=', 'trace_parasite_samples.parasites_id')
                            ->leftJoin('parasite_species as trace_parasite_species', 'trace_parasite_species.id', '=', 'trace_parasites.parasite_species_id')
                            ->where('trace_parasite_species.name_scientific', $tracePrimaryParasiteSpeciesFilter);
                    }

                    if ($traceType === Pools::class) {
                        $sub->join('pools as trace_pools', 'trace_pools.id', '=', 'cultures.cultures_content_id');

                        if ($tracePrimaryPoolMinNrPooled !== null) {
                            $sub->where('trace_pools.nr_pooled', '>=', $tracePrimaryPoolMinNrPooled);
                        }
                        if ($tracePrimaryPoolMaxNrPooled !== null) {
                            $sub->where('trace_pools.nr_pooled', '<=', $tracePrimaryPoolMaxNrPooled);
                        }
                    }
                });

                $handledSpecialTrace = true;
            }

            if (
                ! $handledSpecialTrace
                && $sourceTypeFilter === 'pool'
                && $tracePrimaryTypeFilter === 'parasite'
                && ($tracePrimaryParasiteSpeciesFilter !== '' || $hasDeepTrace)
            ) {
                $deepPrimaryType = match ($traceDeepPrimaryTypeFilter) {
                    'human' => HumanSamples::class,
                    'animal' => AnimalSamples::class,
                    'environment' => EnvironmentSamples::class,
                    default => null,
                };

                $query
                    ->join('pool_contents as trace_pool_contents', function ($join) {
                        $join->on('trace_pool_contents.pools_id', '=', 'nucleic_acids.nucleic_content_id')
                            ->where(function ($typeQ) {
                                $typeQ->whereIn('trace_pool_contents.samples_type', $this->typeVariants(ParasiteSamples::class))
                                    ->orWhereRaw('LOWER(trace_pool_contents.samples_type) LIKE ?', ['%parasite%']);
                            });
                    })
                    ->join('parasite_samples as trace_parasite_samples', 'trace_parasite_samples.id', '=', 'trace_pool_contents.samples_id')
                    ->join('parasites as trace_parasites', 'trace_parasites.id', '=', 'trace_parasite_samples.parasites_id')
                    ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Pools::class));

                if ($tracePrimaryParasiteSpeciesFilter !== '') {
                    $query->leftJoin('parasite_species as trace_parasite_species', 'trace_parasite_species.id', '=', 'trace_parasites.parasite_species_id')
                        ->where('trace_parasite_species.name_scientific', $tracePrimaryParasiteSpeciesFilter);
                }

                if ($hasDeepTrace && $deepPrimaryType) {
                    $query->whereIn('trace_parasites.parasites_origin_type', $this->typeVariants($deepPrimaryType));

                    if ($deepPrimaryType === AnimalSamples::class) {
                        $query->leftJoin('animal_samples as deep_trace_animal_samples', 'deep_trace_animal_samples.id', '=', 'trace_parasites.parasites_origin_id')
                            ->leftJoin('animals as deep_trace_animals', 'deep_trace_animals.id', '=', 'deep_trace_animal_samples.animals_id')
                            ->leftJoin('animal_species as deep_trace_animal_species', 'deep_trace_animal_species.id', '=', 'deep_trace_animals.animal_species_id');

                        if ($traceDeepAnimalSpeciesFilter !== '') {
                            $query->where('deep_trace_animal_species.name_common', $traceDeepAnimalSpeciesFilter);
                        }
                        if ($traceDeepAnimalSexFilter !== '') {
                            $query->where('deep_trace_animals.sex', $traceDeepAnimalSexFilter);
                        }
                        if ($traceDeepAnimalAgeFilter !== '') {
                            $query->where('deep_trace_animals.age', $traceDeepAnimalAgeFilter);
                        }
                    }

                    if ($deepPrimaryType === HumanSamples::class) {
                        $query->leftJoin('human_samples as deep_trace_human_samples', 'deep_trace_human_samples.id', '=', 'trace_parasites.parasites_origin_id')
                            ->leftJoin('humans as deep_trace_humans', 'deep_trace_humans.id', '=', 'deep_trace_human_samples.humans_id')
                            ->leftJoin('countries as deep_trace_countries', 'deep_trace_countries.id', '=', 'deep_trace_humans.countries_id');

                        if ($traceDeepHumanEthnicityFilter !== '') {
                            $query->where('deep_trace_humans.ethnicity', $traceDeepHumanEthnicityFilter);
                        }
                        if ($traceDeepHumanOccupationFilter !== '') {
                            $query->where('deep_trace_humans.occupation', $traceDeepHumanOccupationFilter);
                        }
                        if ($traceDeepHumanCountryFilter !== '') {
                            $query->where('deep_trace_countries.name', $traceDeepHumanCountryFilter);
                        }
                    }
                }

                $handledSpecialTrace = true;
            }

            if (
                ! $handledSpecialTrace
                && $sourceTypeFilter === 'pool'
                && $tracePrimaryTypeFilter === 'culture'
                && ($tracePrimaryCultureTypeFilter !== '' || $tracePrimaryCultureMediumFilter !== '' || $hasDeepTrace)
            ) {
                $deepPrimaryType = match ($traceDeepPrimaryTypeFilter) {
                    'human' => HumanSamples::class,
                    'animal' => AnimalSamples::class,
                    'environment' => EnvironmentSamples::class,
                    default => null,
                };

                $query
                    ->join('pool_contents as trace_pool_culture_contents', function ($join) {
                        $join->on('trace_pool_culture_contents.pools_id', '=', 'nucleic_acids.nucleic_content_id')
                            ->where(function ($typeQ) {
                                $typeQ->whereIn('trace_pool_culture_contents.samples_type', $this->typeVariants(Cultures::class))
                                    ->orWhereRaw('LOWER(trace_pool_culture_contents.samples_type) LIKE ?', ['%culture%']);
                            });
                    })
                    ->join('cultures as trace_cultures', 'trace_cultures.id', '=', 'trace_pool_culture_contents.samples_id')
                    ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Pools::class));

                if ($tracePrimaryCultureTypeFilter !== '') {
                    $query->where('trace_cultures.type', $tracePrimaryCultureTypeFilter);
                }
                if ($tracePrimaryCultureMediumFilter !== '') {
                    $query->where('trace_cultures.medium', $tracePrimaryCultureMediumFilter);
                }

                if ($hasDeepTrace && $deepPrimaryType) {
                    $query->where(function ($deepQ) use ($deepPrimaryType) {
                        $deepQ->whereIn('trace_cultures.cultures_content_type', $this->typeVariants($deepPrimaryType))
                            ->orWhereExists(function ($parasiteQ) use ($deepPrimaryType) {
                                $parasiteQ->select(DB::raw(1))
                                    ->from('parasite_samples as deep_trace_parasite_samples')
                                    ->join('parasites as deep_trace_parasites', 'deep_trace_parasites.id', '=', 'deep_trace_parasite_samples.parasites_id')
                                    ->whereColumn('deep_trace_parasite_samples.id', 'trace_cultures.cultures_content_id')
                                    ->whereIn('trace_cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                                    ->whereIn('deep_trace_parasites.parasites_origin_type', $this->typeVariants($deepPrimaryType));
                            });
                    });
                }

                $handledSpecialTrace = true;
            }

            if (
                ! $handledSpecialTrace
                && $hasDeepTrace
                && $tracePrimaryTypeFilter === 'parasite'
                && in_array($traceDeepPrimaryTypeFilter, ['human', 'animal', 'environment'], true)
                && in_array($sourceTypeFilter, ['parasite', 'culture', 'pool'], true)
            ) {
                $deepOriginType = match ($traceDeepPrimaryTypeFilter) {
                    'human' => HumanSamples::class,
                    'animal' => AnimalSamples::class,
                    'environment' => EnvironmentSamples::class,
                };

                $query->whereExists(function ($sub) use (
                    $sourceTypeFilter,
                    $deepOriginType,
                    $traceDeepAnimalSpeciesFilter,
                    $traceDeepAnimalSexFilter,
                    $traceDeepAnimalAgeFilter,
                    $traceDeepHumanEthnicityFilter,
                    $traceDeepHumanOccupationFilter,
                    $traceDeepHumanCountryFilter
                ) {
                    if ($sourceTypeFilter === 'culture') {
                        $sub->select(DB::raw(1))
                            ->from('cultures')
                            ->join('parasite_samples as deep_trace_parasite_samples', 'deep_trace_parasite_samples.id', '=', 'cultures.cultures_content_id')
                            ->join('parasites as deep_trace_parasites', 'deep_trace_parasites.id', '=', 'deep_trace_parasite_samples.parasites_id')
                            ->whereColumn('cultures.id', 'nucleic_acids.nucleic_content_id')
                            ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Cultures::class))
                            ->whereIn('cultures.cultures_content_type', $this->typeVariants(ParasiteSamples::class))
                            ->whereIn('deep_trace_parasites.parasites_origin_type', $this->typeVariants($deepOriginType));
                    } elseif ($sourceTypeFilter === 'pool') {
                        $sub->select(DB::raw(1))
                            ->from('pool_contents')
                            ->join('parasite_samples as deep_trace_parasite_samples', 'deep_trace_parasite_samples.id', '=', 'pool_contents.samples_id')
                            ->join('parasites as deep_trace_parasites', 'deep_trace_parasites.id', '=', 'deep_trace_parasite_samples.parasites_id')
                            ->whereColumn('pool_contents.pools_id', 'nucleic_acids.nucleic_content_id')
                            ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(Pools::class))
                            ->where(function ($typeQ) {
                                $typeQ->whereIn('pool_contents.samples_type', $this->typeVariants(ParasiteSamples::class))
                                    ->orWhereRaw('LOWER(pool_contents.samples_type) LIKE ?', ['%parasite%']);
                            })
                            ->whereIn('deep_trace_parasites.parasites_origin_type', $this->typeVariants($deepOriginType));
                    } else {
                        $sub->select(DB::raw(1))
                            ->from('parasite_samples as deep_trace_parasite_samples')
                            ->join('parasites as deep_trace_parasites', 'deep_trace_parasites.id', '=', 'deep_trace_parasite_samples.parasites_id')
                            ->whereColumn('deep_trace_parasite_samples.id', 'nucleic_acids.nucleic_content_id')
                            ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(ParasiteSamples::class))
                            ->whereIn('deep_trace_parasites.parasites_origin_type', $this->typeVariants($deepOriginType));
                    }

                    if ($deepOriginType === AnimalSamples::class) {
                        $sub->leftJoin('animal_samples as deep_trace_animal_samples', 'deep_trace_animal_samples.id', '=', 'deep_trace_parasites.parasites_origin_id')
                            ->leftJoin('animals as deep_trace_animals', 'deep_trace_animals.id', '=', 'deep_trace_animal_samples.animals_id')
                            ->leftJoin('animal_species as deep_trace_animal_species', 'deep_trace_animal_species.id', '=', 'deep_trace_animals.animal_species_id');

                        if ($traceDeepAnimalSpeciesFilter !== '') {
                            $sub->where('deep_trace_animal_species.name_common', $traceDeepAnimalSpeciesFilter);
                        }
                        if ($traceDeepAnimalSexFilter !== '') {
                            $sub->where('deep_trace_animals.sex', $traceDeepAnimalSexFilter);
                        }
                        if ($traceDeepAnimalAgeFilter !== '') {
                            $sub->where('deep_trace_animals.age', $traceDeepAnimalAgeFilter);
                        }
                    }

                    if ($deepOriginType === HumanSamples::class) {
                        $sub->leftJoin('human_samples as deep_trace_human_samples', 'deep_trace_human_samples.id', '=', 'deep_trace_parasites.parasites_origin_id')
                            ->leftJoin('humans as deep_trace_humans', 'deep_trace_humans.id', '=', 'deep_trace_human_samples.humans_id')
                            ->leftJoin('countries as deep_trace_countries', 'deep_trace_countries.id', '=', 'deep_trace_humans.countries_id');

                        if ($traceDeepHumanEthnicityFilter !== '') {
                            $sub->where('deep_trace_humans.ethnicity', $traceDeepHumanEthnicityFilter);
                        }
                        if ($traceDeepHumanOccupationFilter !== '') {
                            $sub->where('deep_trace_humans.occupation', $traceDeepHumanOccupationFilter);
                        }
                        if ($traceDeepHumanCountryFilter !== '') {
                            $sub->where('deep_trace_countries.name', $traceDeepHumanCountryFilter);
                        }
                    }
                });

                $handledSpecialTrace = true;
            }

            if (! $handledSpecialTrace) {
                $upstreamType = match ($tracePrimaryTypeFilter) {
                    'human' => HumanSamples::class,
                    'animal' => AnimalSamples::class,
                    'environment' => EnvironmentSamples::class,
                    'parasite' => ParasiteSamples::class,
                    'culture' => Cultures::class,
                    'nucleic' => NucleicAcids::class,
                    'pool' => Pools::class,
                    default => null,
                };

                if ($upstreamType) {
                    $upstreamSeedIds = $this->seedIdsForTraceUpstream(
                        $upstreamType,
                        projectId: $projectId,
                        isGuestMode: $isGuestMode,
                        tracePrimaryAnimalSpeciesFilter: $tracePrimaryAnimalSpeciesFilter,
                        tracePrimaryAnimalSexFilter: $tracePrimaryAnimalSexFilter,
                        tracePrimaryAnimalAgeFilter: $tracePrimaryAnimalAgeFilter,
                        tracePrimaryHumanEthnicityFilter: $tracePrimaryHumanEthnicityFilter,
                        tracePrimaryHumanOccupationFilter: $tracePrimaryHumanOccupationFilter,
                        tracePrimaryHumanCountryFilter: $tracePrimaryHumanCountryFilter,
                        tracePrimaryParasiteSpeciesFilter: $tracePrimaryParasiteSpeciesFilter,
                        tracePrimaryCultureTypeFilter: $tracePrimaryCultureTypeFilter,
                        tracePrimaryCultureMediumFilter: $tracePrimaryCultureMediumFilter,
                        tracePrimaryNucleicTypeFilter: $tracePrimaryNucleicTypeFilter,
                        tracePrimaryPoolMinNrPooled: $tracePrimaryPoolMinNrPooled,
                        tracePrimaryPoolMaxNrPooled: $tracePrimaryPoolMaxNrPooled,
                    );

                    if ($upstreamSeedIds === []) {
                        $query->whereRaw('1 = 0');
                    } else {
                        if ($hasDeepTrace && in_array($tracePrimaryTypeFilter, ['parasite', 'culture', 'nucleic', 'pool'], true)) {
                            $deepPrimaryType = match ($traceDeepPrimaryTypeFilter) {
                                'human' => HumanSamples::class,
                                'animal' => AnimalSamples::class,
                                'environment' => EnvironmentSamples::class,
                                default => null,
                            };

                            if ($deepPrimaryType) {
                                $deepPrimaryIds = $this->primarySampleIdsForDeepTracing(
                                    $deepPrimaryType,
                                    projectId: $projectId,
                                    isGuestMode: $isGuestMode,
                                    animalSpecies: $traceDeepAnimalSpeciesFilter,
                                    animalSex: $traceDeepAnimalSexFilter,
                                    animalAge: $traceDeepAnimalAgeFilter,
                                    humanEthnicity: $traceDeepHumanEthnicityFilter,
                                    humanOccupation: $traceDeepHumanOccupationFilter,
                                    humanCountry: $traceDeepHumanCountryFilter,
                                );

                                $reachability = app(PrimarySampleReachability::class);
                                $reachableUpstream = match ($upstreamType) {
                                    ParasiteSamples::class => $reachability->parasiteSampleIdsFromSeed($deepPrimaryType, $deepPrimaryIds, $projectId, $isGuestMode, maxDepth: 10),
                                    Cultures::class => $reachability->cultureIdsFromPrimary($deepPrimaryType, $deepPrimaryIds, $projectId, $isGuestMode, maxDepth: 10),
                                    NucleicAcids::class => $reachability->nucleicIdsFromPrimary($deepPrimaryType, $deepPrimaryIds, $projectId, $isGuestMode, maxDepth: 10),
                                    Pools::class => $reachability->poolIdsFromPrimary($deepPrimaryType, $deepPrimaryIds, $projectId, $isGuestMode, maxDepth: 10),
                                    default => [],
                                };

                                if ($reachableUpstream === []) {
                                    $query->whereRaw('1 = 0');
                                } else {
                                    $reachableSet = array_fill_keys(array_map('intval', $reachableUpstream), true);
                                    $upstreamSeedIds = array_values(array_filter($upstreamSeedIds, fn ($id) => isset($reachableSet[(int) $id])));
                                    if ($upstreamSeedIds === []) {
                                        $query->whereRaw('1 = 0');
                                    }
                                }
                            }
                        }

                        $reachability = app(PrimarySampleReachability::class);
                        $maxDepth = $upstreamType === Pools::class ? 10 : 6;

                        $ids = in_array($tracePrimaryTypeFilter, ['human', 'animal', 'environment'], true)
                            ? $reachability->nucleicIdsFromPrimary($upstreamType, $upstreamSeedIds, $projectId, $isGuestMode, $maxDepth)
                            : $reachability->nucleicIdsFromSeed($upstreamType, $upstreamSeedIds, $projectId, $isGuestMode, $maxDepth);

                        if ($ids === []) {
                            $query->whereRaw('1 = 0');
                        } else {
                            $query->whereIn('nucleic_acids.id', $ids);
                        }
                    }
                }
            }

        }

        $protocolFilter = (string) $request->query('protocolFilter', '');
        if ($protocolFilter !== '') {
            $query->where('protocols.name', $protocolFilter);
        }

        $laboratoryFilter = (string) $request->query('laboratoryFilter', '');
        if ($laboratoryFilter !== '') {
            $query->where('laboratories.name', $laboratoryFilter);
        }

        $extractedByFilter = (string) $request->query('extractedByFilter', '');
        if ($extractedByFilter !== '') {
            $query->whereRaw($this->peopleNameSql().' = ?', [$extractedByFilter]);
        }

        $startDate = (string) $request->query('startDate', '');
        $endDate = (string) $request->query('endDate', '');
        if ($startDate !== '' && $endDate !== '') {
            $query->whereBetween('nucleic_acids.date_extracted', [$startDate, $endDate]);
        } elseif ($startDate !== '') {
            $query->where('nucleic_acids.date_extracted', '>=', $startDate);
        } elseif ($endDate !== '') {
            $query->where('nucleic_acids.date_extracted', '<=', $endDate);
        }

        return $query;
    }

    /**
     * @param  class-string  $upstreamType
     * @return array<int,int>
     */
    private function seedIdsForTraceUpstream(
        string $upstreamType,
        ?int $projectId,
        bool $isGuestMode,
        string $tracePrimaryAnimalSpeciesFilter,
        string $tracePrimaryAnimalSexFilter,
        string $tracePrimaryAnimalAgeFilter,
        string $tracePrimaryHumanEthnicityFilter,
        string $tracePrimaryHumanOccupationFilter,
        string $tracePrimaryHumanCountryFilter,
        string $tracePrimaryParasiteSpeciesFilter,
        string $tracePrimaryCultureTypeFilter,
        string $tracePrimaryCultureMediumFilter,
        string $tracePrimaryNucleicTypeFilter,
        $tracePrimaryPoolMinNrPooled,
        $tracePrimaryPoolMaxNrPooled,
    ): array {
        if (in_array($upstreamType, [HumanSamples::class, AnimalSamples::class, EnvironmentSamples::class], true)) {
            return $this->primarySampleIdsForTracing(
                $upstreamType,
                projectId: $projectId,
                isGuestMode: $isGuestMode,
                animalSpecies: $tracePrimaryAnimalSpeciesFilter,
                animalSex: $tracePrimaryAnimalSexFilter,
                animalAge: $tracePrimaryAnimalAgeFilter,
                humanEthnicity: $tracePrimaryHumanEthnicityFilter,
                humanOccupation: $tracePrimaryHumanOccupationFilter,
                humanCountry: $tracePrimaryHumanCountryFilter,
            );
        }

        if ($upstreamType === ParasiteSamples::class) {
            $q = ParasiteSamples::query()
                ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
                ->leftJoin('parasite_species', 'parasites.parasite_species_id', '=', 'parasite_species.id')
                ->select('parasite_samples.id');

            if ($tracePrimaryParasiteSpeciesFilter !== '') {
                $q->where('parasite_species.name_scientific', $tracePrimaryParasiteSpeciesFilter);
            }

            $this->applyVisibilityToSeedQuery($q, 'parasite_samples', ParasiteSamples::class, $projectId, $isGuestMode);

            return $q->distinct()->limit(5000)->pluck('parasite_samples.id')->map(fn ($v) => (int) $v)->all();
        }

        if ($upstreamType === Cultures::class) {
            $q = Cultures::query()->select('cultures.id');

            if ($tracePrimaryCultureTypeFilter !== '') {
                $q->where('cultures.type', $tracePrimaryCultureTypeFilter);
            }
            if ($tracePrimaryCultureMediumFilter !== '') {
                $q->where('cultures.medium', $tracePrimaryCultureMediumFilter);
            }

            $this->applyVisibilityToSeedQuery($q, 'cultures', Cultures::class, $projectId, $isGuestMode);

            return $q->distinct()->limit(5000)->pluck('cultures.id')->map(fn ($v) => (int) $v)->all();
        }

        if ($upstreamType === NucleicAcids::class) {
            $q = NucleicAcids::query()->select('nucleic_acids.id');

            if ($tracePrimaryNucleicTypeFilter !== '') {
                $q->where('nucleic_acids.type', $tracePrimaryNucleicTypeFilter);
            }

            $this->applyVisibilityToSeedQuery($q, 'nucleic_acids', NucleicAcids::class, $projectId, $isGuestMode);

            return $q->distinct()->limit(5000)->pluck('nucleic_acids.id')->map(fn ($v) => (int) $v)->all();
        }

        if ($upstreamType === Pools::class) {
            $q = Pools::query()->select('pools.id');

            if ($tracePrimaryPoolMinNrPooled !== null && $tracePrimaryPoolMinNrPooled !== '') {
                $q->where('pools.nr_pooled', '>=', (int) $tracePrimaryPoolMinNrPooled);
            }
            if ($tracePrimaryPoolMaxNrPooled !== null && $tracePrimaryPoolMaxNrPooled !== '') {
                $q->where('pools.nr_pooled', '<=', (int) $tracePrimaryPoolMaxNrPooled);
            }

            $this->applyVisibilityToSeedQuery($q, 'pools', Pools::class, $projectId, $isGuestMode);

            return $q->distinct()->limit(5000)->pluck('pools.id')->map(fn ($v) => (int) $v)->all();
        }

        return [];
    }

    private function applyVisibilityToSeedQuery($q, string $table, string $type, ?int $projectId, bool $isGuestMode): void
    {
        if ($isGuestMode) {
            $q->whereExists(function ($sub) use ($table, $type) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', $table.'.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants($type))
                    ->where('tubes.is_private', false);
            });

            return;
        }

        if ($projectId) {
            $q->where(function ($w) use ($table, $type, $projectId) {
                $w->where($table.'.projects_id', $projectId)
                    ->orWhereExists(function ($sub) use ($table, $type, $projectId) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', $table.'.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants($type))
                            ->where('tubes.projects_id', $projectId);
                    });
            });
        }
    }

    private function typeVariants(string $type): array
    {
        $base = class_basename($type);

        return array_values(array_unique([
            $type,
            "App\\Models\\{$base}",
            "AppModels{$base}",
            $base,
        ]));
    }

    private function normalizedBasename(string $rawType): string
    {
        $normalized = ltrim(trim($rawType), '\\');
        $base = class_basename($normalized);

        if (str_starts_with($base, 'AppModels')) {
            return substr($base, strlen('AppModels'));
        }

        return $base;
    }

    /**
     * @param  class-string  $primaryType
     * @return array<int,int>
     */
    private function primarySampleIdsForTracing(
        string $primaryType,
        ?int $projectId,
        bool $isGuestMode,
        string $animalSpecies,
        string $animalSex,
        string $animalAge,
        string $humanEthnicity,
        string $humanOccupation,
        string $humanCountry,
    ): array {
        $table = (new $primaryType)->getTable();
        $q = $primaryType::query()->select($table.'.id');

        if ($primaryType === AnimalSamples::class) {
            $q->join('animals', 'animal_samples.animals_id', '=', 'animals.id')
                ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id');

            if ($animalSpecies !== '') {
                $q->where('animal_species.name_common', $animalSpecies);
            }
            if ($animalSex !== '') {
                $q->where('animals.sex', $animalSex);
            }
            if ($animalAge !== '') {
                $q->where('animals.age', $animalAge);
            }
        }

        if ($primaryType === HumanSamples::class) {
            $q->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
                ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id');

            if ($humanEthnicity !== '') {
                $q->where('humans.ethnicity', $humanEthnicity);
            }
            if ($humanOccupation !== '') {
                $q->where('humans.occupation', $humanOccupation);
            }
            if ($humanCountry !== '') {
                $q->where('countries.name', $humanCountry);
            }
        }

        if ($isGuestMode) {
            $q->whereExists(function ($sub) use ($primaryType, $table) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', $table.'.id')
                    ->whereIn('tubes.tubes_content_type', $this->typeVariants($primaryType))
                    ->where('tubes.is_private', false);
            });
        } elseif ($projectId) {
            $q->where(function ($w) use ($primaryType, $table, $projectId) {
                $w->where($table.'.projects_id', $projectId)
                    ->orWhereExists(function ($sub) use ($primaryType, $table, $projectId) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', $table.'.id')
                            ->whereIn('tubes.tubes_content_type', $this->typeVariants($primaryType))
                            ->where('tubes.projects_id', $projectId);
                    });
            });
        }

        return $q->limit(8000)->pluck($table.'.id')->map(fn ($v) => (int) $v)->all();
    }

    /**
     * @param  class-string  $primaryType
     * @return array<int,int>
     */
    private function primarySampleIdsForDeepTracing(
        string $primaryType,
        ?int $projectId,
        bool $isGuestMode,
        string $animalSpecies,
        string $animalSex,
        string $animalAge,
        string $humanEthnicity,
        string $humanOccupation,
        string $humanCountry,
    ): array {
        return $this->primarySampleIdsForTracing(
            $primaryType,
            projectId: $projectId,
            isGuestMode: $isGuestMode,
            animalSpecies: $animalSpecies,
            animalSex: $animalSex,
            animalAge: $animalAge,
            humanEthnicity: $humanEthnicity,
            humanOccupation: $humanOccupation,
            humanCountry: $humanCountry,
        );
    }

    private function peopleNameSql(): string
    {
        $driver = DB::getDriverName();

        return match ($driver) {
            'mysql' => "TRIM(CONCAT_WS(' ', people.first_name, people.last_name))",
            'pgsql' => "TRIM(CONCAT(people.first_name, ' ', people.last_name))",
            default => "TRIM(COALESCE(people.first_name, '') || ' ' || COALESCE(people.last_name, ''))",
        };
    }

    /**
     * @param  Collection<int, object>  $rows
     * @param  Collection<int, SamplingSites>  $samplingSites
     * @return array<int, array{latitude: float, longitude: float, code: ?string, type: ?string, source_type: string, protocol: ?string, extracted_by: ?string, human_ethnicity: ?string, human_occupation: ?string, human_country: ?string, animal_species: ?string, animal_sex: ?string, animal_age: ?string, parasite_species: ?string, parasite_stage: ?string, parasite_sex: ?string, culture_type: ?string, culture_medium: ?string, pool_nr_pooled: ?string}>
     */
    private function rowsToPoints(Collection $rows, Collection $samplingSites): array
    {
        $byType = $rows->groupBy(fn ($r) => (string) $r->nucleic_content_type);

        $primaryHuman = $this->loadPrimarySamples($byType->get(HumanSamples::class, collect()), HumanSamples::class);
        $primaryAnimal = $this->loadPrimarySamples($byType->get(AnimalSamples::class, collect()), AnimalSamples::class);
        $primaryEnvironment = $this->loadPrimarySamples($byType->get(EnvironmentSamples::class, collect()), EnvironmentSamples::class);

        $parasiteOrigins = $this->loadParasiteOrigins($byType->get(ParasiteSamples::class, collect()));
        $cultureOrigins = $this->loadCultureOrigins($byType->get(Cultures::class, collect()));
        $poolOrigins = $this->loadPoolOrigins($byType->get(Pools::class, collect()));

        $primary = $primaryHuman
            ->merge($primaryAnimal)
            ->merge($primaryEnvironment)
            ->merge($parasiteOrigins)
            ->merge($cultureOrigins)
            ->merge($poolOrigins);

        $points = [];

        foreach ($rows as $row) {
            $originType = (string) $row->nucleic_content_type;
            $originId = (int) $row->nucleic_content_id;

            $origin = $primary->get($originType.'#'.$originId);
            if (! $origin) {
                continue;
            }

            $lat = $origin['latitude'];
            $lng = $origin['longitude'];
            $samplingSiteId = $origin['sampling_sites_id'];

            if ((! $lat || ! $lng) && $samplingSiteId) {
                $site = $samplingSites->get($samplingSiteId);
                $lat = $site?->latitude;
                $lng = $site?->longitude;
            }

            if (! $lat || ! $lng) {
                continue;
            }

            $points[] = [
                'latitude' => (float) $lat,
                'longitude' => (float) $lng,
                'code' => $row->code,
                'type' => $row->type,
                'source_type' => class_basename($originType),
                'protocol' => $row->protocol !== null ? (string) $row->protocol : null,
                'extracted_by' => $row->extracted_by !== null ? (string) $row->extracted_by : null,
                'human_ethnicity' => $row->human_ethnicity !== null ? (string) $row->human_ethnicity : null,
                'human_occupation' => $row->human_occupation !== null ? (string) $row->human_occupation : null,
                'human_country' => $row->human_country !== null ? (string) $row->human_country : null,
                'animal_species' => $row->animal_species !== null ? (string) $row->animal_species : null,
                'animal_sex' => $row->animal_sex !== null ? (string) $row->animal_sex : null,
                'animal_age' => $row->animal_age !== null ? (string) $row->animal_age : null,
                'parasite_species' => $row->parasite_species !== null ? (string) $row->parasite_species : null,
                'parasite_stage' => $row->parasite_stage !== null ? (string) $row->parasite_stage : null,
                'parasite_sex' => $row->parasite_sex !== null ? (string) $row->parasite_sex : null,
                'culture_type' => $row->culture_type !== null ? (string) $row->culture_type : null,
                'culture_medium' => $row->culture_medium !== null ? (string) $row->culture_medium : null,
                'pool_nr_pooled' => $row->pool_nr_pooled !== null ? (string) $row->pool_nr_pooled : null,
            ];
        }

        return $points;
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<string, array{latitude: ?float, longitude: ?float, sampling_sites_id: ?int}>
     */
    private function loadPrimarySamples(Collection $rows, string $modelClass): Collection
    {
        $ids = $rows->pluck('nucleic_content_id')->filter()->map(fn ($v) => (int) $v)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $table = (new $modelClass)->getTable();

        $samples = $modelClass::query()
            ->whereIn($table.'.id', $ids)
            ->get([$table.'.id', $table.'.latitude', $table.'.longitude', $table.'.sampling_sites_id']);

        return $samples->mapWithKeys(function ($s) use ($modelClass) {
            return [
                $modelClass.'#'.$s->id => [
                    'latitude' => $s->latitude,
                    'longitude' => $s->longitude,
                    'sampling_sites_id' => $s->sampling_sites_id,
                ],
            ];
        });
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<string, array{latitude: ?float, longitude: ?float, sampling_sites_id: ?int}>
     */
    private function loadParasiteOrigins(Collection $rows): Collection
    {
        $ids = $rows->pluck('nucleic_content_id')->filter()->map(fn ($v) => (int) $v)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $parasites = ParasiteSamples::query()
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->whereIn('parasite_samples.id', $ids)
            ->get(['parasite_samples.id', 'parasites.parasites_origin_type', 'parasites.parasites_origin_id']);

        $byOriginType = $parasites->groupBy(fn ($r) => (string) $r->parasites_origin_type);
        $human = $this->loadPrimarySamplesFromIds($byOriginType->get(HumanSamples::class, collect())->pluck('parasites_origin_id'), HumanSamples::class);
        $animal = $this->loadPrimarySamplesFromIds($byOriginType->get(AnimalSamples::class, collect())->pluck('parasites_origin_id'), AnimalSamples::class);
        $environment = $this->loadPrimarySamplesFromIds($byOriginType->get(EnvironmentSamples::class, collect())->pluck('parasites_origin_id'), EnvironmentSamples::class);

        $origins = $human->merge($animal)->merge($environment);

        return $parasites->mapWithKeys(function ($p) use ($origins) {
            $originType = (string) $p->parasites_origin_type;
            $originId = (int) $p->parasites_origin_id;

            $origin = $origins->get($originType.'#'.$originId);

            return [
                ParasiteSamples::class.'#'.$p->id => $origin ?? ['latitude' => null, 'longitude' => null, 'sampling_sites_id' => null],
            ];
        });
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<string, array{latitude: ?float, longitude: ?float, sampling_sites_id: ?int}>
     */
    private function loadCultureOrigins(Collection $rows): Collection
    {
        $ids = $rows->pluck('nucleic_content_id')->filter()->map(fn ($v) => (int) $v)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        return $this->loadCultureOriginsFromIds($ids);
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<string, array{latitude: ?float, longitude: ?float, sampling_sites_id: ?int}>
     */
    private function loadPoolOrigins(Collection $rows): Collection
    {
        $ids = $rows->pluck('nucleic_content_id')->filter()->map(fn ($v) => (int) $v)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $poolContents = PoolContents::query()
            ->whereIn('pools_id', $ids)
            ->orderBy('id')
            ->get(['id', 'pools_id', 'samples_type', 'samples_id']);

        $byPool = $poolContents->groupBy('pools_id');

        $human = $this->loadPrimarySamplesFromIds(
            $poolContents
                ->filter(fn ($row) => $this->normalizedBasename((string) $row->samples_type) === class_basename(HumanSamples::class) || str_contains(strtolower((string) $row->samples_type), 'human'))
                ->pluck('samples_id'),
            HumanSamples::class
        );
        $animal = $this->loadPrimarySamplesFromIds(
            $poolContents
                ->filter(fn ($row) => $this->normalizedBasename((string) $row->samples_type) === class_basename(AnimalSamples::class) || str_contains(strtolower((string) $row->samples_type), 'animal'))
                ->pluck('samples_id'),
            AnimalSamples::class
        );
        $environment = $this->loadPrimarySamplesFromIds(
            $poolContents
                ->filter(fn ($row) => $this->normalizedBasename((string) $row->samples_type) === class_basename(EnvironmentSamples::class) || str_contains(strtolower((string) $row->samples_type), 'environment'))
                ->pluck('samples_id'),
            EnvironmentSamples::class
        );
        $parasite = $this->loadParasiteOriginsFromIds(
            $poolContents
                ->filter(fn ($row) => $this->normalizedBasename((string) $row->samples_type) === class_basename(ParasiteSamples::class) || str_contains(strtolower((string) $row->samples_type), 'parasite'))
                ->pluck('samples_id')
        );
        $culture = $this->loadCultureOriginsFromIds(
            $poolContents
                ->filter(fn ($row) => $this->normalizedBasename((string) $row->samples_type) === class_basename(Cultures::class) || str_contains(strtolower((string) $row->samples_type), 'culture'))
                ->pluck('samples_id')
        );
        $nestedNucleicIds = $poolContents
            ->filter(fn ($row) => $this->normalizedBasename((string) $row->samples_type) === class_basename(NucleicAcids::class) || str_contains(strtolower((string) $row->samples_type), 'nucleic'))
            ->pluck('samples_id')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        $nestedNucleicOrigins = collect();
        if ($nestedNucleicIds->isNotEmpty()) {
            $nestedRows = NucleicAcids::query()
                ->whereIn('id', $nestedNucleicIds)
                ->get(['id', 'nucleic_content_type', 'nucleic_content_id']);

            $nestedByType = $nestedRows->groupBy(fn ($row) => $this->normalizedBasename((string) $row->nucleic_content_type));
            $nestedHuman = $this->loadPrimarySamplesFromIds($nestedByType->get(class_basename(HumanSamples::class), collect())->pluck('nucleic_content_id'), HumanSamples::class);
            $nestedAnimal = $this->loadPrimarySamplesFromIds($nestedByType->get(class_basename(AnimalSamples::class), collect())->pluck('nucleic_content_id'), AnimalSamples::class);
            $nestedEnvironment = $this->loadPrimarySamplesFromIds($nestedByType->get(class_basename(EnvironmentSamples::class), collect())->pluck('nucleic_content_id'), EnvironmentSamples::class);
            $nestedParasite = $this->loadParasiteOriginsFromIds($nestedByType->get(class_basename(ParasiteSamples::class), collect())->pluck('nucleic_content_id'));
            $nestedCulture = $this->loadCultureOriginsFromIds($nestedByType->get(class_basename(Cultures::class), collect())->pluck('nucleic_content_id'));

            foreach ($nestedRows as $nestedRow) {
                $nestedType = $this->normalizedBasename((string) $nestedRow->nucleic_content_type);
                $nestedContentId = (int) $nestedRow->nucleic_content_id;
                $resolved = match ($nestedType) {
                    'HumanSamples' => $nestedHuman->get(HumanSamples::class.'#'.$nestedContentId),
                    'AnimalSamples' => $nestedAnimal->get(AnimalSamples::class.'#'.$nestedContentId),
                    'EnvironmentSamples' => $nestedEnvironment->get(EnvironmentSamples::class.'#'.$nestedContentId),
                    'ParasiteSamples' => $nestedParasite->get(ParasiteSamples::class.'#'.$nestedContentId),
                    'Cultures' => $nestedCulture->get(Cultures::class.'#'.$nestedContentId),
                    default => null,
                };

                $nestedNucleicOrigins->put(
                    NucleicAcids::class.'#'.(int) $nestedRow->id,
                    $resolved ?? ['latitude' => null, 'longitude' => null, 'sampling_sites_id' => null]
                );
            }
        }

        $points = collect();

        foreach ($byPool as $poolId => $contents) {
            $resolved = null;

            foreach ($contents as $pc) {
                $type = $this->normalizedBasename((string) $pc->samples_type);
                $id = (int) $pc->samples_id;

                $resolved = match ($type) {
                    'HumanSamples' => $human->get(HumanSamples::class.'#'.$id),
                    'AnimalSamples' => $animal->get(AnimalSamples::class.'#'.$id),
                    'EnvironmentSamples' => $environment->get(EnvironmentSamples::class.'#'.$id),
                    'ParasiteSamples' => $parasite->get(ParasiteSamples::class.'#'.$id),
                    'Cultures' => $culture->get(Cultures::class.'#'.$id),
                    'NucleicAcids' => $nestedNucleicOrigins->get(NucleicAcids::class.'#'.$id),
                    default => null,
                };

                if ($resolved) {
                    break;
                }
            }

            $points->put(
                Pools::class.'#'.(int) $poolId,
                $resolved ?? ['latitude' => null, 'longitude' => null, 'sampling_sites_id' => null]
            );
        }

        return $points;
    }

    private function loadPrimarySamplesFromIds($ids, string $modelClass): Collection
    {
        $ids = collect($ids)->filter()->map(fn ($v) => (int) $v)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $table = (new $modelClass)->getTable();

        $samples = $modelClass::query()
            ->whereIn($table.'.id', $ids)
            ->get([$table.'.id', $table.'.latitude', $table.'.longitude', $table.'.sampling_sites_id']);

        return $samples->mapWithKeys(function ($s) use ($modelClass) {
            return [
                $modelClass.'#'.$s->id => [
                    'latitude' => $s->latitude,
                    'longitude' => $s->longitude,
                    'sampling_sites_id' => $s->sampling_sites_id,
                ],
            ];
        });
    }

    private function loadParasiteOriginsFromIds($ids): Collection
    {
        $ids = collect($ids)->filter()->map(fn ($v) => (int) $v)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $parasites = ParasiteSamples::query()
            ->join('parasites', 'parasite_samples.parasites_id', '=', 'parasites.id')
            ->whereIn('parasite_samples.id', $ids)
            ->get(['parasite_samples.id', 'parasites.parasites_origin_type', 'parasites.parasites_origin_id']);

        $byOriginType = $parasites->groupBy(fn ($r) => (string) $r->parasites_origin_type);
        $human = $this->loadPrimarySamplesFromIds($byOriginType->get(HumanSamples::class, collect())->pluck('parasites_origin_id'), HumanSamples::class);
        $animal = $this->loadPrimarySamplesFromIds($byOriginType->get(AnimalSamples::class, collect())->pluck('parasites_origin_id'), AnimalSamples::class);
        $environment = $this->loadPrimarySamplesFromIds($byOriginType->get(EnvironmentSamples::class, collect())->pluck('parasites_origin_id'), EnvironmentSamples::class);

        $origins = $human->merge($animal)->merge($environment);

        return $parasites->mapWithKeys(function ($p) use ($origins) {
            $originType = (string) $p->parasites_origin_type;
            $originId = (int) $p->parasites_origin_id;
            $origin = $origins->get($originType.'#'.$originId);

            return [
                ParasiteSamples::class.'#'.$p->id => $origin ?? ['latitude' => null, 'longitude' => null, 'sampling_sites_id' => null],
            ];
        });
    }

    private function loadCultureOriginsFromIds($ids): Collection
    {
        $ids = collect($ids)->filter()->map(fn ($v) => (int) $v)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $cultures = Cultures::query()
            ->whereIn('id', $ids)
            ->get(['id', 'cultures_content_type', 'cultures_content_id']);

        $byType = $cultures->groupBy(fn ($c) => (string) $c->cultures_content_type);

        $human = $this->loadPrimarySamplesFromIds($byType->get(HumanSamples::class, collect())->pluck('cultures_content_id'), HumanSamples::class);
        $animal = $this->loadPrimarySamplesFromIds($byType->get(AnimalSamples::class, collect())->pluck('cultures_content_id'), AnimalSamples::class);
        $environment = $this->loadPrimarySamplesFromIds($byType->get(EnvironmentSamples::class, collect())->pluck('cultures_content_id'), EnvironmentSamples::class);

        $parasite = $this->loadParasiteOriginsFromIds($byType->get(ParasiteSamples::class, collect())->pluck('cultures_content_id'));

        $origins = $human->merge($animal)->merge($environment)->merge($parasite);

        return $cultures->mapWithKeys(function ($c) use ($origins) {
            $originType = (string) $c->cultures_content_type;
            $originId = (int) $c->cultures_content_id;
            $origin = $origins->get($originType.'#'.$originId);

            return [
                Cultures::class.'#'.$c->id => $origin ?? ['latitude' => null, 'longitude' => null, 'sampling_sites_id' => null],
            ];
        });
    }
}
