<?php

namespace App\Http\Controllers;

use App\Models\AnimalSamples;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnimalSamplesDashboardMapPointsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $projectId = session('selected_project_id');
        $isGuestMode = $projectId === null;

        $visualizeBy = (string) $request->query('visualize_by', 'samples');
        $sampleVisibility = (string) $request->query('sampleVisibility', 'all');

        $cursor = (int) $request->query('cursor', 0);
        $limit = (int) $request->query('limit', 1500);
        $limit = max(100, min($limit, 2000));

        $base = AnimalSamples::query()
            ->leftJoin('animals', 'animal_samples.animals_id', '=', 'animals.id')
            ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
            ->leftJoin('sample_types', 'animal_samples.sample_types_id', '=', 'sample_types.id')
            ->leftJoin('sampling_sites', 'animal_samples.sampling_sites_id', '=', 'sampling_sites.id');

        if ($isGuestMode) {
            $base->where('animal_samples.processed', true);
            $base->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                    ->where('tubes.tubes_content_type', AnimalSamples::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $base->where(function ($q) use ($projectId) {
                $q->where('animal_samples.projects_id', $projectId)
                    ->orWhereExists(function ($sub) use ($projectId) {
                        $sub->select(DB::raw(1))
                            ->from('tubes')
                            ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                            ->where('tubes.tubes_content_type', AnimalSamples::class)
                            ->where('tubes.projects_id', $projectId);
                    });
            });

            if ($sampleVisibility === 'processed_with_tubes') {
                $base->where('animal_samples.processed', true);
                $base->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tubes')
                        ->whereColumn('tubes.tubes_content_id', 'animal_samples.id')
                        ->where('tubes.tubes_content_type', AnimalSamples::class);
                });
            }
        }

        $animalSpeciesFilter = (string) $request->query('animal_species_filter', 'All');
        if ($animalSpeciesFilter !== '' && $animalSpeciesFilter !== 'All') {
            $base->where('animal_species.name_common', $animalSpeciesFilter);
        }

        $sampleTypeFilter = (string) $request->query('sample_type_filter', 'All');
        if ($sampleTypeFilter !== '' && $sampleTypeFilter !== 'All') {
            $base->where('sample_types.name', $sampleTypeFilter);
        }

        $samplingSiteFilter = (string) $request->query('sampling_site_filter', 'All');
        if ($samplingSiteFilter !== '' && $samplingSiteFilter !== 'All') {
            $base->where('sampling_sites.name', $samplingSiteFilter);
        }

        $subProjectFilter = (string) $request->query('subProjectFilter', '');
        if ($subProjectFilter !== '' && $subProjectFilter !== 'All') {
            $base->whereExists(function ($sub) use ($subProjectFilter) {
                $sub->select(DB::raw(1))
                    ->from('sub_project_assignments')
                    ->join('sub_projects', 'sub_projects.id', '=', 'sub_project_assignments.sub_project_id')
                    ->whereColumn('sub_project_assignments.assignable_id', 'animal_samples.id')
                    ->where('sub_project_assignments.assignable_type', AnimalSamples::class)
                    ->where('sub_projects.code', $subProjectFilter);
            });
        }

        $startDate = (string) $request->query('startDate', '');
        $endDate = (string) $request->query('endDate', '');
        if ($startDate !== '' && $endDate !== '') {
            $base->whereBetween('animal_samples.date_collected', [$startDate, $endDate]);
        } elseif ($startDate !== '') {
            $base->where('animal_samples.date_collected', '>=', $startDate);
        } elseif ($endDate !== '') {
            $base->where('animal_samples.date_collected', '<=', $endDate);
        }

        if ($visualizeBy === 'animals') {
            $latestPerAnimal = (clone $base)
                ->select([
                    'animals.id as cursor_id',
                    DB::raw('MAX(animal_samples.id) as animal_sample_id'),
                ])
                ->whereNotNull('animals.id')
                ->groupBy('animals.id');

            $rows = AnimalSamples::query()
                ->joinSub($latestPerAnimal, 'latest', function ($join) {
                    $join->on('animal_samples.id', '=', 'latest.animal_sample_id');
                })
                ->leftJoin('animals', 'animal_samples.animals_id', '=', 'animals.id')
                ->leftJoin('animal_species', 'animals.animal_species_id', '=', 'animal_species.id')
                ->leftJoin('sample_types', 'animal_samples.sample_types_id', '=', 'sample_types.id')
                ->leftJoin('sampling_sites', 'animal_samples.sampling_sites_id', '=', 'sampling_sites.id')
                ->select([
                    'latest.cursor_id',
                    'animal_samples.code',
                    'animal_samples.date_collected',
                    DB::raw('COALESCE(animal_samples.latitude, sampling_sites.latitude) as latitude'),
                    DB::raw('COALESCE(animal_samples.longitude, sampling_sites.longitude) as longitude'),
                    'animals.code as animal_code',
                    'animal_species.name_common as species_name_common',
                    'animals.sex as animal_sex',
                    'animals.age as animal_age',
                    'sample_types.name as sample_type',
                    'sampling_sites.name as sampling_site',
                ])
                ->where('latest.cursor_id', '>', $cursor)
                ->orderBy('latest.cursor_id')
                ->limit($limit)
                ->get();
        } else {
            $rows = (clone $base)
                ->select([
                    'animal_samples.id',
                    'animal_samples.code',
                    'animal_samples.date_collected',
                    DB::raw('COALESCE(animal_samples.latitude, sampling_sites.latitude) as latitude'),
                    DB::raw('COALESCE(animal_samples.longitude, sampling_sites.longitude) as longitude'),
                    'animals.code as animal_code',
                    'animal_species.name_common as species_name_common',
                    'animals.sex as animal_sex',
                    'animals.age as animal_age',
                    'sample_types.name as sample_type',
                    'sampling_sites.name as sampling_site',
                ])
                ->where('animal_samples.id', '>', $cursor)
                ->orderBy('animal_samples.id')
                ->limit($limit)
                ->get();
        }

        $points = [];
        foreach ($rows as $row) {
            if (! $row->latitude || ! $row->longitude) {
                continue;
            }

            $points[] = [
                'latitude' => (float) $row->latitude,
                'longitude' => (float) $row->longitude,
                'code' => $row->code,
                'animal_code' => $row->animal_code ?? 'Unknown',
                'species' => $row->species_name_common ?? 'Unknown',
                'sex' => $row->animal_sex ?? 'Unknown',
                'age' => $row->animal_age ?? 'Unknown',
                'type' => $row->sample_type ?? 'Unknown',
                'sampling_site' => $row->sampling_site ?? 'Unknown',
                'date_collected' => $row->date_collected,
            ];
        }

        $nextCursor = null;
        if ($rows->count() === $limit) {
            $nextCursor = $visualizeBy === 'animals'
                ? (int) $rows->last()->cursor_id
                : (int) $rows->last()->id;
        }

        return response()->json([
            'points' => $points,
            'next_cursor' => $nextCursor,
        ]);
    }
}
