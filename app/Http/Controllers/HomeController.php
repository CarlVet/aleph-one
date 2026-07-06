<?php

namespace App\Http\Controllers;

use App\Services\MetricsService;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    public function index()
    {
        try {
            $metricsService = new MetricsService;
            $metrics = $metricsService->getOverallMetrics();
        } catch (\Exception $e) {
            Log::error('Error loading home page metrics: '.$e->getMessage());
            // Provide default metrics if service fails
            $metrics = [
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
                    'pending' => 0,
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

        return view('home', compact('metrics'));
    }
}
