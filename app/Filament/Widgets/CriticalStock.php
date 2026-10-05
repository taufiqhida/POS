<?php

namespace App\Filament\Widgets;

use App\Models\Ingredient;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CriticalStock extends BaseWidget
{
    protected static ?string $heading = '📦 Stok kritis';

    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        return $table
            ->query(Ingredient::query()->with('outlet')->critical())
            ->emptyStateHeading('Semua stok aman 👍')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Bahan')->weight('bold'),
                Tables\Columns\TextColumn::make('outlet.name')->label('Outlet')->badge(),
                Tables\Columns\TextColumn::make('stock_current')->label('Sisa')->color('danger')
                    ->formatStateUsing(fn ($state, $record) => rtrim(rtrim(number_format($state, 2, ',', '.'), '0'), ',').' '.$record->unit),
                Tables\Columns\TextColumn::make('stock_min')->label('Min')->numeric(0, ',', '.'),
            ])->paginated([5, 10])->defaultPaginationPageOption(5);
    }
}
