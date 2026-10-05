<?php

namespace App\Filament\Resources;

use App\Models\Ingredient;
use App\Models\Topping;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ToppingResource extends Resource
{
    protected static ?string $model = Topping::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Menu & Produk';

    protected static ?string $modelLabel = 'topping';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nama topping')->required(),
            Forms\Components\Select::make('group')->label('Grup')->options(Topping::GROUPS)->default('minuman')->required(),
            Forms\Components\TextInput::make('price')->label('Harga tambahan')->numeric()->prefix('Rp')->required()
                ->disabled(fn (string $operation) => $operation === 'edit' && ! auth()->user()->canChangePrice()),
            Forms\Components\Toggle::make('is_active')->label('Tersedia')->default(true),
            Forms\Components\Repeater::make('recipes')->relationship()->label('Bahan per porsi')->columns(2)->columnSpanFull()
                ->helperText('Nama bahan dicocokkan dengan daftar stok di tiap outlet.')
                ->defaultItems(0)->addActionLabel('Tambah bahan')
                ->schema([
                    Forms\Components\Select::make('ingredient_name')->label('Bahan')->required()->searchable()
                        ->options(fn () => Ingredient::distinct()->orderBy('name')->pluck('name', 'name')),
                    Forms\Components\TextInput::make('qty')->label('Takaran')->numeric()->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Topping')->searchable()->weight('bold'),
            Tables\Columns\TextColumn::make('group')->label('Grup')->badge()->formatStateUsing(fn ($state) => Topping::GROUPS[$state] ?? $state),
            Tables\Columns\TextColumn::make('price')->label('Harga')->money('IDR', locale: 'id'),
            Tables\Columns\TextColumn::make('recipes')->label('Resep')
                ->state(fn (Topping $record) => $record->recipes->map(fn ($x) => "{$x->qty} {$x->ingredient_name}")->implode(', ')),
            Tables\Columns\ToggleColumn::make('is_active')->label('Tersedia'),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ToppingResource\Pages\ManageToppings::route('/')];
    }
}
