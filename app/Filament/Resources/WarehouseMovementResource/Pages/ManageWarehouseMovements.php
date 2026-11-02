<?php

namespace App\Filament\Resources\WarehouseMovementResource\Pages;

use App\Filament\Resources\WarehouseMovementResource;
use Filament\Resources\Pages\ManageRecords;

class ManageWarehouseMovements extends ManageRecords
{
    protected static string $resource = WarehouseMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
