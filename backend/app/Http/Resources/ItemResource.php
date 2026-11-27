<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'type'        => $this->type,
            'name'        => $this->name,
            'description' => $this->description,
            'photo_url'   => $this->photo ? asset('storage/'.$this->photo) : null,
            'event_date'  => $this->event_date,
            'event_time'  => $this->event_time,
            'status'      => $this->status,
            'pending_claims_count' => $this->whenCounted('pending_claims'),
            'category'    => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id, 'name' => $this->category->name,
            ]),
            'location'    => $this->whenLoaded('location', fn () => [
                'id' => $this->location->id, 'name' => $this->location->name,
            ]),
            'reporter'    => $this->whenLoaded('reporter', fn () => [
                'id' => $this->reporter->id, 'name' => $this->reporter->name,
            ]),
            'created_at'  => $this->created_at,
        ];
    }
}