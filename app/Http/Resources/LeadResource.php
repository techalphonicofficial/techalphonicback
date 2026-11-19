<?php

namespace App\Http\Resources;

use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Lead */
class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'company' => $this->company,
            'service' => $this->service,
            'location' => $this->location,
            'message' => $this->message,
            'status' => $this->status->value,
            'source_page' => $this->source_page,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
