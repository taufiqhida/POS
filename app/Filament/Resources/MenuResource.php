<?php

namespace App\Filament\Resources;

use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\MenuVariant;
use App\Models\Recipe;
use App\Services\ReportService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = 'Menu & Produk';

    protected static ?string $modelLabel = 'menu';

    protected static ?string $pluralModelLabel = 'Menu & Resep';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        $canPrice = fn () => auth()->user()->canChangePrice();

        return $form->schema([
            Forms\Components\Section::make('Menu')->columns(2)->schema([
                Forms\Components\Select::make('outlet_id')->label('Outlet')->relationship('outlet', 'name')->required()->live(),
                Forms\Components\TextInput::make('name')->label('Nama menu')->required(),
                Forms\Components\TextInput::make('category')->label('Kategori')->default('Minuman')->required()
                    ->datalist(fn () => Menu::distinct()->pluck('category')->all()),
                Forms\Components\TextInput::make('base_price')->label('Harga dasar')->numeric()->prefix('Rp')->required()
                    ->disabled(fn (string $operation) => $operation === 'edit' && ! $canPrice())
                    ->helperText(fn () => $canPrice() ? null : '🔒 Hanya owner / manager berhak yang bisa mengubah harga.'),
                Forms\Components\Select::make('topping_group')->label('Grup topping')->options(\App\Models\Topping::GROUPS)
                    ->default('minuman')->required(),
                Forms\Components\TextInput::make('free_toppings')->label('Topping gratis per cup')->numeric()->minValue(0)->maxValue(5)->default(0)
                    ->helperText('Mis. 1 = "harga sudah termasuk free 1 topping".'),
                Forms\Components\Toggle::make('is_active')->label('Tersedia')->default(true),
                Forms\Components\FileUpload::make('image')->label('Foto produk (opsional)')->image()->imageEditor()
                    ->disk('public')->directory('menus')->maxSize(2048)->columnSpanFull(),
            ]),
            Forms\Components\Section::make('Ukuran & harga')->schema([
                Forms\Components\Repeater::make('variants')->relationship()->hiddenLabel()->columns(2)
                    ->defaultItems(0)->addActionLabel('Tambah ukuran')
                    ->addable($canPrice)->deletable($canPrice)
                    ->schema([
                        Forms\Components\TextInput::make('size')->label('Ukuran')->required()->placeholder('Regular'),
                        Forms\Components\TextInput::make('price')->label('Harga')->numeric()->prefix('Rp')->required()
                            ->disabled(fn ($record) => $record && ! $canPrice()),
                    ]),
            ]),
            Forms\Components\Section::make('Resep standar (takaran per porsi)')
                ->description('Setiap penjualan otomatis memotong stok bahan sesuai takaran ini. Kosongkan "Khusus ukuran" agar berlaku untuk semua ukuran.')
                ->schema([
                    Forms\Components\Repeater::make('recipes')->relationship()->hiddenLabel()->columns(3)
                        ->defaultItems(0)->addActionLabel('Tambah bahan')
                        ->schema([
                            Forms\Components\Select::make('ingredient_id')->label('Bahan')->required()->searchable()
                                ->options(fn (Forms\Get $get) => Ingredient::where('outlet_id', $get('../../outlet_id'))->orderBy('name')
                                    ->get()->mapWithKeys(fn ($i) => [$i->id => "{$i->name} ({$i->unit})"])),
                            Forms\Components\TextInput::make('qty')->label('Takaran')->numeric()->required(),
                            Forms\Components\Select::make('variant_id')->label('Khusus ukuran')->placeholder('Semua ukuran')
                                ->options(function (Forms\Components\Select $component) {
                                    // $record di dalam repeater adalah baris Recipe; menu ada di record milik repeater.
                                    $menu = $component->getContainer()->getParentComponent()->getRecord();

                                    return $menu instanceof Menu ? MenuVariant::where('menu_id', $menu->id)->pluck('size', 'id') : [];
                                }),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ImageColumn::make('image')->label('')->disk('public')->square()->size(40),
            Tables\Columns\TextColumn::make('name')->label('Menu')->searchable()->weight('bold'),
            Tables\Columns\TextColumn::make('outlet.name')->label('Outlet')->badge(),
            Tables\Columns\TextColumn::make('category')->label('Kategori'),
            Tables\Columns\TextColumn::make('variants')->label('Harga')
                ->state(fn (Menu $record) => $record->variants->isEmpty() ? ReportService::rp($record->base_price)
                    : $record->variants->map(fn ($v) => $v->size.' '.ReportService::rp($v->price))->implode(' · ')),
            Tables\Columns\TextColumn::make('recipes_count')->counts('recipes')->label('Bahan resep')
                ->color(fn ($state) => $state ? null : 'danger'),
            Tables\Columns\ToggleColumn::make('is_active')->label('Tersedia'),
        ])->filters([
            Tables\Filters\SelectFilter::make('outlet_id')->relationship('outlet', 'name')->label('Outlet'),
            Tables\Filters\SelectFilter::make('category')->options(fn () => Menu::distinct()->pluck('category', 'category')->all())->label('Kategori'),
        ])->actions([
            Tables\Actions\EditAction::make()->slideOver(),
            Tables\Actions\Action::make('copy')->label('Salin ke outlet lain')->icon('heroicon-o-document-duplicate')
                ->form([Forms\Components\Select::make('outlet_id')->label('Outlet tujuan')->relationship('outlet', 'name')->required()])
                ->action(function (Menu $record, array $data) {
                    $copy = $record->replicate()->fill(['outlet_id' => $data['outlet_id']]);
                    $copy->save();
                    $map = [];
                    foreach ($record->variants as $v) {
                        $map[$v->id] = $copy->variants()->create($v->only('size', 'price'))->id;
                    }
                    foreach ($record->recipes()->with('ingredient')->get() as $r) {
                        $ing = Ingredient::firstOrCreate(
                            ['outlet_id' => $data['outlet_id'], 'name' => $r->ingredient->name],
                            ['unit' => $r->ingredient->unit, 'stock_min' => $r->ingredient->stock_min]);
                        Recipe::create(['menu_id' => $copy->id, 'variant_id' => $r->variant_id ? ($map[$r->variant_id] ?? null) : null,
                            'ingredient_id' => $ing->id, 'qty' => $r->qty]);
                    }
                    Notification::make()->title('Menu disalin')->success()->send();
                }),
            Tables\Actions\DeleteAction::make(),
        ])->defaultSort('name');
    }

    public static function getPages(): array
    {
        return ['index' => MenuResource\Pages\ManageMenus::route('/')];
    }
}
