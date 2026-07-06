<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => trim(($this->people->first_name ?? '').' '.($this->people->last_name ?? '')) ?: null,
            'permission' => $this->permission,
            'projects' => $this->projects()->pluck('projects.id'),
        ];
    }
}
