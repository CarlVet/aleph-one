<?php

namespace App\Http\Controllers;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\Experiments;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use App\Models\Sequences;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SequencesDashboardModalTablesController extends Controller
{
    public function all(Request $request)
    {
        return $this->table($request, null, route('sequences.dashboard.modal.all'));
    }

    public function human(Request $request)
    {
        return $this->table($request, HumanSamples::class, route('sequences.dashboard.modal.human'));
    }

    public function animal(Request $request)
    {
        return $this->table($request, AnimalSamples::class, route('sequences.dashboard.modal.animal'));
    }

    public function environment(Request $request)
    {
        return $this->table($request, EnvironmentSamples::class, route('sequences.dashboard.modal.environment'));
    }

    public function culture(Request $request)
    {
        return $this->table($request, Cultures::class, route('sequences.dashboard.modal.culture'));
    }

    public function pool(Request $request)
    {
        return $this->table($request, Pools::class, route('sequences.dashboard.modal.pool'));
    }

    private function table(Request $request, ?string $sourceType, string $path)
    {
        $projectId = session('selected_project_id');
        $isGuestMode = $projectId === null;

        $query = Sequences::query()
            ->leftJoin('nucleic_acids as exp_na', 'sequences.nucleic_acids_id', '=', 'exp_na.id')
            ->leftJoin('experiments', function ($join) {
                $join->on('exp_na.nucleic_content_id', '=', 'experiments.id')
                    ->where('exp_na.nucleic_content_type', Experiments::class)
                    ->where('experiments.experiments_content_type', NucleicAcids::class);
            })
            ->leftJoin('nucleic_acids as orig_na', 'experiments.experiments_content_id', '=', 'orig_na.id')
            ->leftJoin('sub_project_assignments', function ($join) {
                $join->on('sub_project_assignments.assignable_id', '=', 'sequences.id')
                    ->where('sub_project_assignments.assignable_type', Sequences::class);
            })
            ->leftJoin('sub_projects', 'sub_projects.id', '=', 'sub_project_assignments.sub_project_id')
            ->leftJoin('laboratories', 'sequences.laboratories_id', '=', 'laboratories.id')
            ->leftJoin('people', 'sequences.people_id', '=', 'people.id');

        if ($isGuestMode) {
            $query->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tubes')
                    ->whereColumn('tubes.tubes_content_id', 'nucleic_acids.id')
                    ->where('tubes.tubes_content_type', NucleicAcids::class)
                    ->where('tubes.is_private', false);
            });
        } else {
            $query->where('sequences.projects_id', $projectId);
        }

        $sourceTypeFilter = (string) $request->query('sourceTypeFilter', 'all');
        if ($sourceTypeFilter !== '' && $sourceTypeFilter !== 'all') {
            $mapped = match ($sourceTypeFilter) {
                'human' => HumanSamples::class,
                'animal' => AnimalSamples::class,
                'environment' => EnvironmentSamples::class,
                'parasite' => ParasiteSamples::class,
                'culture' => Cultures::class,
                'pool' => Pools::class,
                default => null,
            };

            if ($mapped) {
                $query->where('orig_na.nucleic_content_type', $mapped);
            }
        }

        if ($sourceType) {
            $query->where('orig_na.nucleic_content_type', $sourceType);
        }

        $methodFilter = (string) $request->query('methodFilter', '');
        if ($methodFilter !== '') {
            $query->where('sequences.method', $methodFilter);
        }

        $instrumentFilter = (string) $request->query('instrumentFilter', '');
        if ($instrumentFilter !== '') {
            $query->where('sequences.instrument', $instrumentFilter);
        }

        $laboratoryFilter = (string) $request->query('laboratoryFilter', '');
        if ($laboratoryFilter !== '') {
            $query->where('laboratories.name', $laboratoryFilter);
        }

        $sequencedByFilter = (string) $request->query('sequencedByFilter', '');
        if ($sequencedByFilter !== '') {
            $query->whereRaw($this->peopleNameSql().' = ?', [$sequencedByFilter]);
        }
        $subProjectFilter = (string) $request->query('subProjectFilter', '');
        if ($subProjectFilter !== '') {
            $query->whereExists(function ($sub) use ($subProjectFilter) {
                $sub->select(DB::raw(1))
                    ->from('sub_project_assignments')
                    ->join('sub_projects', 'sub_projects.id', '=', 'sub_project_assignments.sub_project_id')
                    ->whereColumn('sub_project_assignments.assignable_id', 'sequences.id')
                    ->where('sub_project_assignments.assignable_type', Sequences::class)
                    ->where('sub_projects.code', $subProjectFilter);
            });
        }

        $startLength = (string) $request->query('startLength', '');
        $endLength = (string) $request->query('endLength', '');
        if ($startLength !== '' && $endLength !== '') {
            $query->whereBetween('sequences.length', [(int) $startLength, (int) $endLength]);
        } elseif ($startLength !== '') {
            $query->where('sequences.length', '>=', (int) $startLength);
        } elseif ($endLength !== '') {
            $query->where('sequences.length', '<=', (int) $endLength);
        }

        $startDate = (string) $request->query('startDate', '');
        $endDate = (string) $request->query('endDate', '');
        if ($startDate !== '' && $endDate !== '') {
            $query->whereBetween('sequences.date_sequenced', [$startDate, $endDate]);
        } elseif ($startDate !== '') {
            $query->where('sequences.date_sequenced', '>=', $startDate);
        } elseif ($endDate !== '') {
            $query->where('sequences.date_sequenced', '<=', $endDate);
        }

        $samples = $query
            ->select([
                'sequences.id',
                'sequences.code',
                'sequences.accession_number',
                'sequences.length',
                'sequences.method',
                'sequences.instrument',
                'sequences.date_sequenced',
                'exp_na.code as nucleic_code',
                'orig_na.nucleic_content_type as nucleic_content_type',
                'sub_projects.code as sub_project_code',
                'laboratories.name as laboratory',
                DB::raw($this->peopleNameSql().' as sequenced_by'),
            ])
            ->orderByDesc('sequences.created_at')
            ->paginate(25)
            ->withQueryString();

        $samples->withPath($path);

        return view('samples.sequences.modals.dashboard_sequences_table', [
            'samples' => $samples,
            'paginationPath' => $path,
        ]);
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
}
