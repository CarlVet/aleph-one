<?php

namespace App\Services;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\Experiments;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pathogens;
use App\Models\Pools;
use App\Models\Projects;
use App\Models\Protocols;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MetricsService
{
    public function getOverallMetrics()
    {
        try {
            return [
                'samples' => $this->getSampleMetrics(),
                'experiments' => $this->getExperimentMetrics(),
                'projects' => $this->getProjectMetrics(),
                'pathogens' => $this->getPathogenMetrics(),
                'protocols' => $this->getProtocolMetrics(),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching metrics: '.$e->getMessage());

            return $this->getDefaultMetrics();
        }
    }

    private function getDefaultMetrics()
    {
        return [
            'samples' => [
                'total' => 0,
                'by_type' => [
                    'animal_samples' => 0,
                    'human_samples' => 0,
                    'environment_samples' => 0,
                    'parasite_samples' => 0,
                    'nucleic_acids' => 0,
                    'cultures' => 0,
                    'pools' => 0,
                ],
            ],
            'experiments' => [
                'total' => 0,
                'by_pathogen' => [],
            ],
            'projects' => [
                'total' => 0,
                'active' => 0,
                'completed' => 0,
            ],
            'pathogens' => [
                'total' => 0,
                'with_protocols' => 0,
                'without_protocols' => 0,
            ],
            'protocols' => [
                'total' => 0,
                'with_techniques' => 0,
                'with_pathogens' => 0,
                'with_studies' => 0,
            ],
        ];
    }

    private function getSampleMetrics()
    {
        try {
            return [
                'total' => $this->getTotalSamples(),
                'by_type' => [
                    'animal_samples' => AnimalSamples::count(),
                    'human_samples' => HumanSamples::count(),
                    'environment_samples' => EnvironmentSamples::count(),
                    'parasite_samples' => ParasiteSamples::count(),
                    'nucleic_acids' => NucleicAcids::count(),
                    'cultures' => Cultures::count(),
                    'pools' => Pools::count(),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching sample metrics: '.$e->getMessage());

            return $this->getDefaultMetrics()['samples'];
        }
    }

    private function getTotalSamples()
    {
        try {
            return AnimalSamples::count() +
                HumanSamples::count() +
                EnvironmentSamples::count() +
                ParasiteSamples::count() +
                NucleicAcids::count() +
                Cultures::count() +
                Pools::count();
        } catch (\Exception $e) {
            Log::error('Error calculating total samples: '.$e->getMessage());

            return 0;
        }
    }

    private function getExperimentMetrics()
    {
        try {
            $experiments = Experiments::select('pathogens_id', DB::raw('count(*) as count'))
                ->whereNotNull('pathogens_id')
                ->groupBy('pathogens_id')
                ->with('pathogens')
                ->get();

            $hierarchicalData = [];
            foreach ($experiments as $exp) {
                if ($exp->pathogens) {
                    $domain = $exp->pathogens->domain ?? 'Unknown Domain';
                    $family = $exp->pathogens->family ?? 'Unknown Family';
                    $species = $exp->pathogens->species ?? 'Unknown Species';

                    if (! isset($hierarchicalData[$domain])) {
                        $hierarchicalData[$domain] = [
                            'count' => 0,
                            'families' => [],
                        ];
                    }

                    if (! isset($hierarchicalData[$domain]['families'][$family])) {
                        $hierarchicalData[$domain]['families'][$family] = [
                            'count' => 0,
                            'species' => [],
                        ];
                    }

                    if (! isset($hierarchicalData[$domain]['families'][$family]['species'][$species])) {
                        $hierarchicalData[$domain]['families'][$family]['species'][$species] = 0;
                    }

                    $hierarchicalData[$domain]['families'][$family]['species'][$species] += $exp->count;
                    $hierarchicalData[$domain]['families'][$family]['count'] += $exp->count;
                    $hierarchicalData[$domain]['count'] += $exp->count;
                }
            }

            // Sort by count descending at each level
            foreach ($hierarchicalData as $domain => &$domainData) {
                uasort($domainData['families'], function ($a, $b) {
                    return $b['count'] <=> $a['count'];
                });

                foreach ($domainData['families'] as &$familyData) {
                    arsort($familyData['species']);
                }
            }

            uasort($hierarchicalData, function ($a, $b) {
                return $b['count'] <=> $a['count'];
            });

            return [
                'total' => Experiments::count(),
                'by_pathogen' => $hierarchicalData,
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching experiment metrics: '.$e->getMessage());

            return $this->getDefaultMetrics()['experiments'];
        }
    }

    private function getProjectMetrics()
    {
        try {
            return [
                'total' => Projects::count(),
                'active' => Projects::where(function ($query) {
                    $query->whereNull('date_end')
                        ->orWhere('date_end', '>', Carbon::today());
                })->count(),
                'completed' => Projects::where('date_end', '<=', Carbon::today())->count(),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching project metrics: '.$e->getMessage());

            return $this->getDefaultMetrics()['projects'];
        }
    }

    private function getPathogenMetrics()
    {
        try {
            return [
                'total' => Pathogens::count(),
                'with_protocols' => Pathogens::whereHas('protocols')->count(),
                'without_protocols' => Pathogens::whereDoesntHave('protocols')->count(),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching pathogen metrics: '.$e->getMessage());

            return $this->getDefaultMetrics()['pathogens'];
        }
    }

    private function getProtocolMetrics()
    {
        try {
            return [
                'total' => Protocols::count(),
                'with_techniques' => Protocols::whereHas('techniques')->count(),
                'with_pathogens' => Protocols::whereHas('pathogens')->count(),
                'with_studies' => Protocols::whereHas('studies')->count(),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching protocol metrics: '.$e->getMessage());

            return $this->getDefaultMetrics()['protocols'];
        }
    }
}
