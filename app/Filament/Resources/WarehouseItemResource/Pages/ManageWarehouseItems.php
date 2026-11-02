<?php

namespace App\Filament\Resources\WarehouseItemResource\Pages;

use App\Filament\Resources\WarehouseItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageWarehouseItems extends ManageRecords
{
    protected static string $resource = WarehouseItemResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
