<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ReadOnlyResource;
use App\Filament\Support\CsvExport;
use App\Models\Shift;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ShiftResource extends Resource
{
    use ReadOnlyResource;

    protected static ?string $model = Shift::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Penjualan';

    protected static ?string $modelLabel = 'shift';

    protected static ?string $pluralModelLabel = 'Shift & Selisih Kas';

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('opened_at')->label('Buka')->dateTime('d/m/y H:i')->sortable(),
            Tables\Columns\TextColumn::make('closed_at')->label('Tutup')->dateTime('H:i')->placeholder('—'),
            Tables\Columns\TextColumn::make('outlet.name')->label('Outlet')->badge(),
            Tables\Columns\TextColumn::make('opener.name')->label('Pegawai'),
            Tables\Columns\TextColumn::make('opening_cash')->label('Modal')->money('IDR', locale: 'id'),
            Tables\Columns\TextColumn::make('expected_cash')->label('Seharusnya')->money('IDR', locale: 'id'),
            Tables\Columns\TextColumn::make('actual_cash')->label('Fisik')->money('IDR', locale: 'id'),
            Tables\Columns\TextColumn::make('cash_difference')->label('Selisih')->money('IDR', locale: 'id')->weight('bold')
                ->color(fn ($state) => $state === null ? null : ($state < 0 ? 'danger' : ($state > 0 ? 'warning' : 'success')))
                ->summarize(Tables\Columns\Summarizers\Sum::make()->money('IDR', locale: 'id')),
            Tables\Columns\TextColumn::make('status')->badge()->color(fn ($state) => $state === 'open' ? 'success' : 'gray')
                ->formatStateUsing(fn ($state) => $state === 'open' ? 'Aktif' : 'Ditutup'),
            Tables\Columns\TextColumn::make('note')->label('Catatan')->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            Tables\Filters\SelectFilter::make('outlet_id')->relationship('outlet', 'name')->label('Outlet'),
            Tables\Filters\SelectFilter::make('opened_by')->relationship('opener', 'name')->label('Pegawai'),
            Tables\Filters\Filter::make('selisih')->label('Hanya yang selisih')->query(fn ($query) => $query->where('cash_difference', '!=', 0)),
        ])->headerActions([
            CsvExport::action('shift-kas', [
                'Buka' => fn ($r) => $r->opened_at->format('Y-m-d H:i'),
                'Tutup' => fn ($r) => $r->closed_at?->format('Y-m-d H:i'),
                'Outlet' => fn ($r) => $r->outlet->name,
                'Pegawai' => fn ($r) => $r->opener->name,
                'Modal' => fn ($r) => $r->opening_cash,
                'Seharusnya' => fn ($r) => $r->expected_cash,
                'Fisik' => fn ($r) => $r->actual_cash,
                'Selisih' => fn ($r) => $r->cash_difference,
            ]),
        ])->defaultSort('opened_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ShiftResource\Pages\ManageShifts::route('/')];
    }
}
