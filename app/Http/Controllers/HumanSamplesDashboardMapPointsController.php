<?php

namespace App\Http\Controllers;

use App\Models\HumanSamples;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HumanSamplesDashboardMapPointsController extends Controller
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

        $query = HumanSamples::query()
            ->leftJoin('sample_types', 'human_samples.sample_types_id', '=', 'sample_types.id')
            ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
            ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id')
            ->leftJoin('sampling_sites', 'human_samples.sampling_sites_id', '=', 'sampling_sites.id');

        if ($isGuestMode) {
            $query->where('human_samples.processed', true);
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'human_samples.id')
                    ->where('tubes.tubes_content_type', HumanSamples::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $query->where('human_samples.projects_id', $projectId);

            if ($sampleVisibility === 'processed_with_tubes') {
                $query->where('human_samples.processed', true);
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tubes')
                        ->whereColumn('tubes.tubes_content_id', 'human_samples.id')
                        ->where('tubes.tubes_content_type', HumanSamples::class);
                });
            }
        }

        $sampleTypeFilter = (string) $request->query('sampleTypeFilter', '');
        if ($sampleTypeFilter !== '') {
            $query->where('sample_types.name', $sampleTypeFilter);
        }

        $samplingSiteFilter = (string) $request->query('samplingSiteFilter', '');
        if ($samplingSiteFilter !== '') {
            $query->where('sampling_sites.name', $samplingSiteFilter);
        }

        $ethnicityFilter = (string) $request->query('ethnicityFilter', '');
        if ($ethnicityFilter !== '') {
            $query->where('humans.ethnicity', $ethnicityFilter);
        }

        $occupationFilter = (string) $request->query('occupationFilter', '');
        if ($occupationFilter !== '') {
            $query->where('humans.occupation', $occupationFilter);
        }

        $countryFilter = (string) $request->query('countryFilter', '');
        if ($countryFilter !== '') {
            $query->where('countries.name', $countryFilter);
        }

        $subProjectFilter = (string) $request->query('subProjectFilter', '');
        if ($subProjectFilter !== '') {
            $query->whereExists(function ($sub) use ($subProjectFilter) {
                $sub->select(DB::raw(1))
                    ->from('sub_project_assignments')
                    ->join('sub_projects', 'sub_projects.id', '=', 'sub_project_assignments.sub_project_id')
                    ->whereColumn('sub_project_assignments.assignable_id', 'human_samples.id')
                    ->where('sub_project_assignments.assignable_type', HumanSamples::class)
                    ->where('sub_projects.code', $subProjectFilter);
            });
        }

        $startDate = (string) $request->query('startDate', '');
        $endDate = (string) $request->query('endDate', '');
        if ($startDate !== '' && $endDate !== '') {
            $query->whereBetween('human_samples.date_collected', [$startDate, $endDate]);
        } elseif ($startDate !== '') {
            $query->where('human_samples.date_collected', '>=', $startDate);
        } elseif ($endDate !== '') {
            $query->where('human_samples.date_collected', '<=', $endDate);
        }

        if ($visualizeBy === 'patients') {
            $latestPerHuman = (clone $query)
                ->select([
                    'humans.id as cursor_id',
                    DB::raw('MAX(human_samples.id) as human_sample_id'),
                ])
                ->whereNotNull('humans.id')
                ->groupBy('humans.id');

            $rows = HumanSamples::query()
                ->joinSub($latestPerHuman, 'latest', function ($join) {
                    $join->on('human_samples.id', '=', 'latest.human_sample_id');
                })
                ->leftJoin('sample_types', 'human_samples.sample_types_id', '=', 'sample_types.id')
                ->leftJoin('humans', 'human_samples.humans_id', '=', 'humans.id')
                ->leftJoin('countries', 'humans.countries_id', '=', 'countries.id')
                ->leftJoin('sampling_sites', 'human_samples.sampling_sites_id', '=', 'sampling_sites.id')
                ->select([
                    'latest.cursor_id',
                    'human_samples.code',
                    'human_samples.date_collected',
                    DB::raw('COALESCE(human_samples.latitude, sampling_sites.latitude) as latitude'),
                    DB::raw('COALESCE(human_samples.longitude, sampling_sites.longitude) as longitude'),
                    'sample_types.name as sample_type',
                    'sampling_sites.name as sampling_site',
                    'humans.ethnicity',
                    'humans.occupation',
                    'humans.sex',
                    'humans.date_of_birth',
                    'countries.name as country',
                ])
                ->where('latest.cursor_id', '>', $cursor)
                ->orderBy('latest.cursor_id')
                ->limit($limit)
                ->get();
        } else {
            $rows = $query
                ->select([
                    'human_samples.id',
                    'human_samples.code',
                    'human_samples.date_collected',
                    DB::raw('COALESCE(human_samples.latitude, sampling_sites.latitude) as latitude'),
                    DB::raw('COALESCE(human_samples.longitude, sampling_sites.longitude) as longitude'),
                    'sample_types.name as sample_type',
                    'sampling_sites.name as sampling_site',
                    'humans.ethnicity',
                    'humans.occupation',
                    'humans.sex',
                    'humans.date_of_birth',
                    'countries.name as country',
                ])
                ->where('human_samples.id', '>', $cursor)
                ->orderBy('human_samples.id')
                ->limit($limit)
                ->get();
        }

        $points = [];
        foreach ($rows as $row) {
            if (! $row->latitude || ! $row->longitude) {
                continue;
            }

            $age = null;
            if ($row->date_of_birth) {
                $age = Carbon::parse($row->date_of_birth)->age;
            }

            $ageRange = 'Unknown';
            if ($age !== null) {
                $ageRange = match (true) {
                    $age < 18 => '0-17',
                    $age < 30 => '18-29',
                    $age < 50 => '30-49',
                    $age < 70 => '50-69',
                    default => '70+',
                };
            }

            $points[] = [
                'latitude' => (float) $row->latitude,
                'longitude' => (float) $row->longitude,
                'code' => $row->code,
                'type' => $row->sample_type ?? 'Unknown',
                'sampling_site' => $row->sampling_site ?? 'Unknown',
                'ethnicity' => $row->ethnicity ?? 'Unknown',
                'occupation' => $row->occupation ?? 'Unknown',
                'country' => $row->country ?? 'Unknown',
                'sex' => $row->sex ?? 'Unknown',
                'age' => $age,
                'age_range' => $ageRange,
                'date_collected' => $row->date_collected,
            ];
        }

        $nextCursor = null;
        if ($rows->count() === $limit) {
            $nextCursor = $visualizeBy === 'patients'
                ? (int) $rows->last()->cursor_id
                : (int) $rows->last()->id;
        }

        return response()->json([
            'points' => $points,
            'next_cursor' => $nextCursor,
        ]);
    }
}
