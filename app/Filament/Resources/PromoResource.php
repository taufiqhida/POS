<?php

namespace App\Filament\Resources;

use App\Models\Category;
use App\Models\Menu;
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
        $isFree = fn (Forms\Get $get) => $get('type') === 'free_item';
        $categories = fn () => Category::ordered()->mapWithKeys(fn ($n) => [$n => $n])->all();
        $menuNames = fn () => Menu::distinct()->orderBy('name')->pluck('name', 'name')->all();

        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nama promo')->required()->placeholder('mis. Beli 2 Waffle gratis Es Teh'),
            Forms\Components\Select::make('outlet_id')->label('Outlet')->relationship('outlet', 'name')->placeholder('Semua outlet'),
            Forms\Components\Select::make('type')->label('Jenis')->options(Promo::TYPES)->required()->default('percent')->live(),

            // ----- Diskon persen / rupiah -----
            Forms\Components\TextInput::make('value')->label('Nilai')->numeric()->minValue(1)->required()->visible(fn (Forms\Get $get) => ! $isFree($get)),
            Forms\Components\TextInput::make('min_subtotal')->label('Minimal belanja')->numeric()->prefix('Rp')->default(0)
                ->visible(fn (Forms\Get $get) => ! $isFree($get)),

            // ----- Beli item, gratis item -----
            Forms\Components\Section::make('Syarat: item yang dibeli')->visible($isFree)->columns(2)->schema([
                Forms\Components\Select::make('trigger_categories')->label('Kategori pemicu')->multiple()->options($categories)
                    ->live()->required(fn (Forms\Get $get) => $isFree($get) && empty($get('trigger_menus')))
                    ->helperText('mis. Waffle. Isi kategori ATAU menu (boleh keduanya).'),
                Forms\Components\Select::make('trigger_menus')->label('Menu pemicu (opsional)')->multiple()->searchable()->options($menuNames)
                    ->live()->required(fn (Forms\Get $get) => $isFree($get) && empty($get('trigger_categories'))),
                Forms\Components\TextInput::make('buy_qty')->label('Beli berapa')->numeric()->minValue(1)->maxValue(50)->default(2)
                    ->required($isFree)->helperText('Kelipatan berlaku: beli 4 = dapat 2× hadiah.'),
            ]),
            Forms\Components\Section::make('Hadiah: item gratis')->visible($isFree)->columns(2)->schema([
                Forms\Components\Select::make('reward_categories')->label('Kategori hadiah')->multiple()->options($categories)
                    ->live()->required(fn (Forms\Get $get) => $isFree($get) && empty($get('reward_menus')))
                    ->helperText('mis. Es Teh · Teh Series. Kasir memilih salah satu menu di kategori ini.'),
                Forms\Components\Select::make('reward_menus')->label('Menu hadiah (opsional)')->multiple()->searchable()->options($menuNames)
                    ->live()->required(fn (Forms\Get $get) => $isFree($get) && empty($get('reward_categories'))),
                Forms\Components\TextInput::make('reward_qty')->label('Gratis berapa')->numeric()->minValue(1)->maxValue(5)->default(1)
                    ->required($isFree),
            ]),

            Forms\Components\DatePicker::make('starts_at')->label('Mulai'),
            Forms\Components\DatePicker::make('ends_at')->label('Selesai'),
            Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Promo')->weight('bold')
                ->description(fn (Promo $record) => $record->isFreeItem() ? $record->describe() : null),
            Tables\Columns\TextColumn::make('outlet.name')->label('Outlet')->placeholder('Semua'),
            Tables\Columns\TextColumn::make('type')->label('Jenis')->badge()
                ->formatStateUsing(fn ($state) => Promo::TYPES[$state] ?? $state),
            Tables\Columns\TextColumn::make('value')->label('Nilai')
                ->state(fn (Promo $record) => $record->isFreeItem() ? '—' : $record->describe()),
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
