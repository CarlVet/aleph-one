<?php

namespace App\Http\Controllers;

use App\Models\Tubes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TubePositionsDashboardMapPointsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $projectId = session('selected_project_id');
        $isGuestMode = $projectId === null;

        $cursor = (int) $request->query('cursor', 0);
        $limit = max(100, min((int) $request->query('limit', 1500), 2000));

        $laboratoryFilter = (string) $request->query('laboratoryFilter', '');
        $locationFilter = (string) $request->query('locationFilter', '');
        $boxFilter = (string) $request->query('boxFilter', '');
        $contentTypeFilter = (string) $request->query('contentTypeFilter', '');
        $subProjectFilter = (string) $request->query('subProjectFilter', '');
        $startDate = (string) $request->query('startDate', '');
        $endDate = (string) $request->query('endDate', '');

        $ltp = DB::table('tube_positions')
            ->select('tubes_id', DB::raw('MAX(id) as latest_id'))
            ->groupBy('tubes_id');

        $lbp = DB::table('box_positions')
            ->select('boxes_id', DB::raw('MAX(id) as latest_id'))
            ->groupBy('boxes_id');

        $query = DB::table('tubes')
            ->joinSub($ltp, 'ltp', 'tubes.id', '=', 'ltp.tubes_id')
            ->join('tube_positions as tp', 'tp.id', '=', 'ltp.latest_id')
            ->join('boxes', 'boxes.id', '=', 'tp.boxes_id')
            ->joinSub($lbp, 'lbp', 'boxes.id', '=', 'lbp.boxes_id')
            ->join('box_positions as bp', 'bp.id', '=', 'lbp.latest_id')
            ->join('locations', 'locations.id', '=', 'bp.locations_id')
            ->join('laboratories', 'laboratories.id', '=', 'locations.laboratories_id')
            ->whereNotNull('laboratories.latitude')
            ->whereNotNull('laboratories.longitude');

        if ($isGuestMode) {
            $query->where('tubes.is_private', false);
        } else {
            $query->where('tubes.projects_id', $projectId);
        }

        if ($laboratoryFilter !== '') {
            $query->where('laboratories.name', $laboratoryFilter);
        }

        if ($locationFilter !== '') {
            $query->where('locations.name', $locationFilter);
        }

        if ($boxFilter !== '') {
            $query->where(function ($w) use ($boxFilter) {
                $w->where('boxes.code', $boxFilter)
                    ->orWhere('boxes.name', $boxFilter);
            });
        }

        if ($contentTypeFilter !== '') {
            $query->where(function ($w) use ($contentTypeFilter) {
                $w->where('tubes.tubes_content_type', 'like', '%'.$contentTypeFilter)
                    ->orWhere('tubes.tubes_content_type', 'App\\Models\\'.$contentTypeFilter);
            });
        }

        if ($subProjectFilter !== '') {
            $query->whereExists(function ($sub) use ($subProjectFilter) {
                $sub->select(DB::raw(1))
                    ->from('sub_project_assignments')
                    ->join('sub_projects', 'sub_projects.id', '=', 'sub_project_assignments.sub_project_id')
                    ->whereColumn('sub_project_assignments.assignable_id', 'tubes.id')
                    ->where('sub_project_assignments.assignable_type', Tubes::class)
                    ->where('sub_projects.code', $subProjectFilter);
            });
        }

        if ($startDate !== '' && $endDate !== '') {
            $query->whereBetween('tp.date_moved', [$startDate, $endDate]);
        } elseif ($startDate !== '') {
            $query->where('tp.date_moved', '>=', $startDate);
        } elseif ($endDate !== '') {
            $query->where('tp.date_moved', '<=', $endDate);
        }

        $rows = $query
            ->select([
                'tubes.id',
                'tubes.code',
                'tubes.tubes_content_type',
                'laboratories.name as laboratory',
                'laboratories.latitude',
                'laboratories.longitude',
                'locations.name as location',
                'boxes.code as box',
                'tp.date_moved',
            ])
            ->where('tubes.id', '>', $cursor)
            ->orderBy('tubes.id')
            ->limit($limit)
            ->get();

        $points = [];
        foreach ($rows as $row) {
            if ($row->latitude === null || $row->longitude === null) {
                continue;
            }

            $lat = (float) $row->latitude;
            $lng = (float) $row->longitude;

            $points[] = [
                'latitude' => $lat,
                'longitude' => $lng,
                'code' => $row->code,
                'content_type' => $this->contentTypeLabel((string) $row->tubes_content_type),
                'laboratory' => $row->laboratory ?? 'Unknown',
                'location' => $row->location ?? 'Unknown',
                'box' => $row->box ?? 'Unknown',
                'date_moved' => $row->date_moved,
            ];
        }

        $nextCursor = null;
        if ($rows->count() === $limit) {
            $nextCursor = (int) $rows->last()->id;
        }

        return response()->json([
            'points' => $points,
            'next_cursor' => $nextCursor,
        ]);
    }

    private function contentTypeLabel(string $type): string
    {
        if (str_starts_with($type, 'AppModels')) {
            return substr($type, strlen('AppModels'));
        }

        return class_basename($type);
    }
}
