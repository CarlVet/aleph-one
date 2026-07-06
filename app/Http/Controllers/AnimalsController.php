<?php

namespace App\Http\Controllers;

use App\Models\Animals;
use App\Models\AnimalSpecies;
use App\Models\Humans;
use App\Models\Organizations;
use App\Models\Projects;
use App\Services\AnimalSamplesService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AnimalsController extends Controller
{
    protected $service;

    public function __construct(AnimalSamplesService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        return view('animals.create', $this->service->assign());
    }

    public function store()
    {
        $projectId = session('selected_project_id');

        if (! $projectId) {
            session()->flash('error', 'No project selected. Please select a project first.');

            return back()->withInput();
        }

        $project = Projects::findOrFail($projectId);
        $project_code = $project->code;

        $rules = [
            'animal_species' => 'required|string',
            'number_of_animals' => 'required|integer|min:1|max:100',
            'field_labels' => 'required|array|min:1',
            'field_labels.*' => 'required|string',
            'sex' => 'required|in:Male,Female,NA',
            'age' => 'required|in:Juvenile,Sub-adult,Adult,Old,NA',
            'owner_type' => 'required|in:individual,organization',
            'owner_person' => 'required_if:owner_type,individual|nullable|exists:humans,id',
            'owner_organization' => 'required_if:owner_type,organization|nullable|exists:organizations,id',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png,webp,tif,tiff,pdf|max:51200', // 50MB max
        ];

        $validator = Validator::make(request()->all(), $rules);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {
            $photoPath = null;
            if (request()->hasFile('photo')) {
                $photoPath = request()->file('photo')->store('animals', 'local');
            }

            // Handle custom species if provided
            $animal_species_id = null;
            if (request('animal_species') && ! request('new_species_family') && ! request('new_species_scientific')) {
                // Use existing species
                $species = AnimalSpecies::where('name_common', request('animal_species'))->first();
                if ($species) {
                    $animal_species_id = $species->id;
                }
            } else {
                // Create new species
                $animal_species_id = $this->service->check_or_create(
                    AnimalSpecies::class,
                    ['name_common' => request('animal_species')],
                    [
                        'name_scientific' => request('new_species_scientific'),
                        'family' => request('new_species_family'),
                    ]
                );
            }

            Log::info('Species ID determined', ['species_id' => $animal_species_id]);

            // Get owner information
            $ownerType = request('owner_type') === 'individual'
                ? Humans::class
                : Organizations::class;
            $ownerId = request('owner_type') === 'individual'
                ? request('owner_person')
                : request('owner_organization');

            $fieldLabels = request('field_labels');
            $numberOfAnimals = count($fieldLabels);
            $createdAnimals = [];

            // Get animal species slug (short, uppercase, no spaces)
            $species = AnimalSpecies::find($animal_species_id);
            $species_slug = $species ? strtoupper(str_replace(' ', '', substr($species->name_common, 0, 6))) : 'UNK';

            // Create multiple animals
            for ($i = 0; $i < $numberOfAnimals; $i++) {
                // Generate unique animal code
                $existingAnimalCodes = Animals::where('projects_id', $projectId)
                    ->where('code', 'like', $project_code.'-AN-%')
                    ->pluck('code');

                $usedNumbers = $existingAnimalCodes->map(function ($code) {
                    preg_match('/-AN-(\d+)$/', $code, $matches);

                    return isset($matches[1]) ? (int) $matches[1] : null;
                })->filter()->sort()->values();

                $newSerial = 1;
                foreach ($usedNumbers as $num) {
                    if ($num != $newSerial) {
                        break;
                    }
                    $newSerial++;
                }

                $animal_code = $project_code.'-AN-'.$newSerial;
                $field_label = $fieldLabels[$i];

                $animal = Animals::create([
                    'code' => $animal_code,
                    'animal_species_id' => $animal_species_id,
                    'field_label' => $field_label,
                    'sex' => request('sex'),
                    'age' => request('age'),
                    'owner_type' => $ownerType,
                    'owner_id' => $ownerId,
                    'pic_path' => $photoPath,
                    'projects_id' => $projectId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $createdAnimals[] = $animal;
            }

            // Flash success message to the session
            $message = $numberOfAnimals === 1
                ? 'Animal registered successfully!'
                : "{$numberOfAnimals} animals registered successfully!";
            session()->flash('success', $message);

            Log::info('Animals registration completed', [
                'number_created' => count($createdAnimals),
                'message' => $message,
            ]);

            // Get the authenticated user
            $user = Auth::user();

            // Create a single notification for all animals registered
            $notificationMessage = $numberOfAnimals === 1
                ? $user->people->first_name.' registered a new animal: '.$createdAnimals[0]->code
                : $user->people->first_name.' registered '.$numberOfAnimals.' new animals';

            NotificationController::create(
                'animal_created',
                'New Animal'.($numberOfAnimals > 1 ? 's' : ''),
                $notificationMessage,
                '/animals/list',
                $projectId
            );

            return back();
        } catch (\Exception $e) {
            Log::error('Animals registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'An unexpected error occurred: '.$e->getMessage());

            return back()->withInput();
        }
    }
}
