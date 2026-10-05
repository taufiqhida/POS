<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuVariant extends Model
{
    use HasUuids;

    protected $fillable = ['menu_id', 'size', 'price'];

    protected $casts = ['price' => 'integer'];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }
}
