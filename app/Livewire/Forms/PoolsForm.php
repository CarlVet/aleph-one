<?php

namespace App\Livewire\Forms;

use App\Models\AnimalSamples;
use App\Models\Cultures;
use App\Models\EnvironmentSamples;
use App\Models\HumanSamples;
use App\Models\NucleicAcids;
use App\Models\ParasiteSamples;
use App\Models\PoolContents;
use App\Models\Pools;
use App\Models\Tubes;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\Form;

class PoolsForm extends Form
{
    use WithFileUploads;

    public $pool_tubes;

    public $photo;

    public function mount()
    {
        $this->pool_tubes = Tubes::whereHas('tubes_content', function ($query) {
            $query->where('tubes_content_type', Pools::class);
        })->with(
            'tubes_content',
            'tubes_content.pool_contents',
            'tubes_content.pool_contents.samples',
            'tubes_content.people',
            'tubes_content.laboratories',
            'tubes_content.projects'
        )->get();
    }

    public function updateField($sampleId, $field, $value)
    {
        $sample = Tubes::whereHas('tubes_content', function ($query) {
            $query->where('tubes_content_type', Pools::class);
        })->find($sampleId);

        if ($sample) {
            switch ($field) {
                case 'tube_code':
                    // Validate that the tube code exists (check if it's a valid tube code)
                    $existingTube = Tubes::where('code', $value)->first();
                    if (! $existingTube) {
                        session()->flash('error', 'Tube code not found: '.$value);

                        return;
                    }
                    $sample->update(['code' => $value]);
                    break;
                case 'date_pooled':
                    $sample->tubes_content()->update(['date_pooled' => $value]);
                    break;
            }

            session()->flash('success', 'Pool sample edited successfully!');

            $this->refreshData();
        }
    }

    public function addContentCode($sampleId, $contentCode, $sampleType)
    {
        $sample = Tubes::whereHas('tubes_content', function ($query) {
            $query->where('tubes_content_type', Pools::class);
        })->find($sampleId);

        if (! $sample) {
            session()->flash('error', 'Sample not found: '.$sampleId);

            return;
        }

        if (! $sample->tubes_content) {
            session()->flash('error', 'Pool content not found for sample: '.$sampleId);

            return;
        }

        // Validate that the content code exists for the specified type
        $contentModel = null;

        // Normalize the sampleType to handle both formats
        $normalizedSampleType = $sampleType;
        if (strpos($sampleType, 'AppModels') === 0) {
            $normalizedSampleType = 'App\\Models\\'.substr($sampleType, 9);
        }

        switch ($normalizedSampleType) {
            case 'App\\Models\\AnimalSamples':
                $contentModel = AnimalSamples::where('code', $contentCode)->first();
                break;
            case 'App\\Models\\HumanSamples':
                $contentModel = HumanSamples::where('code', $contentCode)->first();
                break;
            case 'App\\Models\\EnvironmentSamples':
                $contentModel = EnvironmentSamples::where('code', $contentCode)->first();
                break;
            case 'App\\Models\\ParasiteSamples':
                $contentModel = ParasiteSamples::where('code', $contentCode)->first();
                break;
            case 'App\\Models\\NucleicAcids':
                $contentModel = NucleicAcids::where('code', $contentCode)->first();
                break;
            case 'App\\Models\\Cultures':
                $contentModel = Cultures::where('code', $contentCode)->first();
                break;
            case 'App\\Models\\Pools':
                $contentModel = Pools::where('code', $contentCode)->first();
                break;
            default:
                session()->flash('error', 'Unknown sample type: '.$sampleType);

                return;
        }

        if (! $contentModel) {
            session()->flash('error', 'Content code not found for type '.class_basename($sampleType).': '.$contentCode);

            return;
        }

        // Check if this content is already in the pool
        $existingContent = PoolContents::where('pools_id', $sample->tubes_content->id)
            ->where('samples_type', $normalizedSampleType)
            ->where('samples_id', $contentModel->id)
            ->first();

        if ($existingContent) {
            session()->flash('error', 'Content code already exists in this pool: '.$contentCode);

            return;
        }

        // Add the new content to the pool
        try {
            PoolContents::create([
                'pools_id' => $sample->tubes_content->id,
                'samples_type' => $normalizedSampleType,
                'samples_id' => $contentModel->id,
            ]);

            session()->flash('success', 'Content code added successfully: '.$contentCode);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to add content code: '.$e->getMessage());

            return;
        }

        $this->refreshData();
    }

    public function removeContentCode($sampleId, $contentId)
    {
        $poolContent = PoolContents::find($contentId);

        if ($poolContent) {
            $contentCode = $poolContent->samples->code ?? 'Unknown';
            $poolContent->delete();

            session()->flash('success', 'Content code removed successfully: '.$contentCode);

            $this->refreshData();
        } else {
            session()->flash('error', 'Content not found');
        }
    }

    public function refreshData()
    {
        $this->pool_tubes = Tubes::whereHas('tubes_content', function ($query) {
            $query->where('tubes_content_type', Pools::class);
        })->with(
            'tubes_content',
            'tubes_content.pool_contents',
            'tubes_content.pool_contents.samples',
            'tubes_content.people',
            'tubes_content.laboratories',
            'tubes_content.projects'
        )->get();
    }

    public function getAvailableTubeCodes()
    {
        return Tubes::pluck('code')->toArray();
    }

    public function getAvailableContentCodes($sampleType)
    {
        switch ($sampleType) {
            case AnimalSamples::class:
                return AnimalSamples::pluck('code')->toArray();
            case HumanSamples::class:
                return HumanSamples::pluck('code')->toArray();
            case EnvironmentSamples::class:
                return EnvironmentSamples::pluck('code')->toArray();
            case ParasiteSamples::class:
                return ParasiteSamples::pluck('code')->toArray();
            case NucleicAcids::class:
                return NucleicAcids::pluck('code')->toArray();
            case Cultures::class:
                return Cultures::pluck('code')->toArray();
            case Pools::class:
                return Pools::pluck('code')->toArray();
            default:
                return [];
        }
    }
}
