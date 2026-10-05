<?php

namespace App\Filament\Concerns;

/** Resource/page yang hanya boleh dibuka owner (pengaturan, pegawai, outlet). */
trait OwnerOnly
{
    public static function canAccess(): bool
    {
        return auth()->user()?->isOwner() ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }
}
