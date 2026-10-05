<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recipe extends Model
{
    use HasUuids;

    protected $fillable = ['menu_id', 'variant_id', 'ingredient_id', 'qty'];

    protected $casts = ['qty' => 'float'];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(MenuVariant::class, 'variant_id');
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
