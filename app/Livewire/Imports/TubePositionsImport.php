<?php

namespace App\Livewire\Imports;

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TubesController;
use App\Livewire\PlainComponent;
use App\Models\AnimalSamples;
use App\Models\Boxes;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\People;
use App\Models\Pools;
use App\Models\Projects;
use App\Models\SampleTypes;
use App\Models\TubePositions;
use App\Models\Tubes;
use App\Services\Imports\ColumnMatcher;
use App\Services\Imports\DelimitedTableReader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;

class TubePositionsImport extends PlainComponent
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
        }, 'tube_positions_import_template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function openTemplateOptions(string $field): void
    {
        $projectId = $this->selectedProjectId();
        $result = $this->templateOptionsForField($field, $projectId);

        $itemsHtml = implode('', array_map(function (string $value): string {
            $escaped = e($value);

            return "<li class=\"py-1\"><span class=\"font-mono text-xs text-slate-800\">{$escaped}</span></li>";
        }, $result['values']));

        $note = $result['truncated']
            ? '<div class="mt-3 text-xs text-slate-500">Showing a subset of values for performance. Refine by typing the exact value in your CSV.</div>'
            : '';

        $html = <<<HTML
<div class="text-left">
  <div class="text-sm text-slate-700">{$result['total']} value(s)</div>
  <div class="mt-3 max-h-96 overflow-auto rounded-lg border border-slate-200 bg-white px-4 py-3">
    <ul class="divide-y divide-slate-100">{$itemsHtml}</ul>
  </div>
  {$note}
</div>
HTML;

        $this->dispatch('swal', [
            'icon' => 'info',
            'title' => $result['title'],
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

        if (! $this->userCanWriteModule('tube_positions')) {
            $this->globalIssues[] = 'You do not have permission to import tube positions in this project (viewer accounts are read-only).';
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

        if (
            ! array_key_exists('tube_id', $this->fieldToIndex)
            && ! array_key_exists('tube_alias', $this->fieldToIndex)
            && ! array_key_exists('sample_code', $this->fieldToIndex)
        ) {
            $this->globalIssues[] = 'You must provide at least one identifier column: tube_id, tube_alias, or sample_code.';
        }

        if (! empty($this->globalIssues)) {
            $previewHeaders = array_slice($this->headers, 0, 40);
            $headersText = implode(' | ', array_map(static fn (string $h): string => $h === '' ? '(empty)' : $h, $previewHeaders));
            $suffix = count($this->headers) > count($previewHeaders) ? ' | …' : '';
            $this->globalIssues[] = 'Detected headers: '.$headersText.$suffix;
        }

        $this->cacheKey = "imports:tube_positions:{$projectId}:".bin2hex(random_bytes(8));
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

        if ($this->previewHasBlockingIssues()) {
            $this->globalIssues[] = 'Fix all row errors in the preview before importing (new mover emails require first and last name).';
            $this->status = 'error';

            return;
        }

        if (! $this->userCanWriteModule('tube_positions')) {
            $this->globalIssues[] = 'You do not have permission to import tube positions in this project.';
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
        $rollbackSignal = '__tube_positions_bulk_import_rollback__';
        $nextBoxSerial = $this->nextBoxSerialForProject($projectId, $project->code);
        $reservedBoxCodes = [];
        $batchPositionKeys = [];
        $tubesController = app(TubesController::class);

        try {
            DB::transaction(function () use (
                $rows,
                $project,
                &$created,
                &$errors,
                $rollbackSignal,
                &$nextBoxSerial,
                &$reservedBoxCodes,
                &$batchPositionKeys,
                $tubesController
            ): void {
                foreach ($rows as $index => $row) {
                    $rowNumber = $index + 2;
                    $resolved = $this->resolveRow(is_array($row) ? $row : [], (int) $project->id, $rowNumber, $batchPositionKeys, true);

                    if (! empty($resolved['warnings'])) {
                        $this->globalWarnings[] = "Row {$rowNumber}: ".implode(' | ', $resolved['warnings']);
                    }

                    if (! empty($resolved['issues'])) {
                        $errors++;
                        $this->globalIssues[] = "Row {$rowNumber}: ".implode(' | ', $resolved['issues']);

                        continue;
                    }

                    $tube = $resolved['tube'];
                    $mover = $resolved['mover'];
                    if (! $tube || ! $mover) {
                        $errors++;
                        $this->globalIssues[] = "Row {$rowNumber}: unable to resolve tube or moved_by";

                        continue;
                    }

                    $box = $resolved['box'];
                    if (! $box) {
                        $box = $this->resolveOrCreateBox(
                            $project,
                            $resolved['box_code'],
                            (int) $resolved['box_x'],
                            (int) $resolved['box_y'],
                            $nextBoxSerial,
                            $reservedBoxCodes
                        );
                    }

                    if (! $box) {
                        $errors++;
                        $this->globalIssues[] = "Row {$rowNumber}: unable to resolve or create box";

                        continue;
                    }

                    TubePositions::query()->create([
                        'tubes_id' => $tube->id,
                        'boxes_id' => $box->id,
                        'position_x' => (int) $resolved['position_x'],
                        'position_y' => (int) $resolved['position_y'],
                        'date_moved' => $resolved['date_moved'],
                        'people_id' => $mover->id,
                        'reason' => 'Bulk CSV import',
                    ]);

                    $batchPositionKeys[$box->id.'-'.$resolved['position_x'].'-'.$resolved['position_y']] = $rowNumber;

                    Boxes::query()->where('id', $box->id)->update([
                        'content_type' => $tubesController->getDynamicContentType($box->id),
                    ]);

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
        $successMessage = "{$created} tube position(s) imported successfully.";
        session()->flash('success', $successMessage);
        NotificationController::create(
            'tube_moved',
            'Bulk tube positions imported',
            $successMessage,
            '/bank/tubes/list',
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
     * @param  array<string, int>  $batchPositionKeys
     * @return array<string, mixed>
     */
    private function resolveRow(array $row, int $projectId, ?int $rowNumber = null, array $batchPositionKeys = [], bool $createMissingPeople = false): array
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
        $boxCode = $this->sanitizeCell($get('box_code'));
        $boxX = $get('box_x');
        $boxY = $get('box_y');
        $positionX = $get('position_x');
        $positionY = $get('position_y');
        $dateMoved = $get('date_moved');
        $movedBy = $this->sanitizeCell($get('moved_by'));
        $movedByFirstName = $this->normalizeWordsTitleCase($get('moved_by_first_name'));
        $movedByLastName = $this->normalizeWordsTitleCase($get('moved_by_last_name'));

        $issues = [];
        $warnings = [];
        $fieldWarnings = [];
        $positionReplacement = null;

        if ($tubeId === '' && $tubeAlias === '' && $sampleCode === '') {
            $issues[] = 'at least one of tube_id, tube_alias, or sample_code is required';
        }
        if ($sampleType === '') {
            $issues[] = 'sample_type is required';
        } elseif (! in_array($sampleType, $this->allowedSampleTypes, true)) {
            $issues[] = 'sample_type must be one of: '.implode(', ', $this->allowedSampleTypes);
        }
        if ($tubeAlias !== '' && $sampleType === 'Animal samples' && $animalSampleType === '') {
            $issues[] = 'animal_sample_type is required when using tube_alias for Animal samples';
        }
        if ($boxCode === '') {
            $issues[] = 'box_code is required';
        }
        if ($positionX === '' || ! ctype_digit($positionX) || (int) $positionX < 1) {
            $issues[] = 'position_x must be a positive integer';
        }
        if ($positionY === '' || ! ctype_digit($positionY) || (int) $positionY < 1) {
            $issues[] = 'position_y must be a positive integer';
        }
        if ($dateMoved === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateMoved)) {
            $issues[] = 'date_moved must be YYYY-MM-DD';
        }
        if ($movedBy === '') {
            $issues[] = 'moved_by is required';
        } elseif (! filter_var($movedBy, FILTER_VALIDATE_EMAIL)) {
            $parts = preg_split('/\s+/', trim($movedBy), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            if (count($parts) < 2) {
                $issues[] = 'moved_by must be a valid email or "First Last" name';
            }
        }

        $tubeResolution = $this->resolveTube($projectId, $tubeId, $tubeAlias, $sampleCode, $sampleType, $animalSampleType);
        $tube = $tubeResolution['tube'];
        if (! $tube) {
            $issues[] = $tubeResolution['issue'] ?? 'tube identifier did not match any tube in current project';
        }

        $box = $this->findBox($projectId, $boxCode);
        $boxExists = $box !== null;
        $boxColumns = $boxExists ? (int) $box->n_columns : (int) $boxX;
        $boxRows = $boxExists ? (int) $box->n_rows : (int) $boxY;

        if (! $boxExists) {
            if ($boxX === '' || ! ctype_digit($boxX) || (int) $boxX < 6) {
                $issues[] = 'box_x is required and must be at least 6 when box_code is new';
            }
            if ($boxY === '' || ! ctype_digit($boxY) || (int) $boxY < 6) {
                $issues[] = 'box_y is required and must be at least 6 when box_code is new';
            }
            $boxColumns = (int) $boxX;
            $boxRows = (int) $boxY;
        }

        if ($positionX !== '' && $boxColumns > 0 && (int) $positionX > $boxColumns) {
            $issues[] = "position_x exceeds box columns ({$boxColumns})";
        }
        if ($positionY !== '' && $boxRows > 0 && (int) $positionY > $boxRows) {
            $issues[] = "position_y exceeds box rows ({$boxRows})";
        }

        if ($box && $positionX !== '' && $positionY !== '') {
            $positionKey = $box->id.'-'.$positionX.'-'.$positionY;
            if (isset($batchPositionKeys[$positionKey])) {
                $issues[] = 'duplicate position in import file (same box and coordinates used on another row)';
            }

            $occupant = $this->occupantAtPosition((int) $box->id, (int) $positionX, (int) $positionY);
            if ($occupant && (int) $occupant['tubes_id'] !== (int) ($tube?->id ?? 0)) {
                $positionReplacement = [
                    'tube_code' => $occupant['tube_code'],
                    'tube_alias' => $occupant['tube_alias'],
                    'text' => $this->positionReplacementMessage($occupant),
                ];
                $warnings[] = $positionReplacement['text'];
            }
        }

        if (! $boxExists && $boxCode !== '') {
            $similarBoxes = $this->similarBoxes($projectId, $boxCode);
            if (! empty($similarBoxes)) {
                $fieldWarnings['box_code'] = [
                    'text' => '≈ '.implode(' and ', $similarBoxes),
                    'suggested' => $similarBoxes[0],
                    'options' => $similarBoxes,
                ];
                $warnings[] = $fieldWarnings['box_code']['text'];
            }
        }

        $moverExists = $this->moverExists($movedBy);
        $mover = null;
        if ($moverExists) {
            $mover = $this->resolveOrCreateMover($movedBy);
        } elseif ($movedBy !== '') {
            if (filter_var($movedBy, FILTER_VALIDATE_EMAIL)) {
                if ($movedByFirstName === '') {
                    $issues[] = 'moved_by_first_name is required when moved_by email is new';
                }
                if ($movedByLastName === '') {
                    $issues[] = 'moved_by_last_name is required when moved_by email is new';
                }
            }

            $similarPeople = $this->similarPeople($movedBy);
            if (! empty($similarPeople)) {
                $fieldWarnings['moved_by'] = [
                    'text' => '≈ '.implode(' and ', $similarPeople),
                    'suggested' => $similarPeople[0],
                    'options' => $similarPeople,
                ];
                $warnings[] = $fieldWarnings['moved_by']['text'];
            }
        }

        if ($moverExists || (empty($issues) && $movedBy !== '')) {
            $mover = $this->resolveOrCreateMover($movedBy, $movedByFirstName, $movedByLastName, $createMissingPeople);
            if ($createMissingPeople && ! $mover) {
                $issues[] = 'unable to resolve moved_by person';
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
            'box_code' => $boxCode,
            'box_x' => $boxX,
            'box_y' => $boxY,
            'position_x' => $positionX,
            'position_y' => $positionY,
            'date_moved' => $dateMoved,
            'moved_by' => $movedBy,
            'moved_by_first_name' => $movedByFirstName,
            'moved_by_last_name' => $movedByLastName,
            'tube_exists' => $tube !== null,
            'box_exists' => $boxExists,
            'box' => $box,
            'selected_box_code' => $box?->code ? (string) $box->code : '',
            'mover_exists' => $moverExists,
            'mover_needs_names' => ! $moverExists && filter_var($movedBy, FILTER_VALIDATE_EMAIL),
            'position_replacement' => $positionReplacement ?? null,
            'field_warnings' => $fieldWarnings,
            'warnings' => $warnings,
            'issues' => $issues,
            'tube' => $tube,
            'mover' => $mover,
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
            ->whereHasMorph('tubes_content', ['*'], function ($q) use ($sampleCode): void {
                $q->whereRaw('lower(trim(code)) = ?', [strtolower(trim($sampleCode))]);
            })
            ->with('tubes_content')
            ->latest('id')
            ->first();

        return ['tube' => $tube, 'issue' => null];
    }

    private function findBox(int $projectId, string $boxCode): ?Boxes
    {
        $normalized = strtolower(trim($boxCode));
        if ($normalized === '') {
            return null;
        }

        return Boxes::query()
            ->where('projects_id', $projectId)
            ->where(function (Builder $query) use ($normalized): void {
                $query->whereRaw('lower(trim(code)) = ?', [$normalized])
                    ->orWhereRaw('lower(trim(name)) = ?', [$normalized]);
            })
            ->first();
    }

    /**
     * @param  array<string, bool>  $reservedBoxCodes
     */
    private function resolveOrCreateBox(
        Projects $project,
        string $boxCode,
        int $columns,
        int $rows,
        int &$nextSerial,
        array &$reservedBoxCodes
    ): ?Boxes {
        $existing = $this->findBox((int) $project->id, $boxCode);
        if ($existing) {
            return $existing;
        }

        if ($columns < 6 || $rows < 6) {
            return null;
        }

        while (true) {
            $candidate = $project->code.'-BO-'.$nextSerial;
            $nextSerial++;

            if (isset($reservedBoxCodes[$candidate])) {
                continue;
            }

            if (Boxes::query()->where('projects_id', $project->id)->where('code', $candidate)->exists()) {
                continue;
            }

            $reservedBoxCodes[$candidate] = true;

            return Boxes::query()->create([
                'code' => $candidate,
                'name' => $boxCode,
                'n_columns' => $columns,
                'n_rows' => $rows,
                'projects_id' => $project->id,
            ]);
        }
    }

    /**
     * @return array{tubes_id:int, tube_code:string, tube_alias:string}|null
     */
    private function occupantAtPosition(int $boxId, int $positionX, int $positionY): ?array
    {
        $latestByTube = TubePositions::query()
            ->select('tubes_id')
            ->selectRaw('MAX(id) as latest_id')
            ->groupBy('tubes_id');

        $row = TubePositions::query()
            ->joinSub($latestByTube, 'latest_by_tube', function ($join): void {
                $join->on('tube_positions.tubes_id', '=', 'latest_by_tube.tubes_id')
                    ->on('tube_positions.id', '=', 'latest_by_tube.latest_id');
            })
            ->where('tube_positions.boxes_id', $boxId)
            ->where('tube_positions.position_x', $positionX)
            ->where('tube_positions.position_y', $positionY)
            ->join('tubes', 'tube_positions.tubes_id', '=', 'tubes.id')
            ->select(['tube_positions.tubes_id', 'tubes.code as tube_code', 'tubes.alias_code as tube_alias'])
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'tubes_id' => (int) $row->tubes_id,
            'tube_code' => (string) $row->tube_code,
            'tube_alias' => (string) ($row->tube_alias ?? ''),
        ];
    }

    private function resolveOrCreateMover(string $movedBy, string $firstName = '', string $lastName = '', bool $create = true): ?People
    {
        $movedBy = trim($movedBy);
        if ($movedBy === '') {
            return null;
        }

        if (filter_var($movedBy, FILTER_VALIDATE_EMAIL)) {
            $person = People::query()
                ->whereRaw('lower(email) = ?', [strtolower($movedBy)])
                ->first();

            if ($person) {
                return $person;
            }

            $first = $this->normalizeWordsTitleCase($firstName);
            $last = $this->normalizeWordsTitleCase($lastName);
            if ($first === '' || $last === '') {
                return null;
            }

            if (! $create) {
                return null;
            }

            return People::query()->create([
                'first_name' => $first,
                'last_name' => $last,
                'email' => strtolower($movedBy),
            ]);
        }

        $parts = preg_split('/\s+/', $movedBy, -1, PREG_SPLIT_NO_EMPTY) ?: [];
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

        if (! $create) {
            return null;
        }

        return People::query()->create([
            'first_name' => $first,
            'last_name' => $last,
        ]);
    }

    /**
     * @param  array{tube_code:string, tube_alias:string}  $occupant
     */
    private function positionReplacementMessage(array $occupant): string
    {
        $tubeCode = trim($occupant['tube_code']);
        $tubeAlias = trim($occupant['tube_alias']);

        if ($tubeAlias !== '') {
            return "This tube will replace tube code {$tubeCode} ({$tubeAlias}).";
        }

        return "This tube will replace tube code {$tubeCode}.";
    }

    private function moverExists(string $movedBy): bool
    {
        if ($movedBy === '') {
            return false;
        }

        if (filter_var($movedBy, FILTER_VALIDATE_EMAIL)) {
            return People::query()->whereRaw('lower(email) = ?', [strtolower($movedBy)])->exists();
        }

        $parts = preg_split('/\s+/', trim($movedBy), -1, PREG_SPLIT_NO_EMPTY) ?: [];
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
    private function similarPeople(string $movedBy): array
    {
        if ($movedBy === '') {
            return [];
        }

        if (filter_var($movedBy, FILTER_VALIDATE_EMAIL)) {
            return $this->similarOptions($this->projectPeopleEmailsQuery($this->selectedProjectId()), 'email', strtolower($movedBy));
        }

        return $this->similarOptions(People::query()->selectRaw("CONCAT(first_name, ' ', last_name) as full_name"), 'full_name', $movedBy);
    }

    /**
     * @return list<string>
     */
    private function similarBoxes(int $projectId, string $boxCode): array
    {
        $query = Boxes::query()->where('projects_id', $projectId);

        return array_values(array_unique(array_merge(
            $this->similarOptions((clone $query)->whereNotNull('code'), 'code', $boxCode),
            $this->similarOptions((clone $query)->whereNotNull('name'), 'name', $boxCode)
        )));
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

    private function nextBoxSerialForProject(int $projectId, string $projectCode): int
    {
        $codes = Boxes::query()
            ->where('projects_id', $projectId)
            ->where('code', 'like', $projectCode.'-BO-%')
            ->pluck('code');

        $used = $codes->map(function ($code) {
            preg_match('/-BO-(\d+)$/', (string) $code, $matches);

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
            'box_code',
            'position_x',
            'position_y',
            'date_moved',
            'moved_by',
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private function synonymsByField(): array
    {
        return [
            'tube_id' => ['tube_id', 'tube code', 'tube_code', 'tube', 'tubeid'],
            'tube_alias' => ['tube_alias', 'tube alias', 'tube_alias_code', 'tube alias code', 'alias_code', 'alias'],
            'sample_code' => ['sample_code', 'sample code', 'origin_sample_code', 'origin sample code'],
            'sample_type' => ['sample_type', 'sample type'],
            'animal_sample_type' => ['animal_sample_type', 'animal sample type', 'animal_matrix', 'animal matrix', 'matrix'],
            'box_code' => ['box_code', 'box code', 'box'],
            'box_x' => ['box_x', 'box columns', 'box_columns', 'n_columns', 'columns'],
            'box_y' => ['box_y', 'box rows', 'box_rows', 'n_rows', 'rows'],
            'position_x' => ['position_x', 'position x', 'x_position', 'x position', 'x'],
            'position_y' => ['position_y', 'position y', 'y_position', 'y position', 'y'],
            'date_moved' => ['date_moved', 'date moved', 'movement_date', 'date'],
            'moved_by' => ['moved_by', 'moved by', 'mover', 'mover_email', 'person'],
            'moved_by_first_name' => ['moved_by_first_name', 'moved by first name', 'mover_first_name', 'first_name'],
            'moved_by_last_name' => ['moved_by_last_name', 'moved by last name', 'mover_last_name', 'last_name'],
        ];
    }

    /**
     * @return array{rows: list<array<string,mixed>>, total: int, from: int, to: int, current_page: int, last_page: int, start_page: int, end_page: int}
     */
    public function previewHasBlockingIssues(): bool
    {
        if (! $this->cacheKey) {
            return false;
        }

        $rows = Cache::get($this->cacheKey);
        if (! is_array($rows) || $rows === []) {
            return false;
        }

        $projectId = $this->selectedProjectId() ?? 0;
        $batchPositionKeys = [];

        foreach ($rows as $index => $row) {
            $resolved = $this->resolveRow(is_array($row) ? $row : [], (int) $projectId, $index + 2, $batchPositionKeys);
            if (! empty($resolved['issues'])) {
                return true;
            }

            if (
                $resolved['box_exists']
                && $resolved['position_x'] !== ''
                && $resolved['position_y'] !== ''
                && isset($resolved['box'])
            ) {
                $batchPositionKeys[$resolved['box']->id.'-'.$resolved['position_x'].'-'.$resolved['position_y']] = $index + 2;
            }
        }

        return false;
    }

    public function previewPage(): array
    {
        if (! $this->cacheKey) {
            return ['rows' => [], 'total' => 0, 'from' => 0, 'to' => 0, 'current_page' => 1, 'last_page' => 1, 'start_page' => 1, 'end_page' => 1, 'has_blocking_issues' => false];
        }

        $rows = Cache::get($this->cacheKey);
        if (! is_array($rows)) {
            return ['rows' => [], 'total' => 0, 'from' => 0, 'to' => 0, 'current_page' => 1, 'last_page' => 1, 'start_page' => 1, 'end_page' => 1, 'has_blocking_issues' => false];
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
        $batchPositionKeys = [];

        foreach ($slice as $row) {
            $rowNumber = $offset + count($mapped) + 2;
            $resolved = $this->resolveRow(is_array($row) ? $row : [], (int) $projectId, $rowNumber, $batchPositionKeys);
            if (
                $resolved['box_exists']
                && $resolved['position_x'] !== ''
                && $resolved['position_y'] !== ''
                && isset($resolved['box'])
            ) {
                $batchPositionKeys[$resolved['box']->id.'-'.$resolved['position_x'].'-'.$resolved['position_y']] = $rowNumber;
            }
            $mapped[] = $resolved;
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
            'has_blocking_issues' => $this->previewHasBlockingIssues(),
        ];
    }

    public function render()
    {
        return view('livewire.imports.tube-positions-import', [
            'previewPageData' => $this->previewPage(),
            'template' => $this->templateDefinition(),
        ]);
    }

    /**
     * @return array{columns:list<array<string,mixed>>}
     */
    private function templateDefinition(): array
    {
        $projectId = $this->selectedProjectId();

        $options = Cache::remember(
            'imports:tube_positions:template_options:v1:'.($projectId ?? 0),
            now()->addMinutes(30),
            function () use ($projectId): array {
                return [
                    'tube_code' => $this->optionPreviewTubes($projectId, 'code'),
                    'tube_alias' => $this->optionPreviewTubes($projectId, 'alias_code'),
                    'animal_sample_types' => $this->optionPreview(SampleTypes::query()->orderBy('name'), 'name'),
                    'box_codes' => $this->optionPreview(
                        Boxes::query()->when($projectId, fn ($q) => $q->where('projects_id', $projectId))->orderBy('code'),
                        'code'
                    ),
                    'people_emails' => $this->optionPreview(
                        $this->projectPeopleEmailsQuery($projectId)->orderBy('email'),
                        'email'
                    ),
                ];
            }
        );

        $aliases = $this->synonymsByField();

        $columns = [
            [
                'header' => 'tube_id',
                'field' => 'tube_id',
                'required' => 'conditional',
                'description' => 'Tube code in the selected project. Provide tube_id OR tube_alias OR sample_code (with sample_type).',
                'format' => 'Text (exact tube code).',
                'accepted' => [],
                'aliases' => $aliases['tube_id'] ?? [],
                'create_policy' => 'Links to existing tubes. Cannot create tubes here.',
                'create_notes' => 'Use sample_type to disambiguate when needed.',
                'example' => 'A1A1-AS-394-2',
                'options' => $options['tube_code']['values'],
                'options_total' => $options['tube_code']['total'],
            ],
            [
                'header' => 'tube_alias',
                'field' => 'tube_alias',
                'required' => 'conditional',
                'description' => 'Tube alias code. Provide tube_id OR tube_alias OR sample_code (with sample_type).',
                'format' => 'Text (exact alias).',
                'accepted' => [],
                'aliases' => $aliases['tube_alias'] ?? [],
                'create_policy' => 'Links to existing tubes by alias or code.',
                'create_notes' => 'For Animal samples, also provide animal_sample_type when alias repeats across matrices.',
                'example' => '',
                'options' => $options['tube_alias']['values'],
                'options_total' => $options['tube_alias']['total'],
            ],
            [
                'header' => 'sample_code',
                'field' => 'sample_code',
                'required' => 'conditional',
                'description' => 'Origin sample code linked through the tube content.',
                'format' => 'Text (exact sample code).',
                'accepted' => [],
                'aliases' => $aliases['sample_code'] ?? [],
                'create_policy' => 'Links to an existing tube by matching sample code within the project.',
                'create_notes' => 'sample_type is required to identify the correct tube content type.',
                'example' => '',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'sample_type',
                'field' => 'sample_type',
                'required' => 'required',
                'description' => 'Tube content type used to match the correct polymorphic sample.',
                'format' => 'One of the accepted values.',
                'accepted' => $this->allowedSampleTypes,
                'aliases' => $aliases['sample_type'] ?? [],
                'create_policy' => 'Must match accepted values.',
                'create_notes' => 'Required for tube_alias and sample_code matching.',
                'example' => 'Nucleic acids',
                'options' => array_slice($this->allowedSampleTypes, 0, 10),
                'options_total' => count($this->allowedSampleTypes),
            ],
            [
                'header' => 'animal_sample_type',
                'field' => 'animal_sample_type',
                'required' => 'conditional',
                'description' => 'Animal biological matrix when using tube_alias for Animal samples.',
                'format' => 'Text (e.g. Serum, Blood).',
                'accepted' => [],
                'aliases' => $aliases['animal_sample_type'] ?? [],
                'create_policy' => 'Required only for Animal samples + tube_alias.',
                'create_notes' => '',
                'example' => 'Serum',
                'options' => $options['animal_sample_types']['values'],
                'options_total' => $options['animal_sample_types']['total'],
            ],
            [
                'header' => 'box_code',
                'field' => 'box_code',
                'required' => 'required',
                'description' => 'Box code or box name in the selected project.',
                'format' => 'Text (exact box code or name).',
                'accepted' => [],
                'aliases' => $aliases['box_code'] ?? [],
                'create_policy' => 'Links to an existing box when matched; otherwise creates a new box (box_x and box_y required).',
                'create_notes' => 'New boxes receive an auto-generated code (PROJECT-BO-N).',
                'example' => 'Freezer A shelf 1',
                'options' => $options['box_codes']['values'],
                'options_total' => $options['box_codes']['total'],
            ],
            [
                'header' => 'box_x',
                'field' => 'box_x',
                'required' => 'conditional',
                'description' => 'Number of box columns when creating a new box.',
                'format' => 'Integer >= 6.',
                'accepted' => [],
                'aliases' => $aliases['box_x'] ?? [],
                'create_policy' => 'Required only when box_code is new.',
                'create_notes' => '',
                'example' => '10',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'box_y',
                'field' => 'box_y',
                'required' => 'conditional',
                'description' => 'Number of box rows when creating a new box.',
                'format' => 'Integer >= 6.',
                'accepted' => [],
                'aliases' => $aliases['box_y'] ?? [],
                'create_policy' => 'Required only when box_code is new.',
                'create_notes' => '',
                'example' => '10',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'position_x',
                'field' => 'position_x',
                'required' => 'required',
                'description' => 'Column position inside the box.',
                'format' => 'Positive integer (1..box columns).',
                'accepted' => [],
                'aliases' => $aliases['position_x'] ?? [],
                'create_policy' => 'Must fit within the box column count.',
                'create_notes' => '',
                'example' => '1',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'position_y',
                'field' => 'position_y',
                'required' => 'required',
                'description' => 'Row position inside the box.',
                'format' => 'Positive integer (1..box rows).',
                'accepted' => [],
                'aliases' => $aliases['position_y'] ?? [],
                'create_policy' => 'Must fit within the box row count.',
                'create_notes' => '',
                'example' => '1',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'date_moved',
                'field' => 'date_moved',
                'required' => 'required',
                'description' => 'Date the tube was moved into the box position.',
                'format' => 'YYYY-MM-DD.',
                'accepted' => [],
                'aliases' => $aliases['date_moved'] ?? [],
                'create_policy' => 'Must be a valid date string.',
                'create_notes' => '',
                'example' => now()->toDateString(),
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'moved_by',
                'field' => 'moved_by',
                'required' => 'required',
                'description' => 'Person who moved the tube (project team member).',
                'format' => 'Email address OR "First Last" name.',
                'accepted' => [],
                'aliases' => $aliases['moved_by'] ?? [],
                'create_policy' => 'Links to an existing People record; otherwise creates a new person when first/last name are provided for a new email.',
                'create_notes' => 'Leave first/last name empty when the email already exists. Both are required to create a new person.',
                'example' => 'person@example.org',
                'options' => $options['people_emails']['values'],
                'options_total' => $options['people_emails']['total'],
            ],
            [
                'header' => 'moved_by_first_name',
                'field' => 'moved_by_first_name',
                'required' => 'conditional',
                'description' => 'First name when moved_by email is new.',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['moved_by_first_name'] ?? [],
                'create_policy' => 'Required only when moved_by email is new.',
                'create_notes' => '',
                'example' => '',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'moved_by_last_name',
                'field' => 'moved_by_last_name',
                'required' => 'conditional',
                'description' => 'Last name when moved_by email is new.',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['moved_by_last_name'] ?? [],
                'create_policy' => 'Required only when moved_by email is new.',
                'create_notes' => '',
                'example' => '',
                'options' => [],
                'options_total' => 0,
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
    private function templateOptionsForField(string $field, ?int $projectId): array
    {
        $field = trim($field);
        $max = 1200;

        if ($field === 'sample_type') {
            return [
                'title' => 'Sample type',
                'values' => $this->allowedSampleTypes,
                'total' => count($this->allowedSampleTypes),
                'truncated' => false,
            ];
        }

        $result = match ($field) {
            'tube_id' => $this->templateOptionsFromQuery(
                $projectId ? Tubes::query()->where('projects_id', $projectId) : Tubes::query()->whereRaw('1 = 0'),
                'code',
                'Tube codes',
                $max
            ),
            'tube_alias' => $this->templateOptionsFromQuery(
                $projectId ? Tubes::query()->where('projects_id', $projectId) : Tubes::query()->whereRaw('1 = 0'),
                'alias_code',
                'Tube aliases',
                $max
            ),
            'animal_sample_type' => $this->templateOptionsFromQuery(SampleTypes::query(), 'name', 'Animal sample matrices', $max),
            'box_code' => $this->templateOptionsFromQuery(
                $projectId ? Boxes::query()->where('projects_id', $projectId) : Boxes::query()->whereRaw('1 = 0'),
                'code',
                'Box codes',
                $max
            ),
            'moved_by' => $this->templateOptionsFromQuery(
                $this->projectPeopleEmailsQuery($projectId),
                'email',
                'Mover emails (project team)',
                $max
            ),
            default => [
                'title' => 'Values',
                'values' => [],
                'total' => 0,
                'truncated' => false,
            ],
        };

        return $result;
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
