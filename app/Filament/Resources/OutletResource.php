<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\OwnerOnly;
use App\Models\Outlet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OutletResource extends Resource
{
    use OwnerOnly;

    protected static ?string $model = Outlet::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?string $modelLabel = 'outlet';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nama outlet')->required(),
            Forms\Components\TextInput::make('phone')->label('Telepon'),
            Forms\Components\Textarea::make('address')->label('Alamat')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Outlet')->searchable()->weight('bold'),
            Tables\Columns\TextColumn::make('address')->label('Alamat')->limit(40),
            Tables\Columns\TextColumn::make('users_count')->counts('users')->label('Pegawai'),
            Tables\Columns\TextColumn::make('menus_count')->counts('menus')->label('Menu'),
        ])->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => OutletResource\Pages\ManageOutlets::route('/')];
    }
}
