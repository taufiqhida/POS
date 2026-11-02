<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ReadOnlyResource;
use App\Models\WarehouseMovement;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WarehouseMovementResource extends Resource
{
    use ReadOnlyResource;

    protected static ?string $model = WarehouseMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Stok';

    protected static ?string $pluralModelLabel = 'Riwayat Gudang';

    protected static ?int $navigationSort = 0;

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('created_at')->label('Waktu')->dateTime('d/m/y H:i')->sortable(),
            Tables\Columns\TextColumn::make('item.name')->label('Bahan')->searchable(),
            Tables\Columns\TextColumn::make('qty_change')->label('Perubahan')->numeric(2, ',', '.')
                ->suffix(fn (WarehouseMovement $record) => ' '.$record->item?->unit)
                ->color(fn ($state) => $state < 0 ? 'danger' : 'success'),
            Tables\Columns\TextColumn::make('reason')->label('Jenis')->badge()->formatStateUsing(fn ($state) => WarehouseMovement::REASONS[$state] ?? $state),
            Tables\Columns\TextColumn::make('outlet.name')->label('Outlet tujuan')->badge()->placeholder('—'),
            Tables\Columns\TextColumn::make('user.name')->label('Oleh'),
            Tables\Columns\TextColumn::make('note')->label('Catatan'),
        ])->filters([
            Tables\Filters\SelectFilter::make('warehouse_item_id')->relationship('item', 'name')->label('Bahan')->searchable(),
            Tables\Filters\SelectFilter::make('outlet_id')->relationship('outlet', 'name')->label('Outlet tujuan'),
            Tables\Filters\SelectFilter::make('reason')->options(WarehouseMovement::REASONS)->label('Jenis'),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => WarehouseMovementResource\Pages\ManageWarehouseMovements::route('/')];
    }
}
