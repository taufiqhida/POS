<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToppingRecipe extends Model
{
    use HasUuids;

    protected $fillable = ['topping_id', 'ingredient_name', 'qty'];

    protected $casts = ['qty' => 'float'];

    public function topping(): BelongsTo
    {
        return $this->belongsTo(Topping::class);
    }
}
