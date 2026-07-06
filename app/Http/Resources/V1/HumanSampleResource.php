<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HumanSampleResource extends JsonResource
{
    /**
     * Sample-level metadata only. Patient identifiers and precise geolocation
     * are deliberately omitted from the API (privacy by design).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'project_id' => $this->projects_id,
            'human_id' => $this->humans_id,
            'sample_type_id' => $this->sample_types_id,
            'date_collected' => $this->date_collected,
            'sampling_site_id' => $this->sampling_sites_id,
            'area' => $this->area,
            'sample_purpose' => $this->sample_purpose,
            'storage_state' => $this->storage_state,
            'processed' => $this->processed,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
