<?php

namespace App\Support;

use App\Models\SubProject;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectPermission
{
    private static function normalizePermission(?string $permission): string
    {
        return strtolower(trim((string) $permission));
    }

    /**
     * @return array<string, string>
     */
    public static function moduleOptions(): array
    {
        return [
            'animal_samples' => 'Animal samples',
            'human_samples' => 'Human samples',
            'environment_samples' => 'Environment samples',
            'parasite_samples' => 'Parasite samples',
            'experiments' => 'Experiments',
            'nucleic_acids' => 'Nucleic acids',
            'microplastics' => 'Microplastics',
            'cultures' => 'Cultures',
            'pools' => 'Pools',
            'tubes' => 'Tubes',
            'tube_positions' => 'Tube positions',
            'box_positions' => 'Box positions',
            'literature' => 'Literature / meta',
        ];
    }

    public static function canAssignRegistrar(User $user, int $projectId): bool
    {
        $membership = self::membership($user, $projectId);

        return self::normalizePermission($membership['permission'] ?? null) === 'admin';
    }

    public static function canManageSubProjects(User $user, int $projectId): bool
    {
        return self::canAssignRegistrar($user, $projectId);
    }

    public static function canDeleteProject(User $user, int $projectId): bool
    {
        return self::canAssignRegistrar($user, $projectId);
    }

    public static function currentRegistrarPeopleId(User $user): ?int
    {
        return $user->people ? (int) $user->people->id : null;
    }

    /**
     * @return array{permission: string|null, modules: array<int, string>, module_matrix: array<string, array{view: bool, edit: bool}>}
     */
    public static function membership(User $user, int $projectId): array
    {
        $person = $user->people;
        if (! $person) {
            return [
                'permission' => null,
                'modules' => [],
                'module_matrix' => self::defaultModuleMatrix(null),
            ];
        }

        $project = $person->projects()
            ->where('projects.id', $projectId)
            ->withPivot('permission', 'module_permissions')
            ->first();

        if (! $project || ! $project->pivot) {
            return [
                'permission' => null,
                'modules' => [],
                'module_matrix' => self::defaultModuleMatrix(null),
            ];
        }

        $permission = $project->pivot->permission;
        $moduleMatrix = self::resolveModuleMatrix(
            self::normalizePermission($permission),
            $project->pivot->module_permissions
        );

        $editableModules = array_keys(array_filter(
            $moduleMatrix,
            static fn (array $access): bool => $access['edit'] === true
        ));

        return [
            'permission' => $permission,
            'modules' => array_values($editableModules),
            'module_matrix' => $moduleMatrix,
        ];
    }

    /**
     * @return array<string, array{view: bool, edit: bool}>
     */
    public static function moduleMatrixForUser(User $user, int $projectId): array
    {
        return self::membership($user, $projectId)['module_matrix'];
    }

    /**
     * @return array<string, array{view: bool, edit: bool}>
     */
    public static function moduleAccessFlags(User $user, int $projectId): array
    {
        return self::moduleMatrixForUser($user, $projectId);
    }

    public static function canView(User $user, int $projectId, ?string $module = null): bool
    {
        $membership = self::membership($user, $projectId);
        $permission = self::normalizePermission($membership['permission']);

        if ($permission === 'admin') {
            return true;
        }

        if ($permission === '' || $permission === null) {
            return false;
        }

        $matrix = $membership['module_matrix'];

        if ($module === null) {
            return collect($matrix)->contains(static fn (array $access): bool => $access['view'] || $access['edit']);
        }

        $access = $matrix[$module] ?? ['view' => false, 'edit' => false];

        return $access['view'] || $access['edit'];
    }

    public static function canWrite(User $user, int $projectId, ?string $module = null): bool
    {
        $membership = self::membership($user, $projectId);
        $permission = self::normalizePermission($membership['permission']);

        if ($permission === 'admin') {
            return true;
        }

        if ($permission === '' || $permission === null) {
            return false;
        }

        $matrix = $membership['module_matrix'];

        if ($module === null) {
            return collect($matrix)->contains(static fn (array $access): bool => $access['edit'] === true);
        }

        return ($matrix[$module]['edit'] ?? false) === true;
    }

    public static function canEditOrDelete(User $user, int $projectId, ?int $ownerPeopleId, ?string $module = null): bool
    {
        $membership = self::membership($user, $projectId);
        $permission = self::normalizePermission($membership['permission']);

        if ($permission === 'admin') {
            return true;
        }

        if (! self::canWrite($user, $projectId, $module)) {
            return false;
        }

        $currentPeopleId = self::currentRegistrarPeopleId($user);

        return $currentPeopleId !== null && $ownerPeopleId !== null && $currentPeopleId === (int) $ownerPeopleId;
    }

    public static function detectModule(Request $request): ?string
    {
        $path = trim($request->path(), '/');

        return match (true) {
            str_starts_with($path, 'samples/animals') => 'animal_samples',
            str_starts_with($path, 'animals') => 'animal_samples',
            str_starts_with($path, 'samples/humans') => 'human_samples',
            str_starts_with($path, 'samples/environment') => 'environment_samples',
            str_starts_with($path, 'samples/parasites') => 'parasite_samples',
            str_starts_with($path, 'experiments') => 'experiments',
            str_starts_with($path, 'samples/nucleic') => 'nucleic_acids',
            str_starts_with($path, 'samples/microplastics') => 'microplastics',
            str_starts_with($path, 'samples/cultures') => 'cultures',
            str_starts_with($path, 'samples/pools') => 'pools',
            str_starts_with($path, 'bank/tubes') => 'tube_positions',
            str_starts_with($path, 'samples/process') => 'tubes',
            str_starts_with($path, 'bank/boxes') => 'box_positions',
            str_starts_with($path, 'meta') => 'literature',
            default => null,
        };
    }

    /**
     * @return array<int, SubProject>
     */
    public static function subProjectsForRegistrar(User $user, int $projectId): array
    {
        $person = $user->people;
        if (! $person) {
            return [];
        }

        return SubProject::query()
            ->where('project_id', $projectId)
            ->whereHas('people', function ($query) use ($person) {
                $query->where('people.id', $person->id);
            })
            ->orderBy('code')
            ->get()
            ->all();
    }

    public static function requiresSubProjectSelection(User $user, int $projectId): bool
    {
        return SubProjectFlag::requiresSelection($user, $projectId);
    }

    /**
     * @return array<string, array{view: bool, edit: bool}>
     */
    public static function matrixForMembership(?string $permission, mixed $rawModules): array
    {
        return self::resolveModuleMatrix(self::normalizePermission($permission), $rawModules);
    }

    /**
     * @param  array<string, mixed>|null  $input
     * @return string JSON encoded value for storage
     */
    public static function encodeModulePermissionsForStorage(string $permission, ?array $input): string
    {
        $permission = self::normalizePermission($permission);

        if ($permission === 'admin') {
            return json_encode([]);
        }

        $matrix = self::sanitizeModuleMatrixInput($input ?? []);

        if (self::isUnrestrictedMatrix($matrix, $permission)) {
            return json_encode([]);
        }

        return json_encode($matrix);
    }

    /**
     * @param  array<string, mixed>|null  $input
     * @return array<string, array{view: bool, edit: bool}>
     */
    public static function sanitizeModuleMatrixInput(?array $input): array
    {
        $allowed = array_keys(self::moduleOptions());
        $matrix = [];

        foreach ($allowed as $module) {
            $row = is_array($input[$module] ?? null) ? $input[$module] : [];
            $view = filter_var($row['view'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $edit = filter_var($row['edit'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($edit) {
                $view = true;
            }

            $matrix[$module] = [
                'view' => $view,
                'edit' => $edit,
            ];
        }

        return $matrix;
    }

    /**
     * @param  array<string, array{view: bool, edit: bool}>  $matrix
     */
    public static function isUnrestrictedMatrix(array $matrix, string $permission): bool
    {
        $permission = self::normalizePermission($permission);

        if ($permission === 'admin') {
            return true;
        }

        $defaults = self::defaultModuleMatrix($permission);

        return $matrix === $defaults;
    }

    /**
     * @return array<string, array{view: bool, edit: bool}>
     */
    public static function defaultModuleMatrix(?string $permission): array
    {
        $permission = self::normalizePermission($permission);
        $canEditByRole = in_array($permission, ['admin', 'editor'], true);

        return collect(self::moduleOptions())
            ->mapWithKeys(static fn (string $label, string $module): array => [
                $module => [
                    'view' => $permission !== '' && $permission !== null,
                    'edit' => $canEditByRole,
                ],
            ])
            ->all();
    }

    /**
     * @return array<string, array{view: bool, edit: bool}>
     */
    private static function resolveModuleMatrix(string $permission, mixed $rawModules): array
    {
        if ($permission === 'admin') {
            return self::defaultModuleMatrix('admin');
        }

        $parsed = self::decodeRawModulePermissions($rawModules);

        if ($parsed === null) {
            return self::defaultModuleMatrix($permission);
        }

        if (isset($parsed['__legacy_write_modules__'])) {
            return self::matrixFromLegacyWriteModules($permission, $parsed['__legacy_write_modules__']);
        }

        return self::matrixFromExplicitInput($permission, $parsed);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function decodeRawModulePermissions(mixed $rawModules): ?array
    {
        if ($rawModules === null || $rawModules === '') {
            return null;
        }

        $decoded = is_array($rawModules) ? $rawModules : json_decode((string) $rawModules, true);

        if (! is_array($decoded) || $decoded === []) {
            return null;
        }

        if (array_is_list($decoded)) {
            $legacyModules = array_values(array_filter(
                $decoded,
                static fn ($value): bool => is_string($value) && trim($value) !== ''
            ));

            if ($legacyModules === []) {
                return null;
            }

            return ['__legacy_write_modules__' => $legacyModules];
        }

        return $decoded;
    }

    /**
     * @param  array<int, string>  $legacyWriteModules
     * @return array<string, array{view: bool, edit: bool}>
     */
    private static function matrixFromLegacyWriteModules(string $permission, array $legacyWriteModules): array
    {
        $allowed = array_keys(self::moduleOptions());
        $writeModules = array_values(array_intersect($legacyWriteModules, $allowed));
        $defaults = self::defaultModuleMatrix($permission);

        foreach ($allowed as $module) {
            $canWrite = in_array($module, $writeModules, true);
            $defaults[$module] = [
                'view' => true,
                'edit' => $canWrite,
            ];
        }

        return $defaults;
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array<string, array{view: bool, edit: bool}>
     */
    private static function matrixFromExplicitInput(string $permission, array $parsed): array
    {
        $matrix = self::sanitizeModuleMatrixInput($parsed);

        if (self::isUnrestrictedMatrix($matrix, $permission)) {
            return self::defaultModuleMatrix($permission);
        }

        return $matrix;
    }
}
