<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\DateFilter;
use App\Models\Shift;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CashDifferences extends BaseWidget
{
    use DateFilter;

    protected static ?string $heading = '💰 Shift & selisih kas';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        [$from, $to] = $this->range();

        return $table
            ->query(Shift::query()->with(['outlet', 'opener'])->whereBetween('opened_at', [$from, $to]))
            ->emptyStateHeading('Belum ada shift di tanggal ini')
            ->columns([
                Tables\Columns\TextColumn::make('outlet.name')->label('Outlet')->badge(),
                Tables\Columns\TextColumn::make('opener.name')->label('Pegawai'),
                Tables\Columns\TextColumn::make('opened_at')->label('Jam')
                    ->formatStateUsing(fn (Shift $r) => $r->opened_at->format('H:i').' – '.($r->closed_at?->format('H:i') ?? 'aktif')),
                Tables\Columns\TextColumn::make('expected_cash')->label('Seharusnya')->money('IDR', locale: 'id')->placeholder('—'),
                Tables\Columns\TextColumn::make('actual_cash')->label('Fisik')->money('IDR', locale: 'id')->placeholder('—'),
                Tables\Columns\TextColumn::make('cash_difference')->label('Selisih')->money('IDR', locale: 'id')->weight('bold')->placeholder('—')
                    ->color(fn ($state) => $state === null ? null : ($state < 0 ? 'danger' : ($state > 0 ? 'warning' : 'success'))),
            ])->paginated(false);
    }
}
