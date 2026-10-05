<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\DateFilter;
use App\Models\TransactionItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopProducts extends BaseWidget
{
    use DateFilter;

    protected static ?string $heading = '🏆 Produk terlaris';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        [$from, $to] = $this->range();

        return $table
            ->query(TransactionItem::query()
                ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
                ->join('outlets', 'outlets.id', '=', 'transactions.outlet_id')
                ->where('transactions.status', 'paid')
                ->whereBetween('transactions.created_at', [$from, $to])
                ->selectRaw('MIN(transaction_items.id) as id, transaction_items.menu_name, outlets.name as outlet_name, SUM(transaction_items.qty) as qty, SUM(transaction_items.subtotal) as omzet')
                ->groupBy('transaction_items.menu_name', 'outlets.name')
                ->orderByDesc('qty'))
            ->columns([
                Tables\Columns\TextColumn::make('menu_name')->label('Menu')->weight('bold'),
                Tables\Columns\TextColumn::make('outlet_name')->label('Outlet')->badge(),
                Tables\Columns\TextColumn::make('qty')->label('Terjual'),
                Tables\Columns\TextColumn::make('omzet')->label('Omzet')->money('IDR', locale: 'id'),
            ])
            ->paginated([5, 10])->defaultPaginationPageOption(5);
    }
}
