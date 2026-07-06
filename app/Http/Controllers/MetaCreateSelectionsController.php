<?php

namespace App\Http\Controllers;

use App\Models\Studies;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MetaCreateSelectionsController extends Controller
{
    public function studies(Request $request): View
    {
        $filters = (array) $request->query('filters', []);

        $query = Studies::query()->orderByDesc('id');

        $this->applyStudiesFilters($query, $filters);

        $studies = $query->paginate(25, pageName: 'studies_page');
        $paginationPath = route('meta.create.studies');

        return view('meta.modals.studies_selection', compact('studies', 'paginationPath', 'filters'));
    }

    public function studiesSearch(Request $request): JsonResponse
    {
        $q = (string) $request->query('q', '');

        $results = Studies::query()
            ->when($q !== '', function (Builder $query) use ($q) {
                $query->where(function (Builder $q2) use ($q) {
                    $q2->where('ref_key', 'like', '%'.$q.'%')
                        ->orWhere('title', 'like', '%'.$q.'%')
                        ->orWhere('doi', 'like', '%'.$q.'%');
                });
            })
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'ref_key', 'title'])
            ->map(fn ($row) => [
                'value' => $row->id,
                'text' => $row->ref_key,
            ])
            ->values();

        return response()->json($results);
    }

    private function applyStudiesFilters(Builder $query, array $filters): void
    {
        $filters = array_filter(
            $filters,
            fn ($value) => is_string($value) ? trim($value) !== '' : false
        );

        if (! $filters) {
            return;
        }

        // Column indexes:
        // 0 select, 1 ref_key, 2 title, 3 publication_year, 4 doi
        if (! empty($filters[1])) {
            $query->where('ref_key', 'like', '%'.trim((string) $filters[1]).'%');
        }

        if (! empty($filters[2])) {
            $query->where('title', 'like', '%'.trim((string) $filters[2]).'%');
        }

        if (! empty($filters[3])) {
            $value = trim((string) $filters[3]);
            $query->whereRaw('CAST(publication_year as TEXT) like ?', ['%'.$value.'%']);
        }

        if (! empty($filters[4])) {
            $query->where('doi', 'like', '%'.trim((string) $filters[4]).'%');
        }
    }
}
