<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\OwnerOnly;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    use OwnerOnly;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Pegawai';

    protected static ?string $modelLabel = 'akun';

    protected static ?string $pluralModelLabel = 'Akun & PIN Pegawai';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nama')->required(),
            Forms\Components\Select::make('role')->label('Peran')->options(User::ROLES)->required()->default('kasir')->live(),
            Forms\Components\Select::make('outlet_id')->label('Outlet tugas')->relationship('outlet', 'name')
                ->helperText('Kosongkan untuk owner / manager semua outlet.'),
            Forms\Components\TextInput::make('pin')->label('PIN (4–6 angka)')->password()->revealable()
                ->rule('digits_between:4,6')->dehydrated(fn ($state) => filled($state))
                ->required(fn (string $operation) => $operation === 'create')
                ->helperText(fn (string $operation) => $operation === 'edit' ? 'Kosongkan jika tidak diganti.' : null),
            Forms\Components\TextInput::make('email')->label('Email login panel')->email()->unique(ignoreRecord: true)
                ->visible(fn (Forms\Get $get) => in_array($get('role'), ['owner', 'manager'])),
            Forms\Components\TextInput::make('password')->label('Password panel')->password()->revealable()
                ->dehydrated(fn ($state) => filled($state))
                ->visible(fn (Forms\Get $get) => in_array($get('role'), ['owner', 'manager'])),
            Forms\Components\Toggle::make('can_change_price')->label('Boleh ubah harga (PIN)')
                ->visible(fn (Forms\Get $get) => $get('role') === 'manager'),
            Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Nama')->searchable()->weight('bold'),
            Tables\Columns\TextColumn::make('role')->label('Peran')->badge()->formatStateUsing(fn ($state) => User::ROLES[$state] ?? $state)
                ->color(fn ($state) => ['owner' => 'danger', 'manager' => 'warning', 'kasir' => 'info'][$state] ?? 'gray'),
            Tables\Columns\TextColumn::make('outlet.name')->label('Outlet')->placeholder('Semua'),
            Tables\Columns\IconColumn::make('can_change_price')->label('Ubah harga')->boolean(),
            Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
        ])->filters([
            Tables\Filters\SelectFilter::make('outlet_id')->relationship('outlet', 'name')->label('Outlet'),
            Tables\Filters\SelectFilter::make('role')->options(User::ROLES)->label('Peran'),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => UserResource\Pages\ManageUsers::route('/')];
    }
}
