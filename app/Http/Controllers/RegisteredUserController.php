<?php

namespace App\Http\Controllers;

use App\Mail\RegistrationEmail;
use App\Mail\VerificationEmail;
use App\Models\Countries;
use App\Models\Notification;
use App\Models\Organizations;
use App\Models\People;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class RegisteredUserController extends Controller
{
    public function create()
    {
        // Get existing organizations with their countries
        $organizations = Organizations::with('countries')
            ->orderBy('name')
            ->get()
            ->map(function ($org) {
                $affiliation = $org->name;
                if ($org->countries) {
                    $affiliation .= ', '.$org->countries->name;
                }

                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'country' => $org->countries ? $org->countries->name : null,
                    'full_affiliation' => $affiliation,
                ];
            });

        // Get existing jobs from people table
        $jobs = People::whereNotNull('job')
            ->where('job', '!=', '')
            ->pluck('job')
            ->unique()
            ->values()
            ->toArray();

        // Add some common veterinary jobs if not already present
        $commonJobs = [
            'Veterinarian',
            'Veterinary Technician',
            'Veterinary Assistant',
            'Veterinary Nurse',
            'Veterinary Student',
            'Research Veterinarian',
            'Clinical Veterinarian',
            'Pathologist',
            'Microbiologist',
            'Laboratory Technician',
            'Research Assistant',
            'Professor',
            'Associate Professor',
            'Assistant Professor',
            'Postdoctoral Researcher',
            'PhD Student',
            'MSc Student',
            'Laboratory Manager',
            'Field Technician',
            'Wildlife Veterinarian',
        ];

        $jobs = array_merge($jobs, $commonJobs);
        $jobs = array_unique($jobs);
        sort($jobs);

        // Handle old values for organization name
        $oldOrganizationName = old('organization_name');

        // If we have old organization_id but no organization_name, find the name
        if (! $oldOrganizationName && old('organization_id')) {
            $org = $organizations->firstWhere('id', old('organization_id'));
            if ($org) {
                $oldOrganizationName = $org['full_affiliation'];
            }
        }

        return view('auth.register', [
            'organizations' => $organizations,
            'jobs' => $jobs,
            'organization_types' => [
                'Government Agency' => 'Government Agency',
                'Research Institute' => 'Research Institute',
                'University' => 'University',
                'Non-Profit Organization' => 'Non-Profit Organization',
                'Private Company' => 'Private Company',
                'Zoo' => 'Zoo',
                'Wildlife Sanctuary' => 'Wildlife Sanctuary',
                'Veterinary Clinic' => 'Veterinary Clinic',
                'Laboratory' => 'Laboratory',
                'Conservation Organization' => 'Conservation Organization',
                'National Park' => 'National Park',
                'Game Reserve' => 'Game Reserve',
                'Museum' => 'Museum',
                'Hospital' => 'Hospital',
                'Pharmaceutical Company' => 'Pharmaceutical Company',
                'Biotechnology Company' => 'Biotechnology Company',
            ],
            'countries' => Countries::all(),
            'oldOrganizationName' => $oldOrganizationName,
        ]);
    }

    public function validate()
    {
        Log::info('Validating registration data: '.json_encode(request()->all()));

        try {
            $normalizedEmail = mb_strtolower(trim((string) request('email')));
            request()->merge(['email' => $normalizedEmail]);

            $rules = [
                'orcid' => ['nullable', 'string'],
                'title' => ['required'],
                'first_name' => ['required'],
                'last_name' => ['required'],
                'date_birth' => ['nullable', 'date', 'before_or_equal:'.now()->subYears(10)->format('Y-m-d')],
                'email' => ['required', 'email'],
                'password' => ['required', Password::min(6), 'confirmed'],
                'organization_id' => ['nullable', 'exists:organizations,id'],
                'organization_name' => ['nullable', 'string'],
                'job' => ['required', 'string'],
                'new_organization_data' => ['nullable', 'string'],
            ];

            if ($this->legalConsentRequired()) {
                $rules['accept_legal'] = ['accepted'];
            }

            $validated = request()->validate($rules, [
                'accept_legal.accepted' => 'You must accept the Terms of Service and Privacy Policy to register.',
            ]);

            if (User::query()->whereRaw('lower(email) = ?', [$normalizedEmail])->exists()) {
                throw ValidationException::withMessages([
                    'email' => [$this->duplicateEmailMessage()],
                ]);
            }

            // Additional validation for organization names
            if (request('organization_name') && ! request('organization_id') && ! request('new_organization_data')) {
                throw ValidationException::withMessages([
                    'organization_name' => ['Please select a valid organization from the list or create a new one.'],
                ]);
            }

            // Validate new organization data if provided
            if (request('new_organization_data')) {
                $orgData = json_decode(request('new_organization_data'), true);
                if (! $orgData || ! isset($orgData['name']) || empty(trim($orgData['name']))) {
                    throw ValidationException::withMessages([
                        'organization_name' => ['Organization name is required when creating a new organization.'],
                    ]);
                }

                $orgName = trim((string) $orgData['name']);
                if (Organizations::query()
                    ->whereRaw('lower(trim(name)) = ?', [mb_strtolower($orgName)])
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'organization_name' => ['An organization with this name already exists. Select it from the list instead.'],
                    ]);
                }
            }

            Log::info('Validation passed successfully');

            return $validated;
        } catch (ValidationException $e) {
            Log::error('Validation failed: '.json_encode($e->errors()));
            throw $e;
        }
    }

    private function legalConsentRequired(): bool
    {
        return (bool) (config('legal.terms_url') || config('legal.privacy_url'));
    }

    /**
     * Acceptance timestamps and document versions to persist on the new user.
     * Empty when no legal documents are configured.
     *
     * @return array<string, mixed>
     */
    private function legalConsentAttributes(): array
    {
        if (! $this->legalConsentRequired()) {
            return [];
        }

        $now = now();
        $attributes = [];

        if (config('legal.terms_url')) {
            $attributes['terms_accepted_at'] = $now;
            $attributes['terms_version'] = config('legal.terms_version');
        }

        if (config('legal.privacy_url')) {
            $attributes['privacy_accepted_at'] = $now;
            $attributes['privacy_version'] = config('legal.privacy_version');
        }

        return $attributes;
    }

    public function store()
    {
        $validated = $this->validate();

        $orcid = request('orcid');
        $email = $validated['email'];
        $organizationId = request('organization_id');

        // Handle new organization data if provided
        $newOrganizationData = request('new_organization_data');
        if ($newOrganizationData) {
            $orgData = json_decode($newOrganizationData, true);

            $orgName = trim((string) ($orgData['name'] ?? ''));
            if ($orgName !== '' && Organizations::query()
                ->whereRaw('lower(trim(name)) = ?', [mb_strtolower($orgName)])
                ->exists()) {
                throw ValidationException::withMessages([
                    'organization_name' => ['An organization with this name already exists. Select it from the list instead.'],
                ]);
            }

            // Find or create country
            $countryId = null;
            if (! empty($orgData['country'])) {
                $country = Countries::firstOrCreate(['name' => $orgData['country']]);
                $countryId = $country->id;
            }

            // Create new organization
            $newOrganization = Organizations::create([
                'name' => $orgData['name'],
                'type' => $orgData['type'] ?? 'company',
                'countries_id' => $countryId,
                'city' => $orgData['city'] ?? null,
                'region' => $orgData['region'] ?? null,
                'address' => $orgData['address'] ?? null,
                'website' => $orgData['website'] ?? null,
                'description' => $orgData['description'] ?? null,
            ]);

            $organizationId = $newOrganization->id;
        }

        // Check if a person with this email exists
        $existing_person = People::where('email', $email)->first();

        if ($existing_person) {
            $people_id = $existing_person->id;
        } else {
            // Create new person record
            $person = People::create([
                'title' => request('title'),
                'first_name' => request('first_name'),
                'last_name' => request('last_name'),
                'email' => $email,
                'date_birth' => request('date_birth'),
                'organizations_id' => $organizationId,
                'orcid' => $orcid,
                'job' => request('job'),
            ]);

            $people_id = $person->id;
        }

        // Generate verification code
        $verificationCode = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);

        // Send verification email FIRST before creating user
        try {
            Mail::to($email)->send(new VerificationEmail($verificationCode, request('first_name')));

        } catch (\Exception $e) {

            return back()->withErrors(['email' => 'Failed to send verification email. Please try again.'])
                ->withInput();
        }

        // Only create user account AFTER email is sent successfully
        try {
            $user = User::create([
                'people_id' => $people_id,
                'email' => $email,
                'password' => bcrypt(request('password')),
                'permission' => 'Guest',
                'verification_code' => $verificationCode,
                'verification_code_expires_at' => now()->addMinutes(15),
                'email_verified' => false,
            ] + $this->legalConsentAttributes());
        } catch (QueryException $exception) {
            if ($this->isDuplicateUsersEmailException($exception)) {
                return back()
                    ->withErrors(['email' => $this->duplicateEmailMessage()])
                    ->withInput();
            }

            throw $exception;
        }

        // Store user ID in session for verification
        session(['pending_verification_user_id' => $user->id]);

        // If the person already existed, generate project invitations for all their projects
        if ($existing_person) {
            $projects = $existing_person->projects;
            foreach ($projects as $project) {
                $token = bin2hex(random_bytes(16));
                $invitation = ProjectInvitation::create([
                    'people_id' => $existing_person->id,
                    'user_id' => $user->id,
                    'project_id' => $project->id,
                    'role' => $project->pivot->role,
                    'permission' => $project->pivot->permission ?? 'viewer',
                    'token' => $token,
                    'status' => 'pending',
                    'expires_at' => now()->addDays(3),
                ]);
                // Find inviter (the user who added the person to the project, if possible)
                $inviterName = 'a project member';
                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'project_invitation',
                    'title' => 'Project Invitation',
                    'message' => 'You have been invited to join project "'.($project->title ?? $project->code).'" as '.$invitation->role.' by '.$inviterName.'.',
                    'link' => '/my-projects',
                    'read' => false,
                    'projects_id' => $project->id,
                ]);
            }

            // No redirect to invitation view; user will see notification
            return redirect('/verify-email')->with('profile_notice', 'Your profile was already registered. Check and complete your personal data.');
        }

        return redirect('/verify-email')->with('verification_code', config('app.debug') ? $verificationCode : null);
    }

    private function duplicateEmailMessage(): string
    {
        return 'A user with this email already exists. Please use a different email or try logging in.';
    }

    private function isDuplicateUsersEmailException(QueryException $exception): bool
    {
        return str_contains(mb_strtolower($exception->getMessage()), 'users.email');
    }

    public function showVerificationForm()
    {
        $userId = session('pending_verification_user_id');
        Log::info('showVerificationForm called, pending_verification_user_id: '.($userId ?? 'null'));

        if (! $userId) {
            Log::info('No pending verification found, redirecting to register');

            return redirect('/register');
        }

        $user = User::find($userId);
        if (! $user) {
            session()->forget('pending_verification_user_id');

            return redirect('/register')->with('error', 'User not found.');
        }

        $isVerified = (bool) $user->email_verified || $user->email_verified_at !== null;
        if (! $isVerified) {
            $missingOrExpired = ! $user->verification_code || ! $user->verification_code_expires_at || $user->verification_code_expires_at->lt(now());

            // If user arrives here with an expired/missing code, automatically send a fresh one.
            // The POST /resend-verification endpoint is also available in the UI.
            if ($missingOrExpired) {
                try {
                    // Throttle to avoid spam on refresh
                    $throttleKey = 'verification-code-sent:'.$user->id;
                    if (! Cache::has($throttleKey)) {
                        $verificationCode = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
                        $user->update([
                            'verification_code' => $verificationCode,
                            'verification_code_expires_at' => now()->addMinutes(15),
                        ]);
                        Cache::put($throttleKey, true, now()->addSeconds(60));
                        Mail::to($user->email)->send(new VerificationEmail($verificationCode, $user->people?->first_name ?? ''));
                    }
                } catch (\Throwable $e) {
                    Log::error('Failed to auto-send verification email on verify page: '.$e->getMessage());
                }
            }
        }

        Log::info('Showing verification form for user ID: '.$userId);

        return view('auth.verify-email');
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'verification_code' => 'required|string|size:5',
        ]);

        $userId = session('pending_verification_user_id');
        if (! $userId) {
            return redirect('/register')->with('error', 'No pending verification found.');
        }

        $user = User::find($userId);
        if (! $user) {
            return redirect('/register')->with('error', 'User not found.');
        }

        if ($user->verification_code !== $request->verification_code) {
            return back()->withErrors(['verification_code' => 'Invalid verification code.']);
        }

        if ($user->verification_code_expires_at < now()) {
            return back()->withErrors(['verification_code' => 'Verification code has expired. Please request a new one.']);
        }

        // Mark user as verified
        $user->update([
            'email_verified' => true,
            'email_verified_at' => now(),
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ]);

        // Clear session
        session()->forget('pending_verification_user_id');

        // Log in the user
        Auth::login($user);

        // Send welcome email
        try {
            Mail::to($user->email)->send(new RegistrationEmail($user->people->first_name));
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email: '.$e->getMessage());
        }

        return redirect('/')->with('success', 'Email verified successfully! Welcome to Aleph∞One.');
    }

    public function resendVerification(Request $request)
    {
        $userId = session('pending_verification_user_id');
        if (! $userId) {
            return response()->json(['success' => false, 'message' => 'No pending verification found.']);
        }

        $user = User::find($userId);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not found.']);
        }

        // Generate new verification code
        $verificationCode = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);

        $user->update([
            'verification_code' => $verificationCode,
            'verification_code_expires_at' => now()->addMinutes(15),
        ]);

        // Send new verification email
        try {
            Mail::to($user->email)->send(new VerificationEmail($verificationCode, $user->people->first_name));

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Failed to resend verification email: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to send verification email.']);
        }
    }

    //  public function invitationView()
    //  {
    //      $userId = session('pending_verification_user_id');
    //      if (!$userId) {
    //          return redirect('/register');
    //      }
    //      $user = \App\Models\User::find($userId);
    //      if (!$user) {
    //          return redirect('/register');
    //      }
    //      $invitations = \App\Models\ProjectInvitation::where('user_id', $userId)->where('status', 'pending')->get();
    //      $profile_notice = session('profile_notice');
    //      return view('auth.register-invitation', compact('user', 'invitations', 'profile_notice'));
    //  }
    //
    //  public function handleInvitation(Request $request, $invitationId)
    //  {
    //      $invitation = \App\Models\ProjectInvitation::findOrFail($invitationId);
    //      $userId = session('pending_verification_user_id');
    //      if (!$userId || $invitation->user_id != $userId) {
    //          return redirect()->route('profile.invitations')->withErrors(['Invalid invitation or session.']);
    //      }
    //      $action = $request->input('action');
    //      if ($action === 'accept') {
    //          $token = $request->input('token');
    //          if ($token !== $invitation->token) {
    //              return redirect()->route('profile.invitations')->withErrors(['Invalid token.']);
    //          }
    //          // Add user to project (attach people_id to project with role/permission)
    //          $project = $invitation->project;
    //          $project->people()->syncWithoutDetaching([
    //              $invitation->people_id => [
    //                  'role' => $invitation->role,
    //                  'permission' => $invitation->permission,
    //                  'date_joined' => now(),
    //              ]
    //          ]);
    //          $invitation->status = 'accepted';
    //          $invitation->save();
    //          return redirect()->route('profile.invitations')->with('success', 'Invitation accepted. The project has been added to your account.');
    //      } elseif ($action === 'reject') {
    //          // Remove person from project
    //          $project = $invitation->project;
    //          $project->people()->detach($invitation->people_id);
    //          $invitation->status = 'rejected';
    //          $invitation->save();
    //          return redirect()->route('profile.invitations')->with('success', 'Invitation rejected. You have been removed from the project team.');
    //      }
    //      return redirect()->route('profile.invitations');
    //  }
}
