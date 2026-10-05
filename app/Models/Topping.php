<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topping extends Model
{
    use HasUuids;

    public const GROUPS = ['minuman' => 'Minuman', 'waffle' => 'Waffle', 'croffle' => 'Croffle (tanpa tambahan)', 'makanan' => 'Kebab / Burger / Maryam'];

    protected $fillable = ['name', 'group', 'price', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'price' => 'integer'];

    public function recipes(): HasMany
    {
        return $this->hasMany(ToppingRecipe::class);
    }
}
