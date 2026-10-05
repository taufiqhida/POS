<?php

namespace App\Filament\Resources;

use App\Models\Promo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PromoResource extends Resource
{
    protected static ?string $model = Promo::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Menu & Produk';

    protected static ?string $modelLabel = 'promo';

    protected static ?string $pluralModelLabel = 'Promo & Diskon';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nama promo')->required(),
            Forms\Components\Select::make('outlet_id')->label('Outlet')->relationship('outlet', 'name')->placeholder('Semua outlet'),
            Forms\Components\Select::make('type')->label('Jenis')->options(['percent' => 'Persen (%)', 'fixed' => 'Potongan (Rp)'])->required()->default('percent'),
            Forms\Components\TextInput::make('value')->label('Nilai')->numeric()->required(),
            Forms\Components\TextInput::make('min_subtotal')->label('Minimal belanja')->numeric()->prefix('Rp')->default(0),
            Forms\Components\DatePicker::make('starts_at')->label('Mulai'),
            Forms\Components\DatePicker::make('ends_at')->label('Selesai'),
            Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Promo')->weight('bold'),
            Tables\Columns\TextColumn::make('outlet.name')->label('Outlet')->placeholder('Semua'),
            Tables\Columns\TextColumn::make('value')->label('Nilai')
                ->formatStateUsing(fn ($state, Promo $record) => $record->type === 'percent' ? "{$state}%" : 'Rp'.number_format($state, 0, ',', '.')),
            Tables\Columns\TextColumn::make('min_subtotal')->label('Min. belanja')->money('IDR', locale: 'id'),
            Tables\Columns\TextColumn::make('starts_at')->label('Periode')->date('d M')
                ->formatStateUsing(fn (Promo $record) => ($record->starts_at?->format('d M') ?? '…').' – '.($record->ends_at?->format('d M') ?? '…')),
            Tables\Columns\ToggleColumn::make('is_active')->label('Aktif'),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => PromoResource\Pages\ManagePromos::route('/')];
    }
}
