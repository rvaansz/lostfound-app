<?php

namespace App\Models;

use illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemCategory extends Model
{
    protected $fillable = ['name'];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'category_id');
    }
}