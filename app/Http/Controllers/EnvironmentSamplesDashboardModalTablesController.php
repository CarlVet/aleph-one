<?php

namespace App\Http\Controllers;

use App\Models\EnvironmentSamples;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnvironmentSamplesDashboardModalTablesController extends Controller
{
    public function samples(Request $request)
    {
        $projectId = session('selected_project_id');
        $isGuestMode = $projectId === null;
        $sampleVisibility = (string) $request->query('sampleVisibility', 'all');

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

        $samples = $query
            ->select([
                'environment_samples.id',
                'environment_samples.code',
                'environment_samples.date_collected',
                'environment_sample_types.name as sample_type',
                'sampling_sites.name as sampling_site',
                'people.first_name as collector_first_name',
                'people.last_name as collector_last_name',
            ])
            ->orderByDesc('environment_samples.created_at')
            ->paginate(25)
            ->withQueryString();

        $samples->withPath(route('environment.dashboard.modal.samples'));

        return view('samples.environment.modals.dashboard_samples_table', [
            'samples' => $samples,
            'paginationPath' => route('environment.dashboard.modal.samples'),
        ]);
    }
}
