<?php

namespace App\Livewire\Imports;

use App\Http\Controllers\NotificationController;
use App\Models\Laboratories;
use App\Models\MpsTypes;
use App\Models\People;
use App\Models\Projects;
use App\Models\Protocols;
use App\Models\Techniques;
use App\Models\Tubes;
use App\Services\MicroplasticsService;
use App\Support\SubProjectFlag;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class MicroplasticsImport extends Component
{
    use WithFileUploads;

    public $file;

    public string $status = 'idle';

    public array $rows = [];

    public array $errorsList = [];

    public bool $hasBlockingIssues = false;

    public ?int $sub_project_id = null;

    public function updatedFile(): void
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:20480',
        ]);

        $this->resetPreview();
        $this->parseUploadedFile();
    }

    public function resetPreview(): void
    {
        $this->rows = [];
        $this->errorsList = [];
        $this->status = 'idle';
        $this->hasBlockingIssues = false;
    }

    public function applySuggestedValue(int $line, string $field, string $value): void
    {
        $index = $this->rowIndexForLine($line);
        if ($index === null) {
            return;
        }

        $this->rows[$index][$field] = trim($value);
        if (in_array($field, ['tube_code', 'tube_alias'], true)) {
            $this->rows[$index]['tube_id'] = null;
        }

        $this->refreshRow($index);
    }

    public function selectTubeForRow(int $line, string $tubeId): void
    {
        $index = $this->rowIndexForLine($line);
        if ($index === null) {
            return;
        }

        $projectId = (int) session('selected_project_id');
        $tube = $this->resolveTubeById($projectId, (int) $tubeId);

        if ($tube) {
            $this->rows[$index]['tube_id'] = (int) $tube->id;
            $this->rows[$index]['tube_code'] = (string) $tube->code;
            $this->rows[$index]['tube_alias'] = (string) ($tube->alias_code ?? '');
        } else {
            $this->rows[$index]['tube_id'] = null;
        }

        $this->refreshRow($index);
    }

    public function downloadTemplate()
    {
        $headers = [
            'tube_code',
            'tube_alias',
            'protocol_name',
            'mps_type',
            'sample_weight',
            'r_coeff',
            'm_feret',
            'laboratory',
            'identified_by_email',
        ];

        $exampleRow = [
            'A1A1-NA-001-1',
            'MP-TUBE-001',
            'Microplastics identification',
            'Polyamide',
            '2.4',
            '0.8',
            '156.2',
            'Central Lab',
            'analyst@example.org',
        ];

        return response()->streamDownload(function () use ($headers, $exampleRow): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            fputcsv($output, $headers);
            fputcsv($output, $exampleRow);
            fclose($output);
        }, 'microplastics_import_template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function import(): void
    {
        if ($this->rows === []) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'No data',
                'text' => 'Upload a CSV file first.',
            ]);

            return;
        }

        if ($this->hasBlockingIssues) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Import blocked',
                'text' => 'Fix the CSV errors shown in the preview before importing.',
            ]);

            return;
        }

        $projectId = (int) session('selected_project_id');
        if (! SubProjectFlag::isSelectableByUser(Auth::user(), $projectId, $this->sub_project_id)) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Invalid sub-project',
                'text' => 'Selected sub-project is not allowed for your user.',
            ]);

            return;
        }

        $service = app(MicroplasticsService::class);

        try {
            $service->registerFromTableRows(
                $projectId,
                array_map(fn (array $row): array => [
                    'tube_id' => $row['tube_id'],
                    'sample_weight' => $row['sample_weight'],
                    'r_coeff' => $row['r_coeff'],
                    'mps_type' => $row['mps_type'],
                    'm_feret' => $row['m_feret'],
                    'identification_date' => $row['identification_date'],
                    'protocol_name' => $row['protocol_name'],
                    'laboratory' => $row['laboratory'],
                    'identified_by' => $row['identified_by'],
                ], $this->rows),
                $this->sub_project_id
            );

            $user = Auth::user();
            NotificationController::create(
                'microplastics_created',
                'New Microplastics Identification',
                $user->people->first_name.' registered microplastics records from CSV import.',
                '/samples/microplastics/list',
                $projectId
            );

            $this->reset(['file', 'rows', 'errorsList', 'sub_project_id']);
            $this->status = 'idle';

            $this->dispatch('swal', [
                'icon' => 'success',
                'title' => 'Import complete',
                'text' => 'Microplastics CSV imported successfully.',
            ]);
            $this->dispatch('notification-created');
        } catch (\Throwable $throwable) {
            $this->status = 'error';
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Import failed',
                'text' => $throwable->getMessage(),
            ]);
        }
    }

    private function parseUploadedFile(): void
    {
        $this->rows = [];
        $this->errorsList = [];
        $this->status = 'idle';
        $this->hasBlockingIssues = false;

        $projectId = (int) session('selected_project_id');
        $path = $this->file->getRealPath();
        if (! $path) {
            $this->errorsList[] = 'Unable to read the uploaded file.';
            $this->status = 'error';

            return;
        }

        $handle = fopen($path, 'r');
        if (! $handle) {
            $this->errorsList[] = 'Unable to open the uploaded file.';
            $this->status = 'error';

            return;
        }

        $delimiter = $this->detectDelimiter($handle);
        $header = fgetcsv($handle, 0, $delimiter);
        if (! is_array($header)) {
            fclose($handle);
            $this->errorsList[] = 'CSV header row is missing.';
            $this->status = 'error';

            return;
        }

        $normalizedHeader = array_map(fn ($value): string => $this->normalizeHeaderValue($value), $header);

        $required = ['protocol_name', 'mps_type', 'identification_date', 'laboratory', 'identified_by_email'];
        foreach ($required as $column) {
            if (! in_array($column, $normalizedHeader, true)) {
                $this->errorsList[] = "Missing required column: {$column}";
            }
        }

        if (! in_array('tube_code', $normalizedHeader, true) && ! in_array('tube_alias', $normalizedHeader, true)) {
            $this->errorsList[] = 'CSV must include either `tube_code` or `tube_alias`.';
        }

        if ($this->errorsList !== []) {
            fclose($handle);
            $this->status = 'error';

            return;
        }

        $rows = [];
        $lineNumber = 1;

        while (($record = fgetcsv($handle, 0, $delimiter)) !== false) {
            $lineNumber++;
            if ($record === [null] || $record === []) {
                continue;
            }

            $row = [];
            foreach ($normalizedHeader as $index => $column) {
                $row[$column] = trim((string) ($record[$index] ?? ''));
            }

            $rows[] = $this->resolvePreviewRow([
                'line' => $lineNumber,
                'tube_id' => null,
                'tube_code' => (string) ($row['tube_code'] ?? ''),
                'tube_alias' => (string) ($row['tube_alias'] ?? ''),
                'protocol_name' => (string) ($row['protocol_name'] ?? ''),
                'sample_weight' => ($row['sample_weight'] ?? '') !== '' ? (float) $row['sample_weight'] : null,
                'r_coeff' => ($row['r_coeff'] ?? '') !== '' ? (float) $row['r_coeff'] : null,
                'mps_type' => (string) ($row['mps_type'] ?? ''),
                'm_feret' => ($row['m_feret'] ?? '') !== '' ? (float) $row['m_feret'] : null,
                'identification_date' => (string) ($row['identification_date'] ?? ''),
                'laboratory' => (string) ($row['laboratory'] ?? ''),
                'identified_by' => null,
                'identified_by_email' => (string) ($row['identified_by_email'] ?? ''),
            ], $projectId);
        }

        fclose($handle);

        $this->rows = $rows;
        $this->syncPreviewState();
    }

    private function detectDelimiter($handle): string
    {
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            rewind($handle);

            return ',';
        }

        $delimiters = [',', ';', "\t", '|'];
        $bestDelimiter = ',';
        $bestCount = -1;

        foreach ($delimiters as $delimiter) {
            $count = substr_count($firstLine, $delimiter);
            if ($count > $bestCount) {
                $bestCount = $count;
                $bestDelimiter = $delimiter;
            }
        }

        rewind($handle);

        return $bestDelimiter;
    }

    private function normalizeHeaderValue(mixed $value): string
    {
        $header = (string) $value;
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/', '_', $header) ?? $header;
        $header = trim($header, '_');

        return match ($header) {
            'protocol', 'protocolname' => 'protocol_name',
            'mpstype', 'mps_types', 'microplastics_type', 'microplastics_types' => 'mps_type',
            'tubecode' => 'tube_code',
            'tubealias' => 'tube_alias',
            'identificationdate', 'identified_on', 'date_identified', 'identifieddate' => 'identification_date',
            'identifiedbyemail', 'identified_by', 'identified_by_mail', 'identifiedbymail' => 'identified_by_email',
            default => $header,
        };
    }

    private function similarOptions($query, string $column, string $value, int $threshold = 72, int $limit = 2): array
    {
        $needle = trim(strtolower($value));
        if ($needle === '') {
            return [];
        }

        return $query
            ->pluck($column)
            ->filter()
            ->map(fn ($option) => (string) $option)
            ->map(function (string $option) use ($needle): array {
                similar_text(strtolower($option), $needle, $percent);

                return ['value' => $option, 'score' => $percent];
            })
            ->filter(fn (array $row): bool => $row['score'] >= $threshold)
            ->sortByDesc('score')
            ->pluck('value')
            ->take($limit)
            ->values()
            ->all();
    }

    private function rowIndexForLine(int $line): ?int
    {
        foreach ($this->rows as $index => $row) {
            if ((int) ($row['line'] ?? 0) === $line) {
                return $index;
            }
        }

        return null;
    }

    private function refreshRow(int $index): void
    {
        $projectId = (int) session('selected_project_id');
        $row = $this->rows[$index] ?? null;
        if (! is_array($row)) {
            return;
        }

        $this->rows[$index] = $this->resolvePreviewRow($row, $projectId);
        $this->syncPreviewState();
    }

    private function syncPreviewState(): void
    {
        $this->errorsList = [];

        foreach ($this->rows as $row) {
            foreach (($row['issues'] ?? []) as $issue) {
                $line = (int) ($row['line'] ?? 0);
                $this->errorsList[] = "Row {$line}: {$issue}.";
            }
        }

        $this->hasBlockingIssues = $this->errorsList !== [];
        $this->status = $this->rows !== [] ? 'preview' : 'error';
    }

    private function resolvePreviewRow(array $row, int $projectId): array
    {
        $lineNumber = (int) ($row['line'] ?? 0);
        $tubeCode = trim((string) ($row['tube_code'] ?? ''));
        $tubeAlias = trim((string) ($row['tube_alias'] ?? ''));
        $protocolName = trim((string) ($row['protocol_name'] ?? ''));
        $mpsTypeName = trim((string) ($row['mps_type'] ?? ''));
        $identificationDate = trim((string) ($row['identification_date'] ?? ''));
        $laboratoryName = trim((string) ($row['laboratory'] ?? ''));
        $personEmail = trim((string) ($row['identified_by_email'] ?? ''));

        $issues = [];
        $warnings = [];
        $fieldWarnings = [];

        $tube = $this->resolveTubeMatch($projectId, $row);
        $similarTubes = ! $tube ? $this->similarTubeOptions($projectId, $tubeCode, $tubeAlias) : [];
        if (! $tube) {
            $issues[] = 'matching source tube not found';
            if ($similarTubes !== []) {
                $fieldWarnings['tube'] = [
                    'text' => 'Similar tube codes/aliases found',
                    'options' => $similarTubes,
                ];
                $warnings[] = 'similar source tube matches are available';
            }
        }

        $person = $personEmail !== ''
            ? People::query()->whereRaw('lower(email) = ?', [strtolower($personEmail)])->first()
            : null;
        if (! $person) {
            $issues[] = 'identified_by_email does not match an existing person';
        }

        $mpsType = $mpsTypeName !== ''
            ? MpsTypes::query()->whereRaw('lower(name) = ?', [strtolower($mpsTypeName)])->first()
            : null;
        $similarMpsTypes = $mpsTypeName !== '' && ! $mpsType
            ? $this->similarOptions(MpsTypes::query(), 'name', $mpsTypeName)
            : [];
        if ($mpsTypeName === '') {
            $issues[] = 'mps_type is required';
        } elseif ($similarMpsTypes !== []) {
            $warnings[] = 'similar microplastics type exists: '.implode(', ', $similarMpsTypes);
        }

        $protocol = $protocolName !== ''
            ? Protocols::query()->whereRaw('lower(name) = ?', [strtolower($protocolName)])->first()
            : null;
        if (! $protocol) {
            $issues[] = 'protocol_name does not match an existing protocol';
        }

        if ($identificationDate === '') {
            $issues[] = 'identification_date is required';
        } elseif (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $identificationDate)) {
            $issues[] = 'identification_date must be YYYY-MM-DD';
        }

        $laboratory = $laboratoryName !== ''
            ? Laboratories::query()->whereRaw('lower(name) = ?', [strtolower($laboratoryName)])->first()
            : null;
        $similarLaboratories = $laboratoryName !== '' && ! $laboratory
            ? $this->similarOptions(Laboratories::query(), 'name', $laboratoryName)
            : [];
        if ($similarLaboratories !== []) {
            $warnings[] = 'similar laboratory exists: '.implode(', ', $similarLaboratories);
        }

        return [
            'line' => $lineNumber,
            'tube_id' => $tube ? (int) $tube->id : null,
            'tube_code' => $tube ? (string) $tube->code : $tubeCode,
            'tube_alias' => $tube?->alias_code ?: $tubeAlias,
            'protocol_name' => $protocol ? (string) $protocol->name : $protocolName,
            'sample_weight' => $row['sample_weight'] ?? null,
            'r_coeff' => $row['r_coeff'] ?? null,
            'mps_type' => $mpsType ? (string) $mpsType->name : $mpsTypeName,
            'm_feret' => $row['m_feret'] ?? null,
            'identification_date' => $identificationDate,
            'laboratory' => $laboratory ? (string) $laboratory->name : $laboratoryName,
            'identified_by' => $person ? (int) $person->id : null,
            'identified_by_email' => $person ? (string) $person->email : $personEmail,
            'tube_status' => $tube ? 'existing' : ($similarTubes !== [] ? 'similar' : 'missing'),
            'protocol_status' => $protocol ? 'existing' : 'missing',
            'mps_type_status' => $mpsType ? 'existing' : ($similarMpsTypes !== [] ? 'similar' : ($mpsTypeName !== '' ? 'new' : 'missing')),
            'laboratory_status' => $laboratory ? 'existing' : ($similarLaboratories !== [] ? 'similar' : ($laboratoryName !== '' ? 'new' : 'missing')),
            'identified_by_status' => $person ? 'existing' : 'missing',
            'issues' => $issues,
            'warnings' => $warnings,
            'field_warnings' => $fieldWarnings,
            'resolved_tube_label' => $tube ? $this->formatTubeOption($tube) : '',
        ];
    }

    private function resolveTubeMatch(int $projectId, array $row): ?Tubes
    {
        $selectedTubeId = (int) ($row['tube_id'] ?? 0);
        if ($selectedTubeId > 0) {
            $tube = $this->resolveTubeById($projectId, $selectedTubeId);
            if ($tube) {
                return $tube;
            }
        }

        $tubeCode = trim((string) ($row['tube_code'] ?? ''));
        if ($tubeCode !== '') {
            $tube = Tubes::query()
                ->where('projects_id', $projectId)
                ->whereIn('tubes_content_type', app(MicroplasticsService::class)->eligibleContentTypes())
                ->whereRaw('lower(trim(code)) = ?', [strtolower($tubeCode)])
                ->first();
            if ($tube) {
                return $tube;
            }
        }

        $tubeAlias = trim((string) ($row['tube_alias'] ?? ''));
        if ($tubeAlias !== '') {
            return Tubes::query()
                ->where('projects_id', $projectId)
                ->whereIn('tubes_content_type', app(MicroplasticsService::class)->eligibleContentTypes())
                ->whereRaw('lower(trim(alias_code)) = ?', [strtolower($tubeAlias)])
                ->first();
        }

        return null;
    }

    private function resolveTubeById(int $projectId, int $tubeId): ?Tubes
    {
        if ($tubeId <= 0) {
            return null;
        }

        return Tubes::query()
            ->where('projects_id', $projectId)
            ->whereIn('tubes_content_type', app(MicroplasticsService::class)->eligibleContentTypes())
            ->whereKey($tubeId)
            ->first();
    }

    private function similarTubeOptions(int $projectId, string $tubeCode, string $tubeAlias, int $limit = 5): array
    {
        $needle = strtolower(trim($tubeCode !== '' ? $tubeCode : $tubeAlias));
        if ($needle === '') {
            return [];
        }

        return Tubes::query()
            ->where('projects_id', $projectId)
            ->whereIn('tubes_content_type', app(MicroplasticsService::class)->eligibleContentTypes())
            ->get(['id', 'code', 'alias_code'])
            ->map(function (Tubes $tube) use ($needle): array {
                similar_text(strtolower((string) $tube->code), $needle, $codeScore);
                similar_text(strtolower((string) ($tube->alias_code ?? '')), $needle, $aliasScore);
                $contains = str_contains(strtolower((string) $tube->code), $needle)
                    || str_contains(strtolower((string) ($tube->alias_code ?? '')), $needle);

                return [
                    'id' => (int) $tube->id,
                    'code' => (string) $tube->code,
                    'alias' => (string) ($tube->alias_code ?? ''),
                    'label' => $this->formatTubeOption($tube),
                    'score' => $contains ? 100 : max($codeScore, $aliasScore),
                ];
            })
            ->filter(fn (array $option): bool => $option['score'] >= 60)
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->all();
    }

    private function formatTubeOption(Tubes $tube): string
    {
        $alias = trim((string) ($tube->alias_code ?? ''));

        return $alias !== ''
            ? "{$tube->code} [{$alias}]"
            : (string) $tube->code;
    }

    public function render()
    {
        $projectId = (int) session('selected_project_id');
        $projectCode = (string) (Projects::query()->where('id', $projectId)->value('code') ?? '');

        return view('livewire.imports.microplastics-import', [
            'projectCode' => $projectCode,
            'subProjectOptions' => SubProjectFlag::optionsForUser(Auth::user(), $projectId),
            'microplastic_techniques' => Techniques::query()
                ->where('type', 'Microplastics identification')
                ->orderBy('name')
                ->get(),
            'eligibleTubeOptions' => Tubes::query()
                ->where('projects_id', $projectId)
                ->whereIn('tubes_content_type', app(MicroplasticsService::class)->eligibleContentTypes())
                ->orderBy('code')
                ->get(['id', 'code', 'alias_code'])
                ->map(fn (Tubes $tube): array => [
                    'id' => (int) $tube->id,
                    'label' => $this->formatTubeOption($tube),
                ])
                ->all(),
        ]);
    }
}
