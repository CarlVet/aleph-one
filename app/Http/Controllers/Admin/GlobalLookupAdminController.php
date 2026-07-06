<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminLookupRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GlobalLookupAdminController extends Controller
{
    public function index(): View
    {
        $lookups = collect(AdminLookupRegistry::all())
            ->map(function (array $definition, string $lookup): array {
                $modelClass = $definition['model'];

                return [
                    'lookup' => $lookup,
                    'title' => $definition['title'],
                    'count' => $modelClass::query()->count(),
                ];
            })
            ->sortBy('title')
            ->values();

        return view('admin.lookups.index', [
            'lookups' => $lookups,
        ]);
    }

    public function show(Request $request, string $lookup): View
    {
        $definition = AdminLookupRegistry::get($lookup);
        $modelClass = $definition['model'];
        $search = trim((string) $request->string('q'));
        $query = $modelClass::query()->with(AdminLookupRegistry::eagerLoadRelations($lookup));

        if ($search !== '') {
            $query->where(function ($builder) use ($definition, $search): void {
                foreach ($definition['search_columns'] as $index => $column) {
                    if ($index === 0) {
                        $builder->where($column, 'like', "%{$search}%");
                    } else {
                        $builder->orWhere($column, 'like', "%{$search}%");
                    }
                }
            });
        }

        $records = $query
            ->orderBy($definition['label_column'])
            ->paginate(25)
            ->withQueryString();

        $usageById = [];
        foreach ($records as $record) {
            $usageById[$record->getKey()] = AdminLookupRegistry::linkedUsage($record, $lookup);
        }

        return view('admin.lookups.list', [
            'definition' => $definition,
            'lookup' => $lookup,
            'records' => $records,
            'usageById' => $usageById,
            'search' => $search,
        ]);
    }

    public function create(string $lookup): View
    {
        return view('admin.lookups.create', [
            'definition' => AdminLookupRegistry::get($lookup),
            'lookup' => $lookup,
            'record' => null,
            'selectOptions' => AdminLookupRegistry::selectOptions($lookup),
            'linkedUsage' => [],
            'linkedTotal' => 0,
            'editConfirmMessage' => null,
        ]);
    }

    public function store(Request $request, string $lookup): RedirectResponse
    {
        $definition = AdminLookupRegistry::get($lookup);
        $modelClass = $definition['model'];
        $validated = $request->validate(AdminLookupRegistry::validationRules($lookup));
        $payload = AdminLookupRegistry::preparePayload($validated, $lookup);

        $modelClass::query()->create($payload);

        return redirect()
            ->route('admin.lookups.show', $lookup)
            ->with('success', $definition['title'].' entry created.');
    }

    public function edit(string $lookup, int $id): View
    {
        $definition = AdminLookupRegistry::get($lookup);
        $record = $this->resolveRecord($definition['model'], $id, $lookup);
        $linkedUsage = AdminLookupRegistry::linkedUsage($record, $lookup);
        $linkedTotal = collect($linkedUsage)->sum('count');

        return view('admin.lookups.edit', [
            'definition' => $definition,
            'lookup' => $lookup,
            'record' => $record,
            'selectOptions' => AdminLookupRegistry::selectOptions($lookup),
            'linkedUsage' => $linkedUsage,
            'linkedTotal' => $linkedTotal,
            'editConfirmMessage' => $linkedTotal > 0
                ? 'This value is linked to '.$linkedTotal.' existing records. Editing it will affect linked data. Continue?'
                : null,
        ]);
    }

    public function update(Request $request, string $lookup, int $id): RedirectResponse
    {
        $definition = AdminLookupRegistry::get($lookup);
        $record = $this->resolveRecord($definition['model'], $id, $lookup);
        $validated = $request->validate(AdminLookupRegistry::validationRules($lookup, $record));

        $record->update($validated);

        return redirect()
            ->route('admin.lookups.show', $lookup)
            ->with('success', $definition['title'].' entry updated.');
    }

    public function destroy(string $lookup, int $id): RedirectResponse
    {
        $definition = AdminLookupRegistry::get($lookup);
        $record = $this->resolveRecord($definition['model'], $id, $lookup);
        $linkedUsage = AdminLookupRegistry::linkedUsage($record, $lookup);

        if ($linkedUsage !== []) {
            $details = collect($linkedUsage)
                ->map(fn (array $usage): string => $usage['count'].' '.$usage['label'])
                ->implode(', ');

            return redirect()
                ->route('admin.lookups.show', $lookup)
                ->with('error', 'Deletion blocked. This value is still linked to '.$details.'.');
        }

        try {
            $record->delete();
        } catch (QueryException) {
            return redirect()
                ->route('admin.lookups.show', $lookup)
                ->with('error', 'Deletion blocked. This value is still linked to existing data.');
        }

        return redirect()
            ->route('admin.lookups.show', $lookup)
            ->with('success', $definition['title'].' entry deleted.');
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function resolveRecord(string $modelClass, int $id, string $lookup): Model
    {
        return $modelClass::query()
            ->with(AdminLookupRegistry::eagerLoadRelations($lookup))
            ->findOrFail($id);
    }
}
