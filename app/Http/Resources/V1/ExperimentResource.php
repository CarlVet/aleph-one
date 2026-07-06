<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExperimentResource extends JsonResource
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
            'protocol_id' => $this->protocols_id,
            'pathogen_id' => $this->pathogens_id,
            'laboratory_id' => $this->laboratories_id,
            'purpose' => $this->purpose,
            'date_tested' => $this->date_tested,
            'outcome_discrete' => $this->outcome_discrete,
            'outcome_quant' => $this->outcome_quant,
            'outcome_binary' => $this->outcome_binary,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
