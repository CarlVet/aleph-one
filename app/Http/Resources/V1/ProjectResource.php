<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'alias_code' => $this->alias_code,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'date_started' => $this->date_started,
            'date_end_intended' => $this->date_end_intended,
            'date_end' => $this->date_end,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
