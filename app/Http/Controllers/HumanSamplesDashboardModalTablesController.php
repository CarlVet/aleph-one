<?php

namespace App\Http\Controllers;

use App\Models\HumanSamples;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HumanSamplesDashboardModalTablesController extends Controller
{
    public function samples(Request $request)
    {
        $projectId = session('selected_project_id');
        $isGuestMode = $projectId === null;
        $sampleVisibility = (string) $request->query('sampleVisibility', 'all');

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

        $samples = $query
            ->select([
                'human_samples.id',
                'human_samples.code',
                'human_samples.date_collected',
                'sample_types.name as sample_type',
                'sampling_sites.name as sampling_site',
                'humans.ethnicity',
                'humans.occupation',
                'countries.name as country',
            ])
            ->orderByDesc('human_samples.created_at')
            ->paginate(25)
            ->withQueryString();

        $samples->withPath(route('humans.dashboard.modal.samples'));

        return view('samples.humans.modals.dashboard_samples_table', [
            'samples' => $samples,
            'paginationPath' => route('humans.dashboard.modal.samples'),
        ]);
    }
}
