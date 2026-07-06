<?php

namespace App\Http\Controllers;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NucleicAcidsDashboardModalTablesController extends Controller
{
    public function all(Request $request)
    {
        return $this->table($request, null, route('nucleic.dashboard.modal.all'));
    }

    public function human(Request $request)
    {
        return $this->table($request, HumanSamples::class, route('nucleic.dashboard.modal.human'));
    }

    public function animal(Request $request)
    {
        return $this->table($request, AnimalSamples::class, route('nucleic.dashboard.modal.animal'));
    }

    public function environment(Request $request)
    {
        return $this->table($request, EnvironmentSamples::class, route('nucleic.dashboard.modal.environment'));
    }

    public function parasite(Request $request)
    {
        return $this->table($request, ParasiteSamples::class, route('nucleic.dashboard.modal.parasite'));
    }

    public function culture(Request $request)
    {
        return $this->table($request, Cultures::class, route('nucleic.dashboard.modal.culture'));
    }

    public function pool(Request $request)
    {
        return $this->table($request, Pools::class, route('nucleic.dashboard.modal.pool'));
    }

    private function table(Request $request, ?string $sourceType, string $path)
    {
        $projectId = session('selected_project_id');
        $isGuestMode = $projectId === null;
        $poolContentTypesSub = DB::table('pool_contents')
            ->select('pools_id', DB::raw($this->poolContentTypesAggregateSql().' as pool_content_types'))
            ->groupBy('pools_id');

        $query = app(NucleicAcidsDashboardMapPointsController::class)
            ->filteredQuery($request, $isGuestMode, $projectId)
            ->leftJoin('sampling_sites as content_human_sites', 'content_human_sites.id', '=', 'content_human_samples.sampling_sites_id')
            ->leftJoin('sampling_sites as content_animal_sites', 'content_animal_sites.id', '=', 'content_animal_samples.sampling_sites_id')
            ->leftJoin('countries as content_animal_countries', 'content_animal_countries.id', '=', 'content_animal_sites.countries_id')
            ->leftJoin('environment_samples as content_environment_samples', function ($join) {
                $join->on('content_environment_samples.id', '=', 'nucleic_acids.nucleic_content_id')
                    ->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants(EnvironmentSamples::class));
            })
            ->leftJoin('sampling_sites as content_environment_sites', 'content_environment_sites.id', '=', 'content_environment_samples.sampling_sites_id')
            ->leftJoin('countries as content_environment_countries', 'content_environment_countries.id', '=', 'content_environment_sites.countries_id')
            ->leftJoin('parasite_sample_types as content_parasite_sample_types', 'content_parasite_sample_types.id', '=', 'content_parasite_samples.parasite_sample_types_id')
            ->leftJoinSub($poolContentTypesSub, 'pool_content_types', function ($join) {
                $join->on('pool_content_types.pools_id', '=', 'content_pools.id');
            });

        if ($sourceType) {
            $query->whereIn('nucleic_acids.nucleic_content_type', $this->typeVariants($sourceType));
        }

        $samples = $query
            ->select([
                'nucleic_acids.id',
                'nucleic_acids.code',
                'nucleic_acids.type',
                'nucleic_acids.nucleic_content_type',
                'nucleic_acids.nucleic_content_id',
                'nucleic_acids.date_extracted',
                'protocols.name as protocol',
                'laboratories.name as laboratory',
                DB::raw($this->peopleNameSql().' as extracted_by'),
                'content_humans.sex as human_sex',
                'content_humans.ethnicity as human_ethnicity',
                'content_humans.occupation as human_occupation',
                'content_countries.name as human_country',
                'content_human_sites.name as human_sampling_site',
                'content_animal_species.name_common as animal_species',
                'content_animals.sex as animal_sex',
                'content_animals.age as animal_age',
                'content_animal_sites.name as animal_sampling_site',
                'content_animal_countries.name as animal_country',
                'content_environment_sites.name as environment_sampling_site',
                'content_environment_countries.name as environment_country',
                'content_parasite_species.name_scientific as parasite_species',
                'content_parasites.stage as parasite_stage',
                'content_parasites.sex as parasite_sex',
                'content_parasite_sample_types.name as parasite_sample_type',
                'content_parasites.parasites_origin_type as parasite_content_type',
                'content_cultures.type as culture_type',
                'content_cultures.medium as culture_medium',
                'content_cultures.cultures_content_type as culture_content_type',
                'content_pools.nr_pooled as pool_nr_pooled',
                'pool_content_types.pool_content_types as pool_content_type',
            ])
            ->orderByDesc('nucleic_acids.created_at')
            ->paginate(25)
            ->withQueryString();

        $samples->withPath($path);

        $modalSourceKey = match ($sourceType) {
            HumanSamples::class => 'human',
            AnimalSamples::class => 'animal',
            EnvironmentSamples::class => 'environment',
            ParasiteSamples::class => 'parasite',
            Cultures::class => 'culture',
            Pools::class => 'pool',
            default => 'all',
        };

        return view('samples.nucleic_acids.modals.dashboard_nucleic_acids_table', [
            'samples' => $samples,
            'paginationPath' => $path,
            'modalSourceKey' => $modalSourceKey,
        ]);
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

    private function peopleNameSql(): string
    {
        $driver = DB::getDriverName();

        return match ($driver) {
            'mysql' => "TRIM(CONCAT_WS(' ', people.first_name, people.last_name))",
            'pgsql' => "TRIM(CONCAT(people.first_name, ' ', people.last_name))",
            default => "TRIM(COALESCE(people.first_name, '') || ' ' || COALESCE(people.last_name, ''))",
        };
    }

    private function poolContentTypesAggregateSql(): string
    {
        $driver = DB::getDriverName();

        return match ($driver) {
            'mysql' => "GROUP_CONCAT(DISTINCT samples_type ORDER BY samples_type SEPARATOR ', ')",
            'pgsql' => "string_agg(DISTINCT samples_type, ', ')",
            default => 'group_concat(DISTINCT samples_type)',
        };
    }
}
