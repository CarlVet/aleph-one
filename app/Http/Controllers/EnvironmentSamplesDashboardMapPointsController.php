<?php

namespace App\Http\Controllers;

use App\Models\EnvironmentSamples;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnvironmentSamplesDashboardMapPointsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $projectId = session('selected_project_id');
        $isGuestMode = $projectId === null;
        $sampleVisibility = (string) $request->query('sampleVisibility', 'all');

        $cursor = (int) $request->query('cursor', 0);
        $limit = (int) $request->query('limit', 1500);
        $limit = max(100, min($limit, 2000));

        $query = EnvironmentSamples::query()
            ->leftJoin('environment_sample_types', 'environment_samples.environment_sample_types_id', '=', 'environment_sample_types.id')
            ->leftJoin('sampling_sites', 'environment_samples.sampling_sites_id', '=', 'sampling_sites.id')
            ->leftJoin('people', 'environment_samples.people_id', '=', 'people.id');

        if ($isGuestMode) {
            $query->where('environment_samples.processed', true);
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'environment_samples.id')
                    ->where('tubes.tubes_content_type', EnvironmentSamples::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $query->where('environment_samples.projects_id', $projectId);

            if ($sampleVisibility === 'processed_with_tubes') {
                $query->where('environment_samples.processed', true);
                $query->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tubes')
                        ->whereColumn('tubes.tubes_content_id', 'environment_samples.id')
                        ->where('tubes.tubes_content_type', EnvironmentSamples::class);
                });
            }
        }

        $sampleTypeFilter = (string) $request->query('sampleTypeFilter', '');
        if ($sampleTypeFilter !== '') {
            $query->where('environment_sample_types.name', $sampleTypeFilter);
        }

        $samplingSiteFilter = (string) $request->query('samplingSiteFilter', '');
        if ($samplingSiteFilter !== '') {
            $query->where('sampling_sites.name', $samplingSiteFilter);
        }

        $subProjectFilter = (string) $request->query('subProjectFilter', '');
        if ($subProjectFilter !== '') {
            $query->whereExists(function ($sub) use ($subProjectFilter) {
                $sub->select(DB::raw(1))
                    ->from('sub_project_assignments')
                    ->join('sub_projects', 'sub_projects.id', '=', 'sub_project_assignments.sub_project_id')
                    ->whereColumn('sub_project_assignments.assignable_id', 'environment_samples.id')
                    ->where('sub_project_assignments.assignable_type', EnvironmentSamples::class)
                    ->where('sub_projects.code', $subProjectFilter);
            });
        }

        $startDate = (string) $request->query('startDate', '');
        $endDate = (string) $request->query('endDate', '');
        if ($startDate !== '' && $endDate !== '') {
            $query->whereBetween('environment_samples.date_collected', [$startDate, $endDate]);
        } elseif ($startDate !== '') {
            $query->where('environment_samples.date_collected', '>=', $startDate);
        } elseif ($endDate !== '') {
            $query->where('environment_samples.date_collected', '<=', $endDate);
        }

        $rows = $query
            ->select([
                'environment_samples.id',
                'environment_samples.code',
                'environment_samples.date_collected',
                DB::raw('COALESCE(environment_samples.latitude, sampling_sites.latitude) as latitude'),
                DB::raw('COALESCE(environment_samples.longitude, sampling_sites.longitude) as longitude'),
                'environment_sample_types.name as sample_type',
                'sampling_sites.name as sampling_site',
                'people.first_name as collector_first_name',
                'people.last_name as collector_last_name',
            ])
            ->where('environment_samples.id', '>', $cursor)
            ->orderBy('environment_samples.id')
            ->limit($limit)
            ->get();

        $points = [];
        foreach ($rows as $row) {
            if (! $row->latitude || ! $row->longitude) {
                continue;
            }

            $collector = trim((string) ($row->collector_first_name ?? '').' '.(string) ($row->collector_last_name ?? ''));
            if ($collector === '') {
                $collector = 'Unknown';
            }

            $points[] = [
                'latitude' => (float) $row->latitude,
                'longitude' => (float) $row->longitude,
                'code' => $row->code,
                'type' => $row->sample_type ?? 'Unknown',
                'sampling_site' => $row->sampling_site ?? 'Unknown',
                'collector' => $collector,
                'date_collected' => $row->date_collected,
            ];
        }

        $nextCursor = null;
        if ($rows->count() === $limit) {
            $nextCursor = (int) $rows->last()->id;
        }

        return response()->json([
            'points' => $points,
            'next_cursor' => $nextCursor,
        ]);
    }
}
