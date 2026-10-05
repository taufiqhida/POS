<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use HasUuids;

    protected $fillable = ['outlet_id', 'name', 'category', 'image', 'base_price', 'free_toppings', 'topping_group', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'base_price' => 'integer', 'free_toppings' => 'integer'];

    public function imageUrl(): ?string
    {
        return $this->image ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->image) : null;
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(MenuVariant::class)->orderBy('price');
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }
}
