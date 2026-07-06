<?php

namespace App\Http\Controllers;

use App\Models\AnimalHealth;
use App\Models\ClinicalSigns;
use App\Models\Lesions;
use App\Models\Projects;
use App\Services\AnimalHealthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AnimalHealthController extends Controller
{
    protected $service;

    public function __construct(AnimalHealthService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        return view('samples.animals.health.create', $this->service->assign());
    }

    public function store()
    {
        $projectId = session('selected_project_id');
        Projects::findOrFail($projectId);

        $rules = [
            'animal_id' => 'required|array|min:1',
            'animal_id.*' => 'required|exists:animals,id',
            'health_status' => 'required|string|max:255',
            'check_date' => 'required|date|before_or_equal:today',
            'check_type' => 'required|string|max:255',
            'clinical_signs' => 'nullable|array',
            'clinical_signs.*' => 'nullable|string|max:255',
            'lesions' => 'nullable|array',
            'lesions.*' => 'nullable|string|max:255',
            'alive' => 'required|boolean',
            'notes' => 'nullable|string',
        ];

        $validator = Validator::make(request()->all(), $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {
            $animalIds = collect(request()->input('animal_id', []))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            $clinicalSigns = collect(request()->input('clinical_signs', []))
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->unique()
                ->values();

            $lesions = collect(request()->input('lesions', []))
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->unique()
                ->values();

            $clinicalSigns = $clinicalSigns->isNotEmpty() ? $clinicalSigns : collect([null]);
            $lesions = $lesions->isNotEmpty() ? $lesions : collect([null]);

            $createdCount = 0;

            DB::transaction(function () use ($animalIds, $clinicalSigns, $lesions, &$createdCount): void {
                foreach ($animalIds as $animalId) {
                    foreach ($clinicalSigns as $clinicalSign) {
                        foreach ($lesions as $lesion) {
                            $clinicalSignId = $clinicalSign
                                ? $this->service->check_or_create(ClinicalSigns::class, ['name' => $clinicalSign])
                                : null;

                            $lesionId = $lesion
                                ? $this->service->check_or_create(Lesions::class, ['name' => $lesion])
                                : null;

                            AnimalHealth::create([
                                'animals_id' => $animalId,
                                'health_status' => request('health_status'),
                                'check_date' => request('check_date'),
                                'check_type' => request('check_type'),
                                'clinical_signs_id' => $clinicalSignId,
                                'lesions_id' => $lesionId,
                                'alive' => request('alive'),
                                'notes' => request('notes'),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            $createdCount++;
                        }
                    }
                }
            });

            // Get the authenticated user
            $user = Auth::user();

            // Create notification
            NotificationController::create(
                'animal_health_created',
                'New Animal Health Record',
                $user->people->first_name.' registered '.$createdCount.' animal health record(s).',
                '/samples/animals/health/list',
                $projectId
            );

            return response()->json([
                'success' => true,
                'message' => $createdCount.' animal health record(s) registered successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Animal Health creation error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred: '.$e->getMessage(),
            ], 500);
        }
    }
}
