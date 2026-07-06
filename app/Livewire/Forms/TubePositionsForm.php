<?php

namespace App\Livewire\Forms;

use App\Models\Boxes;
use App\Models\TubePositions;
use App\Models\Tubes;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\Form;

class TubePositionsForm extends Form
{
    use WithFileUploads;

    public $tube_positions;

    public $projectId = 1;

    public function mount()
    {
        $this->tube_positions = TubePositions::with(
            'tubes',
            'boxes',
            'boxes.projects',
            'boxes.box_positions',
            'boxes.box_positions.locations',
            'boxes.box_positions.locations.laboratories',
            'people',
        )->whereHas('boxes', function ($query) {
            $query->where('projects_id', $this->projectId);
        })->get();
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function updateField($sampleId, $field, $value): array
    {
        $tube_position = TubePositions::find($sampleId);

        if (! $tube_position) {
            return ['ok' => false, 'message' => 'Tube position not found.'];
        }

        try {
            switch ($field) {
                case 'tube':
                    $tube = Tubes::where('code', $value)->first();
                    if (! $tube) {
                        return ['ok' => false, 'message' => 'Tube code not found.'];
                    }

                    $tube_position->update(['tubes_id' => $tube->id]);
                    break;
                case 'box':
                    $box = Boxes::where('code', $value)->first();
                    if (! $box) {
                        return ['ok' => false, 'message' => 'Box code not found.'];
                    }

                    $tube_position->update(['boxes_id' => $box->id]);
                    break;
                case 'content_type':
                    $tube_position->boxes()->update(['content_type' => $value]);
                    break;
                case 'position_x':
                    $tube_position->update(['position_x' => $value]);
                    break;
                case 'position_y':
                    $tube_position->update(['position_y' => $value]);
                    break;
                case 'date_moved':
                    $tube_position->update(['date_moved' => $value]);
                    break;
                case 'reason':
                    $tube_position->update(['reason' => $value]);
                    break;
                default:
                    return ['ok' => false, 'message' => 'Unsupported field.'];
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Update failed.'];
        }

        $this->refreshData();

        return ['ok' => true, 'message' => 'Tube position edited successfully!'];
    }

    public function refreshData()
    {
        $this->tube_positions = TubePositions::with(
            'tubes',
            'boxes',
            'boxes.projects',
            'boxes.box_positions',
            'boxes.box_positions.locations',
            'boxes.box_positions.locations.laboratories',
            'people',
        )->whereHas('boxes', function ($query) {
            $query->where('projects_id', $this->projectId);
        })->get();
    }
}
