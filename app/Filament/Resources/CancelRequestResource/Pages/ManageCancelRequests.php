<?php

namespace App\Filament\Resources\CancelRequestResource\Pages;

use App\Filament\Resources\CancelRequestResource;
use Filament\Resources\Pages\ManageRecords;

class ManageCancelRequests extends ManageRecords
{
    protected static string $resource = CancelRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
