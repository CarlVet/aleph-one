<?php

namespace App\Http\Controllers;

use App\Models\Countries;
use App\Models\Organizations;
use App\Models\People;
use App\Models\ProjectInvitation;
use App\Models\Projects;
use App\Models\ProjectsPeople;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function show($id = null)
    {
        if ($id) {
            $person = People::with([
                'organizations',
                'users',
                'projects' => function ($query) {
                    $query->withPivot('role', 'date_joined');
                },
                'human_samples.sample_types',
                'animal_samples.sample_types',
                'environment_samples.environment_sample_types',
                'parasite_samples.parasite_sample_types',
                'nucleic_acids',
                'cultures',
                'pools',
                'experiments.protocols.techniques',
                'experiments.pathogens',
                'experiments.people',
                'experiments.laboratories',
                'experiments.experiments_content',
                'sequences',
                'parasites',
                'meta_animals.studies',
                'meta_animals.animal_species',
                'meta_animals.sample_types',
                'meta_animals.pathogens',
                'meta_humans.studies',
                'meta_humans.sample_types',
                'meta_humans.pathogens',
                'meta_environments.studies',
                'meta_environments.environment_sample_types',
                'meta_environments.pathogens',
                'meta_parasites.studies',
                'meta_parasites.parasite_sample_types',
                'meta_parasites.pathogens',
                'fundings',
            ])->findOrFail($id);

            $viewerPeopleId = Auth::user()?->people_id;
            $sharesProject = ProjectsPeople::where('people_id', $viewerPeopleId)
                ->whereIn('projects_id', ProjectsPeople::where('people_id', $id)->pluck('projects_id'))
                ->exists();
            abort_unless((int) $id === (int) $viewerPeopleId || $sharesProject, 403);

            $user = $person->users;
        } else {
            $user = Auth::user();
            $person = $user->people;

            // Load relationships for the current user's person
            $person->load([
                'organizations',
                'users',
                'projects' => function ($query) {
                    $query->withPivot('role', 'date_joined');
                },
                'human_samples.sample_types',
                'animal_samples.sample_types',
                'environment_samples.environment_sample_types',
                'parasite_samples.parasite_sample_types',
                'nucleic_acids',
                'cultures',
                'pools',
                'experiments.protocols.techniques',
                'experiments.pathogens',
                'experiments.people',
                'experiments.laboratories',
                'experiments.experiments_content',
                'sequences',
                'parasites',
                'meta_animals.studies',
                'meta_animals.animal_species',
                'meta_animals.sample_types',
                'meta_animals.pathogens',
                'meta_humans.studies',
                'meta_humans.sample_types',
                'meta_humans.pathogens',
                'meta_environments.studies',
                'meta_environments.environment_sample_types',
                'meta_environments.pathogens',
                'meta_parasites.studies',
                'meta_parasites.parasite_sample_types',
                'meta_parasites.pathogens',
                'fundings',
            ]);
        }

        // Calculate comprehensive stats
        $stats = [
            'total_projects' => $person->projects()->count(),
            'total_samples' => $person->human_samples()->count() +
                              $person->animal_samples()->count() +
                              $person->environment_samples()->count() +
                              $person->parasite_samples()->count() +
                              $person->nucleic_acids()->count() +
                              $person->cultures()->count() +
                              $person->pools()->count(),
            'total_experiments' => $person->experiments()->count(),
            'total_nucleic_acids' => $person->nucleic_acids()->count(),
            'total_cultures' => $person->cultures()->count(),
            'total_pools' => $person->pools()->count(),
            'total_sequences' => $person->sequences()->count(),
            'total_parasites' => $person->parasites()->count(),
            'total_meta_studies' => $person->meta_animals()->count() +
                                   $person->meta_humans()->count() +
                                   $person->meta_environments()->count() +
                                   $person->meta_parasites()->count(),
            'total_fundings' => $person->fundings()->count(),
        ];

        // Get countries for organization form
        $countries = Countries::orderBy('name')->get();

        // Get organizations for datalist
        $organizations = Organizations::orderBy('name')->get();

        return view('profile.show', [
            'person' => $person,
            'user' => $user,
            'contactEmail' => $person->contactEmail(),
            'stats' => $stats,
            'countries' => $countries,
            'organizations' => $organizations,
        ]);
    }

    // Photo upload method
    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|max:51200', // 50MB max
        ]);

        $user = Auth::user();
        $person = $user->people;

        try {
            // Delete old photo if exists
            if ($person->pic_path) {
                Storage::delete($person->pic_path);
            }

            // Store new photo
            $photoPath = $request->file('photo')->store('profile-photos', 'local');

            // Update person record
            $person->update(['pic_path' => $photoPath]);

            return response()->json([
                'success' => true,
                'message' => 'Photo uploaded successfully!',
                'photo_path' => Storage::url($photoPath),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload photo: '.$e->getMessage(),
            ], 500);
        }
    }

    // Delete photo method
    public function deletePhoto()
    {
        $user = Auth::user();
        $person = $user->people;

        try {
            if ($person->pic_path) {
                Storage::delete($person->pic_path);
                $person->update(['pic_path' => null]);

                return response()->json([
                    'success' => true,
                    'message' => 'Photo deleted successfully!',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete photo: '.$e->getMessage(),
            ], 500);
        }
    }

    // Update person field method
    public function updateField(Request $request)
    {
        $request->validate([
            'field' => 'required|string|in:title,first_name,last_name,job,orcid,organization',
            'value' => 'required|string|max:255',
        ]);

        $user = Auth::user();
        $person = $user->people;

        try {
            if ($request->field === 'organization') {
                // Find the organization by name
                $organization = Organizations::where('name', $request->value)->first();
                if (! $organization) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Organization not found',
                    ], 404);
                }
                $person->update(['organizations_id' => $organization->id]);
            } else {
                $person->update([$request->field => $request->value]);
            }

            return response()->json([
                'success' => true,
                'message' => ucfirst(str_replace('_', ' ', $request->field)).' updated successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update field: '.$e->getMessage(),
            ], 500);
        }
    }

    public function projects()
    {
        $user = Auth::user();
        $person = $user->people;

        $search = trim((string) request('search', ''));
        $type = trim((string) request('type', ''));
        $role = trim((string) request('role', ''));
        $funding = trim((string) request('funding', ''));

        $base = $person->projects()
            ->with([
                'people' => function ($query) {
                    $query->select('people.*')
                        ->with('users:id,email,people_id')
                        ->withPivot('role', 'date_joined', 'permission');
                },
                'fundings',
                'fundings.recipient',
            ])
            ->withPivot('role', 'date_joined', 'permission');

        if ($search !== '') {
            $base->where(function ($q) use ($search) {
                $q->where('projects.code', 'like', '%'.$search.'%')
                    ->orWhere('projects.title', 'like', '%'.$search.'%');
            });
        }

        if ($type !== '') {
            $base->where('projects.type', $type);
        }

        if ($role !== '') {
            $base->wherePivot('role', $role);
        }

        if ($funding !== '') {
            $base->whereHas('fundings', function ($q) use ($funding) {
                $q->where('source', 'like', '%'.$funding.'%')
                    ->orWhere('reference', 'like', '%'.$funding.'%');
            });
        }

        $activeProjects = (clone $base)
            ->where(function ($q) {
                $q->whereNull('projects.date_end')
                    ->where(function ($qq) {
                        $qq->whereNull('projects.status')
                            ->orWhere('projects.status', '!=', 'completed');
                    });
            })
            ->orderBy('projects.date_started', 'desc')
            ->paginate(10, ['projects.*'], 'activePage')
            ->withQueryString();

        $completedProjects = (clone $base)
            ->where(function ($q) {
                $q->whereNotNull('projects.date_end')
                    ->orWhere('projects.status', 'completed');
            })
            ->orderBy('projects.date_end', 'desc')
            ->paginate(10, ['projects.*'], 'completedPage')
            ->withQueryString();

        return view('profile.projects', [
            'activeProjects' => $activeProjects,
            'completedProjects' => $completedProjects,
            'person' => $person,
            'filters' => [
                'search' => $search,
                'type' => $type,
                'role' => $role,
                'funding' => $funding,
            ],
        ]);
    }

    public function invitations()
    {
        $user = Auth::user();
        if (! $user) {
            return redirect('/login');
        }
        $invitations = ProjectInvitation::where('user_id', $user->id)->where('status', 'pending')->with('project')->get();

        return view('profile.invitations', compact('invitations'));
    }

    public function settings()
    {
        $user = Auth::user();

        return view('profile.settings', [
            'user' => $user,
            'passkeys' => $user->webAuthnCredentials()->whereEnabled()->latest()->get(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|min:8|confirmed',
        ]);

        $user->email = $validated['email'];
        $user->save();

        if (! empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
            $user->save();
        }

        return redirect()->route('profile.settings')
            ->with('success', 'Settings updated successfully.');
    }

    public function storeProject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:255|unique:projects,code',
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:PhD project,MSc project,Research assignment,Publication-related project',
            'date_started' => 'required|date',
            'date_end_intended' => 'nullable|date|after:date_started',
            'ethics_ref' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $project = Projects::create([
                'code' => $request->code,
                'title' => $request->title,
                'type' => $request->type,
                'date_started' => $request->date_started,
                'date_end_intended' => $request->date_end_intended,
                'ethics_ref' => $request->ethics_ref,
            ]);

            // Associate the project with the current user's person
            $user = Auth::user();
            if ($user && $user->people) {
                $user->people->projects()->attach($project->id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Project created successfully',
                'project' => $project,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create project: '.$e->getMessage(),
            ], 500);
        }
    }
}
