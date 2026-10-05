<?php

namespace App\Filament\Resources;

use App\Models\Ingredient;
use App\Models\StockMovement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class IngredientResource extends Resource
{
    protected static ?string $model = Ingredient::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Stok';

    protected static ?string $modelLabel = 'bahan';

    protected static ?string $pluralModelLabel = 'Stok Bahan & Kemasan';

    public static function getNavigationBadge(): ?string
    {
        $n = Ingredient::critical()->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('outlet_id')->label('Outlet')->relationship('outlet', 'name')->required(),
            Forms\Components\TextInput::make('name')->label('Nama bahan')->required(),
            Forms\Components\Select::make('unit')->label('Satuan')->options(['pcs' => 'pcs', 'gram' => 'gram', 'ml' => 'ml', 'kg' => 'kg', 'liter' => 'liter', 'pack' => 'pack'])->required(),
            Forms\Components\TextInput::make('stock_current')->label('Stok saat ini')->numeric()->default(0)
                ->disabledOn('edit')->helperText('Ubah stok lewat aksi Restock / Koreksi agar tercatat di riwayat.'),
            Forms\Components\TextInput::make('stock_min')->label('Batas stok kritis')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        $adjust = fn (string $reason) => function (Ingredient $record, array $data) use ($reason) {
            DB::transaction(function () use ($record, $data, $reason) {
                $qty = (float) $data['qty'];
                $change = match ($reason) {
                    'restock' => abs($qty),
                    'waste' => -abs($qty),
                    default => $qty - $record->stock_current,
                };
                $record->increment('stock_current', $change);
                StockMovement::create(['outlet_id' => $record->outlet_id, 'ingredient_id' => $record->id, 'qty_change' => $change,
                    'reason' => $reason, 'user_id' => auth()->id(), 'note' => $data['note'] ?? null]);
            });
            Notification::make()->title('Stok diperbarui')->success()->send();
        };
        $form = fn (string $label) => [
            Forms\Components\TextInput::make('qty')->label($label)->numeric()->required(),
            Forms\Components\TextInput::make('note')->label('Catatan'),
        ];

        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Bahan')->searchable()->weight('bold')
                ->description(fn (Ingredient $record) => $record->isCritical() ? '⚠️ Stok kritis' : null),
            Tables\Columns\TextColumn::make('outlet.name')->label('Outlet')->badge(),
            Tables\Columns\TextColumn::make('stock_current')->label('Stok')->numeric(2, ',', '.')->suffix(fn ($record) => ' '.$record->unit)
                ->color(fn (Ingredient $record) => $record->isCritical() ? 'danger' : 'success')->sortable(),
            Tables\Columns\TextColumn::make('stock_min')->label('Min')->numeric(2, ',', '.'),
        ])->filters([
            Tables\Filters\SelectFilter::make('outlet_id')->relationship('outlet', 'name')->label('Outlet'),
            Tables\Filters\Filter::make('critical')->label('Hanya stok kritis')->query(fn (Builder $query) => $query->critical()),
        ])->actions([
            Tables\Actions\Action::make('restock')->label('Restock')->icon('heroicon-o-plus-circle')->color('success')
                ->form($form('Jumlah masuk'))->action($adjust('restock')),
            Tables\Actions\ActionGroup::make([
                Tables\Actions\Action::make('waste')->label('Catat waste')->icon('heroicon-o-trash')->color('danger')
                    ->form($form('Jumlah terbuang'))->action($adjust('waste')),
                Tables\Actions\Action::make('adjust')->label('Koreksi stok fisik')->icon('heroicon-o-scale')
                    ->form($form('Jumlah fisik sebenarnya'))->action($adjust('adjustment')),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]),
        ])->defaultSort('name');
    }

    public static function getPages(): array
    {
        return ['index' => IngredientResource\Pages\ManageIngredients::route('/')];
    }
}
