<?php

namespace App\Http\Controllers;

use App\Models\AnimalMedication;
use App\Models\Projects;
use App\Services\AnimalMedicationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AnimalMedicationController extends Controller
{
    protected $service;

    public function __construct(AnimalMedicationService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        return view('samples.animals.medication.create', $this->service->assign());
    }

    public function store()
    {
        $projectId = session('selected_project_id');
        Projects::findOrFail($projectId);

        $rules = [
            'animal_id' => 'required|array|min:1',
            'animal_id.*' => 'required|exists:animals,id',
            'medication_name' => 'required|array|min:1',
            'medication_name.*' => 'required|string|max:255',
            'dosage' => 'nullable|string|max:255',
            'start_date' => 'required|date|before_or_equal:today',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'prescribed_by' => 'nullable|exists:people,id',
            'notes' => 'nullable|string',
        ];

        $validator = Validator::make(request()->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please fix the errors and try again.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $animalIds = collect(request()->input('animal_id', []))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            $medicationNames = collect(request()->input('medication_name', []))
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->unique()
                ->values();

            $createdCount = 0;

            DB::transaction(function () use ($animalIds, $medicationNames, &$createdCount): void {
                foreach ($animalIds as $animalId) {
                    foreach ($medicationNames as $medicationName) {
                        AnimalMedication::create([
                            'animals_id' => $animalId,
                            'medication_name' => $medicationName,
                            'dosage' => request('dosage'),
                            'start_date' => request('start_date'),
                            'end_date' => request('end_date'),
                            'prescribed_by' => request('prescribed_by'),
                            'notes' => request('notes'),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $createdCount++;
                    }
                }
            });

            // Get the authenticated user
            $user = Auth::user();

            // Create notification
            NotificationController::create(
                'animal_medication_created',
                'New Animal Medication Record',
                $user->people->first_name.' registered '.$createdCount.' animal medication record(s).',
                '/samples/animals/medication/list',
                $projectId
            );

            return response()->json([
                'success' => true,
                'message' => $createdCount.' animal medication record(s) registered successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Animal Medication creation error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred: '.$e->getMessage(),
            ], 500);
        }
    }
}
