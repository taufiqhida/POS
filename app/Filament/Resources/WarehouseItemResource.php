<?php

namespace App\Filament\Resources;

use App\Models\Outlet;
use App\Models\WarehouseItem;
use App\Services\WarehouseService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class WarehouseItemResource extends Resource
{
    protected static ?string $model = WarehouseItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Stok';

    protected static ?string $modelLabel = 'bahan gudang';

    protected static ?string $pluralModelLabel = 'Stok Gudang Utama';

    protected static ?int $navigationSort = -1;

    public const UNITS = ['pcs' => 'pcs', 'bungkus' => 'bungkus', 'kantong' => 'kantong', 'gram' => 'gram', 'ml' => 'ml',
        'kg' => 'kg', 'liter' => 'liter', 'pack' => 'pack', 'dus' => 'dus'];

    public static function getNavigationBadge(): ?string
    {
        $n = WarehouseItem::critical()->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nama bahan')->required()->unique(ignoreRecord: true)
                ->helperText('Samakan dengan nama bahan di outlet supaya stok cocok saat diambil.'),
            Forms\Components\Select::make('unit')->label('Satuan')->options(self::UNITS)->required()
                ->helperText('Harus sama dengan satuan bahan di outlet.'),
            Forms\Components\TextInput::make('stock_min')->label('Batas stok kritis')->numeric()->minValue(0)->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        $run = fn (callable $fn, string $ok) => function (WarehouseItem $record, array $data) use ($fn, $ok) {
            try {
                $fn($record, $data);
                Notification::make()->title($ok)->success()->send();
            } catch (ValidationException $e) {
                Notification::make()->title($e->validator->errors()->first())->danger()->send();
            }
        };

        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Bahan')->searchable()->weight('bold')
                ->description(fn (WarehouseItem $record) => $record->isCritical() ? '⚠️ Stok gudang kritis' : null),
            Tables\Columns\TextColumn::make('stock_current')->label('Stok gudang')->numeric(2, ',', '.')
                ->suffix(fn (WarehouseItem $record) => ' '.$record->unit)
                ->color(fn (WarehouseItem $record) => $record->isCritical() ? 'danger' : 'success')->sortable(),
            Tables\Columns\TextColumn::make('stock_min')->label('Min')->numeric(2, ',', '.'),
        ])->filters([
            Tables\Filters\Filter::make('critical')->label('Hanya stok kritis')->query(fn (Builder $query) => $query->critical()),
        ])->actions([
            Tables\Actions\Action::make('receive')->label('Barang masuk')->icon('heroicon-o-plus-circle')->color('success')
                ->form([
                    Forms\Components\TextInput::make('qty')->label('Jumlah masuk')->numeric()->required()->minValue(0.01)->maxValue(WarehouseService::MAX_QTY),
                    Forms\Components\TextInput::make('note')->label('Catatan (mis. supplier / nota)'),
                ])
                ->action($run(fn ($r, $d) => app(WarehouseService::class)->receive($r, (float) $d['qty'], auth()->user(), $d['note'] ?? null), 'Barang masuk dicatat')),
            Tables\Actions\Action::make('send')->label('Kirim ke outlet')->icon('heroicon-o-truck')->color('primary')
                ->form(fn (WarehouseItem $record) => [
                    Forms\Components\Select::make('outlet_id')->label('Outlet tujuan')->options(Outlet::orderBy('name')->pluck('name', 'id'))->required(),
                    Forms\Components\TextInput::make('qty')->label("Jumlah ({$record->unit})")->numeric()->required()->minValue(0.01)
                        ->maxValue($record->stock_current)->helperText("Stok gudang: {$record->stock_current} {$record->unit}"),
                    Forms\Components\TextInput::make('note')->label('Catatan'),
                ])
                ->action($run(fn ($r, $d) => app(WarehouseService::class)->transfer($r, Outlet::findOrFail($d['outlet_id']), (float) $d['qty'], auth()->user(), $d['note'] ?? null), 'Dikirim ke outlet')),
            Tables\Actions\ActionGroup::make([
                Tables\Actions\Action::make('adjust')->label('Koreksi stok fisik')->icon('heroicon-o-scale')
                    ->form([
                        Forms\Components\TextInput::make('qty')->label('Jumlah fisik sebenarnya')->numeric()->required()->minValue(0)->maxValue(WarehouseService::MAX_QTY),
                        Forms\Components\Toggle::make('waste')->label('Selisihnya karena rusak / terbuang'),
                        Forms\Components\TextInput::make('note')->label('Catatan'),
                    ])
                    ->action($run(fn ($r, $d) => app(WarehouseService::class)->adjust($r, (float) $d['qty'], auth()->user(), $d['note'] ?? null, (bool) ($d['waste'] ?? false)), 'Stok gudang dikoreksi')),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->modalDescription('Kartu gudang dan seluruh riwayat keluar-masuknya ikut terhapus. Stok di outlet tidak berubah.'),
            ]),
        ])->defaultSort('name');
    }

    public static function getPages(): array
    {
        return ['index' => WarehouseItemResource\Pages\ManageWarehouseItems::route('/')];
    }
}
