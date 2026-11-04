<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuVariant extends Model
{
    use HasUuids;

    use Concerns\LogsChanges;

    public const AUDITED = ['size' => 'Ukuran', 'price' => 'Harga'];

    public const MONEY = ['price'];

    public function auditLabel(): string
    {
        return 'Ukuran "'.$this->size.'" menu "'.($this->menu?->name ?? '—').'"';
    }

    protected $fillable = ['menu_id', 'size', 'price'];

    protected $casts = ['price' => 'integer'];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }
}
