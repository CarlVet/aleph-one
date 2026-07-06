<?php

namespace App\Livewire\Imports;

use App\Http\Controllers\NotificationController;
use App\Livewire\PlainComponent;
use App\Models\AnimalSamples;
use App\Models\Countries;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\Experiments;
use App\Models\HumanSamples;
use App\Models\Laboratories;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pathogens;
use App\Models\People;
use App\Models\Pools;
use App\Models\Projects;
use App\Models\Protocols;
use App\Models\SampleTypes;
use App\Models\Techniques;
use App\Models\Tubes;
use App\Services\Imports\ColumnMatcher;
use App\Services\Imports\DelimitedTableReader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;

class ExperimentsImport extends PlainComponent
{
    use WithFileUploads;

    #[Validate('required|file|mimes:csv,txt,xlsx,xls|max:20480')]
    public $file;

    public string $status = 'idle';

    public array $globalIssues = [];

    public array $globalWarnings = [];

    /** @var array<int, array<string, string>> */
    public array $rowOverrides = [];

    /** @var list<string> */
    public array $headers = [];

    /** @var array<string, int> */
    public array $fieldToIndex = [];

    public int $page = 1;

    public int $perPage = 25;

    public ?string $cacheKey = null;

    /** @var list<string> */
    private array $allowedSampleTypes = [
        'Human samples',
        'Animal samples',
        'Environmental samples',
        'Parasite samples',
        'Nucleic acids',
        'Cultures',
        'Pools',
    ];

    /** @var list<string> */
    private array $allowedTechniqueCategories = [
        'Nucleic acid detection test',
        'Antibody detection test',
        'Microbiology',
        'Antigen detection test',
    ];

    /** @var list<string> */
    private array $allowedDiscreteOutcomes = [
        'Negative',
        'Suspect',
        'Positive',
        'Strong positive',
    ];

    /** @var list<string> */
    private array $allowedOutcomeTypes = [
        'Qualitative only',
        'Qualitative and quantitative',
    ];

    /** @var list<string> */
    private array $allowedPurposes = [
        'screening',
        'confirmation',
    ];

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

        return response()->streamDownload(function () use ($headers, $exampleRow): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            fputcsv($output, $headers);
            fputcsv($output, $exampleRow);
            fclose($output);
        }, 'experiments_import_template.csv', [
            'Content-Type' => 'text/csv',
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

    public function buildPreview(): void
    {
        $this->globalIssues = [];
        $this->globalWarnings = [];

        if (! $this->userCanEditSelectedProject()) {
            $this->globalIssues[] = 'You do not have permission to import experiments in this project (viewer accounts are read-only).';
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

        $missing = [];
        foreach ($this->requiredFields() as $required) {
            if (! array_key_exists($required, $this->fieldToIndex)) {
                $this->globalIssues[] = "Missing required column: {$required}";
                $missing[] = $required;
            }
        }

        if (! empty($missing)) {
            $previewHeaders = array_slice($this->headers, 0, 40);
            $headersText = implode(' | ', array_map(static fn (string $h): string => $h === '' ? '(empty)' : $h, $previewHeaders));
            $suffix = count($this->headers) > count($previewHeaders) ? ' | …' : '';
            $this->globalIssues[] = 'Detected headers: '.$headersText.$suffix;
        }

        if (
            ! array_key_exists('tube_id', $this->fieldToIndex)
            && ! array_key_exists('tube_alias', $this->fieldToIndex)
            && ! array_key_exists('sample_code', $this->fieldToIndex)
        ) {
            $this->globalIssues[] = 'You must provide at least one identifier column: tube_id, tube_alias, or sample_code.';
        }

        $this->cacheKey = "imports:experiments:{$projectId}:".bin2hex(random_bytes(8));
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
        $this->status = 'idle';
        $this->page = 1;
    }

    public function applySuggestedValue(int $rowNumber, string $field, string $value): void
    {
        if ($rowNumber < 2) {
            return;
        }

        $this->rowOverrides[$rowNumber][$field] = $value;
    }

    public function updatedPerPage(): void
    {
        $this->perPage = max(10, min(200, (int) $this->perPage));
        $this->page = 1;
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function nextPage(): void
    {
        $lastPage = $this->previewPage()['last_page'] ?? 1;
        $this->page = min(max(1, (int) $lastPage), $this->page + 1);
    }

    public function goToPage(int $page): void
    {
        $lastPage = $this->previewPage()['last_page'] ?? 1;
        $this->page = min(max(1, $page), max(1, (int) $lastPage));
    }

    public function import(): void
    {
        $this->globalIssues = [];
        $this->globalWarnings = [];

        if ($this->status !== 'preview' || ! $this->cacheKey) {
            $this->globalIssues[] = 'Upload a file and fix issues before importing.';
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
        $created = 0;
        $errors = 0;
        $rollbackSignal = '__experiments_bulk_import_rollback__';

        try {
            DB::transaction(function () use ($rows, $project, &$created, &$errors, $rollbackSignal): void {
                $nextSerial = $this->nextExperimentSerialForProject($project->id, $project->code);
                $reservedExperimentCodes = Experiments::query()
                    ->where('code', 'like', $project->code.'-EX-%')
                    ->pluck('code')
                    ->mapWithKeys(fn ($code) => [(string) $code => true])
                    ->all();

                foreach ($rows as $index => $row) {
                    $rowNumber = $index + 2;
                    $resolved = $this->resolveRow(is_array($row) ? $row : [], $project->id, $rowNumber);

                    if (! empty($resolved['warnings'])) {
                        $this->globalWarnings[] = "Row {$rowNumber}: ".implode(' | ', $resolved['warnings']);
                    }

                    if (! empty($resolved['issues'])) {
                        $errors++;
                        $this->globalIssues[] = "Row {$rowNumber}: ".implode(' | ', $resolved['issues']);

                        continue;
                    }

                    $tube = $resolved['tube'];
                    if (! $tube) {
                        $errors++;
                        $this->globalIssues[] = "Row {$rowNumber}: unable to resolve tube";

                        continue;
                    }

                    $pathogen = Pathogens::query()
                        ->whereRaw('lower(species) = ?', [strtolower($resolved['pathogen'])])
                        ->first();
                    if (! $pathogen) {
                        $errors++;
                        $this->globalIssues[] = "Row {$rowNumber}: pathogen not found: {$resolved['pathogen']}";

                        continue;
                    }

                    $protocol = $this->resolveOrCreateProtocol(
                        $project,
                        $resolved['protocol_name'],
                        $resolved['technique_type'],
                        $resolved['technique_category']
                    );

                    if (! $protocol) {
                        $errors++;
                        $this->globalIssues[] = "Row {$rowNumber}: unable to resolve/create protocol";

                        continue;
                    }

                    $protocol->pathogens()->syncWithoutDetaching([(int) $pathogen->id]);

                    $laboratory = $this->resolveOrCreateLaboratory(
                        $resolved['laboratory'],
                        $resolved['laboratory_country']
                    );
                    if (! $laboratory) {
                        $errors++;
                        $this->globalIssues[] = "Row {$rowNumber}: unable to resolve/create laboratory";

                        continue;
                    }

                    $testedBy = $this->resolveOrCreateTester(
                        $resolved['tested_by'],
                        (string) ($resolved['tested_by_first_name'] ?? ''),
                        (string) ($resolved['tested_by_last_name'] ?? '')
                    );
                    if (! $testedBy) {
                        $errors++;
                        $this->globalIssues[] = "Row {$rowNumber}: unable to resolve/create tested_by user";

                        continue;
                    }

                    $saved = false;
                    $attempts = 0;
                    while (! $saved && $attempts < 5) {
                        $code = $this->reserveNextExperimentCode($project->code, $nextSerial, $reservedExperimentCodes);

                        try {
                            Experiments::query()->create([
                                'code' => $code,
                                'experiments_content_type' => (string) $tube->tubes_content_type,
                                'experiments_content_id' => (int) $tube->tubes_content_id,
                                'protocols_id' => (int) $protocol->id,
                                'pathogens_id' => (int) $pathogen->id,
                                'outcome_discrete' => $resolved['outcome_discrete'],
                                'outcome_quant' => $resolved['outcome_quant'] !== '' ? (float) $resolved['outcome_quant'] : null,
                                'outcome_binary' => in_array($resolved['outcome_discrete'], ['Positive', 'Strong positive'], true) ? 1 : 0,
                                'purpose' => $resolved['purpose'],
                                'date_tested' => $resolved['date_tested'],
                                'people_id' => (int) $testedBy->id,
                                'laboratories_id' => (int) $laboratory->id,
                                'projects_id' => (int) $project->id,
                            ]);

                            $saved = true;
                        } catch (UniqueConstraintViolationException $e) {
                            if (! str_contains(strtolower($e->getMessage()), 'experiments.code')) {
                                throw $e;
                            }

                            $reservedExperimentCodes[$code] = true;
                            $attempts++;
                        }
                    }

                    if (! $saved) {
                        $errors++;
                        $this->globalIssues[] = "Row {$rowNumber}: unable to generate a unique experiment code";

                        continue;
                    }

                    $created++;
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
        $successMessage = "{$created} experiments imported successfully.";
        session()->flash('success', $successMessage);
        NotificationController::create(
            'experiment_created',
            'Bulk experiments imported',
            $successMessage,
            '/experiments/list',
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
     *   row_number:int,
     *   tube_id:string,
     *   tube_alias:string,
     *   sample_code:string,
     *   sample_type:string,
     *   selected_tube_code:string,
     *   selected_tube_alias_code:string,
     *   selected_sample_code:string,
     *   protocol_name:string,
     *   pathogen:string,
     *   outcome_type:string,
     *   outcome_discrete:string,
     *   outcome_quant:string,
     *   purpose:string,
     *   date_tested:string,
     *   laboratory:string,
     *   tested_by:string,
     *   tested_by_first_name:string,
     *   tested_by_last_name:string,
     *   technique_type:string,
     *   technique_category:string,
     *   laboratory_country:string,
     *   tube_exists:bool,
     *   protocol_exists:bool,
     *   pathogen_exists:bool,
     *   laboratory_exists:bool,
     *   tested_by_exists:bool,
     *   field_warnings:array<string, array{text:string,suggested:string,options:list<string>}>,
     *   warnings:list<string>,
     *   issues:list<string>,
     *   tube:?Tubes
     * }
     */
    private function resolveRow(array $row, int $projectId, ?int $rowNumber = null): array
    {
        $get = function (string $field) use ($row, $rowNumber): string {
            if ($rowNumber !== null) {
                $override = $this->rowOverrides[$rowNumber][$field] ?? null;
                if ($override !== null && trim((string) $override) !== '') {
                    return $this->sanitizeCell((string) $override);
                }
            }

            $idx = $this->fieldToIndex[$field] ?? null;
            if ($idx === null) {
                return '';
            }

            return isset($row[$idx]) ? $this->sanitizeCell((string) $row[$idx]) : '';
        };

        $tubeId = $get('tube_id');
        $tubeAlias = $get('tube_alias');
        $sampleCode = $get('sample_code');
        $sampleType = $this->normalizeSampleType($get('sample_type'));
        $animalSampleType = $this->normalizeNameStyle($get('animal_sample_type'));
        $protocolName = $this->normalizeNameStyle($get('protocol_name'));
        $pathogen = $this->normalizeNameStyle($get('pathogen'));
        $rawOutcomeType = $get('outcome_type');
        $rawOutcomeQuant = $get('outcome_quant');
        $outcomeType = $this->normalizeOutcomeType($rawOutcomeType);
        $outcomeDiscrete = $this->normalizeOutcome($get('outcome_discrete'));
        $outcomeQuant = $this->normalizeOutcomeQuant($rawOutcomeQuant);
        $purpose = $this->normalizePurpose($get('purpose'));

        if (
            $this->sanitizeCell($rawOutcomeType) === ''
            && in_array($this->normalizeOutcomeType($rawOutcomeQuant), $this->allowedOutcomeTypes, true)
        ) {
            $outcomeType = $this->normalizeOutcomeType($rawOutcomeQuant);
            $outcomeQuant = '';
        }
        $dateTested = $get('date_tested');
        $laboratory = $this->normalizeNameStyle($get('laboratory'));
        $testedBy = $this->sanitizeCell($get('tested_by'));
        $testedByFirstName = $this->normalizeWordsTitleCase($get('tested_by_first_name'));
        $testedByLastName = $this->normalizeWordsTitleCase($get('tested_by_last_name'));
        $techniqueType = $this->normalizeWordsTitleCase($get('technique_type'));
        $techniqueCategory = $this->normalizeTechniqueCategory($get('technique_category'));
        $laboratoryCountry = $this->normalizeWordsTitleCase($get('laboratory_country'));

        $issues = [];
        $warnings = [];
        $fieldWarnings = [];

        if ($tubeId === '' && $tubeAlias === '' && $sampleCode === '') {
            $issues[] = 'at least one of tube_id, tube_alias, or sample_code is required';
        }
        if ($sampleType !== '' && ! in_array($sampleType, $this->allowedSampleTypes, true)) {
            $issues[] = 'sample_type must be one of: '.implode(', ', $this->allowedSampleTypes);
        }
        if ($tubeAlias !== '' && $sampleType === 'Animal samples' && $animalSampleType === '') {
            $issues[] = 'animal_sample_type is required when using tube_alias for Animal samples';
        }
        if ($protocolName === '') {
            $issues[] = 'protocol_name is required';
        }
        if ($pathogen === '') {
            $issues[] = 'pathogen is required';
        }
        if (! in_array($outcomeType, $this->allowedOutcomeTypes, true)) {
            $issues[] = 'outcome_type must be one of: '.implode(', ', $this->allowedOutcomeTypes);
        }
        if (! in_array($outcomeDiscrete, $this->allowedDiscreteOutcomes, true)) {
            $issues[] = 'outcome_discrete must be one of: '.implode(', ', $this->allowedDiscreteOutcomes);
        }
        if (! in_array($purpose, $this->allowedPurposes, true)) {
            $issues[] = 'purpose must be one of: '.implode(', ', $this->allowedPurposes);
        }
        if ($outcomeType === 'Qualitative only') {
            $outcomeQuant = '';
        }
        if ($outcomeType === 'Qualitative and quantitative' && $outcomeQuant === '') {
            $issues[] = 'outcome_quant is required when outcome_type is Qualitative and quantitative';
        }
        if ($outcomeType === 'Qualitative and quantitative' && $outcomeQuant !== '' && ! is_numeric($outcomeQuant)) {
            $issues[] = 'outcome_quant must be numeric when outcome_type is Qualitative and quantitative';
        }
        if ($dateTested === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTested)) {
            $issues[] = 'date_tested must be YYYY-MM-DD';
        }
        if ($laboratory === '') {
            $issues[] = 'laboratory is required';
        }
        if ($testedBy === '') {
            $issues[] = 'tested_by is required';
        }
        if ($testedBy !== '' && ! filter_var($testedBy, FILTER_VALIDATE_EMAIL)) {
            $issues[] = 'tested_by must be a valid email address';
        }

        $tubeResolution = $this->resolveTube($projectId, $tubeId, $tubeAlias, $sampleCode, $sampleType, $animalSampleType);
        $tube = $tubeResolution['tube'];
        if (! $tube) {
            $issues[] = $tubeResolution['issue'] ?? 'tube_id/tube_alias/sample_code did not match any tube in current project';
        }

        $protocolExists = Protocols::query()
            ->whereRaw('lower(name) = ?', [strtolower($protocolName)])
            ->exists();
        if (! $protocolExists) {
            if ($techniqueType === '') {
                $issues[] = 'technique_type is required when protocol_name is new';
            }
            if ($techniqueCategory === '') {
                $issues[] = 'technique_category is required when protocol_name is new';
            } elseif (! in_array($techniqueCategory, $this->allowedTechniqueCategories, true)) {
                $issues[] = 'technique_category must be one of: '.implode(', ', $this->allowedTechniqueCategories);
            }

            $similarProtocols = $this->similarOptions(Protocols::query(), 'name', $protocolName);
            if (! empty($similarProtocols)) {
                $fieldWarnings['protocol_name'] = [
                    'text' => '≈ '.implode(' and ', $similarProtocols),
                    'suggested' => $similarProtocols[0],
                    'options' => $similarProtocols,
                ];
                $warnings[] = $fieldWarnings['protocol_name']['text'];
            }
        }

        $pathogenExists = Pathogens::query()
            ->whereRaw('lower(species) = ?', [strtolower($pathogen)])
            ->exists();
        if (! $pathogenExists) {
            $issues[] = "pathogen not found: {$pathogen}";
            $similarPathogens = $this->similarOptions(Pathogens::query(), 'species', $pathogen);
            if (! empty($similarPathogens)) {
                $fieldWarnings['pathogen'] = [
                    'text' => '≈ '.implode(' and ', $similarPathogens),
                    'suggested' => $similarPathogens[0],
                    'options' => $similarPathogens,
                ];
                $warnings[] = $fieldWarnings['pathogen']['text'];
            }
        }

        $laboratoryExists = Laboratories::query()
            ->whereRaw('lower(name) = ?', [strtolower($laboratory)])
            ->exists();
        if (! $laboratoryExists) {
            if ($laboratoryCountry === '') {
                $issues[] = 'laboratory_country is required when laboratory is new';
            }

            $similarLaboratories = $this->similarOptions(Laboratories::query(), 'name', $laboratory);
            if (! empty($similarLaboratories)) {
                $fieldWarnings['laboratory'] = [
                    'text' => '≈ '.implode(' and ', $similarLaboratories),
                    'suggested' => $similarLaboratories[0],
                    'options' => $similarLaboratories,
                ];
                $warnings[] = $fieldWarnings['laboratory']['text'];
            }
        }

        $testedByExists = $this->testerExists($testedBy);
        if (! $testedByExists) {
            if (filter_var($testedBy, FILTER_VALIDATE_EMAIL)) {
                if ($testedByFirstName === '') {
                    $issues[] = 'tested_by_first_name is required when tested_by email is new';
                }
                if ($testedByLastName === '') {
                    $issues[] = 'tested_by_last_name is required when tested_by email is new';
                }
            }

            $similarPeople = $this->similarPeople($testedBy);
            if (! empty($similarPeople)) {
                $fieldWarnings['tested_by'] = [
                    'text' => '≈ '.implode(' and ', $similarPeople),
                    'suggested' => $similarPeople[0],
                    'options' => $similarPeople,
                ];
                $warnings[] = $fieldWarnings['tested_by']['text'];
            }
        }

        return [
            'row_number' => $rowNumber ?? 0,
            'tube_id' => $tubeId,
            'tube_alias' => $tubeAlias,
            'sample_code' => $sampleCode,
            'sample_type' => $sampleType,
            'animal_sample_type' => $animalSampleType,
            'selected_tube_code' => $tube?->code ? (string) $tube->code : '',
            'selected_tube_alias_code' => $tube?->alias_code ? (string) $tube->alias_code : '',
            'selected_sample_code' => ($tube && $tube->tubes_content && isset($tube->tubes_content->code)) ? (string) $tube->tubes_content->code : '',
            'protocol_name' => $protocolName,
            'pathogen' => $pathogen,
            'outcome_type' => $outcomeType,
            'outcome_discrete' => $outcomeDiscrete,
            'outcome_quant' => $outcomeQuant,
            'purpose' => $purpose,
            'date_tested' => $dateTested,
            'laboratory' => $laboratory,
            'tested_by' => $testedBy,
            'tested_by_first_name' => $testedByFirstName,
            'tested_by_last_name' => $testedByLastName,
            'technique_type' => $techniqueType,
            'technique_category' => $techniqueCategory,
            'laboratory_country' => $laboratoryCountry,
            'tube_exists' => $tube !== null,
            'protocol_exists' => $protocolExists,
            'pathogen_exists' => $pathogenExists,
            'laboratory_exists' => $laboratoryExists,
            'tested_by_exists' => $testedByExists,
            'field_warnings' => $fieldWarnings,
            'warnings' => $warnings,
            'issues' => $issues,
            'tube' => $tube,
        ];
    }

    /**
     * @return array{tube:?Tubes, issue:?string}
     */
    private function resolveTube(
        int $projectId,
        string $tubeIdCode,
        string $tubeAliasCode,
        string $sampleCode,
        string $sampleType = '',
        string $animalSampleType = ''
    ): array {
        $normalizedTubeId = strtolower(trim($tubeIdCode));
        $normalizedTubeAlias = strtolower(trim($tubeAliasCode));
        $normalizedSampleCode = strtolower(trim($sampleCode));
        $sampleTypeClass = $this->sampleTypeModelClass($sampleType);

        if ($tubeIdCode !== '') {
            $tube = Tubes::query()
                ->where('projects_id', $projectId)
                ->whereRaw('lower(trim(code)) = ?', [$normalizedTubeId])
                ->when($sampleTypeClass !== null, fn ($query) => $query->where('tubes_content_type', $sampleTypeClass))
                ->with('tubes_content')
                ->first();
            if ($tube) {
                return ['tube' => $tube, 'issue' => null];
            }
        }

        if ($tubeAliasCode !== '') {
            if ($sampleTypeClass === AnimalSamples::class && trim($animalSampleType) === '') {
                return [
                    'tube' => null,
                    'issue' => 'animal_sample_type is required when using tube_alias for Animal samples (alias can repeat across matrices)',
                ];
            }

            $matchingAliasTubesQuery = Tubes::query()
                ->where('projects_id', $projectId)
                ->when($sampleTypeClass !== null, fn ($query) => $query->where('tubes_content_type', $sampleTypeClass))
                ->where(function ($query) use ($tubeAliasCode): void {
                    $query->whereRaw('lower(trim(alias_code)) = ?', [strtolower(trim($tubeAliasCode))])
                        ->orWhereRaw('lower(trim(code)) = ?', [strtolower(trim($tubeAliasCode))]);
                })
                ->with('tubes_content')
                ->latest('id');

            if ($sampleTypeClass === AnimalSamples::class && trim($animalSampleType) !== '') {
                $normalizedMatrix = strtolower(trim($animalSampleType));
                $matchingAliasTubesQuery->whereHasMorph('tubes_content', [AnimalSamples::class], function (Builder $q) use ($normalizedMatrix): void {
                    $q->whereHas('sample_types', function (Builder $q) use ($normalizedMatrix): void {
                        $q->whereRaw('lower(trim(name)) = ?', [$normalizedMatrix]);
                    });
                });
            }

            $matchingAliasTubes = $matchingAliasTubesQuery->get();

            if ($matchingAliasTubes->count() === 1) {
                return ['tube' => $matchingAliasTubes->first(), 'issue' => null];
            }

            if ($matchingAliasTubes->count() > 1 && $sampleTypeClass === null) {
                return [
                    'tube' => null,
                    'issue' => 'tube_alias matched multiple sample types; provide sample_type to disambiguate',
                ];
            }

            if ($matchingAliasTubes->count() > 1) {
                return [
                    'tube' => null,
                    'issue' => 'tube_alias matched multiple tubes even within the selected sample_type',
                ];
            }
        }

        if ($sampleCode === '') {
            return ['tube' => null, 'issue' => null];
        }

        $tube = Tubes::query()
            ->where('projects_id', $projectId)
            ->when($sampleTypeClass !== null, fn ($query) => $query->where('tubes_content_type', $sampleTypeClass))
            ->whereHasMorph('tubes_content', ['*'], function ($q) use ($normalizedSampleCode): void {
                $q->whereRaw('lower(trim(code)) = ?', [$normalizedSampleCode]);
            })
            ->with('tubes_content')
            ->latest('id')
            ->first();

        return ['tube' => $tube, 'issue' => null];
    }

    private function resolveOrCreateProtocol(Projects $project, string $protocolName, string $techniqueType, string $techniqueCategory): ?Protocols
    {
        $existing = Protocols::query()
            ->whereRaw('lower(name) = ?', [strtolower($protocolName)])
            ->first();
        if ($existing) {
            return $existing;
        }

        if ($techniqueType === '' || $techniqueCategory === '') {
            return null;
        }

        $technique = Techniques::query()
            ->whereRaw('lower(name) = ?', [strtolower($techniqueType)])
            ->whereRaw('lower(type) = ?', [strtolower($techniqueCategory)])
            ->first();

        if (! $technique) {
            $technique = Techniques::query()->create([
                'name' => $techniqueType,
                'type' => $techniqueCategory,
            ]);
        }

        $serial = $this->nextProtocolSerialForProject($project->code);

        return Protocols::query()->create([
            'code' => $project->code.'-PR-'.$serial,
            'name' => $protocolName,
            'techniques_id' => $technique->id,
            'users_id' => Auth::id(),
        ]);
    }

    private function resolveOrCreateLaboratory(string $name, string $countryName): ?Laboratories
    {
        $existing = Laboratories::query()
            ->whereRaw('lower(name) = ?', [strtolower($name)])
            ->first();
        if ($existing) {
            return $existing;
        }

        if ($countryName === '') {
            return null;
        }

        $country = Countries::query()
            ->whereRaw('lower(name) = ?', [strtolower($countryName)])
            ->first();
        if (! $country) {
            $country = Countries::query()->create(['name' => $countryName]);
        }

        return Laboratories::query()->create([
            'name' => $name,
            'countries_id' => $country->id,
        ]);
    }

    private function resolveOrCreateTester(string $testedBy, string $firstName = '', string $lastName = ''): ?People
    {
        $testedBy = trim($testedBy);
        if ($testedBy === '') {
            return null;
        }

        if (filter_var($testedBy, FILTER_VALIDATE_EMAIL)) {
            $person = People::query()
                ->whereRaw('lower(email) = ?', [strtolower($testedBy)])
                ->first();

            if ($person) {
                return $person;
            }

            $first = $this->normalizeWordsTitleCase($firstName);
            $last = $this->normalizeWordsTitleCase($lastName);
            if ($first === '' || $last === '') {
                return null;
            }

            return People::query()->create([
                'first_name' => $first,
                'last_name' => $last,
                'email' => strtolower($testedBy),
            ]);
        }

        $parts = preg_split('/\s+/', $testedBy, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($parts) < 2) {
            return null;
        }

        $first = $this->normalizeWordsTitleCase((string) array_shift($parts));
        $last = $this->normalizeWordsTitleCase(implode(' ', $parts));

        $existing = People::query()
            ->whereRaw('lower(first_name) = ?', [strtolower($first)])
            ->whereRaw('lower(last_name) = ?', [strtolower($last)])
            ->first();
        if ($existing) {
            return $existing;
        }

        return People::query()->create([
            'first_name' => $first,
            'last_name' => $last,
        ]);
    }

    private function testerExists(string $testedBy): bool
    {
        if ($testedBy === '') {
            return false;
        }

        if (filter_var($testedBy, FILTER_VALIDATE_EMAIL)) {
            return People::query()->whereRaw('lower(email) = ?', [strtolower($testedBy)])->exists();
        }

        $parts = preg_split('/\s+/', trim($testedBy), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($parts) < 2) {
            return false;
        }

        $first = strtolower((string) array_shift($parts));
        $last = strtolower(implode(' ', $parts));

        return People::query()
            ->whereRaw('lower(first_name) = ?', [$first])
            ->whereRaw('lower(last_name) = ?', [$last])
            ->exists();
    }

    /**
     * @return list<string>
     */
    private function similarPeople(string $testedBy): array
    {
        if ($testedBy === '') {
            return [];
        }

        if (filter_var($testedBy, FILTER_VALIDATE_EMAIL)) {
            return $this->similarOptions($this->projectPeopleEmailsQuery($this->selectedProjectId()), 'email', strtolower($testedBy));
        }

        return $this->similarOptions(People::query()->selectRaw("CONCAT(first_name, ' ', last_name) as full_name"), 'full_name', $testedBy);
    }

    private function normalizeOutcome(string $value): string
    {
        $value = strtolower($this->sanitizeCell($value));
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return match ($value) {
            'negative' => 'Negative',
            'suspect' => 'Suspect',
            'positive' => 'Positive',
            'strong positive', 'strongpositive' => 'Strong positive',
            default => $value,
        };
    }

    private function normalizePurpose(string $value): string
    {
        $value = strtolower($this->sanitizeCell($value));

        return match ($value) {
            'screening', 'screen' => 'screening',
            'confirmation', 'confirm', 'confirmatory' => 'confirmation',
            default => $value,
        };
    }

    private function normalizeTechniqueCategory(string $value): string
    {
        $value = strtolower($this->sanitizeCell($value));
        if ($value === '') {
            return '';
        }

        return match ($value) {
            'nucleic acid detection test' => 'Nucleic acid detection test',
            'antibody detection test' => 'Antibody detection test',
            'microbiology', 'microbiological test' => 'Microbiology',
            'antigen detection test' => 'Antigen detection test',
            default => $this->normalizeWordsTitleCase($value),
        };
    }

    private function normalizeOutcomeType(string $value): string
    {
        $value = strtolower($this->sanitizeCell($value));

        return match ($value) {
            '', 'qualitative only', 'qualitative', 'qualitative-only' => 'Qualitative only',
            'qualitative and quantitative', 'both qualitative and quantitative', 'both', 'qualitative+quantitative' => 'Qualitative and quantitative',
            default => $this->normalizeWordsTitleCase($value),
        };
    }

    private function normalizeOutcomeQuant(string $value): string
    {
        $value = $this->sanitizeCell($value);

        if (in_array(strtolower($value), ['', 'n/a', 'na', 'null', 'none', '-', '--'], true)) {
            return '';
        }

        return $value;
    }

    private function normalizeSampleType(string $value): string
    {
        $value = strtolower($this->sanitizeCell($value));
        if ($value === '') {
            return '';
        }

        return match ($value) {
            'human samples', 'human sample', 'humans', 'human' => 'Human samples',
            'animal samples', 'animal sample', 'animals', 'animal' => 'Animal samples',
            'environmental samples', 'environment sample', 'environment samples', 'environment', 'environmental' => 'Environmental samples',
            'parasite samples', 'parasite sample', 'parasites', 'parasite' => 'Parasite samples',
            'nucleic acids', 'nucleic acid', 'nucleic', 'dna', 'dna samples', 'dna sample' => 'Nucleic acids',
            'cultures', 'culture' => 'Cultures',
            'pools', 'pool' => 'Pools',
            default => $this->normalizeWordsTitleCase($value),
        };
    }

    private function sampleTypeModelClass(string $sampleType): ?string
    {
        return match ($sampleType) {
            'Human samples' => HumanSamples::class,
            'Animal samples' => AnimalSamples::class,
            'Environmental samples' => EnvironmentSamples::class,
            'Parasite samples' => ParasiteSamples::class,
            'Nucleic acids' => NucleicAcids::class,
            'Cultures' => Cultures::class,
            'Pools' => Pools::class,
            default => null,
        };
    }

    /**
     * @param  Builder  $query
     * @return list<string>
     */
    private function similarOptions($query, string $column, string $value, int $threshold = 72, int $limit = 2): array
    {
        $value = trim($value);
        if (mb_strlen($value) < 3) {
            return [];
        }

        $candidates = $query
            ->limit(400)
            ->pluck($column)
            ->filter()
            ->map(fn ($item) => (string) $item)
            ->values()
            ->all();

        $scored = [];
        foreach ($candidates as $candidate) {
            if (mb_strtolower($candidate) === mb_strtolower($value)) {
                continue;
            }
            similar_text(mb_strtolower($candidate), mb_strtolower($value), $pct);
            if ($pct >= $threshold) {
                $scored[] = ['candidate' => $candidate, 'score' => $pct];
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_values(array_unique(array_slice(array_map(fn ($item) => (string) $item['candidate'], $scored), 0, $limit)));
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

    private function normalizeNameStyle(string $value): string
    {
        $value = $this->sanitizeCell($value);
        if ($value === '') {
            return '';
        }

        $lower = mb_strtolower($value, 'UTF-8');
        $first = mb_substr($lower, 0, 1, 'UTF-8');
        $rest = mb_substr($lower, 1, null, 'UTF-8');

        return mb_strtoupper($first, 'UTF-8').$rest;
    }

    private function nextExperimentSerialForProject(int $projectId, string $projectCode): int
    {
        $codes = Experiments::query()
            ->where('projects_id', $projectId)
            ->where('code', 'like', $projectCode.'-EX-%')
            ->pluck('code');

        $used = $codes->map(function ($code) {
            preg_match('/-EX-(\d+)$/', (string) $code, $matches);

            return isset($matches[1]) ? (int) $matches[1] : null;
        })->filter()->sort()->values();

        $serial = 1;
        foreach ($used as $number) {
            if ($number !== $serial) {
                break;
            }
            $serial++;
        }

        return $serial;
    }

    /**
     * @param  array<string, bool>  $reservedCodes
     */
    private function reserveNextExperimentCode(string $projectCode, int &$nextSerial, array &$reservedCodes): string
    {
        while (true) {
            $candidate = $projectCode.'-EX-'.$nextSerial;
            $nextSerial++;

            if (isset($reservedCodes[$candidate])) {
                continue;
            }

            $reservedCodes[$candidate] = true;

            return $candidate;
        }
    }

    private function nextProtocolSerialForProject(string $projectCode): int
    {
        $codes = Protocols::query()
            ->where('code', 'like', $projectCode.'-PR-%')
            ->pluck('code');

        $used = $codes->map(function ($code) {
            preg_match('/-PR-(\d+)$/', (string) $code, $matches);

            return isset($matches[1]) ? (int) $matches[1] : null;
        })->filter()->sort()->values();

        $serial = 1;
        foreach ($used as $number) {
            if ($number !== $serial) {
                break;
            }
            $serial++;
        }

        return $serial;
    }

    /**
     * @return list<string>
     */
    private function requiredFields(): array
    {
        return [
            'sample_type',
            'protocol_name',
            'pathogen',
            'outcome_discrete',
            'purpose',
            'date_tested',
            'laboratory',
            'tested_by',
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private function synonymsByField(): array
    {
        return [
            'tube_id' => ['tube_id', 'tube code', 'tube', 'tubeid'],
            'tube_alias' => ['tube_alias', 'tube alias', 'tube_alias_code', 'tube alias code', 'alias_code', 'alias'],
            'sample_code' => ['sample_code', 'sample code', 'origin_sample_code', 'origin sample code'],
            'sample_type' => ['sample_type', 'sample type'],
            'animal_sample_type' => ['animal_sample_type', 'animal sample type', 'animal_matrix', 'animal matrix', 'animal_biological_matrix', 'animal biological matrix', 'animal_sample_matrix', 'animal sample matrix', 'matrix'],
            'protocol_name' => ['protocol_name', 'protocol', 'protocol name'],
            'technique_type' => ['technique_type', 'technique type'],
            'technique_category' => ['technique_category', 'technique category'],
            'pathogen' => ['pathogen', 'pathogen_name', 'pathogen species'],
            'outcome_type' => ['outcome_type', 'outcome type'],
            'outcome_discrete' => ['outcome_discrete', 'outcome discrete', 'outcome_qual', 'qualitative_outcome'],
            'outcome_quant' => ['outcome_quant', 'outcome quant', 'quantitative_outcome'],
            'purpose' => ['purpose', 'test_purpose', 'test purpose', 'experiment_purpose'],
            'date_tested' => ['date_tested', 'date tested', 'test_date', 'experiment_date'],
            'laboratory' => ['laboratory', 'lab', 'laboratory_name', 'lab_name'],
            'laboratory_country' => ['laboratory_country', 'lab_country', 'laboratory country'],
            'tested_by' => ['tested_by', 'performed_by', 'scientist', 'collector', 'tested by'],
            'tested_by_first_name' => ['tested_by_first_name', 'tested by first name', 'tester_first_name', 'first_name'],
            'tested_by_last_name' => ['tested_by_last_name', 'tested by last name', 'tester_last_name', 'last_name'],
        ];
    }

    /**
     * @return array{rows: list<array<string,mixed>>, total: int, from: int, to: int, current_page: int, last_page: int, start_page: int, end_page: int}
     */
    public function previewPage(): array
    {
        if (! $this->cacheKey) {
            return ['rows' => [], 'total' => 0, 'from' => 0, 'to' => 0, 'current_page' => 1, 'last_page' => 1, 'start_page' => 1, 'end_page' => 1];
        }

        $rows = Cache::get($this->cacheKey);
        if (! is_array($rows)) {
            return ['rows' => [], 'total' => 0, 'from' => 0, 'to' => 0, 'current_page' => 1, 'last_page' => 1, 'start_page' => 1, 'end_page' => 1];
        }

        $total = count($rows);
        $perPage = max(10, min(200, (int) $this->perPage));
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) $this->page), $lastPage);
        $this->page = $page;
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
            'current_page' => $page,
            'last_page' => $lastPage,
            'start_page' => max(1, $page - 2),
            'end_page' => min($lastPage, $page + 2),
        ];
    }

    public function render()
    {
        return view('livewire.imports.experiments-import', [
            'allowedTechniqueCategories' => $this->allowedTechniqueCategories,
            'allowedDiscreteOutcomes' => $this->allowedDiscreteOutcomes,
            'allowedOutcomeTypes' => $this->allowedOutcomeTypes,
            'previewPageData' => $this->previewPage(),
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
            'imports:experiments:template_options:v2:'.($projectId ?? 0),
            now()->addMinutes(30),
            function () use ($projectId): array {
                return [
                    'tube_code' => $this->optionPreviewTubes($projectId, 'code'),
                    'tube_alias' => $this->optionPreviewTubes($projectId, 'alias_code'),
                    'animal_sample_types' => $this->optionPreview(SampleTypes::query()->orderBy('name'), 'name'),
                    'protocols' => $this->optionPreview(Protocols::query()->orderBy('name'), 'name'),
                    'pathogens' => $this->optionPreview(Pathogens::query()->orderBy('species'), 'species'),
                    'laboratories' => $this->optionPreview(Laboratories::query()->orderBy('name'), 'name'),
                    'countries' => $this->optionPreview(Countries::query()->orderBy('name'), 'name'),
                    'people_emails' => $this->optionPreview(
                        $this->projectPeopleEmailsQuery($projectId)->orderBy('email'),
                        'email'
                    ),
                    'technique_types' => $this->optionPreview(Techniques::query()->orderBy('name'), 'name'),
                ];
            }
        );

        $aliases = $this->synonymsByField();

        $columns = [
            [
                'header' => 'tube_id',
                'field' => 'tube_id',
                'required' => 'conditional',
                'description' => 'Tube code to link the experiment to an existing tube in the selected project. Provide tube_id OR tube_alias OR sample_code.',
                'format' => 'Text (exact tube code).',
                'accepted' => [],
                'aliases' => $aliases['tube_id'] ?? [],
                'create_policy' => 'Links to existing Tubes by exact match. Cannot create tubes here.',
                'create_notes' => 'If tube_alias is ambiguous, set sample_type to disambiguate.',
                'example' => 'A1A1-AS-394-2',
                'options' => $options['tube_code']['values'],
                'options_total' => $options['tube_code']['total'],
            ],
            [
                'header' => 'tube_alias',
                'field' => 'tube_alias',
                'required' => 'conditional',
                'description' => 'Tube alias code. Provide tube_id OR tube_alias OR sample_code.',
                'format' => 'Text (exact alias code).',
                'accepted' => [],
                'aliases' => $aliases['tube_alias'] ?? [],
                'create_policy' => 'Links to existing Tubes by alias or code match. Cannot create tubes here.',
                'create_notes' => 'If tube_alias matches multiple tubes, you must provide sample_type.',
                'example' => '',
                'options' => $options['tube_alias']['values'],
                'options_total' => $options['tube_alias']['total'],
            ],
            [
                'header' => 'sample_code',
                'field' => 'sample_code',
                'required' => 'conditional',
                'description' => 'Origin sample code (linked through the tube content). Provide tube_id OR tube_alias OR sample_code.',
                'format' => 'Text (exact sample code).',
                'accepted' => [],
                'aliases' => $aliases['sample_code'] ?? [],
                'create_policy' => 'Links to an existing tube by matching the origin sample code within the selected project. Cannot create samples here.',
                'create_notes' => 'If multiple tubes share the same sample code, the importer uses the most recently created tube (and sample_type helps).',
                'example' => '',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'sample_type',
                'field' => 'sample_type',
                'required' => 'required',
                'description' => 'Type of the sample stored in the tube (used to match the correct polymorphic content).',
                'format' => 'One of the accepted values.',
                'accepted' => $this->allowedSampleTypes,
                'aliases' => $aliases['sample_type'] ?? [],
                'create_policy' => 'Must match accepted values.',
                'create_notes' => 'Required especially when using tube_alias or sample_code, to disambiguate tube content type.',
                'example' => 'Nucleic acids',
                'options' => array_slice($this->allowedSampleTypes, 0, 10),
                'options_total' => count($this->allowedSampleTypes),
            ],
            [
                'header' => 'animal_sample_type',
                'field' => 'animal_sample_type',
                'required' => 'conditional',
                'description' => 'Biological matrix (sample type) for animal samples (e.g. Serum, Blood clot). Used to disambiguate tube_alias collisions within Animal samples.',
                'format' => 'Text (must match an existing sample type name).',
                'accepted' => [],
                'aliases' => $aliases['animal_sample_type'] ?? [],
                'create_policy' => 'Required only when using tube_alias and sample_type is Animal samples. Must match an existing SampleTypes.name record.',
                'create_notes' => '',
                'example' => 'Serum',
                'options' => $options['animal_sample_types']['values'],
                'options_total' => $options['animal_sample_types']['total'],
            ],
            [
                'header' => 'protocol_name',
                'field' => 'protocol_name',
                'required' => 'required',
                'description' => 'Protocol name used for the experiment.',
                'format' => 'Text (case-insensitive match).',
                'accepted' => [],
                'aliases' => $aliases['protocol_name'] ?? [],
                'create_policy' => 'Links to existing Protocols by exact name match; otherwise creates a new protocol.',
                'create_notes' => 'If new, technique_type and technique_category are required.',
                'example' => 'qPCR Brucella',
                'options' => $options['protocols']['values'],
                'options_total' => $options['protocols']['total'],
            ],
            [
                'header' => 'technique_type',
                'field' => 'technique_type',
                'required' => 'conditional',
                'description' => 'Technique name for a new protocol.',
                'format' => 'Text (e.g. qPCR, ELISA).',
                'accepted' => [],
                'aliases' => $aliases['technique_type'] ?? [],
                'create_policy' => 'Required only when protocol_name is new. Creates Techniques if missing.',
                'create_notes' => '',
                'example' => 'qPCR',
                'options' => $options['technique_types']['values'],
                'options_total' => $options['technique_types']['total'],
            ],
            [
                'header' => 'technique_category',
                'field' => 'technique_category',
                'required' => 'conditional',
                'description' => 'Technique category for a new protocol.',
                'format' => 'One of the accepted values.',
                'accepted' => $this->allowedTechniqueCategories,
                'aliases' => $aliases['technique_category'] ?? [],
                'create_policy' => 'Required only when protocol_name is new. Must match accepted values.',
                'create_notes' => '',
                'example' => 'Nucleic acid detection test',
                'options' => array_slice($this->allowedTechniqueCategories, 0, 10),
                'options_total' => count($this->allowedTechniqueCategories),
            ],
            [
                'header' => 'pathogen',
                'field' => 'pathogen',
                'required' => 'required',
                'description' => 'Pathogen species tested in the experiment.',
                'format' => 'Text (must match an existing pathogen species).',
                'accepted' => [],
                'aliases' => $aliases['pathogen'] ?? [],
                'create_policy' => 'Must match an existing Pathogens.species record. This import does not create pathogens.',
                'create_notes' => 'If missing, register the pathogen first (then re-import).',
                'example' => 'Brucella abortus',
                'options' => $options['pathogens']['values'],
                'options_total' => $options['pathogens']['total'],
            ],
            [
                'header' => 'outcome_type',
                'field' => 'outcome_type',
                'required' => 'optional',
                'description' => 'Whether the experiment outcome includes quantitative values.',
                'format' => 'One of the accepted values (blank defaults to Qualitative only).',
                'accepted' => $this->allowedOutcomeTypes,
                'aliases' => $aliases['outcome_type'] ?? [],
                'create_policy' => 'Optional. If blank, defaults to Qualitative only.',
                'create_notes' => '',
                'example' => 'Qualitative only',
                'options' => array_slice($this->allowedOutcomeTypes, 0, 10),
                'options_total' => count($this->allowedOutcomeTypes),
            ],
            [
                'header' => 'outcome_discrete',
                'field' => 'outcome_discrete',
                'required' => 'required',
                'description' => 'Discrete (qualitative) outcome.',
                'format' => 'One of the accepted values.',
                'accepted' => $this->allowedDiscreteOutcomes,
                'aliases' => $aliases['outcome_discrete'] ?? [],
                'create_policy' => 'Must match accepted values.',
                'create_notes' => '',
                'example' => 'Positive',
                'options' => array_slice($this->allowedDiscreteOutcomes, 0, 10),
                'options_total' => count($this->allowedDiscreteOutcomes),
            ],
            [
                'header' => 'outcome_quant',
                'field' => 'outcome_quant',
                'required' => 'conditional',
                'description' => 'Quantitative outcome value (if applicable).',
                'format' => 'Numeric (required only when outcome_type is Qualitative and quantitative).',
                'accepted' => [],
                'aliases' => $aliases['outcome_quant'] ?? [],
                'create_policy' => 'Required only when outcome_type is Qualitative and quantitative. Must be numeric.',
                'create_notes' => '',
                'example' => '',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'purpose',
                'field' => 'purpose',
                'required' => 'required',
                'description' => 'Whether the test is used for screening or for confirmation. Important for prevalence reporting in the dashboard.',
                'format' => 'One of the accepted values.',
                'accepted' => $this->allowedPurposes,
                'aliases' => $aliases['purpose'] ?? [],
                'create_policy' => 'Must match accepted values.',
                'create_notes' => '',
                'example' => 'screening',
                'options' => $this->allowedPurposes,
                'options_total' => count($this->allowedPurposes),
            ],
            [
                'header' => 'date_tested',
                'field' => 'date_tested',
                'required' => 'required',
                'description' => 'Date the test was performed.',
                'format' => 'YYYY-MM-DD.',
                'accepted' => [],
                'aliases' => $aliases['date_tested'] ?? [],
                'create_policy' => 'Must be a valid date string in YYYY-MM-DD format.',
                'create_notes' => '',
                'example' => now()->toDateString(),
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'laboratory',
                'field' => 'laboratory',
                'required' => 'required',
                'description' => 'Laboratory where the test was performed.',
                'format' => 'Text (case-insensitive match).',
                'accepted' => [],
                'aliases' => $aliases['laboratory'] ?? [],
                'create_policy' => 'Links to existing Laboratories by exact name match; otherwise creates a new laboratory.',
                'create_notes' => 'If new, laboratory_country is required.',
                'example' => 'Central Lab',
                'options' => $options['laboratories']['values'],
                'options_total' => $options['laboratories']['total'],
            ],
            [
                'header' => 'laboratory_country',
                'field' => 'laboratory_country',
                'required' => 'conditional',
                'description' => 'Country for a newly created laboratory.',
                'format' => 'Text (country name).',
                'accepted' => [],
                'aliases' => $aliases['laboratory_country'] ?? [],
                'create_policy' => 'Required only when laboratory is new. Links to existing Countries by name; otherwise creates the country record.',
                'create_notes' => '',
                'example' => 'South Africa',
                'options' => $options['countries']['values'],
                'options_total' => $options['countries']['total'],
            ],
            [
                'header' => 'tested_by',
                'field' => 'tested_by',
                'required' => 'required',
                'description' => 'Email address of the person who performed the test.',
                'format' => 'Email address.',
                'accepted' => [],
                'aliases' => $aliases['tested_by'] ?? [],
                'create_policy' => 'Links to an existing People record by email; otherwise creates a new person when first/last name are provided.',
                'create_notes' => 'If the email is new, tested_by_first_name and tested_by_last_name are required.',
                'example' => 'maria@example.org',
                'options' => $options['people_emails']['values'],
                'options_total' => $options['people_emails']['total'],
            ],
            [
                'header' => 'tested_by_first_name',
                'field' => 'tested_by_first_name',
                'required' => 'conditional',
                'description' => 'First name of the tester (only needed when tested_by email is new).',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['tested_by_first_name'] ?? [],
                'create_policy' => 'Required only when tested_by email is new.',
                'create_notes' => '',
                'example' => 'Maria',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'tested_by_last_name',
                'field' => 'tested_by_last_name',
                'required' => 'conditional',
                'description' => 'Last name of the tester (only needed when tested_by email is new).',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['tested_by_last_name'] ?? [],
                'create_policy' => 'Required only when tested_by email is new.',
                'create_notes' => '',
                'example' => 'Cristina',
                'options' => [],
                'options_total' => 0,
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
    private function optionPreviewTubes(?int $projectId, string $column, int $limit = 10): array
    {
        if (! $projectId) {
            return ['values' => [], 'total' => 0];
        }

        $query = Tubes::query()
            ->where('projects_id', $projectId)
            ->whereNotNull($column)
            ->where($column, '!=', '');

        $total = (int) (clone $query)->distinct()->count($column);
        $values = (clone $query)
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
     * @return array{title:string, values:list<string>, total:int, truncated:bool}
     */
    private function templateOptionsForField(string $field, ?int $projectId): array
    {
        $field = trim((string) $field);

        if ($field === 'sample_type') {
            return [
                'title' => 'Sample type',
                'values' => $this->allowedSampleTypes,
                'total' => count($this->allowedSampleTypes),
                'truncated' => false,
            ];
        }

        if ($field === 'outcome_type') {
            return [
                'title' => 'Outcome type',
                'values' => $this->allowedOutcomeTypes,
                'total' => count($this->allowedOutcomeTypes),
                'truncated' => false,
            ];
        }

        if ($field === 'outcome_discrete') {
            return [
                'title' => 'Discrete outcomes',
                'values' => $this->allowedDiscreteOutcomes,
                'total' => count($this->allowedDiscreteOutcomes),
                'truncated' => false,
            ];
        }

        if ($field === 'technique_category') {
            return [
                'title' => 'Technique categories',
                'values' => $this->allowedTechniqueCategories,
                'total' => count($this->allowedTechniqueCategories),
                'truncated' => false,
            ];
        }

        if ($field === 'purpose') {
            return [
                'title' => 'Test purpose',
                'values' => $this->allowedPurposes,
                'total' => count($this->allowedPurposes),
                'truncated' => false,
            ];
        }

        $max = 1200;
        $preview = 1200;

        $result = match ($field) {
            'tube_id' => $this->templateOptionsFromQuery(
                $projectId ? Tubes::query()->where('projects_id', $projectId) : Tubes::query()->whereRaw('1 = 0'),
                'code',
                'Tube IDs (codes)',
                $max
            ),
            'tube_alias' => $this->templateOptionsFromQuery(
                $projectId ? Tubes::query()->where('projects_id', $projectId) : Tubes::query()->whereRaw('1 = 0'),
                'alias_code',
                'Tube aliases',
                $max
            ),
            'animal_sample_type' => $this->templateOptionsFromQuery(SampleTypes::query(), 'name', 'Animal sample matrices (sample types)', $max),
            'protocol_name' => $this->templateOptionsFromQuery(Protocols::query(), 'name', 'Protocols', $max),
            'pathogen' => $this->templateOptionsFromQuery(Pathogens::query(), 'species', 'Pathogens', $max),
            'laboratory' => $this->templateOptionsFromQuery(Laboratories::query(), 'name', 'Laboratories', $max),
            'laboratory_country' => $this->templateOptionsFromQuery(Countries::query(), 'name', 'Countries', $max),
            'tested_by' => $this->templateOptionsFromQuery(
                $this->projectPeopleEmailsQuery($projectId),
                'email',
                'Tester emails',
                $max
            ),
            'technique_type' => $this->templateOptionsFromQuery(Techniques::query(), 'name', 'Technique types', $max),
            default => [
                'title' => 'Values',
                'values' => [],
                'total' => 0,
                'truncated' => false,
            ],
        };

        return $result;
    }

    private function projectPeopleEmailsQuery(?int $projectId): Builder
    {
        if (! $projectId) {
            return People::query()->whereRaw('1 = 0');
        }

        return People::query()
            ->whereIn('id', function ($query) use ($projectId): void {
                $query->from('projects_people')
                    ->select('people_id')
                    ->where('projects_id', $projectId);
            })
            ->whereNotNull('email')
            ->where('email', '!=', '');
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
