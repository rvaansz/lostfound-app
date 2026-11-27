<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Claim extends Model
{
    protected $fillable = [
        'item_id', 'claimant_id', 'proof_description', 'proof_photo',
        'status', 'verified_by', 'verifier_note', 'verified_at',
    ];

    public function item(): BelongsTo
    {
    return $this->belongsTo(Item::class);
    }

    public function claimant(): BelongsTo
    {
    return $this->belongsTo(User::class, 'claimant_id');
    }

    public function verifier(): BelongsTo
    {
    return $this->belongsTo(User::class, 'verified_by');
    }  
    
    protected function casts(): array
    {
    return ['verified_at' => 'datetime'];
    }
}