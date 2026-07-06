<?php

namespace App\Http\Controllers;

use App\Models\AnimalVaccination;
use App\Models\Projects;
use App\Services\AnimalVaccinationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AnimalVaccinationController extends Controller
{
    protected $service;

    public function __construct(AnimalVaccinationService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        return view('samples.animals.vaccination.create', $this->service->assign());
    }

    public function store()
    {
        $projectId = session('selected_project_id');
        Projects::findOrFail($projectId);

        $rules = [
            'animal_id' => 'required|array|min:1',
            'animal_id.*' => 'required|exists:animals,id',
            'vaccine_name' => 'required|array|min:1',
            'vaccine_name.*' => 'required|string|max:255',
            'vaccine_type' => 'required|string|max:255',
            'date_administered' => 'required|date|before_or_equal:today',
            'next_due_date' => 'nullable|date|after:date_administered',
            'administered_by' => 'nullable|exists:people,id',
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

            $vaccineNames = collect(request()->input('vaccine_name', []))
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->unique()
                ->values();

            $createdCount = 0;

            DB::transaction(function () use ($animalIds, $vaccineNames, &$createdCount): void {
                foreach ($animalIds as $animalId) {
                    foreach ($vaccineNames as $vaccineName) {
                        AnimalVaccination::create([
                            'animals_id' => $animalId,
                            'vaccine_name' => $vaccineName,
                            'vaccine_type' => request('vaccine_type'),
                            'date_administered' => request('date_administered'),
                            'next_due_date' => request('next_due_date'),
                            'administered_by' => request('administered_by'),
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
                'animal_vaccination_created',
                'New Animal Vaccination Record',
                $user->people->first_name.' registered '.$createdCount.' animal vaccination record(s).',
                '/samples/animals/vaccination/list',
                $projectId
            );

            return response()->json([
                'success' => true,
                'message' => $createdCount.' animal vaccination record(s) registered successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Animal Vaccination creation error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred: '.$e->getMessage(),
            ], 500);
        }
    }
}
