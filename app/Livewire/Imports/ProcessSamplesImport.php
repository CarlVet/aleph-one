<?php

namespace App\Livewire\Imports;

use App\Http\Controllers\NotificationController;
use App\Livewire\PlainComponent;
use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use App\Models\Projects;
use App\Models\SampleTypes;
use App\Models\SubProject;
use App\Models\Tubes;
use App\Services\Imports\ColumnMatcher;
use App\Services\Imports\DelimitedTableReader;
use App\Support\SubProjectFlag;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;

class ProcessSamplesImport extends PlainComponent
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

        $exampleRows = [
            array_values(array_map(
                static fn (array $col): string => (string) ($col['example'] ?? ''),
                $columns
            )),
        ];

        return response()->streamDownload(function () use ($headers, $exampleRows): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            fputcsv($output, $headers);
            foreach ($exampleRows as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
        }, 'process_samples_import_template.csv', [
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
            $this->globalIssues[] = 'You do not have permission to import processed tubes in this project (viewer accounts are read-only).';
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

        $this->cacheKey = "imports:process_samples:{$projectId}:".bin2hex(random_bytes(8));
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

    public function import(): void
    {
        $this->globalIssues = [];
        $this->globalWarnings = [];

        $projectId = $this->selectedProjectId();
        if ($projectId === null) {
            $this->globalIssues[] = 'Select a project before importing.';
            $this->status = 'error';

            return;
        }

        $rows = $this->cachedRows();
        if (empty($rows)) {
            $this->globalIssues[] = 'Preview data expired or missing. Upload the CSV again.';
            $this->status = 'error';

            return;
        }

        $resolvedRows = [];
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $resolved = $this->resolveRow($row, $projectId, $rowNumber);
            if (! empty($resolved['issues'])) {
                foreach ($resolved['issues'] as $issue) {
                    $this->globalIssues[] = "Row {$rowNumber}: {$issue}";
                }
            } else {
                $resolvedRows[] = $resolved;
            }
        }

        if (! empty($this->globalIssues)) {
            $this->status = 'error';
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Import failed',
                'text' => 'Bulk import failed. No data was registered.',
            ]);

            return;
        }

        DB::transaction(function () use ($resolvedRows, $projectId): void {
            foreach ($resolvedRows as $resolved) {
                $sample = $resolved['sample'];
                $contentType = $resolved['content_type'];
                $aliasCode = $resolved['alias_code'] !== '' ? $resolved['alias_code'] : null;

                $baseTubeCode = $sample->code.'-'.($this->nextTubeSequenceForSample((int) $sample->id, $contentType));
                $tubeCode = $baseTubeCode;
                $collision = 1;
                while (Tubes::query()->where('code', $tubeCode)->exists()) {
                    $tubeCode = $baseTubeCode.'-'.$collision;
                    $collision++;
                }

                $tube = Tubes::query()->create([
                    'code' => $tubeCode,
                    'alias_code' => $aliasCode,
                    'tubes_content_id' => (int) $sample->id,
                    'tubes_content_type' => $contentType,
                    'tube_type' => $resolved['tube_type'],
                    'preservant' => $resolved['preservant'] !== '' ? $resolved['preservant'] : null,
                    'purpose' => $resolved['purpose'],
                    'amount' => $resolved['amount'] !== '' ? (float) $resolved['amount'] : null,
                    'amount_unit' => $resolved['amount_unit'] !== '' ? $resolved['amount_unit'] : null,
                    'date_processed' => $resolved['date_processed'],
                    'projects_id' => $projectId,
                ]);

                SubProjectFlag::assign($tube, $resolved['sub_project_id']);

                if (in_array($resolved['model_key'], ['human', 'animal', 'environment'], true)) {
                    $sample->processed = true;
                    $sample->save();
                }
            }
        });

        $this->status = 'imported';
        $successMessage = 'Import completed successfully.';
        $this->globalWarnings[] = $successMessage;
        session()->flash('success', $successMessage);
        NotificationController::create(
            'field_sample_processed',
            'Bulk processed tubes imported',
            $successMessage,
            '/bank/tubes/list',
            $projectId
        );
        $this->dispatch('notification-created');
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Success',
            'text' => $successMessage,
        ]);
    }

    public function previewPage(): array
    {
        $rows = $this->cachedRows();
        $total = count($rows);
        if ($total === 0) {
            return ['rows' => [], 'total' => 0, 'from' => 0, 'to' => 0];
        }

        $page = max(1, (int) $this->page);
        $perPage = max(10, min(200, (int) $this->perPage));
        $offset = ($page - 1) * $perPage;
        $slice = array_slice($rows, $offset, $perPage);

        $projectId = (int) ($this->selectedProjectId() ?? 0);
        $mapped = [];
        foreach ($slice as $i => $row) {
            $rowNumber = $offset + $i + 2;
            $mapped[] = $this->resolveRow((array) $row, $projectId, $rowNumber);
        }

        return [
            'rows' => $mapped,
            'total' => $total,
            'from' => $total > 0 ? ($offset + 1) : 0,
            'to' => min($total, $offset + $perPage),
        ];
    }

    private function cachedRows(): array
    {
        if (! $this->cacheKey) {
            return [];
        }

        $rows = Cache::get($this->cacheKey);

        return is_array($rows) ? $rows : [];
    }

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

        $aliasCode = $get('alias_code');
        $fieldLabel = $get('field_label');
        $modelRaw = $get('model');
        $sampleType = $this->normalizeWordsTitleCase($get('sample_type'));
        $purpose = $this->sanitizeCell($get('purpose'));
        $tubeType = $this->sanitizeCell($get('tube_type'));
        $preservant = $this->normalizeWordsTitleCase($get('preservant'));
        $amount = $get('amount');
        $amountUnit = $this->sanitizeCell($get('amount_unit'));
        $dateProcessed = $get('date_processed');
        $subProjectCode = strtoupper($this->sanitizeCell($get('sub_project_code')));

        $issues = [];
        $warnings = [];
        $fieldWarnings = [];

        $modelKey = $this->normalizeModelKey($modelRaw);
        if ($fieldLabel === '') {
            $issues[] = 'field_label is required';
        }
        if ($modelRaw === '') {
            $issues[] = 'model is required';
        } elseif ($modelKey === null) {
            $issues[] = "unsupported model '{$modelRaw}'";
        }
        if ($purpose === '') {
            $issues[] = 'purpose is required';
        }
        if ($tubeType === '') {
            $issues[] = 'tube_type is required';
        }
        if ($dateProcessed === '') {
            $issues[] = 'date_processed is required';
        } elseif (! $this->isValidDate($dateProcessed)) {
            $issues[] = 'date_processed must be YYYY-MM-DD';
        }
        if ($amount !== '' && ! is_numeric($amount)) {
            $issues[] = "amount must be numeric (got '{$amount}')";
        }
        if (in_array($modelKey, ['animal', 'human'], true) && $sampleType === '') {
            $issues[] = 'sample_type is required when model is animal_sample or human_sample';
        }

        $sample = null;
        $contentType = null;
        if ($modelKey !== null && $fieldLabel !== '') {
            [$sample, $contentType] = $this->resolveSampleByFieldLabel(
                $projectId,
                $modelKey,
                $fieldLabel,
                $sampleType
            );

            if (! $sample) {
                $issues[] = 'no matching sample found for field_label/model/sample_type';
            }
        }

        $subProjectId = null;
        $subProjectExists = false;
        if ($subProjectCode !== '') {
            $subProject = SubProjectFlag::optionsForUser(Auth::user(), $projectId)
                ->first(fn (SubProject $item) => strtoupper((string) $item->code) === $subProjectCode);

            if (! $subProject) {
                $issues[] = "sub_project_code '{$subProjectCode}' not found or not allowed";
            } else {
                $subProjectId = (int) $subProject->id;
                $subProjectExists = true;
            }
        }

        $preservantExists = $preservant !== '' && Tubes::query()
            ->whereRaw('lower(preservant) = ?', [strtolower($preservant)])
            ->exists();

        return [
            'row_number' => $rowNumber ?? 0,
            'alias_code' => $aliasCode,
            'field_label' => $fieldLabel,
            'model' => $modelRaw,
            'model_key' => $modelKey ?? '',
            'sample_type' => $sampleType,
            'purpose' => $purpose,
            'tube_type' => $tubeType,
            'preservant' => $preservant,
            'amount' => $amount,
            'amount_unit' => $amountUnit,
            'date_processed' => $dateProcessed,
            'sub_project_code' => $subProjectCode,
            'sub_project_id' => $subProjectId,
            'sub_project_exists' => $subProjectExists,
            'preservant_exists' => $preservantExists,
            'sample_found' => $sample !== null,
            'sample_code' => $sample ? (string) $sample->code : '',
            'sample' => $sample,
            'content_type' => $contentType,
            'field_warnings' => $fieldWarnings,
            'warnings' => $warnings,
            'issues' => $issues,
        ];
    }

    private function resolveSampleByFieldLabel(int $projectId, string $modelKey, string $fieldLabel, string $sampleType): array
    {
        if ($modelKey === 'animal') {
            $query = AnimalSamples::query()
                ->where('projects_id', $projectId)
                ->whereHas('animals', function ($q) use ($fieldLabel): void {
                    $q->whereRaw('lower(field_label) = ?', [strtolower($fieldLabel)]);
                });
            if ($sampleType !== '') {
                $query->whereHas('sample_types', function ($q) use ($sampleType): void {
                    $q->whereRaw('lower(name) = ?', [strtolower($sampleType)]);
                });
            }

            return [$query->first(), AnimalSamples::class];
        }

        if ($modelKey === 'human') {
            $query = HumanSamples::query()
                ->where('projects_id', $projectId)
                ->where(function ($q) use ($fieldLabel): void {
                    $q->whereRaw('lower(code) = ?', [strtolower($fieldLabel)])
                        ->orWhereHas('humans', function ($inner) use ($fieldLabel): void {
                            $inner->whereRaw('lower(code) = ?', [strtolower($fieldLabel)]);
                        });
                });
            if ($sampleType !== '') {
                $query->whereHas('sample_types', function ($q) use ($sampleType): void {
                    $q->whereRaw('lower(name) = ?', [strtolower($sampleType)]);
                });
            }

            return [$query->first(), HumanSamples::class];
        }

        if ($modelKey === 'environment') {
            $sample = EnvironmentSamples::query()
                ->where('projects_id', $projectId)
                ->whereRaw('lower(code) = ?', [strtolower($fieldLabel)])
                ->first();

            return [$sample, EnvironmentSamples::class];
        }

        if ($modelKey === 'parasite') {
            $sample = ParasiteSamples::query()
                ->where('projects_id', $projectId)
                ->whereRaw('lower(code) = ?', [strtolower($fieldLabel)])
                ->first();

            return [$sample, ParasiteSamples::class];
        }

        if ($modelKey === 'nucleic') {
            $sample = NucleicAcids::query()
                ->where('projects_id', $projectId)
                ->whereRaw('lower(code) = ?', [strtolower($fieldLabel)])
                ->first();

            return [$sample, NucleicAcids::class];
        }

        if ($modelKey === 'culture') {
            $sample = Cultures::query()
                ->where('projects_id', $projectId)
                ->whereRaw('lower(code) = ?', [strtolower($fieldLabel)])
                ->first();

            return [$sample, Cultures::class];
        }

        if ($modelKey === 'pool') {
            $sample = Pools::query()
                ->where('projects_id', $projectId)
                ->whereRaw('lower(code) = ?', [strtolower($fieldLabel)])
                ->first();

            return [$sample, Pools::class];
        }

        return [null, null];
    }

    private function nextTubeSequenceForSample(int $sampleId, string $contentType): int
    {
        $codes = Tubes::query()
            ->where('tubes_content_id', $sampleId)
            ->where('tubes_content_type', $contentType)
            ->pluck('code')
            ->all();

        $max = 0;
        foreach ($codes as $code) {
            if (preg_match('/-(\d+)$/', (string) $code, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return $max + 1;
    }

    private function isValidDate(string $value): bool
    {
        try {
            $date = Carbon::createFromFormat('Y-m-d', $value);

            return $date !== false && $date->format('Y-m-d') === $value;
        } catch (\Throwable) {
            return false;
        }
    }

    private function normalizeModelKey(string $value): ?string
    {
        $token = strtolower($this->sanitizeCell($value));
        $token = preg_replace('/[^a-z]/', '', $token) ?? $token;

        return match ($token) {
            'animalsample', 'animalsamples', 'animal' => 'animal',
            'humansample', 'humansamples', 'human' => 'human',
            'environmentsample', 'environmentsamples', 'environment' => 'environment',
            'parasitesample', 'parasitesamples', 'parasite' => 'parasite',
            'nucleicacid', 'nucleicacids', 'nucleic' => 'nucleic',
            'culture', 'cultures' => 'culture',
            'pool', 'pools' => 'pool',
            default => null,
        };
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

    private function requiredFields(): array
    {
        return [
            'field_label',
            'model',
            'purpose',
            'tube_type',
            'date_processed',
        ];
    }

    private function synonymsByField(): array
    {
        return [
            'alias_code' => ['alias_code', 'alias code', 'tube_alias', 'legacy_code'],
            'field_label' => ['field_label', 'field label', 'sample_code', 'sample code', 'code'],
            'model' => ['model', 'sample_model', 'sample model', 'content_model'],
            'sample_type' => ['sample_type', 'sample type', 'type'],
            'purpose' => ['purpose', 'tube_purpose', 'process_purpose'],
            'tube_type' => ['tube_type', 'tube type'],
            'preservant' => ['preservant', 'preservative', 'storage_state'],
            'amount' => ['amount', 'quantity'],
            'amount_unit' => ['unit', 'amount_unit', 'amount unit'],
            'date_processed' => ['date_processed', 'date processed', 'processing_date', 'date'],
            'sub_project_code' => ['sub_project_code', 'sub project code', 'subproject_code', 'subproject code'],
        ];
    }

    public function render()
    {
        $projectId = (int) ($this->selectedProjectId() ?? 0);
        $projectCode = $projectId > 0
            ? (string) (Projects::query()->where('id', $projectId)->value('code') ?? '')
            : '';

        return view('livewire.imports.process-samples-import', [
            'projectCode' => $projectCode,
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
            'imports:process_samples:template_options:'.($projectId ?? 0),
            now()->addMinutes(15),
            function () use ($projectId): array {
                $subProjects = [];
                $subProjectsTotal = 0;

                if ($projectId !== null) {
                    $allowed = SubProjectFlag::optionsForUser(Auth::user(), (int) $projectId);
                    $subProjects = collect($allowed)
                        ->map(fn (SubProject $sp): string => (string) $sp->code)
                        ->filter(fn (string $code): bool => $code !== '')
                        ->values()
                        ->all();
                    $subProjectsTotal = count($subProjects);
                    $subProjects = array_slice($subProjects, 0, 10);
                }

                return [
                    'sample_types' => $this->optionPreview(SampleTypes::query()->orderBy('name'), 'name'),
                    'preservants' => $this->optionPreviewDistinct(Tubes::query()->whereNotNull('preservant'), 'preservant'),
                    'tube_types' => $this->optionPreviewDistinct(Tubes::query()->whereNotNull('tube_type'), 'tube_type'),
                    'purposes' => $this->optionPreviewDistinct(Tubes::query()->whereNotNull('purpose'), 'purpose'),
                    'sub_projects' => ['values' => $subProjects, 'total' => $subProjectsTotal],
                ];
            }
        );

        $aliases = $this->synonymsByField();

        $modelValues = [
            'animal_sample',
            'human_sample',
            'environment_sample',
            'parasite_sample',
            'nucleic_acid',
            'culture',
            'pool',
        ];

        $columns = [
            [
                'header' => 'field_label',
                'field' => 'field_label',
                'required' => 'required',
                'description' => 'Identifier of the source record (depends on model).',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['field_label'] ?? [],
                'create_policy' => 'Must match an existing sample in the selected project (based on model). Cannot create samples here.',
                'create_notes' => 'For animal_sample this matches Animals.field_label; for others it typically matches the sample code.',
                'example' => 'Lioness-07',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'model',
                'field' => 'model',
                'required' => 'required',
                'description' => 'Which entity the processed tube belongs to.',
                'format' => 'One of the accepted values.',
                'accepted' => $modelValues,
                'aliases' => $aliases['model'] ?? [],
                'create_policy' => 'Must match accepted values.',
                'create_notes' => '',
                'example' => 'animal_sample',
                'options' => $modelValues,
                'options_total' => count($modelValues),
            ],
            [
                'header' => 'sample_type',
                'field' => 'sample_type',
                'required' => 'conditional',
                'description' => 'Sample type is required only when model is animal_sample or human_sample.',
                'format' => 'Text (case-insensitive match).',
                'accepted' => [],
                'aliases' => $aliases['sample_type'] ?? [],
                'create_policy' => 'Used only to match the correct animal/human sample. Does not create sample types here.',
                'create_notes' => '',
                'example' => 'Swab',
                'options' => $options['sample_types']['values'],
                'options_total' => $options['sample_types']['total'],
            ],
            [
                'header' => 'purpose',
                'field' => 'purpose',
                'required' => 'required',
                'description' => 'Tube purpose (free text).',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['purpose'] ?? [],
                'create_policy' => 'Saved on the tube.',
                'create_notes' => '',
                'example' => 'Research',
                'options' => $options['purposes']['values'],
                'options_total' => $options['purposes']['total'],
            ],
            [
                'header' => 'tube_type',
                'field' => 'tube_type',
                'required' => 'required',
                'description' => 'Tube type (free text).',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['tube_type'] ?? [],
                'create_policy' => 'Saved on the tube.',
                'create_notes' => '',
                'example' => 'Cryotube',
                'options' => $options['tube_types']['values'],
                'options_total' => $options['tube_types']['total'],
            ],
            [
                'header' => 'preservant',
                'field' => 'preservant',
                'required' => 'optional',
                'description' => 'Optional preservant/storage state.',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['preservant'] ?? [],
                'create_policy' => 'Optional. Saved on the tube.',
                'create_notes' => '',
                'example' => 'Formalin',
                'options' => $options['preservants']['values'],
                'options_total' => $options['preservants']['total'],
            ],
            [
                'header' => 'amount',
                'field' => 'amount',
                'required' => 'optional',
                'description' => 'Optional tube amount.',
                'format' => 'Numeric.',
                'accepted' => [],
                'aliases' => $aliases['amount'] ?? [],
                'create_policy' => 'Optional. If provided must be numeric.',
                'create_notes' => '',
                'example' => '1.5',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'unit',
                'field' => 'amount_unit',
                'required' => 'optional',
                'description' => 'Optional amount unit.',
                'format' => 'Text (e.g. mL, µL).',
                'accepted' => [],
                'aliases' => $aliases['amount_unit'] ?? [],
                'create_policy' => 'Optional. Saved on the tube.',
                'create_notes' => '',
                'example' => 'mL',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'alias_code',
                'field' => 'alias_code',
                'required' => 'optional',
                'description' => 'Optional legacy/alias tube code.',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['alias_code'] ?? [],
                'create_policy' => 'Optional. Saved on the tube.',
                'create_notes' => '',
                'example' => 'HIST-ALIAS-001',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'date_processed',
                'field' => 'date_processed',
                'required' => 'required',
                'description' => 'Processing date.',
                'format' => 'YYYY-MM-DD.',
                'accepted' => [],
                'aliases' => $aliases['date_processed'] ?? [],
                'create_policy' => 'Required.',
                'create_notes' => '',
                'example' => now()->toDateString(),
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'sub_project_code',
                'field' => 'sub_project_code',
                'required' => 'optional',
                'description' => 'Optional sub-project assignment code.',
                'format' => 'Text (must be allowed for your account).',
                'accepted' => [],
                'aliases' => $aliases['sub_project_code'] ?? [],
                'create_policy' => 'Optional. If provided, must match an allowed sub-project code.',
                'create_notes' => '',
                'example' => 'SP-A1',
                'options' => $options['sub_projects']['values'],
                'options_total' => $options['sub_projects']['total'],
            ],
        ];

        return ['columns' => $columns];
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
     * @return array{title:string, values:list<string>, total:int, truncated:bool}
     */
    private function templateOptionsForField(string $field, ?int $projectId): array
    {
        $field = trim((string) $field);

        $modelValues = [
            'animal_sample',
            'human_sample',
            'environment_sample',
            'parasite_sample',
            'nucleic_acid',
            'culture',
            'pool',
        ];

        if ($field === 'model') {
            return [
                'title' => 'Model values',
                'values' => $modelValues,
                'total' => count($modelValues),
                'truncated' => false,
            ];
        }

        if ($field === 'sub_project_code') {
            $allowed = $projectId ? SubProjectFlag::optionsForUser(Auth::user(), (int) $projectId) : collect();
            $values = collect($allowed)
                ->map(fn (SubProject $sp): string => (string) $sp->code)
                ->filter(fn (string $code): bool => $code !== '')
                ->values()
                ->all();

            return [
                'title' => 'Allowed sub-project codes',
                'values' => $values,
                'total' => count($values),
                'truncated' => false,
            ];
        }

        $max = 1200;

        return match ($field) {
            'sample_type' => $this->templateOptionsFromQuery(SampleTypes::query(), 'name', 'Sample types', $max),
            'preservant' => $this->templateOptionsFromQuery(Tubes::query()->whereNotNull('preservant'), 'preservant', 'Tube preservants', $max),
            'tube_type' => $this->templateOptionsFromQuery(Tubes::query()->whereNotNull('tube_type'), 'tube_type', 'Tube types', $max),
            'purpose' => $this->templateOptionsFromQuery(Tubes::query()->whereNotNull('purpose'), 'purpose', 'Tube purposes', $max),
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
