<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ReadOnlyResource;
use App\Models\StockMovement;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StockMovementResource extends Resource
{
    use ReadOnlyResource;

    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'Stok';

    protected static ?string $pluralModelLabel = 'Riwayat Pemakaian Bahan';

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('created_at')->label('Waktu')->dateTime('d/m/y H:i')->sortable(),
            Tables\Columns\TextColumn::make('outlet.name')->label('Outlet')->badge(),
            Tables\Columns\TextColumn::make('ingredient.name')->label('Bahan')->searchable(),
            Tables\Columns\TextColumn::make('qty_change')->label('Perubahan')->numeric(2, ',', '.')
                ->color(fn ($state) => $state < 0 ? 'danger' : 'success'),
            Tables\Columns\TextColumn::make('reason')->label('Alasan')->badge()->formatStateUsing(fn ($state) => StockMovement::REASONS[$state] ?? $state),
            Tables\Columns\TextColumn::make('user.name')->label('Oleh'),
            Tables\Columns\TextColumn::make('note')->label('Ket.'),
        ])->filters([
            Tables\Filters\SelectFilter::make('outlet_id')->relationship('outlet', 'name')->label('Outlet'),
            Tables\Filters\SelectFilter::make('ingredient_id')->relationship('ingredient', 'name')->label('Bahan')->searchable(),
            Tables\Filters\SelectFilter::make('reason')->options(StockMovement::REASONS)->label('Alasan'),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => StockMovementResource\Pages\ManageStockMovements::route('/')];
    }
}
