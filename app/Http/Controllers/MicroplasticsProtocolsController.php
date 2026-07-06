<?php

namespace App\Http\Controllers;

use App\Models\Projects;
use App\Models\Protocols;
use App\Models\Techniques;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MicroplasticsProtocolsController extends Controller
{
    public function store()
    {
        $projectId = (int) session('selected_project_id');
        $project = Projects::query()->findOrFail($projectId);

        $validator = Validator::make(request()->all(), [
            'protocol_name' => 'required|string|max:100',
            'protocol_new' => 'required|string|max:100',
            'protocol_pdf' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        if ($validator->fails()) {
            session()->flash('error', 'Registration failed. Please fix the errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        try {
            $techniqueId = Techniques::query()->firstOrCreate(
                ['name' => request('protocol_new')],
                ['type' => 'Microplastics identification']
            )->id;

            DB::transaction(function () use ($project, $projectId, $techniqueId): void {
                $existingCodes = Protocols::query()
                    ->where('code', 'like', $project->code.'-PR-%')
                    ->pluck('code');

                $maxSerial = 0;
                foreach ($existingCodes as $code) {
                    if (preg_match('/-PR-(\d+)$/', (string) $code, $matches)) {
                        $maxSerial = max($maxSerial, (int) $matches[1]);
                    }
                }

                $protocolCode = $project->code.'-PR-'.($maxSerial + 1);
                $filePath = request()->hasFile('protocol_pdf')
                    ? request()->file('protocol_pdf')->store('protocols', 'local')
                    : null;

                $protocol = Protocols::query()->create([
                    'code' => $protocolCode,
                    'name' => request('protocol_name'),
                    'techniques_id' => $techniqueId,
                    'users_id' => Auth::id(),
                    'pdf_path' => $filePath,
                ]);

                $user = Auth::user();
                NotificationController::create(
                    'protocols_created',
                    'New Protocol',
                    $user->people->first_name.' registered a new microplastics protocol.',
                    "/protocols/{$protocol->code}",
                    $projectId
                );
            });

            session()->flash('success', 'Protocol registered successfully!');

            return back();
        } catch (\Throwable $throwable) {
            session()->flash('error', 'An error occurred: '.$throwable->getMessage());

            return back()->withInput();
        }
    }
}
