<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Dashboard Dua Outlet';

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            Section::make()->columns(2)->schema([
                DatePicker::make('date')->label('Tanggal')->default(today())->native(false)->maxDate(today()),
            ]),
        ]);
    }

    public function getColumns(): int|string|array
    {
        return ['md' => 2, 'xl' => 2];
    }
}
