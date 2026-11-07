<?php

namespace App\Filament\Resources\PaymentChangeResource\Pages;

use App\Filament\Resources\PaymentChangeResource;
use Filament\Resources\Pages\ManageRecords;

class ManagePaymentChanges extends ManageRecords
{
    protected static string $resource = PaymentChangeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
