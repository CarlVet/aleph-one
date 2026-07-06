<?php

namespace App\Livewire;

use App\Models\Experiments;
use App\Services\ExperimentsService;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class ExperimentsStatistics extends PlainComponent
{
    use WithPagination;

    public $selectedPathogen;

    public $selectedTimeRange = 'all';

    public $selectedGroup = 'all';

    public $confidenceLevel = 95;

    public $prevalenceData;

    public $riskFactors;

    public $associationData;

    public $regressionData;

    protected $listeners = ['pathogenSelected', 'timeRangeChanged', 'groupChanged'];

    protected $projectId = 1;

    public function mount()
    {
        $this->loadInitialData();
    }

    public function loadInitialData()
    {
        $this->prevalenceData = $this->calculatePrevalence();
        $this->riskFactors = $this->analyzeRiskFactors();
        $this->associationData = $this->analyzeAssociations();
        $this->regressionData = $this->performRegression();
    }

    public function pathogenSelected($pathogenId)
    {
        $this->selectedPathogen = $pathogenId;
        $this->loadInitialData();
    }

    public function timeRangeChanged($range)
    {
        $this->selectedTimeRange = $range;
        $this->loadInitialData();
    }

    public function groupChanged($group)
    {
        $this->selectedGroup = $group;
        $this->loadInitialData();
    }

    protected function calculatePrevalence()
    {
        $query = Experiments::query();

        // Apply filters based on selected options
        if ($this->selectedPathogen) {
            $query->where('pathogens_id', $this->selectedPathogen);
        }

        if ($this->selectedTimeRange !== 'all') {
            $query->whereBetween('date_tested', $this->getDateRange($this->selectedTimeRange));
        }

        if ($this->selectedGroup !== 'all') {
            $query->whereHas('experiments_content', function ($q) {
                $q->where('experiments_content_type', $this->getContentType($this->selectedGroup));
            });
        }

        // Calculate total samples and positive samples
        $totalSamples = $query->count();
        $positiveSamples = $query->where('outcome_binary', 1)->count();

        // Calculate prevalence and confidence interval
        $prevalence = $totalSamples > 0 ? ($positiveSamples / $totalSamples) * 100 : 0;
        $ci = $this->calculateConfidenceInterval($positiveSamples, $totalSamples, $this->confidenceLevel);

        return [
            'total_samples' => $totalSamples,
            'positive_samples' => $positiveSamples,
            'prevalence' => round($prevalence, 2),
            'confidence_interval' => [
                'lower' => round($ci['lower'] * 100, 2),
                'upper' => round($ci['upper'] * 100, 2),
            ],
        ];
    }

    protected function analyzeRiskFactors()
    {
        $query = Experiments::query()
            ->with(['places', 'protocols'])
            ->select(
                'places_id',
                'protocols_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(outcome_binary) as positives')
            )
            ->groupBy('places_id', 'protocols_id');

        if ($this->selectedPathogen) {
            $query->where('pathogens_id', $this->selectedPathogen);
        }

        $results = $query->get();

        $riskFactors = [];
        foreach ($results as $result) {
            $place = $result->places;
            $protocol = $result->protocols;

            $prevalence = $result->total > 0 ? ($result->positives / $result->total) * 100 : 0;
            $ci = $this->calculateConfidenceInterval($result->positives, $result->total, $this->confidenceLevel);

            $riskFactors[] = [
                'factor' => $place->name,
                'protocol' => $protocol->name,
                'total' => $result->total,
                'positives' => $result->positives,
                'prevalence' => round($prevalence, 2),
                'confidence_interval' => [
                    'lower' => round($ci['lower'] * 100, 2),
                    'upper' => round($ci['upper'] * 100, 2),
                ],
            ];
        }

        return $riskFactors;
    }

    protected function analyzeAssociations()
    {
        // Implement association analysis (e.g., Chi-square test)
        // This is a placeholder for the actual implementation
        return [];
    }

    protected function performRegression()
    {
        // Implement regression analysis
        // This is a placeholder for the actual implementation
        return [];
    }

    protected function calculateConfidenceInterval($successes, $total, $confidenceLevel)
    {
        if ($total === 0) {
            return ['lower' => 0, 'upper' => 0];
        }

        $p = $successes / $total;
        $z = $this->getZScore($confidenceLevel);
        $standardError = sqrt(($p * (1 - $p)) / $total);

        return [
            'lower' => max(0, $p - ($z * $standardError)),
            'upper' => min(1, $p + ($z * $standardError)),
        ];
    }

    protected function getZScore($confidenceLevel)
    {
        $zScores = [
            90 => 1.645,
            95 => 1.96,
            99 => 2.576,
        ];

        return $zScores[$confidenceLevel] ?? 1.96;
    }

    protected function getDateRange($range)
    {
        $now = now();

        return match ($range) {
            'last_month' => [$now->copy()->subMonth(), $now],
            'last_3_months' => [$now->copy()->subMonths(3), $now],
            'last_6_months' => [$now->copy()->subMonths(6), $now],
            'last_year' => [$now->copy()->subYear(), $now],
            default => [null, null]
        };
    }

    protected function getContentType($group)
    {
        return match ($group) {
            'animal' => 'App\Models\AnimalSamples',
            'parasite' => 'App\Models\ParasiteSamples',
            'nucleic' => 'App\Models\NucleicAcids',
            default => null
        };
    }

    public function render()
    {
        $service = app(ExperimentsService::class);
        $data = $service->assign();

        return view('livewire.experiments-statistics', [
            'pathogens' => $data['experiment_pathogens_available'],
            'prevalence' => $this->calculatePrevalence(),
            'riskFactors' => $this->analyzeRiskFactors(),
            'associations' => $this->analyzeAssociations(),
            'regression' => $this->performRegression(),
            'experiments' => $data['experiments'],
            'experiments_animals' => $data['experiments_animals'],
            'experiments_parasites' => $data['experiments_parasites'],
            'experiments_nucleic' => $data['experiments_nucleic'],
            'experiments_nucleic_animal' => $data['experiments_nucleic_animal'],
            'experiments_nucleic_parasite' => $data['experiments_nucleic_parasite'],
        ]);
    }
}
