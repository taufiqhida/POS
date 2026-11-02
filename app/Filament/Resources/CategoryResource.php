<?php

namespace App\Filament\Resources;

use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Menu & Produk';

    protected static ?string $modelLabel = 'kategori';

    protected static ?string $pluralModelLabel = 'Kategori Menu';

    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nama kategori')->required()->maxLength(60)
                ->unique(ignoreRecord: true)
                ->helperText('Mengganti nama ikut mengganti kategori di semua menu yang memakainya.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')->defaultSort('sort_order')
            ->description('Seret baris untuk mengatur urutan tab kategori di layar kasir.')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Kategori')->weight('bold')->searchable(),
                Tables\Columns\TextColumn::make('menus')->label('Jumlah menu')
                    ->state(fn (Category $record) => $record->menuCount().' menu'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Category $record, Tables\Actions\DeleteAction $action) {
                        if ($n = $record->menuCount()) {
                            Notification::make()->danger()->title("Kategori \"{$record->name}\" masih dipakai {$n} menu")
                                ->body('Pindahkan menunya ke kategori lain dulu (Menu & Resep), atau ganti nama kategori ini.')->send();
                            $action->cancel();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => CategoryResource\Pages\ManageCategories::route('/')];
    }
}
