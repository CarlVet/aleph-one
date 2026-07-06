<?php

namespace App\Livewire\Forms;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\Laboratories;
use App\Models\ParasiteSamples;
use App\Models\Pools;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\Form;

class CulturesForm extends Form
{
    use WithFileUploads;

    public $cultures;

    public $photo;

    public function mount()
    {
        $this->cultures = Cultures::with(
            'cultures_content',
            'parent',
            'people',
            'laboratories',
            'projects',
        )->get();
    }

    public function updateField($cultureId, $field, $value)
    {
        $culture = Cultures::find($cultureId);

        if ($culture) {
            switch ($field) {
                case 'code':
                    // Check if the culture code already exists
                    $existingCulture = Cultures::where('code', $value)->where('id', '!=', $cultureId)->first();
                    if ($existingCulture) {
                        session()->flash('error', 'Culture code already exists!');

                        return;
                    }
                    $culture->update(['code' => $value]);
                    break;
                case 'parent_code':
                    if ($value) {
                        $parentCulture = Cultures::where('code', $value)->first();
                        if (! $parentCulture) {
                            session()->flash('error', 'Parent culture code not found!');

                            return;
                        }
                        $culture->update(['parent_id' => $parentCulture->id]);
                    } else {
                        $culture->update(['parent_id' => null]);
                    }
                    break;
                case 'alias_code':
                    $culture->update(['alias_code' => $value]);
                    break;
                case 'content_code':
                    if ($value) {
                        // Find the content by code across different sample types
                        $content = null;
                        $contentTypes = [AnimalSamples::class, HumanSamples::class, EnvironmentSamples::class, ParasiteSamples::class, Pools::class];

                        foreach ($contentTypes as $contentType) {
                            $content = $contentType::where('code', $value)->first();
                            if ($content) {
                                $culture->update([
                                    'cultures_content_type' => $contentType,
                                    'cultures_content_id' => $content->id,
                                ]);
                                break;
                            }
                        }

                        if (! $content) {
                            session()->flash('error', 'Content code not found!');

                            return;
                        }
                    }
                    break;
                case 'type':
                    $culture->update(['type' => $value]);
                    break;
                case 'medium':
                    $culture->update(['medium' => $value]);
                    break;
                case 'athmosphere':
                    $culture->update(['athmosphere' => $value]);
                    break;
                case 'incubation_temp':
                    $culture->update(['incubation_temp' => $value]);
                    break;
                case 'date_cultured':
                    $culture->update(['date_cultured' => $value]);
                    break;
                case 'is_discarded':
                    $isDiscarded = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    $culture->update([
                        'is_discarded' => $isDiscarded,
                        'date_discarded' => $isDiscarded ? ($culture->date_discarded ?? now()->toDateString()) : null,
                    ]);
                    break;
                case 'date_discarded':
                    if (! $culture->is_discarded) {
                        session()->flash('error', 'Set culture as discarded before recording a discard date.');

                        return;
                    }
                    $culture->update(['date_discarded' => $value ?: null]);
                    break;
                case 'people_id':
                    $culture->update(['people_id' => (int) $value]);
                    break;
                case 'laboratories_id':
                    $lab = Laboratories::query()->find((int) $value);
                    if (! $lab) {
                        session()->flash('error', 'Laboratory not found!');

                        return;
                    }
                    $culture->update(['laboratories_id' => $lab->id]);
                    break;
            }

            session()->flash('message', 'Culture updated successfully!');
            $this->refreshData();
        }
    }

    public function refreshData()
    {
        $this->cultures = Cultures::with(
            'cultures_content',
            'parent',
            'people',
            'laboratories',
            'projects',
        )->get();
    }
}
