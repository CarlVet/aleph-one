<?php

namespace App\Livewire\Imports;

use App\Http\Controllers\NotificationController;
use App\Livewire\PlainComponent;
use App\Models\Countries;
use App\Models\EnvironmentSamples;
use App\Models\EnvironmentSampleTypes;
use App\Models\Laboratories;
use App\Models\Locations;
use App\Models\People;
use App\Models\Projects;
use App\Models\SamplingSites;
use App\Services\EnvironmentSamplesService;
use App\Services\Imports\ColumnMatcher;
use App\Services\Imports\DelimitedTableReader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;

class EnvironmentSamplesImport extends PlainComponent
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
        }, 'environment_samples_import_template.csv', [
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
            $this->globalIssues[] = 'You do not have permission to import environment samples in this project (viewer accounts are read-only).';
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

        $this->cacheKey = "imports:environment_samples:{$projectId}:".bin2hex(random_bytes(8));
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

        if ($field === 'environment_sample_type') {
            $existing = $this->findEnvironmentSampleTypeByFlexibleName($value);
            if ($existing) {
                $this->rowOverrides[$rowNumber]['environment_sample_type_category'] = (string) ($existing->category ?? '');
            }
        }
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

        if (! $this->userCanEditSelectedProject()) {
            $this->globalIssues[] = 'You do not have permission to import environment samples in this project (viewer accounts are read-only).';
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

        $service = new EnvironmentSamplesService;
        $project = Projects::findOrFail($projectId);

        $created = 0;
        $errors = 0;
        $rollbackSignal = '__environment_samples_bulk_import_rollback__';

        try {
            DB::transaction(function () use ($rows, $service, $project, &$created, &$errors, $rollbackSignal): void {
                $nextSerial = $this->nextEnvironmentSampleSerialForProject($project->id, $project->code);

                foreach ($rows as $i => $row) {
                    $rowNumber = $i + 2;
                    $resolved = $this->resolveRow(is_array($row) ? $row : [], $rowNumber);

                    if (! empty($resolved['warnings'])) {
                        $this->globalWarnings[] = "Row {$rowNumber}: ".implode(' | ', $resolved['warnings']);
                    }

                    if (! empty($resolved['issues'])) {
                        $errors++;
                        $this->globalIssues[] = "Row {$rowNumber}: ".implode(' | ', $resolved['issues']);

                        continue;
                    }

                    $typeAttrs = ['category' => $resolved['environment_sample_type_category']];
                    $environmentSampleTypeId = $service->check_or_create(
                        EnvironmentSampleTypes::class,
                        ['name' => $resolved['environment_sample_type']],
                        $typeAttrs
                    );

                    $collector = People::query()
                        ->whereRaw('lower(email) = ?', [strtolower($resolved['collector_email'])])
                        ->first();
                    if (! $collector) {
                        $collector = People::query()->create([
                            'first_name' => $resolved['collector_first_name'],
                            'last_name' => $resolved['collector_last_name'],
                            'email' => $resolved['collector_email'],
                        ]);
                    }

                    $samplingSiteId = $this->resolveOrCreateSamplingSiteId($service, $resolved['sampling_site'], $resolved['sampling_site_country']);
                    if ($samplingSiteId === null) {
                        $errors++;
                        $this->globalIssues[] = "Row {$rowNumber}: unable to resolve/create sampling_site: {$resolved['sampling_site']}";

                        continue;
                    }

                    $locationId = $this->resolveOrCreateLocationId($service, $resolved['location'], $resolved['location_lab']);
                    if ($locationId === null) {
                        $errors++;
                        $this->globalIssues[] = "Row {$rowNumber}: unable to resolve/create location: {$resolved['location']}";

                        continue;
                    }

                    $code = $project->code.'-ES-'.$nextSerial;
                    $nextSerial++;

                    EnvironmentSamples::query()->create([
                        'code' => $code,
                        'field_label' => $resolved['field_label'] !== '' ? $resolved['field_label'] : null,
                        'environment_sample_types_id' => $environmentSampleTypeId,
                        'date_collected' => $resolved['date_collected'],
                        'sampling_sites_id' => $samplingSiteId,
                        'area' => $resolved['area'] ?: null,
                        'latitude' => $resolved['latitude'] !== '' ? (float) $resolved['latitude'] : null,
                        'longitude' => $resolved['longitude'] !== '' ? (float) $resolved['longitude'] : null,
                        'locations_id' => $locationId,
                        'people_id' => $collector->id,
                        'projects_id' => $project->id,
                        'processed' => false,
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
        $successMessage = "{$created} environment samples imported successfully.";
        session()->flash('success', $successMessage);
        NotificationController::create(
            'environment_sample_created',
            'Bulk environment samples imported',
            $successMessage,
            '/samples/environment/list',
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
     *   environment_sample_type: string,
     *   environment_sample_type_category: string,
     *   field_label: string,
     *   date_collected: string,
     *   sampling_site: string,
     *   sampling_site_country: string,
     *   area: string,
     *   latitude: string,
     *   longitude: string,
     *   location: string,
     *   location_lab: string,
     *   collector_email: string,
     *   collector_first_name: string,
     *   collector_last_name: string,
     *   sample_type_exists: bool,
     *   sampling_site_exists: bool,
     *   location_exists: bool,
     *   location_lab_exists: bool,
     *   collector_exists: bool,
     *   field_warnings: array<string, array{text:string,suggested:string,options:list<string>}>,
     *   warnings: list<string>,
     *   issues: list<string>
     * }
     */
    private function resolveRow(array $row, ?int $rowNumber = null): array
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

        $sampleType = $this->normalizeWordsTitleCase($get('environment_sample_type'));
        $fieldLabel = $this->sanitizeCell($get('field_label'));
        $sampleTypeCategory = $this->normalizeEnvironmentSampleTypeCategory($get('environment_sample_type_category'));
        $dateCollected = $get('date_collected');
        $samplingSite = $this->normalizeWordsTitleCase($get('sampling_site'));
        $samplingSiteCountry = $this->normalizeWordsTitleCase($get('sampling_site_country'));
        $area = $this->sanitizeCell($get('area'));
        $latitude = $this->sanitizeCell($get('latitude'));
        $longitude = $this->sanitizeCell($get('longitude'));
        $location = $this->normalizeWordsTitleCase($get('location'));
        $locationLab = $this->normalizeWordsTitleCase($get('location_lab'));
        $collectorEmail = strtolower(trim($get('collector_email')));
        $collectorFirstName = $this->normalizeWordsTitleCase($get('collector_first_name'));
        $collectorLastName = $this->normalizeWordsTitleCase($get('collector_last_name'));

        $issues = [];
        $warnings = [];
        $fieldWarnings = [];

        if ($sampleType === '') {
            $issues[] = 'environment_sample_type is required';
        }
        if ($dateCollected === '') {
            $issues[] = 'date_collected is required';
        } elseif (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateCollected)) {
            $issues[] = 'date_collected must be YYYY-MM-DD';
        }
        if ($samplingSite === '') {
            $issues[] = 'sampling_site is required';
        }
        if ($location === '') {
            $issues[] = 'location is required';
        }
        if ($collectorEmail === '') {
            $issues[] = 'collector_email is required';
        }

        if ($latitude !== '' && ! is_numeric($latitude)) {
            $issues[] = 'latitude must be numeric';
        }
        if ($longitude !== '' && ! is_numeric($longitude)) {
            $issues[] = 'longitude must be numeric';
        }

        $sampleTypeExists = false;
        $existingSampleType = null;
        if ($sampleType !== '') {
            $existingSampleType = $this->findEnvironmentSampleTypeByFlexibleName($sampleType);
            $sampleTypeExists = $existingSampleType !== null;
            if ($sampleTypeExists) {
                $sampleType = (string) ($existingSampleType->name ?? $sampleType);
                $sampleTypeCategory = (string) ($existingSampleType->category ?? $sampleTypeCategory);
            } else {
                if ($sampleTypeCategory === '') {
                    $issues[] = 'environment_sample_type_category is required when environment_sample_type is new';
                }
            }

            $similarSampleTypes = $this->similarOptions(EnvironmentSampleTypes::query(), 'name', $sampleType);
            if (! empty($similarSampleTypes) && ! $sampleTypeExists) {
                $fieldWarnings['environment_sample_type'] = [
                    'text' => '≈ '.implode(' and ', $similarSampleTypes),
                    'suggested' => $similarSampleTypes[0],
                    'options' => $similarSampleTypes,
                ];
                $warnings[] = $fieldWarnings['environment_sample_type']['text'];
            }
        }

        $samplingSiteExists = false;
        if ($samplingSite !== '') {
            $samplingSiteExists = SamplingSites::query()
                ->whereRaw('lower(name) = ?', [strtolower($samplingSite)])
                ->exists();
            if (! $samplingSiteExists && $samplingSiteCountry === '') {
                $issues[] = 'sampling_site_country is required when sampling_site is new';
            }

            $similarSamplingSites = $this->similarOptions(SamplingSites::query(), 'name', $samplingSite);
            if (! empty($similarSamplingSites) && ! $samplingSiteExists) {
                $fieldWarnings['sampling_site'] = [
                    'text' => '≈ '.implode(' and ', $similarSamplingSites),
                    'suggested' => $similarSamplingSites[0],
                    'options' => $similarSamplingSites,
                ];
                $warnings[] = $fieldWarnings['sampling_site']['text'];
            }
        }

        if (! $samplingSiteExists && $samplingSiteCountry !== '') {
            $countryExists = Countries::query()
                ->whereRaw('lower(name) = ?', [strtolower($samplingSiteCountry)])
                ->exists();
            if (! $countryExists) {
                $similarCountries = $this->similarOptions(Countries::query(), 'name', $samplingSiteCountry);
                if (! empty($similarCountries)) {
                    $fieldWarnings['sampling_site_country'] = [
                        'text' => '≈ '.implode(' and ', $similarCountries),
                        'suggested' => $similarCountries[0],
                        'options' => $similarCountries,
                    ];
                    $warnings[] = $fieldWarnings['sampling_site_country']['text'];
                }
            }
        }

        $locationExists = false;
        if ($location !== '') {
            $locationExists = Locations::query()
                ->whereRaw('lower(name) = ?', [strtolower($location)])
                ->exists();
            if (! $locationExists && $locationLab === '') {
                $issues[] = 'location_lab is required when location is new';
            }

            $similarLocations = $this->similarOptions(Locations::query(), 'name', $location);
            if (! empty($similarLocations) && ! $locationExists) {
                $fieldWarnings['location'] = [
                    'text' => '≈ '.implode(' and ', $similarLocations),
                    'suggested' => $similarLocations[0],
                    'options' => $similarLocations,
                ];
                $warnings[] = $fieldWarnings['location']['text'];
            }
        }

        $locationLabExists = false;
        if ($locationLab !== '') {
            $locationLabExists = Laboratories::query()
                ->whereRaw('lower(name) = ?', [strtolower($locationLab)])
                ->exists();
            $similarLabs = $this->similarOptions(Laboratories::query(), 'name', $locationLab);
            if (! empty($similarLabs) && ! $locationLabExists) {
                $fieldWarnings['location_lab'] = [
                    'text' => '≈ '.implode(' and ', $similarLabs),
                    'suggested' => $similarLabs[0],
                    'options' => $similarLabs,
                ];
                $warnings[] = $fieldWarnings['location_lab']['text'];
            }
        }

        $collectorExists = false;
        if ($collectorEmail !== '') {
            $collectorExists = People::query()
                ->whereRaw('lower(email) = ?', [strtolower($collectorEmail)])
                ->exists();
            if (! $collectorExists && ($collectorFirstName === '' || $collectorLastName === '')) {
                $issues[] = "collector_email not found: {$collectorEmail} (provide collector_first_name and collector_last_name)";
            }

            $similarCollectorEmails = $this->similarEmailOptions($collectorEmail);
            if (! empty($similarCollectorEmails) && ! $collectorExists) {
                $fieldWarnings['collector_email'] = [
                    'text' => '≈ '.implode(' and ', $similarCollectorEmails),
                    'suggested' => $similarCollectorEmails[0],
                    'options' => $similarCollectorEmails,
                ];
                $warnings[] = $fieldWarnings['collector_email']['text'];
            }
        }

        return [
            'row_number' => $rowNumber ?? 0,
            'environment_sample_type' => $sampleType,
            'field_label' => $fieldLabel,
            'environment_sample_type_category' => $sampleTypeCategory,
            'date_collected' => $dateCollected,
            'sampling_site' => $samplingSite,
            'sampling_site_country' => $samplingSiteCountry,
            'area' => $area,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'location' => $location,
            'location_lab' => $locationLab,
            'collector_email' => $collectorEmail,
            'collector_first_name' => $collectorFirstName,
            'collector_last_name' => $collectorLastName,
            'sample_type_exists' => $sampleTypeExists,
            'sampling_site_exists' => $samplingSiteExists,
            'location_exists' => $locationExists,
            'location_lab_exists' => $locationLabExists,
            'collector_exists' => $collectorExists,
            'field_warnings' => $fieldWarnings,
            'warnings' => $warnings,
            'issues' => $issues,
        ];
    }

    private function normalizeEnvironmentSampleTypeCategory(string $value): string
    {
        $value = mb_strtolower($this->sanitizeCell($value));
        if ($value === '') {
            return '';
        }

        return match ($value) {
            'air' => 'air',
            'water' => 'water',
            'soil' => 'soil',
            'surface', 'swab' => 'surface',
            'other' => 'other',
            default => $value,
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

    private function findEnvironmentSampleTypeByFlexibleName(string $sampleType): ?EnvironmentSampleTypes
    {
        $sampleType = mb_strtolower($this->sanitizeCell($sampleType));
        if ($sampleType === '') {
            return null;
        }

        $canonical = preg_replace('/[\s_-]+/', ' ', $sampleType) ?? $sampleType;
        $canonical = trim($canonical);

        $all = EnvironmentSampleTypes::query()->select('id', 'name', 'category')->get();
        foreach ($all as $candidate) {
            $candidateCanonical = mb_strtolower($this->sanitizeCell((string) $candidate->name));
            $candidateCanonical = preg_replace('/[\s_-]+/', ' ', $candidateCanonical) ?? $candidateCanonical;
            $candidateCanonical = trim($candidateCanonical);
            if ($candidateCanonical === $canonical) {
                return $candidate;
            }
        }

        return null;
    }

    private function resolveOrCreateSamplingSiteId(EnvironmentSamplesService $service, string $name, string $countryName = ''): ?int
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $existing = SamplingSites::query()
            ->whereRaw('lower(name) = ?', [strtolower($name)])
            ->first();
        if ($existing) {
            return (int) $existing->id;
        }

        $countryName = trim($countryName);
        if ($countryName === '') {
            return null;
        }

        $countryId = (int) $service->check_or_create(Countries::class, ['name' => $countryName]);
        $site = SamplingSites::query()->create([
            'name' => $name,
            'countries_id' => $countryId,
        ]);

        return (int) $site->id;
    }

    private function resolveOrCreateLocationId(EnvironmentSamplesService $service, string $locationName, string $labName = ''): ?int
    {
        $locationName = trim($locationName);
        if ($locationName === '') {
            return null;
        }

        $existing = Locations::query()
            ->whereRaw('lower(name) = ?', [strtolower($locationName)])
            ->first();
        if ($existing) {
            return (int) $existing->id;
        }

        $labName = trim($labName);
        if ($labName === '') {
            return null;
        }

        $lab = Laboratories::query()
            ->whereRaw('lower(name) = ?', [strtolower($labName)])
            ->first();
        if (! $lab) {
            $labId = (int) $service->check_or_create(Laboratories::class, ['name' => $labName]);
            $lab = Laboratories::find($labId);
        }
        if (! $lab) {
            return null;
        }

        return (int) $service->check_or_create(
            Locations::class,
            ['name' => $locationName],
            ['laboratories_id' => $lab->id]
        );
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
            $tokenScore = $this->tokenOverlapScore($valueCanonical, $candidateCanonical);
            $prefixScore = $this->tokenPrefixScore($valueCanonical, $candidateCanonical);
            $score = max($pct, $tokenScore, $prefixScore);

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

    /**
     * @return list<string>
     */
    private function similarEmailOptions(string $email, int $limit = 2): array
    {
        $email = mb_strtolower($this->sanitizeCell($email));
        if ($email === '' || ! str_contains($email, '@')) {
            return [];
        }

        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($local === '' || $domain === '') {
            return [];
        }

        $candidates = $this->projectPeopleEmailsQuery($this->selectedProjectId())
            ->whereRaw('lower(email) like ?', ['%@'.$domain])
            ->pluck('email')
            ->filter()
            ->map(fn ($v) => (string) $v)
            ->values()
            ->all();

        $scored = [];
        foreach ($candidates as $candidateEmail) {
            $candidateLower = mb_strtolower($this->sanitizeCell($candidateEmail));
            if ($candidateLower === $email || ! str_contains($candidateLower, '@')) {
                continue;
            }

            [$candidateLocal, $candidateDomain] = array_pad(explode('@', $candidateLower, 2), 2, '');
            if ($candidateDomain !== $domain || $candidateLocal === '') {
                continue;
            }

            similar_text($local, $candidateLocal, $pct);
            if ($pct >= 72.0) {
                $scored[] = ['email' => $candidateEmail, 'score' => $pct];
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        $result = [];
        foreach ($scored as $entry) {
            $result[] = (string) $entry['email'];
            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }

    private function canonicalEntityName(string $value): string
    {
        $normalized = mb_strtolower($this->sanitizeCell($value));
        $normalized = str_replace(['.', ',', ';', ':', '-', '_', '/', '\\', '(', ')'], ' ', $normalized);
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        $tokens = preg_split('/\s+/', trim($normalized), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $tokens = array_values(array_unique($tokens));
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
                if ($shortToken === $longToken || str_starts_with($longToken, $shortToken) || str_starts_with($shortToken, $longToken)) {
                    $matched++;
                    break;
                }
            }
        }

        return (count($shorter) > 0) ? (($matched / count($shorter)) * 100) : 0.0;
    }

    private function nextEnvironmentSampleSerialForProject(int $projectId, string $projectCode): int
    {
        $codes = EnvironmentSamples::query()
            ->where('projects_id', $projectId)
            ->where('code', 'like', $projectCode.'-ES-%')
            ->pluck('code');

        $used = $codes->map(function ($code) {
            preg_match('/-ES-(\d+)$/', (string) $code, $m);

            return isset($m[1]) ? (int) $m[1] : null;
        })->filter()->sort()->values();

        $serial = 1;
        foreach ($used as $n) {
            if ($n !== $serial) {
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
            'environment_sample_type',
            'date_collected',
            'sampling_site',
            'location',
            'collector_email',
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private function synonymsByField(): array
    {
        return [
            'environment_sample_type' => ['environment_sample_type', 'environment sample type', 'sample_type', 'sample type'],
            'field_label' => ['field_label', 'field label', 'label', 'sample_label', 'sample label'],
            'environment_sample_type_category' => ['environment_sample_type_category', 'environment category', 'sample_type_category', 'category'],
            'date_collected' => ['date_collected', 'date', 'collection_date', 'collected_date'],
            'sampling_site' => ['sampling_site', 'sampling site', 'site'],
            'sampling_site_country' => ['sampling_site_country', 'sampling site country', 'site_country', 'country'],
            'area' => ['area', 'sampling_area'],
            'latitude' => ['latitude', 'lat'],
            'longitude' => ['longitude', 'lon', 'lng'],
            'location' => ['location', 'storage_location', 'location_name'],
            'location_lab' => ['location_lab', 'lab', 'laboratory', 'laboratory_name'],
            'collector_email' => ['collector_email', 'collector', 'collected_by_email', 'people_email'],
            'collector_first_name' => ['collector_first_name', 'collector first name', 'collector_first'],
            'collector_last_name' => ['collector_last_name', 'collector last name', 'collector_last'],
        ];
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
        foreach ($slice as $row) {
            $rowNumber = $offset + count($mapped) + 2;
            $mapped[] = $this->resolveRow(is_array($row) ? $row : [], $rowNumber);
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
        return view('livewire.imports.environment-samples-import', [
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
            'imports:environment_samples:template_options:'.($projectId ?? 0),
            now()->addMinutes(30),
            function () use ($projectId): array {
                return [
                    'environment_types' => $this->optionPreview(EnvironmentSampleTypes::query()->orderBy('name'), 'name'),
                    'countries' => $this->optionPreview(Countries::query()->orderBy('name'), 'name'),
                    'sampling_sites' => $this->optionPreview(SamplingSites::query()->orderBy('name'), 'name'),
                    'laboratories' => $this->optionPreview(Laboratories::query()->orderBy('name'), 'name'),
                    'locations' => $this->optionPreview(Locations::query()->orderBy('name'), 'name'),
                    'collector_emails' => $this->optionPreview(
                        $this->projectPeopleEmailsQuery($projectId)->orderBy('email'),
                        'email'
                    ),
                ];
            }
        );

        $aliases = $this->synonymsByField();
        $categorySuggestions = ['air', 'water', 'soil', 'surface', 'other'];

        $columns = [
            [
                'header' => 'environment_sample_type',
                'field' => 'environment_sample_type',
                'required' => 'required',
                'description' => 'Environment sample type (e.g. Water).',
                'format' => 'Text (case-insensitive match).',
                'accepted' => [],
                'aliases' => $aliases['environment_sample_type'] ?? [],
                'create_policy' => 'Links to existing EnvironmentSampleTypes by flexible match; otherwise creates a new environment sample type.',
                'create_notes' => 'If new, environment_sample_type_category is required.',
                'example' => 'Water',
                'options' => $options['environment_types']['values'],
                'options_total' => $options['environment_types']['total'],
            ],
            [
                'header' => 'environment_sample_type_category',
                'field' => 'environment_sample_type_category',
                'required' => 'conditional',
                'description' => 'Category for a newly created environment sample type.',
                'format' => 'Free text (commonly: air, water, soil, surface, other).',
                'accepted' => $categorySuggestions,
                'aliases' => $aliases['environment_sample_type_category'] ?? [],
                'create_policy' => 'Required only when environment_sample_type is new.',
                'create_notes' => '',
                'example' => 'water',
                'options' => $categorySuggestions,
                'options_total' => count($categorySuggestions),
            ],
            [
                'header' => 'field_label',
                'field' => 'field_label',
                'required' => 'optional',
                'description' => 'Optional field label (stored as provided).',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['field_label'] ?? [],
                'create_policy' => 'Optional.',
                'create_notes' => '',
                'example' => 'ES-Field-01',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'date_collected',
                'field' => 'date_collected',
                'required' => 'required',
                'description' => 'Collection date.',
                'format' => 'YYYY-MM-DD.',
                'accepted' => [],
                'aliases' => $aliases['date_collected'] ?? [],
                'create_policy' => 'Required.',
                'create_notes' => '',
                'example' => now()->toDateString(),
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'sampling_site',
                'field' => 'sampling_site',
                'required' => 'required',
                'description' => 'Sampling site name.',
                'format' => 'Text (case-insensitive match).',
                'accepted' => [],
                'aliases' => $aliases['sampling_site'] ?? [],
                'create_policy' => 'Links to existing SamplingSites by name; otherwise creates a new sampling site.',
                'create_notes' => 'If new, sampling_site_country is required.',
                'example' => 'Okavango Site A',
                'options' => $options['sampling_sites']['values'],
                'options_total' => $options['sampling_sites']['total'],
            ],
            [
                'header' => 'sampling_site_country',
                'field' => 'sampling_site_country',
                'required' => 'conditional',
                'description' => 'Country for a newly created sampling site.',
                'format' => 'Text (country name).',
                'accepted' => [],
                'aliases' => $aliases['sampling_site_country'] ?? [],
                'create_policy' => 'Required only when sampling_site is new. Links to Countries by name; otherwise creates the country record.',
                'create_notes' => '',
                'example' => 'Botswana',
                'options' => $options['countries']['values'],
                'options_total' => $options['countries']['total'],
            ],
            [
                'header' => 'location',
                'field' => 'location',
                'required' => 'required',
                'description' => 'Storage location name.',
                'format' => 'Text (case-insensitive match).',
                'accepted' => [],
                'aliases' => $aliases['location'] ?? [],
                'create_policy' => 'Links to existing Locations by name; otherwise creates a new location.',
                'create_notes' => 'If new, location_lab is required.',
                'example' => 'Freezer B Shelf 4',
                'options' => $options['locations']['values'],
                'options_total' => $options['locations']['total'],
            ],
            [
                'header' => 'location_lab',
                'field' => 'location_lab',
                'required' => 'conditional',
                'description' => 'Laboratory for a newly created location.',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['location_lab'] ?? [],
                'create_policy' => 'Required only when location is new. Links to existing Laboratories by name; otherwise creates the lab record.',
                'create_notes' => '',
                'example' => 'Hydrology Lab',
                'options' => $options['laboratories']['values'],
                'options_total' => $options['laboratories']['total'],
            ],
            [
                'header' => 'collector_email',
                'field' => 'collector_email',
                'required' => 'required',
                'description' => 'Collector email address.',
                'format' => 'Email.',
                'accepted' => [],
                'aliases' => $aliases['collector_email'] ?? [],
                'create_policy' => 'Links to existing People by email; otherwise creates a new person.',
                'create_notes' => 'If new, collector_first_name and collector_last_name are required.',
                'example' => 'collector@example.org',
                'options' => $options['collector_emails']['values'],
                'options_total' => $options['collector_emails']['total'],
            ],
            [
                'header' => 'collector_first_name',
                'field' => 'collector_first_name',
                'required' => 'conditional',
                'description' => 'Collector first name (for new collector_email).',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['collector_first_name'] ?? [],
                'create_policy' => 'Required only when collector_email is new.',
                'create_notes' => '',
                'example' => 'Mario',
                'options' => [],
                'options_total' => 0,
            ],
            [
                'header' => 'collector_last_name',
                'field' => 'collector_last_name',
                'required' => 'conditional',
                'description' => 'Collector last name (for new collector_email).',
                'format' => 'Text.',
                'accepted' => [],
                'aliases' => $aliases['collector_last_name'] ?? [],
                'create_policy' => 'Required only when collector_email is new.',
                'create_notes' => '',
                'example' => 'Rossi',
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
     * @return array{title:string, values:list<string>, total:int, truncated:bool}
     */
    private function templateOptionsForField(string $field, ?int $projectId): array
    {
        $field = trim((string) $field);
        $max = 1200;

        return match ($field) {
            'environment_sample_type' => $this->templateOptionsFromQuery(EnvironmentSampleTypes::query(), 'name', 'Environment sample types', $max),
            'sampling_site' => $this->templateOptionsFromQuery(SamplingSites::query(), 'name', 'Sampling sites', $max),
            'sampling_site_country' => $this->templateOptionsFromQuery(Countries::query(), 'name', 'Countries', $max),
            'location' => $this->templateOptionsFromQuery(Locations::query(), 'name', 'Locations', $max),
            'location_lab' => $this->templateOptionsFromQuery(Laboratories::query(), 'name', 'Laboratories', $max),
            'collector_email' => $this->templateOptionsFromQuery(
                $this->projectPeopleEmailsQuery($projectId),
                'email',
                'Collector emails',
                $max
            ),
            default => [
                'title' => 'Values',
                'values' => [],
                'total' => 0,
                'truncated' => false,
            ],
        };
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
