<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClaimResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'status'            => $this->status,
            'proof_description' => $this->proof_description,
            'proof_photo_url'   => $this->proof_photo ? asset('storage/'.$this->proof_photo) : null,
            'verifier_note'     => $this->verifier_note,
            'verified_at'       => $this->verified_at,
            'created_at'        => $this->created_at,
            'item'              => new ItemResource($this->whenLoaded('item')),
            'claimant'          => $this->whenLoaded('claimant', fn () => [
                'id' => $this->claimant->id, 'name' => $this->claimant->name,
            ]),
            'contact'           => $this->when($this->status === 'approved', function () use ($request) {
                $other = $request->user()->id === $this->claimant_id
                    ? $this->item->reporter
                    : $this->claimant;

                return ['name' => $other->name, 'phone' => $other->phone];
            }),
        ];
    }
}
