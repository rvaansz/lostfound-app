<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $fillable = [
    'user_id', 'category_id', 'location_id', 'type', 'name',
    'description', 'photo', 'event_date', 'event_time', 'status',
    ];

    public function reporter(): BelongsTo
    {
    return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
    return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function location(): BelongsTo
    {
    return $this->belongsTo(Location::class);
    }

    public function claims(): HasMany
    {
    return $this->hasMany(Claim::class);
    }
}