<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SequenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'accession_number' => $this->accession_number,
            'project_id' => $this->projects_id,
            'nucleic_acid_id' => $this->nucleic_acids_id,
            'length' => $this->length,
            'method' => $this->method,
            'instrument' => $this->instrument,
            'date_sequenced' => $this->date_sequenced,
            'laboratory_id' => $this->laboratories_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
