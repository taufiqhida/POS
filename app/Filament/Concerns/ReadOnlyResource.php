<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

/** Data log/riwayat tidak boleh diubah dari panel. */
trait ReadOnlyResource
{
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
