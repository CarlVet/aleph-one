<?php

namespace App\Livewire\Forms;

use App\Models\Laboratories;
use App\Models\Microplastics;
use App\Models\MpsTypes;
use App\Models\Protocols;
use Carbon\Carbon;
use Livewire\Form;

class MicroplasticsForm extends Form
{
    public $microplastics;

    public function mount(): void
    {
        $this->refreshData();
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function updateField(int $microplasticId, string $field, mixed $value): array
    {
        $microplastic = Microplastics::query()->find($microplasticId);

        if (! $microplastic) {
            return ['ok' => false, 'message' => 'Microplastics record not found.'];
        }

        switch ($field) {
            case 'mps_type':
                $mpsType = MpsTypes::query()->where('name', (string) $value)->first();
                if (! $mpsType) {
                    return ['ok' => false, 'message' => 'Microplastics type not found.'];
                }
                $microplastic->update(['mps_types_id' => $mpsType->id]);
                break;
            case 'sample_weight':
            case 'r_coeff':
            case 'm_feret':
                $microplastic->update([$field => $value !== '' ? $value : null]);
                break;
            case 'identification_date':
                if ($value === '' || $value === null) {
                    $microplastic->update(['identification_date' => null]);
                    break;
                }

                try {
                    $microplastic->update([
                        'identification_date' => Carbon::parse((string) $value)->toDateString(),
                    ]);
                } catch (\Throwable) {
                    return ['ok' => false, 'message' => 'Identification date is invalid.'];
                }
                break;
            case 'protocol':
                $protocol = Protocols::query()->where('name', (string) $value)->first();
                if (! $protocol) {
                    return ['ok' => false, 'message' => 'Protocol not found.'];
                }
                $microplastic->update(['protocols_id' => $protocol->id]);
                break;
            case 'laboratory':
                $laboratory = Laboratories::query()->where('name', (string) $value)->first();
                if (! $laboratory) {
                    return ['ok' => false, 'message' => 'Laboratory not found.'];
                }
                $microplastic->update(['laboratories_id' => $laboratory->id]);
                break;
            default:
                return ['ok' => false, 'message' => 'Unsupported field update.'];
        }

        $this->refreshData();

        return ['ok' => true, 'message' => 'Microplastics record updated successfully!'];
    }

    public function refreshData(): void
    {
        $this->microplastics = Microplastics::query()
            ->with(['mps_types', 'protocols', 'laboratories', 'people', 'projects', 'tubes'])
            ->get();
    }
}
