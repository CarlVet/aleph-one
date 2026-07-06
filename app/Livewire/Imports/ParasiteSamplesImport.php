<?php

namespace App\Livewire\Imports;

use App\Http\Controllers\NotificationController;
use App\Livewire\PlainComponent;
use App\Models\AnimalSamples;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\Laboratories;
use App\Models\Parasites;
use App\Models\ParasiteSamples;
use App\Models\ParasiteSampleTypes;
use App\Models\ParasiteSpecies;
use App\Models\People;
use App\Models\Projects;
use App\Models\Tubes;
use App\Services\Imports\ColumnMatcher;
use App\Services\Imports\DelimitedTableReader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;

class ParasiteSamplesImport extends PlainComponent
{
    use WithFileUploads;

    #[Validate('required|file|mimes:csv,txt,xlsx,xls|max:20480')]
    public $file;

    /** @var array<int, mixed> */
    public array $rowPhotos = [];

    public string $status = 'idle';

    /** @var list<string> */
    public array $globalIssues = [];

    /** @var list<string> */
    public array $globalWarnings = [];

    /** @var array<int, array<string, string>> */
    public array $rowOverrides = [];

    /** @var list<string> */
    public array $headers = [];

    /** @var array<string,int> */
    public array $fieldToIndex = [];

    public int $page = 1;

    public int $perPage = 25;

    public ?string $cacheKey = null;

    public function updatedFile(): void
    {
        $this->resetPreview();
        $this->buildPreview();
    }

    public function downloadTemplate()
    {
        $template = $this->templateDefinition();
        $columns = (array) ($template['columns'] ?? []);

        $headers = array_values(array_map(
            static fn (array $col): string => (string) ($col['header'] ?? ''),
            $columns
        ));

        $exampleRow = array_values(array_map(
            static fn (array $col): string => (string) ($col['example'] ?? ''),
            $columns
        ));

        $fileName = 'parasite-samples-import-template.csv';

        return response()->streamDownload(function () use ($headers, $exampleRow): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, $headers);
            fputcsv($handle, $exampleRow);
            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function openTemplateOptions(string $field): void
    {
        $projectId = $this->selectedProjectId();

        $result = $this->templateOptionsForField($field, $projectId);
        $values = $result['values'];
        $title = $result['title'];
        $total = $result['total'];
        $truncated = $result['truncated'];

        $itemsHtml = implode('', array_map(function (string $value): string {
            $escaped = e($value);

            return "<li class=\"py-1\"><span class=\"font-mono text-xs text-slate-800\">{$escaped}</span></li>";
        }, $values));

        $note = $truncated
            ? '<div class="mt-3 text-xs text-slate-500">Showing a subset of values for performance. Refine by typing the exact value in your CSV.</div>'
            : '';

        $html = <<<HTML
<div class="text-left">
  <div class="text-sm text-slate-700">{$total} value(s)</div>
  <div class="mt-3 max-h-96 overflow-auto rounded-lg border border-slate-200 bg-white px-4 py-3">
    <ul class="divide-y divide-slate-100">{$itemsHtml}</ul>
  </div>
  {$note}
</div>
HTML;

        $this->dispatch('swal', [
            'icon' => 'info',
            'title' => $title,
            'html' => $html,
            'width' => '56rem',
            'showCloseButton' => true,
            'confirmButtonText' => 'Close',
        ]);
    }

    public function applySuggestedValue(int $rowNumber, string $field, string $value): void
    {
        if ($rowNumber < 2) {
            return;
        }

        $this->rowOverrides[$rowNumber][$field] = $value;
    }

    public function buildPreview(): void
    {
        $this->globalIssues = [];
        $this->globalWarnings = [];

        if (! $this->userCanEditSelectedProject()) {
            $this->globalIssues[] = 'You do not have permission to import parasite samples in this project (viewer accounts are read-only).';
            $this->status = 'error';

            return;
        }

        $projectId = $this->selectedProjectId();
        if ($projectId === null) {
            $this->globalIssues[] = 'Select a project before importing.';
            $this->status = 'error';

            return;
        }

        $reader = new DelimitedTableReader;
        try {
            $table = $reader->read($this->file);
        } catch (\Throwable $e) {
            $this->globalIssues[] = $e->getMessage();
            $this->status = 'error';

            return;
        }

        $this->headers = $table['headers'];
        $rows = $table['rows'];

        if (count($this->headers) === 0) {
            $this->globalIssues[] = 'No headers detected. Make sure the first row contains column names.';
            $this->status = 'error';

            return;
        }

        if (count($rows) === 0) {
            $this->globalIssues[] = 'No data rows detected.';
            $this->status = 'error';

            return;
        }

        $matcher = new ColumnMatcher;
        $match = $matcher->match($this->headers, $this->synonymsByField());
        $this->fieldToIndex = $match['fieldToIndex'];

        foreach ($this->requiredFields() as $required) {
            if (! array_key_exists($required, $this->fieldToIndex)) {
                $this->globalIssues[] = "Missing required column: {$required}";
            }
        }

        $this->cacheKey = "imports:parasite_samples:{$projectId}:".bin2hex(random_bytes(8));
        Cache::put($this->cacheKey, $rows, now()->addHour());

        $this->page = 1;
        $this->status = empty($this->globalIssues) ? 'preview' : 'error';
    }

    public function resetPreview(): void
    {
        if ($this->cacheKey) {
            Cache::forget($this->cacheKey);
        }

        $this->cacheKey = null;
        $this->headers = [];
        $this->fieldToIndex = [];
        $this->globalIssues = [];
        $this->globalWarnings = [];
        $this->rowOverrides = [];
        $this->rowPhotos = [];
        $this->status = 'idle';
        $this->page = 1;
    }

    public function import(): void
    {
        $this->globalIssues = [];
        $this->globalWarnings = [];

        $this->validate([
            'rowPhotos.*' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:51200',
        ]);

        if ($this->status !== 'preview' || ! $this->cacheKey) {
            $this->globalIssues[] = 'Upload a file and fix issues before importing.';
            $this->status = 'error';

            return;
        }

        if (! $this->userCanEditSelectedProject()) {
            $this->globalIssues[] = 'You do not have permission to import parasite samples in this project (viewer accounts are read-only).';
            $this->status = 'error';

            return;
        }

        $projectId = $this->selectedProjectId();
        if ($projectId === null) {
            $this->globalIssues[] = 'Select a project before importing.';
            $this->status = 'error';

            return;
        }

        $rows = Cache::get($this->cacheKey);
        if (! is_array($rows)) {
            $this->globalIssues[] = 'Import data expired. Please re-upload the file.';
            $this->status = 'error';

            return;
        }

        $project = Projects::findOrFail($projectId);
        $wholeParasiteTypeId = $this->resolveOrCreateWholeParasiteSampleTypeId();
        $usedPaSerials = $this->usedSerialSet(Parasites::class, $projectId, $project->code, 'PA');
        $usedPsSerials = $this->usedSerialSet(ParasiteSamples::class, $projectId, $project->code, 'PS');
        $nextPaSerial = 1;
        $nextPsSerial = 1;
        $createdParasites = 0;
        $createdParasiteSamples = 0;
        $createdTubes = 0;
        $errors = 0;
        $rollbackSignal = '__parasite_samples_bulk_import_rollback__';

        try {
            DB::transaction(function () use (
                $rows,
                $project,
                $wholeParasiteTypeId,
                &$usedPaSerials,
                &$usedPsSerials,
                &$nextPaSerial,
                &$nextPsSerial,
                &$createdParasites,
                &$createdParasiteSamples,
                &$createdTubes,
                &$errors,
                $rollbackSignal
            ): void {
                foreach ($rows as $i => $rawRow) {
                    $rowNumber = $i + 2;
                    $row = is_array($rawRow) ? $rawRow : [];
                    $resolved = $this->resolveRow($row, $project->id, $rowNumber);

                    if (! empty($resolved['warnings'])) {
                        $this->globalWarnings[] = "Row {$rowNumber}: ".implode(' | ', $resolved['warnings']);
                    }

                    if (! empty($resolved['issues'])) {
                        $errors++;
                        $this->globalIssues[] = "Row {$rowNumber}: ".implode(' | ', $resolved['issues']);

                        continue;
                    }

                    $parasiteSpeciesId = $this->resolveOrCreateParasiteSpeciesId(
                        $resolved['parasite_species'],
                        $resolved['parasite_family']
                    );

                    $laboratoryId = $this->resolveOrCreateLaboratoryId($resolved['identified_at']);
                    $personId = $this->resolveOrCreatePersonId($resolved['identified_by']);
                    if ($parasiteSpeciesId === null || $laboratoryId === null || $personId === null) {
                        $errors++;
                        $this->globalIssues[] = "Row {$rowNumber}: unable to resolve species/laboratory/identified_by.";

                        continue;
                    }

                    $photoPath = $this->storeRowPhoto($rowNumber);
                    $paSerial = $this->nextAvailableSerial($usedPaSerials, $nextPaSerial);
                    $psSerial = $this->nextAvailableSerial($usedPsSerials, $nextPsSerial);
                    $parasiteCode = "{$project->code}-PA-{$paSerial}";
                    $parasiteSampleCode = "{$project->code}-PS-{$psSerial}";

                    $parasite = Parasites::query()->create([
                        'code' => $parasiteCode,
                        'parasite_species_id' => $parasiteSpeciesId,
                        'stage' => $resolved['stage'],
                        'sex' => $resolved['sex'],
                        'state' => $resolved['repletion_state'],
                        'date_identified' => $resolved['date_identified'],
                        'photo_path' => $photoPath,
                        'parasites_origin_type' => $resolved['origin_model'],
                        'parasites_origin_id' => $resolved['origin_id'],
                        'laboratories_id' => $laboratoryId,
                        'people_id' => $personId,
                        'projects_id' => $project->id,
                    ]);
                    $createdParasites++;

                    $parasiteSample = ParasiteSamples::query()->create([
                        'code' => $parasiteSampleCode,
                        'parasites_id' => $parasite->id,
                        'parasite_sample_types_id' => $wholeParasiteTypeId,
                        'people_id' => $personId,
                        'laboratories_id' => $laboratoryId,
                        'projects_id' => $project->id,
                        'date_processed' => $resolved['date_identified'],
                    ]);
                    $createdParasiteSamples++;

                    $tubeCode = $parasiteSampleCode.'-'.$this->nextTubeSerialForPrefix($parasiteSampleCode);
                    Tubes::query()->create([
                        'code' => $tubeCode,
                        'tubes_content_type' => ParasiteSamples::class,
                        'tubes_content_id' => $parasiteSample->id,
                        'tube_type' => '1.5ml/2ml tube',
                        'purpose' => 'for parasite analysis',
                        'preservant' => $resolved['sample_state'],
                        'date_processed' => $resolved['date_identified'],
                        'projects_id' => $project->id,
                    ]);
                    $createdTubes++;
                }

                if ($errors > 0) {
                    throw new \RuntimeException($rollbackSignal);
                }
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== $rollbackSignal) {
                throw $e;
            }
        }

        Cache::forget($this->cacheKey);
        $this->cacheKey = null;

        if ($errors > 0) {
            $this->status = 'error';
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Import failed',
                'text' => 'Bulk import failed. No data was registered.',
            ]);

            return;
        }

        $this->status = 'imported';
        $successMessage = "{$createdParasites} parasite sample(s) imported successfully, resulting in {$createdParasiteSamples} whole-parasite sample(s) and {$createdTubes} tube(s).";
        session()->flash(
            'success',
            $successMessage
        );
        NotificationController::create(
            'parasite_sample_created',
            'Bulk parasite samples imported',
            $successMessage,
            '/samples/parasites/list',
            $project->id
        );
        $this->dispatch('notification-created');
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Success',
            'text' => $successMessage,
        ]);
    }

    /**
     * @return array{
     *   row_number: int,
     *   origin_type: string,
     *   origin_code: string,
     *   origin_model: class-string|null,
     *   origin_id: int|null,
     *   origin_code_exists: bool,
     *   parasite_species: string,
     *   parasite_species_exists: bool,
     *   parasite_family: string,
     *   stage: string,
     *   stage_invalid: bool,
     *   sex: string,
     *   sex_invalid: bool,
     *   repletion_state: string,
     *   repletion_state_invalid: bool,
     *   date_identified: string,
     *   identified_at: string,
     *   identified_at_exists: bool,
     *   identified_by: string,
     *   identified_by_exists: bool,
     *   sample_state: string,
     *   sample_state_exists: bool,
     *   field_warnings: array<string, array{text: string, suggested: string, options: list<string>}>,
     *   warnings: list<string>,
     *   issues: list<string>
     * }
     */
    private function resolveRow(array $row, int $projectId, int $rowNumber): array
    {
        $get = function (string $field) use ($row, $rowNumber): string {
            $override = $this->rowOverrides[$rowNumber][$field] ?? null;
            if ($override !== null && trim((string) $override) !== '') {
                return $this->sanitizeCell((string) $override);
            }

            $idx = $this->fieldToIndex[$field] ?? null;
            if ($idx === null) {
                return '';
            }

            return isset($row[$idx]) ? $this->sanitizeCell((string) $row[$idx]) : '';
        };
        $getRaw = function (string $field) use ($row): string {
            $idx = $this->fieldToIndex[$field] ?? null;
            if ($idx === null) {
                return '';
            }

            return isset($row[$idx]) ? $this->sanitizeCell((string) $row[$idx]) : '';
        };

        $originTypeRaw = $get('origin_type');
        $originType = $this->normalizeOriginType($originTypeRaw);
        $originCode = $get('origin_code');
        $parasiteSpecies = $this->sanitizeCell($get('parasite_species'));
        $parasiteFamily = $this->normalizeWordsTitleCase($get('parasite_family'));
        $stageRaw = $get('stage');
        $sexRaw = $get('sex');
        $repletionStateRaw = $get('repletion_state');
        $dateIdentified = $get('date_identified');
        $identifiedAt = $this->normalizeWordsTitleCase($get('identified_at'));
        $identifiedBy = $this->normalizeWordsTitleCase($get('identified_by'));
        $sampleState = $this->normalizeWordsTitleCase($get('sample_state'));
        $showDateIdentifiedInput = $getRaw('date_identified') === '' || isset($this->rowOverrides[$rowNumber]['date_identified']);
        $showIdentifiedAtInput = $getRaw('identified_at') === '' || isset($this->rowOverrides[$rowNumber]['identified_at']);
        $showIdentifiedByInput = $getRaw('identified_by') === '' || isset($this->rowOverrides[$rowNumber]['identified_by']);
        $showSampleStateInput = $getRaw('sample_state') === '' || isset($this->rowOverrides[$rowNumber]['sample_state']);

        $issues = [];
        $warnings = [];
        $fieldWarnings = [];

        if ($originType === '') {
            $issues[] = 'origin_type is required';
        }

        $originModel = $this->originModelFromType($originType);
        if ($originType !== '' && $originModel === null) {
            $issues[] = "origin_type must be human, animal, or environment (got '{$originTypeRaw}')";
        }

        $originCodeExists = false;
        $originId = null;
        if ($originCode === '') {
            $issues[] = 'origin_code is required';
        } elseif ($originModel !== null) {
            $originQuery = $this->originCandidatesQuery($originModel, $projectId);
            $origin = (clone $originQuery)->whereRaw('lower(code) = ?', [strtolower($originCode)])->first(['id', 'code']);
            if ($origin) {
                $originCodeExists = true;
                $originCode = (string) $origin->code;
                $originId = (int) $origin->id;
            } else {
                $issues[] = "origin_code not found for origin_type '{$originType}'";
                $similar = $this->similarOptionsFromQuery($originQuery, 'code', $originCode);
                if (! empty($similar)) {
                    $fieldWarnings['origin_code'] = [
                        'text' => '≈ '.implode(' and ', $similar),
                        'suggested' => $similar[0],
                        'options' => $similar,
                    ];
                    $warnings[] = $fieldWarnings['origin_code']['text'];
                }
            }
        }

        $parasiteSpeciesExists = false;
        if ($parasiteSpecies === '') {
            $issues[] = 'parasite_species is required';
        } else {
            $species = ParasiteSpecies::query()
                ->whereRaw('lower(name_scientific) = ?', [strtolower($parasiteSpecies)])
                ->first(['id', 'name_scientific']);

            if ($species) {
                $parasiteSpeciesExists = true;
                $parasiteSpecies = (string) $species->name_scientific;
            } else {
                if ($parasiteFamily === '') {
                    $issues[] = 'parasite_family is required when parasite_species is new';
                }
            }

            $similarSpecies = $this->similarOptionsFromQuery(ParasiteSpecies::query(), 'name_scientific', $parasiteSpecies);
            if (! empty($similarSpecies)) {
                $fieldWarnings['parasite_species'] = [
                    'text' => '≈ '.implode(' and ', $similarSpecies),
                    'suggested' => $similarSpecies[0],
                    'options' => $similarSpecies,
                ];
                $warnings[] = $fieldWarnings['parasite_species']['text'];
            }
        }

        $stage = $this->normalizeStage($stageRaw);
        if ($stage === '') {
            $issues[] = 'stage is required';
        }
        $stageInvalid = $stage !== '' && ! in_array($stage, $this->stageOptions(), true);
        if ($stageInvalid) {
            $issues[] = "stage must be egg, larva, pupa, nymph, adult or N/A (got '{$stageRaw}')";
        }

        $sex = $this->normalizeSex($sexRaw);
        if ($sex === '') {
            $issues[] = 'sex is required';
        }
        $sexInvalid = $sex !== '' && ! in_array($sex, $this->sexOptions(), true);
        if ($sexInvalid) {
            $issues[] = "sex must be Male, Female, or N/A (got '{$sexRaw}')";
        }

        $repletionState = $this->normalizeRepletionState($repletionStateRaw);
        if ($repletionState === '') {
            $issues[] = 'repletion_state is required';
        }
        $repletionStateInvalid = $repletionState !== '' && ! in_array($repletionState, $this->repletionStateOptions(), true);
        if ($repletionStateInvalid) {
            $issues[] = "repletion_state must be Engorged, Partially engorged, Not engorged, or N/A (got '{$repletionStateRaw}')";
        }

        if ($dateIdentified === '') {
            $issues[] = 'date_identified is required';
        } elseif (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateIdentified) || strtotime($dateIdentified) === false) {
            $issues[] = 'date_identified must be YYYY-MM-DD';
        }

        $identifiedAtExists = false;
        if ($identifiedAt === '') {
            $issues[] = 'identified_at is required';
        } else {
            $identifiedAtExists = Laboratories::query()->whereRaw('lower(name) = ?', [strtolower($identifiedAt)])->exists();
            if (! $identifiedAtExists) {
                $similarLabs = $this->similarOptionsFromQuery(Laboratories::query(), 'name', $identifiedAt);
                if (! empty($similarLabs)) {
                    $fieldWarnings['identified_at'] = [
                        'text' => '≈ '.implode(' and ', $similarLabs),
                        'suggested' => $similarLabs[0],
                        'options' => $similarLabs,
                    ];
                    $warnings[] = $fieldWarnings['identified_at']['text'];
                }
            }
        }

        $identifiedByExists = false;
        if ($identifiedBy === '') {
            $issues[] = 'identified_by is required';
        } else {
            $identifiedByExists = $this->findPersonByFullName($identifiedBy) !== null;
            if (! $identifiedByExists) {
                $similarPeople = $this->similarPersonNames($identifiedBy);
                if (! empty($similarPeople)) {
                    $fieldWarnings['identified_by'] = [
                        'text' => '≈ '.implode(' and ', $similarPeople),
                        'suggested' => $similarPeople[0],
                        'options' => $similarPeople,
                    ];
                    $warnings[] = $fieldWarnings['identified_by']['text'];
                }

                if (! $this->canSplitPersonName($identifiedBy)) {
                    $issues[] = 'identified_by must include first and last name for new people';
                }
            }
        }

        $sampleStateExists = false;
        if ($sampleState === '') {
            $issues[] = 'sample_state is required';
        } else {
            $sampleStateExists = Tubes::query()
                ->whereNotNull('preservant')
                ->whereRaw('lower(preservant) = ?', [strtolower($sampleState)])
                ->exists();
            if (! $sampleStateExists) {
                $similarStates = $this->similarOptionsFromQuery(
                    Tubes::query()->whereNotNull('preservant'),
                    'preservant',
                    $sampleState
                );
                if (! empty($similarStates)) {
                    $fieldWarnings['sample_state'] = [
                        'text' => '≈ '.implode(' and ', $similarStates),
                        'suggested' => $similarStates[0],
                        'options' => $similarStates,
                    ];
                    $warnings[] = $fieldWarnings['sample_state']['text'];
                }
            }
        }

        return [
            'row_number' => $rowNumber,
            'origin_type' => $originType,
            'origin_code' => $originCode,
            'origin_model' => $originModel,
            'origin_id' => $originId,
            'origin_code_exists' => $originCodeExists,
            'parasite_species' => $parasiteSpecies,
            'parasite_species_exists' => $parasiteSpeciesExists,
            'parasite_family' => $parasiteFamily,
            'stage' => $stage,
            'stage_invalid' => $stageInvalid,
            'sex' => $sex,
            'sex_invalid' => $sexInvalid,
            'repletion_state' => $repletionState,
            'repletion_state_invalid' => $repletionStateInvalid,
            'date_identified' => $dateIdentified,
            'identified_at' => $identifiedAt,
            'identified_at_exists' => $identifiedAtExists,
            'identified_by' => $identifiedBy,
            'identified_by_exists' => $identifiedByExists,
            'sample_state' => $sampleState,
            'sample_state_exists' => $sampleStateExists,
            'show_date_identified_input' => $showDateIdentifiedInput,
            'show_identified_at_input' => $showIdentifiedAtInput,
            'show_identified_by_input' => $showIdentifiedByInput,
            'show_sample_state_input' => $showSampleStateInput,
            'field_warnings' => $fieldWarnings,
            'warnings' => $warnings,
            'issues' => $issues,
        ];
    }

    private function normalizeOriginType(string $value): string
    {
        $value = strtolower(trim($this->sanitizeCell($value)));

        return match ($value) {
            'human', 'humans', 'human sample', 'human samples' => 'human',
            'animal', 'animals', 'animal sample', 'animal samples' => 'animal',
            'environment', 'environmental', 'environment sample', 'environmental sample', 'environment samples', 'environmental samples' => 'environment',
            default => $value === '' ? '' : $value,
        };
    }

    private function normalizeStage(string $value): string
    {
        $value = strtolower(trim($this->sanitizeCell($value)));

        return match ($value) {
            'egg' => 'Egg',
            'larva' => 'Larva',
            'pupa' => 'Pupa',
            'nymph' => 'Nymph',
            'adult' => 'Adult',
            'n/a', 'na', 'none', 'unknown' => 'N/A',
            default => $value === '' ? '' : $this->sanitizeCell($value),
        };
    }

    private function normalizeSex(string $value): string
    {
        $value = strtolower(trim($this->sanitizeCell($value)));

        return match ($value) {
            'male', 'm' => 'Male',
            'female', 'f' => 'Female',
            'n/a', 'na', 'none', 'unknown' => 'N/A',
            default => $value === '' ? '' : $this->sanitizeCell($value),
        };
    }

    private function normalizeRepletionState(string $value): string
    {
        $value = strtolower(trim($this->sanitizeCell($value)));

        return match ($value) {
            'engorged' => 'Engorged',
            'partially engorged', 'partially_engorged' => 'Partially engorged',
            'not engorged', 'not_engorged' => 'Not engorged',
            'n/a', 'na', 'none', 'unknown' => 'N/A',
            default => $value === '' ? '' : $this->sanitizeCell($value),
        };
    }

    /** @return list<string> */
    private function stageOptions(): array
    {
        return ['Egg', 'Larva', 'Pupa', 'Nymph', 'Adult', 'N/A'];
    }

    /** @return list<string> */
    private function sexOptions(): array
    {
        return ['Male', 'Female', 'N/A'];
    }

    /** @return list<string> */
    private function repletionStateOptions(): array
    {
        return ['Engorged', 'Partially engorged', 'Not engorged', 'N/A'];
    }

    /**
     * @return array<string, list<string>>
     */
    private function synonymsByField(): array
    {
        return [
            'origin_type' => ['origin_type', 'origin type', 'sample_origin_type', 'origin'],
            'origin_code' => ['origin_code', 'origin code', 'sample_origin_code', 'code_origin'],
            'parasite_species' => ['parasite_species', 'parasite species', 'species', 'name_scientific'],
            'parasite_family' => ['parasite_family', 'parasite family', 'family'],
            'stage' => ['stage', 'parasite_stage'],
            'sex' => ['sex', 'parasite_sex'],
            'repletion_state' => ['repletion_state', 'repletion state', 'state', 'parasite_state'],
            'date_identified' => ['date_identified', 'date identified', 'identification_date', 'date'],
            'identified_at' => ['identified_at', 'identified at', 'laboratory', 'lab'],
            'identified_by' => ['identified_by', 'identified by', 'identifier', 'identificator'],
            'sample_state' => ['sample_state', 'sample state', 'tube_state', 'preservant', 'storage_state'],
        ];
    }

    /** @return list<string> */
    private function requiredFields(): array
    {
        return [
            'origin_type',
            'origin_code',
            'parasite_species',
            'stage',
            'sex',
            'repletion_state',
            'date_identified',
            'identified_at',
            'identified_by',
            'sample_state',
        ];
    }

    private function sanitizeCell(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/u', '', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function normalizeWordsTitleCase(string $value): string
    {
        $value = $this->sanitizeCell($value);
        if ($value === '') {
            return '';
        }

        return mb_convert_case(mb_strtolower($value), MB_CASE_TITLE, 'UTF-8');
    }

    private function canonicalEntityName(string $value): string
    {
        $normalized = mb_strtolower($this->sanitizeCell($value));
        $normalized = preg_replace('/[^a-z0-9]+/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;
        $tokens = preg_split('/\s+/', trim($normalized), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        sort($tokens);

        return implode(' ', $tokens);
    }

    private function tokenOverlapScore(string $leftCanonical, string $rightCanonical): float
    {
        $left = array_values(array_filter(explode(' ', $leftCanonical)));
        $right = array_values(array_filter(explode(' ', $rightCanonical)));
        if ($left === [] || $right === []) {
            return 0.0;
        }

        $intersection = array_intersect($left, $right);
        $union = array_unique(array_merge($left, $right));
        if (count($union) === 0) {
            return 0.0;
        }

        return (count($intersection) / count($union)) * 100;
    }

    private function tokenPrefixScore(string $leftCanonical, string $rightCanonical): float
    {
        $left = array_values(array_filter(explode(' ', $leftCanonical)));
        $right = array_values(array_filter(explode(' ', $rightCanonical)));
        if ($left === [] || $right === []) {
            return 0.0;
        }

        $shorter = count($left) <= count($right) ? $left : $right;
        $longer = count($left) <= count($right) ? $right : $left;
        $matched = 0;

        foreach ($shorter as $shortToken) {
            foreach ($longer as $longToken) {
                if (
                    $shortToken === $longToken ||
                    str_starts_with($longToken, $shortToken) ||
                    str_starts_with($shortToken, $longToken)
                ) {
                    $matched++;
                    break;
                }
            }
        }

        return count($shorter) > 0 ? (($matched / count($shorter)) * 100) : 0.0;
    }

    /**
     * @param  Builder<Model>  $query
     * @return list<string>
     */
    private function similarOptionsFromQuery(Builder $query, string $column, string $value, int $threshold = 72, int $limit = 2): array
    {
        $value = trim($value);
        if (mb_strlen($value) < 3) {
            return [];
        }

        $candidates = $query
            ->limit(500)
            ->pluck($column)
            ->filter()
            ->map(fn ($v) => (string) $v)
            ->values()
            ->all();

        $valueCanonical = $this->canonicalEntityName($value);
        $scored = [];
        foreach ($candidates as $candidate) {
            if (mb_strtolower($this->sanitizeCell($candidate)) === mb_strtolower($this->sanitizeCell($value))) {
                continue;
            }

            $candidateCanonical = $this->canonicalEntityName($candidate);
            similar_text($valueCanonical, $candidateCanonical, $pct);
            $score = max(
                $pct,
                $this->tokenOverlapScore($valueCanonical, $candidateCanonical),
                $this->tokenPrefixScore($valueCanonical, $candidateCanonical)
            );
            if ($score >= $threshold) {
                $scored[] = ['candidate' => $candidate, 'score' => $score];
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        $result = [];
        $seen = [];
        foreach ($scored as $entry) {
            $candidate = (string) $entry['candidate'];
            $key = mb_strtolower($this->sanitizeCell($candidate));
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $candidate;
            }
            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }

    private function originModelFromType(string $originType): ?string
    {
        return match ($originType) {
            'human' => HumanSamples::class,
            'animal' => AnimalSamples::class,
            'environment' => EnvironmentSamples::class,
            default => null,
        };
    }

    /**
     * @param  class-string  $originModel
     * @return Builder<Model>
     */
    private function originCandidatesQuery(string $originModel, int $projectId): Builder
    {
        if ($originModel === HumanSamples::class) {
            return HumanSamples::query()
                ->where('projects_id', $projectId)
                ->whereHas('sample_types', function (Builder $query): void {
                    $query->where('category', 'non_host_derived');
                });
        }

        if ($originModel === AnimalSamples::class) {
            return AnimalSamples::query()
                ->where('projects_id', $projectId)
                ->whereHas('sample_types', function (Builder $query): void {
                    $query->where('category', 'non_host_derived');
                });
        }

        return EnvironmentSamples::query()
            ->where('projects_id', $projectId);
    }

    private function findPersonByFullName(string $fullName): ?People
    {
        $parts = preg_split('/\s+/', $this->sanitizeCell($fullName), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($parts) < 2) {
            return null;
        }

        $firstName = (string) array_shift($parts);
        $lastName = implode(' ', $parts);

        return People::query()
            ->whereRaw('lower(trim(first_name)) = ?', [strtolower(trim($firstName))])
            ->whereRaw('lower(trim(last_name)) = ?', [strtolower(trim($lastName))])
            ->first();
    }

    private function canSplitPersonName(string $fullName): bool
    {
        $parts = preg_split('/\s+/', $this->sanitizeCell($fullName), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return count($parts) >= 2;
    }

    /** @return list<string> */
    private function similarPersonNames(string $fullName): array
    {
        $allNames = People::query()
            ->select('first_name', 'last_name')
            ->get()
            ->map(fn ($person) => trim(((string) $person->first_name).' '.((string) $person->last_name)))
            ->filter()
            ->values()
            ->all();

        $valueCanonical = $this->canonicalEntityName($fullName);
        $scored = [];
        foreach ($allNames as $candidate) {
            if (mb_strtolower($this->sanitizeCell($candidate)) === mb_strtolower($this->sanitizeCell($fullName))) {
                continue;
            }

            $candidateCanonical = $this->canonicalEntityName($candidate);
            similar_text($valueCanonical, $candidateCanonical, $pct);
            $score = max(
                $pct,
                $this->tokenOverlapScore($valueCanonical, $candidateCanonical),
                $this->tokenPrefixScore($valueCanonical, $candidateCanonical)
            );
            if ($score >= 72.0) {
                $scored[] = ['candidate' => $candidate, 'score' => $score];
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        $result = [];
        foreach ($scored as $entry) {
            $candidate = (string) $entry['candidate'];
            if (! in_array($candidate, $result, true)) {
                $result[] = $candidate;
            }
            if (count($result) >= 2) {
                break;
            }
        }

        return $result;
    }

    private function resolveOrCreateWholeParasiteSampleTypeId(): int
    {
        $existing = ParasiteSampleTypes::query()
            ->whereRaw('lower(name) = ?', ['whole parasite'])
            ->first(['id']);

        if ($existing) {
            return (int) $existing->id;
        }

        return (int) ParasiteSampleTypes::query()->create(['name' => 'Whole parasite'])->id;
    }

    private function resolveOrCreateParasiteSpeciesId(string $nameScientific, string $family): ?int
    {
        $nameScientific = $this->sanitizeCell($nameScientific);
        if ($nameScientific === '') {
            return null;
        }

        $existing = ParasiteSpecies::query()
            ->whereRaw('lower(name_scientific) = ?', [strtolower($nameScientific)])
            ->first(['id']);
        if ($existing) {
            return (int) $existing->id;
        }

        $species = ParasiteSpecies::query()->create([
            'name_scientific' => $nameScientific,
            'family' => $family !== '' ? $family : null,
        ]);

        return (int) $species->id;
    }

    private function resolveOrCreateLaboratoryId(string $name): ?int
    {
        $name = $this->sanitizeCell($name);
        if ($name === '') {
            return null;
        }

        $existing = Laboratories::query()
            ->whereRaw('lower(name) = ?', [strtolower($name)])
            ->first(['id']);
        if ($existing) {
            return (int) $existing->id;
        }

        return (int) Laboratories::query()->create(['name' => $name])->id;
    }

    private function resolveOrCreatePersonId(string $fullName): ?int
    {
        $fullName = $this->sanitizeCell($fullName);
        if ($fullName === '') {
            return null;
        }

        $existing = $this->findPersonByFullName($fullName);
        if ($existing) {
            return (int) $existing->id;
        }

        $parts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($parts) < 2) {
            return null;
        }

        $firstName = (string) array_shift($parts);
        $lastName = implode(' ', $parts);

        return (int) People::query()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
        ])->id;
    }

    private function storeRowPhoto(int $rowNumber): ?string
    {
        $upload = $this->rowPhotos[$rowNumber] ?? null;
        if (! $upload) {
            return null;
        }

        return $upload->store('parasites', 'local');
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array<int, bool>
     */
    private function usedSerialSet(string $modelClass, int $projectId, string $projectCode, string $suffix): array
    {
        $codes = $modelClass::query()
            ->where('projects_id', $projectId)
            ->where('code', 'like', "{$projectCode}-{$suffix}-%")
            ->pluck('code');

        $used = [];
        foreach ($codes as $code) {
            preg_match('/-'.preg_quote($suffix, '/').'-(\d+)$/', (string) $code, $match);
            if (isset($match[1])) {
                $used[(int) $match[1]] = true;
            }
        }

        return $used;
    }

    /**
     * @param  array<int, bool>  $usedSet
     */
    private function nextAvailableSerial(array &$usedSet, int &$cursor): int
    {
        while (isset($usedSet[$cursor])) {
            $cursor++;
        }

        $serial = $cursor;
        $usedSet[$serial] = true;
        $cursor++;

        return $serial;
    }

    private function nextTubeSerialForPrefix(string $prefix): int
    {
        $existing = Tubes::query()
            ->where('code', 'like', $prefix.'-%')
            ->pluck('code');

        $used = [];
        foreach ($existing as $code) {
            preg_match('/'.preg_quote($prefix, '/').'-(\d+)$/', (string) $code, $match);
            if (isset($match[1])) {
                $used[(int) $match[1]] = true;
            }
        }

        $serial = 1;
        while (isset($used[$serial])) {
            $serial++;
        }

        return $serial;
    }

    /**
     * @return array{rows: list<array<string,mixed>>, total: int, from: int, to: int}
     */
    public function previewPage(): array
    {
        if (! $this->cacheKey) {
            return ['rows' => [], 'total' => 0, 'from' => 0, 'to' => 0];
        }

        $rows = Cache::get($this->cacheKey);
        if (! is_array($rows)) {
            return ['rows' => [], 'total' => 0, 'from' => 0, 'to' => 0];
        }

        $total = count($rows);
        $page = max(1, (int) $this->page);
        $perPage = max(10, min(200, (int) $this->perPage));
        $offset = ($page - 1) * $perPage;

        $slice = array_slice($rows, $offset, $perPage);
        $mapped = [];
        $projectId = $this->selectedProjectId() ?? 0;
        foreach ($slice as $row) {
            $rowNumber = $offset + count($mapped) + 2;
            $mapped[] = $this->resolveRow(is_array($row) ? $row : [], (int) $projectId, $rowNumber);
        }

        return [
            'rows' => $mapped,
            'total' => $total,
            'from' => $total === 0 ? 0 : $offset + 1,
            'to' => min($total, $offset + $perPage),
        ];
    }

    public function render()
    {
        return view('livewire.imports.parasite-samples-import', [
            'template' => $this->templateDefinition(),
        ]);
    }

    /**
     * @return array{columns:list<array{
     *   header:string,
     *   field:string,
     *   required:'required'|'optional'|'conditional',
     *   description:string,
     *   format:string,
     *   accepted:list<string>,
     *   aliases:list<string>,
     *   create_policy:string,
     *   create_notes:string,
     *   example:string,
     *   options:list<string>,
     *   options_total:int
     * }>}
     */
    private function templateDefinition(): array
    {
        $projectId = $this->selectedProjectId();

        $options = Cache::remember(
            'imports:parasite_samples:template_options:'.($projectId ?? 0),
            now()->addMinutes(30),
            function () use ($projectId): array {
                return [
                    'origin_codes' => $this->originCodePreview($projectId),
                    'parasite_species' => $this->optionPreview(ParasiteSpecies::query()->orderBy('name_scientific'), 'name_scientific'),
                    'parasite_families' => $this->optionPreviewDistinct(ParasiteSpecies::query(), 'family'),
                    'laboratories' => $this->optionPreview(Laboratories::query()->orderBy('name'), 'name'),
                    'people_names' => $this->optionPreviewPeopleNames($projectId),
                    'sample_state' => $this->optionPreviewDistinct(Tubes::query()->whereNotNull('preservant'), 'preservant'),
                ];
            }
        );

        $aliases = $this->synonymsByField();
        $originTypes = ['human', 'animal', 'environment'];
        $stage = $this->stageOptions();
        $sex = $this->sexOptions();
        $repletion = $this->repletionStateOptions();

        $columns = [
            [
                'header' => 'origin_type',
                'field' => 'origin_type',
                'required' => 'required',
                'description' => 'Type of the origin sample that the parasite was collected from.',
                'format' => 'One of the accepted values.',
                'accepted' => $originTypes,
                'aliases' => $aliases['origin_type'] ?? [],
                'create_policy' => 'Must match accepted values.',
                'create_notes' => '',
                'example' => 'animal',
                'options' => $originTypes,
                'options_total' => count($originTypes),
            ],
            [
                'header' => 'origin_code',
                'field' => 'origin_code',
                'required' => 'required',
                'description' => 'Code of the origin sample (must exist in current project for the selected origin_type).',
                'format' => 'Exact sample code.',
                'accepted' => [],
                'aliases' => $aliases['origin_code'] ?? [],
                'create_policy' => 'Links to existing origin samples by exact code. Cannot create origin samples here.',
                'create_notes' => 'For human/animal origins, matching is restricted to non-host-derived sample codes.',
                'example' => 'A1A1-AS-102',
                'options' => $options['origin_codes']['values'],
                'options_total' => $options['origin_codes']['total'],
            ],
            [
                'header' => 'parasite_species',
                'field' => 'parasite_species',
                'required' => 'required',
                'description' => 'Scientific name of the parasite species.',
                'format' => 'Text (case-insensitive match).',
                'accepted' => [],
                'aliases' => $aliases['parasite_species'] ?? [],
                'create_policy' => 'Links to existing ParasiteSpecies by exact scientific name; otherwise creates a new species.',
                'create_notes' => 'If new, parasite_family is required.',
                'example' => 'Rhipicephalus appendiculatus',
                'options' => $options['parasite_species']['values'],
                'options_total' => $options['parasite_species']['total'],
            ],
            [
                'header' => 'parasite_family',
                'field' => 'parasite_family',
                'required' => 'conditional',
                'description' => 'Family for a newly created parasite species.',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['parasite_family'] ?? [],
                'create_policy' => 'Required only when parasite_species is new.',
                'create_notes' => '',
                'example' => 'Ixodidae',
                'options' => $options['parasite_families']['values'],
                'options_total' => $options['parasite_families']['total'],
            ],
            [
                'header' => 'stage',
                'field' => 'stage',
                'required' => 'required',
                'description' => 'Parasite stage.',
                'format' => 'One of the accepted values.',
                'accepted' => $stage,
                'aliases' => $aliases['stage'] ?? [],
                'create_policy' => 'Must match accepted values.',
                'create_notes' => '',
                'example' => 'Adult',
                'options' => $stage,
                'options_total' => count($stage),
            ],
            [
                'header' => 'sex',
                'field' => 'sex',
                'required' => 'required',
                'description' => 'Parasite sex.',
                'format' => 'One of the accepted values.',
                'accepted' => $sex,
                'aliases' => $aliases['sex'] ?? [],
                'create_policy' => 'Must match accepted values.',
                'create_notes' => '',
                'example' => 'Female',
                'options' => $sex,
                'options_total' => count($sex),
            ],
            [
                'header' => 'repletion_state',
                'field' => 'repletion_state',
                'required' => 'required',
                'description' => 'Repletion/engorgement state.',
                'format' => 'One of the accepted values.',
                'accepted' => $repletion,
                'aliases' => $aliases['repletion_state'] ?? [],
                'create_policy' => 'Must match accepted values.',
                'create_notes' => '',
                'example' => 'Engorged',
                'options' => $repletion,
                'options_total' => count($repletion),
            ],
            [
                'header' => 'date_identified',
                'field' => 'date_identified',
                'required' => 'required',
                'description' => 'Date the parasite was identified.',
                'format' => 'YYYY-MM-DD.',
                'accepted' => [],
                'aliases' => $aliases['date_identified'] ?? [],
                'create_policy' => 'Required.',
                'create_notes' => '',
                'example' => now()->toDateString(),
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'identified_at',
                'field' => 'identified_at',
                'required' => 'required',
                'description' => 'Laboratory where identification happened.',
                'format' => 'Text (case-insensitive match).',
                'accepted' => [],
                'aliases' => $aliases['identified_at'] ?? [],
                'create_policy' => 'Links to existing Laboratories by exact name match; otherwise creates a new laboratory.',
                'create_notes' => '',
                'example' => 'Tuks Parasitology Lab',
                'options' => $options['laboratories']['values'],
                'options_total' => $options['laboratories']['total'],
            ],
            [
                'header' => 'identified_by',
                'field' => 'identified_by',
                'required' => 'required',
                'description' => 'Person who identified the parasite (full name).',
                'format' => 'First name and last name.',
                'accepted' => [],
                'aliases' => $aliases['identified_by'] ?? [],
                'create_policy' => 'Links to existing People by full name; otherwise creates a new person.',
                'create_notes' => 'For new people, the value must include at least first + last name.',
                'example' => 'Carlo Cossu',
                'options' => $options['people_names']['values'],
                'options_total' => $options['people_names']['total'],
            ],
            [
                'header' => 'sample_state',
                'field' => 'sample_state',
                'required' => 'required',
                'description' => 'Sample state/preservant. Saved into the auto-created tube as preservant.',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['sample_state'] ?? [],
                'create_policy' => 'Saved as tube preservant. Not restricted to existing values.',
                'create_notes' => '',
                'example' => 'Preserved in 100% ethanol',
                'options' => $options['sample_state']['values'],
                'options_total' => $options['sample_state']['total'],
            ],
        ];

        return [
            'columns' => $columns,
        ];
    }

    /**
     * @return array{values:list<string>, total:int}
     */
    private function optionPreview(Builder $query, string $column, int $limit = 10): array
    {
        $base = (clone $query)
            ->whereNotNull($column)
            ->where($column, '!=', '');

        $total = (int) (clone $base)->distinct()->count($column);
        $values = (clone $base)
            ->distinct()
            ->orderBy($column)
            ->limit($limit)
            ->pluck($column)
            ->filter(fn ($value): bool => filled($value))
            ->map(fn ($value): string => (string) $value)
            ->values()
            ->all();

        return ['values' => $values, 'total' => $total];
    }

    /**
     * @return array{values:list<string>, total:int}
     */
    private function optionPreviewDistinct(Builder $query, string $column, int $limit = 10): array
    {
        $base = (clone $query)
            ->whereNotNull($column)
            ->where($column, '!=', '');

        $total = (int) (clone $base)->distinct()->count($column);
        $values = (clone $base)
            ->distinct()
            ->orderBy($column)
            ->limit($limit)
            ->pluck($column)
            ->filter(fn ($value): bool => filled($value))
            ->map(fn ($value): string => (string) $value)
            ->values()
            ->all();

        return ['values' => $values, 'total' => $total];
    }

    /**
     * @return array{values:list<string>, total:int}
     */
    private function optionPreviewPeopleNames(?int $projectId, int $limit = 10): array
    {
        $base = $this->projectPeopleQuery($projectId)
            ->whereNotNull('first_name')
            ->where('first_name', '!=', '')
            ->whereNotNull('last_name')
            ->where('last_name', '!=', '');

        $total = (int) (clone $base)->count();

        $values = (clone $base)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit($limit)
            ->get(['first_name', 'last_name'])
            ->map(fn (People $p): string => trim(((string) $p->first_name).' '.((string) $p->last_name)))
            ->filter(fn (string $name): bool => $name !== '')
            ->values()
            ->all();

        return [
            'values' => array_values(array_unique($values)),
            'total' => $total,
        ];
    }

    /**
     * @return array{values:list<string>, total:int}
     */
    private function originCodePreview(?int $projectId, int $limit = 10): array
    {
        if (! $projectId) {
            return ['values' => [], 'total' => 0];
        }

        $humanTotal = (int) HumanSamples::query()
            ->where('projects_id', $projectId)
            ->whereHas('sample_types', fn (Builder $q) => $q->where('category', 'non_host_derived'))
            ->distinct()
            ->count('code');

        $animalTotal = (int) AnimalSamples::query()
            ->where('projects_id', $projectId)
            ->whereHas('sample_types', fn (Builder $q) => $q->where('category', 'non_host_derived'))
            ->distinct()
            ->count('code');

        $environmentTotal = (int) EnvironmentSamples::query()
            ->where('projects_id', $projectId)
            ->distinct()
            ->count('code');

        $values = array_values(array_unique(array_filter(array_merge(
            HumanSamples::query()
                ->where('projects_id', $projectId)
                ->whereHas('sample_types', fn (Builder $q) => $q->where('category', 'non_host_derived'))
                ->orderBy('code')
                ->limit((int) ceil($limit / 3))
                ->pluck('code')
                ->map(fn ($v) => (string) $v)
                ->all(),
            AnimalSamples::query()
                ->where('projects_id', $projectId)
                ->whereHas('sample_types', fn (Builder $q) => $q->where('category', 'non_host_derived'))
                ->orderBy('code')
                ->limit((int) ceil($limit / 3))
                ->pluck('code')
                ->map(fn ($v) => (string) $v)
                ->all(),
            EnvironmentSamples::query()
                ->where('projects_id', $projectId)
                ->orderBy('code')
                ->limit((int) ceil($limit / 3))
                ->pluck('code')
                ->map(fn ($v) => (string) $v)
                ->all(),
        ))));

        return [
            'values' => array_slice($values, 0, $limit),
            'total' => $humanTotal + $animalTotal + $environmentTotal,
        ];
    }

    /**
     * @return array{title:string, values:list<string>, total:int, truncated:bool}
     */
    private function templateOptionsForField(string $field, ?int $projectId): array
    {
        $field = trim((string) $field);

        if ($field === 'origin_type') {
            $values = ['human', 'animal', 'environment'];

            return [
                'title' => 'Origin types',
                'values' => $values,
                'total' => count($values),
                'truncated' => false,
            ];
        }

        if ($field === 'stage') {
            $values = $this->stageOptions();

            return [
                'title' => 'Stage values',
                'values' => $values,
                'total' => count($values),
                'truncated' => false,
            ];
        }

        if ($field === 'sex') {
            $values = $this->sexOptions();

            return [
                'title' => 'Sex values',
                'values' => $values,
                'total' => count($values),
                'truncated' => false,
            ];
        }

        if ($field === 'repletion_state') {
            $values = $this->repletionStateOptions();

            return [
                'title' => 'Repletion state values',
                'values' => $values,
                'total' => count($values),
                'truncated' => false,
            ];
        }

        if ($field === 'origin_code') {
            $max = 1200;

            return $this->templateOptionsOriginCodes($projectId, $max);
        }

        $max = 1200;

        return match ($field) {
            'parasite_species' => $this->templateOptionsFromQuery(ParasiteSpecies::query(), 'name_scientific', 'Parasite species (scientific)', $max),
            'parasite_family' => $this->templateOptionsFromQuery(ParasiteSpecies::query()->whereNotNull('family'), 'family', 'Parasite families', $max),
            'identified_at' => $this->templateOptionsFromQuery(Laboratories::query(), 'name', 'Laboratories', $max),
            'sample_state' => $this->templateOptionsFromQuery(Tubes::query()->whereNotNull('preservant'), 'preservant', 'Tube preservants', $max),
            'identified_by' => $this->templateOptionsPeopleNames($projectId, $max),
            default => [
                'title' => 'Values',
                'values' => [],
                'total' => 0,
                'truncated' => false,
            ],
        };
    }

    /**
     * @return array{title:string, values:list<string>, total:int, truncated:bool}
     */
    private function templateOptionsOriginCodes(?int $projectId, int $max = 1200): array
    {
        if (! $projectId) {
            return ['title' => 'Origin codes', 'values' => [], 'total' => 0, 'truncated' => false];
        }

        $human = HumanSamples::query()
            ->where('projects_id', $projectId)
            ->whereHas('sample_types', fn (Builder $q) => $q->where('category', 'non_host_derived'));
        $animal = AnimalSamples::query()
            ->where('projects_id', $projectId)
            ->whereHas('sample_types', fn (Builder $q) => $q->where('category', 'non_host_derived'));
        $environment = EnvironmentSamples::query()
            ->where('projects_id', $projectId);

        $total = (int) $human->distinct()->count('code')
            + (int) $animal->distinct()->count('code')
            + (int) $environment->distinct()->count('code');

        $values = array_values(array_unique(array_filter(array_merge(
            (clone $human)->orderBy('code')->limit((int) floor($max / 3))->pluck('code')->map(fn ($v) => (string) $v)->all(),
            (clone $animal)->orderBy('code')->limit((int) floor($max / 3))->pluck('code')->map(fn ($v) => (string) $v)->all(),
            (clone $environment)->orderBy('code')->limit((int) floor($max / 3))->pluck('code')->map(fn ($v) => (string) $v)->all(),
        ))));

        $values = array_slice($values, 0, $max);
        $truncated = $total > count($values);

        return [
            'title' => 'Origin codes (all types)',
            'values' => $values,
            'total' => $total,
            'truncated' => $truncated,
        ];
    }

    /**
     * @return array{title:string, values:list<string>, total:int, truncated:bool}
     */
    private function templateOptionsPeopleNames(?int $projectId, int $max = 1200): array
    {
        $base = $this->projectPeopleQuery($projectId)
            ->whereNotNull('first_name')
            ->where('first_name', '!=', '')
            ->whereNotNull('last_name')
            ->where('last_name', '!=', '');

        $total = (int) (clone $base)->count();

        $values = (clone $base)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit($max)
            ->get(['first_name', 'last_name'])
            ->map(fn (People $p): string => trim(((string) $p->first_name).' '.((string) $p->last_name)))
            ->filter(fn (string $name): bool => $name !== '')
            ->values()
            ->all();

        $values = array_values(array_unique($values));
        $truncated = $total > count($values);

        return [
            'title' => 'People (full names)',
            'values' => $values,
            'total' => $total,
            'truncated' => $truncated,
        ];
    }

    private function projectPeopleQuery(?int $projectId): Builder
    {
        if (! $projectId) {
            return People::query()->whereRaw('1 = 0');
        }

        return People::query()
            ->whereIn('id', function ($query) use ($projectId): void {
                $query->from('projects_people')
                    ->select('people_id')
                    ->where('projects_id', $projectId);
            });
    }

    /**
     * @return array{title:string, values:list<string>, total:int, truncated:bool}
     */
    private function templateOptionsFromQuery(Builder $query, string $column, string $title, int $max = 1200): array
    {
        $base = (clone $query)
            ->whereNotNull($column)
            ->where($column, '!=', '');

        $total = (int) (clone $base)->distinct()->count($column);

        $values = (clone $base)
            ->distinct()
            ->orderBy($column)
            ->limit($max)
            ->pluck($column)
            ->filter(fn ($value): bool => filled($value))
            ->map(fn ($value): string => (string) $value)
            ->values()
            ->all();

        $truncated = $total > count($values);

        return [
            'title' => $title,
            'values' => $values,
            'total' => $total,
            'truncated' => $truncated,
        ];
    }
}
