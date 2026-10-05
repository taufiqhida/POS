<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockCount extends Model
{
    use HasUuids;

    protected $fillable = ['outlet_id', 'ingredient_id', 'user_id', 'system_qty', 'physical_qty', 'difference', 'note'];

    protected $casts = ['system_qty' => 'float', 'physical_qty' => 'float', 'difference' => 'float'];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
