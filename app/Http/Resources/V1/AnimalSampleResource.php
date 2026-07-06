<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnimalSampleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'project_id' => $this->projects_id,
            'animal_id' => $this->animals_id,
            'sample_type_id' => $this->sample_types_id,
            'date_collected' => $this->date_collected,
            'date_received' => $this->date_received,
            'sampling_site_id' => $this->sampling_sites_id,
            'area' => $this->area,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'storage_state' => $this->storage_state,
            'processed' => $this->processed,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
