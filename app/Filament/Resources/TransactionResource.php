<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ReadOnlyResource;
use App\Filament\Support\CsvExport;
use App\Models\Transaction;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionResource extends Resource
{
    use ReadOnlyResource;

    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Penjualan';

    protected static ?string $modelLabel = 'transaksi';

    protected static ?string $pluralModelLabel = 'Transaksi';

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('created_at')->label('Waktu')->dateTime('d/m/y H:i')->sortable(),
            Tables\Columns\TextColumn::make('number')->label('No.')->searchable(),
            Tables\Columns\TextColumn::make('outlet.name')->label('Outlet')->badge(),
            Tables\Columns\TextColumn::make('cashier.name')->label('Pegawai'),
            Tables\Columns\TextColumn::make('channel')->label('Kanal')->formatStateUsing(fn ($state) => Transaction::CHANNELS[$state])->badge()
                ->color(fn ($state) => ['gofood' => 'success', 'grabfood' => 'success', 'shopeefood' => 'warning'][$state] ?? 'gray'),
            Tables\Columns\TextColumn::make('payment_method')->label('Bayar')->formatStateUsing(fn ($state) => Transaction::METHODS[$state]),
            Tables\Columns\TextColumn::make('discount')->label('Diskon')->money('IDR', locale: 'id')->toggleable(),
            Tables\Columns\TextColumn::make('total')->label('Total')->money('IDR', locale: 'id')->weight('bold')
                ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total')->money('IDR', locale: 'id')
                    ->query(fn ($query) => $query->where('status', 'paid'))),
            Tables\Columns\TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => Transaction::STATUSES[$state])
                ->color(fn ($state) => $state === 'paid' ? 'success' : 'danger'),
        ])->filters([
            Tables\Filters\SelectFilter::make('outlet_id')->relationship('outlet', 'name')->label('Outlet'),
            Tables\Filters\SelectFilter::make('cashier_id')->relationship('cashier', 'name')->label('Pegawai'),
            Tables\Filters\SelectFilter::make('channel')->options(Transaction::CHANNELS)->label('Kanal'),
            Tables\Filters\SelectFilter::make('payment_method')->options(Transaction::METHODS)->label('Metode bayar'),
            Tables\Filters\SelectFilter::make('status')->options(Transaction::STATUSES),
            Tables\Filters\Filter::make('tanggal')->form([
                \Filament\Forms\Components\DatePicker::make('from')->label('Dari')->default(today()),
                \Filament\Forms\Components\DatePicker::make('until')->label('Sampai')->default(today()),
            ])->query(fn (Builder $query, array $data) => $query
                ->when($data['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
                ->when($data['until'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))),
        ])->headerActions([
            CsvExport::action('transaksi', [
                'Waktu' => fn ($r) => $r->created_at->format('Y-m-d H:i'),
                'No' => fn ($r) => $r->number,
                'Outlet' => fn ($r) => $r->outlet->name,
                'Pegawai' => fn ($r) => $r->cashier->name,
                'Kanal' => fn ($r) => Transaction::CHANNELS[$r->channel],
                'Metode' => fn ($r) => Transaction::METHODS[$r->payment_method],
                'Subtotal' => fn ($r) => $r->subtotal,
                'Diskon' => fn ($r) => $r->discount,
                'Total' => fn ($r) => $r->total,
                'Status' => fn ($r) => Transaction::STATUSES[$r->status],
            ]),
        ])->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\Action::make('struk')->label('Struk')->icon('heroicon-o-printer')
                ->url(fn (Transaction $record) => route('receipt', $record))->openUrlInNewTab(),
        ])->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\TextEntry::make('number')->label('No.'),
            Infolists\Components\TextEntry::make('created_at')->label('Waktu')->dateTime('d/m/Y H:i'),
            Infolists\Components\TextEntry::make('cashier.name')->label('Pegawai'),
            Infolists\Components\RepeatableEntry::make('items')->label('Item')->columnSpanFull()->columns(4)->schema([
                Infolists\Components\TextEntry::make('menu_name')->label('Menu'),
                Infolists\Components\TextEntry::make('size')->label('Ukuran'),
                Infolists\Components\TextEntry::make('qty')->label('Qty'),
                Infolists\Components\TextEntry::make('subtotal')->label('Subtotal')->money('IDR', locale: 'id'),
            ]),
            Infolists\Components\TextEntry::make('discount_label')->label('Promo/diskon')->placeholder('-'),
            Infolists\Components\TextEntry::make('total')->money('IDR', locale: 'id'),
        ])->columns(3);
    }

    public static function getPages(): array
    {
        return ['index' => TransactionResource\Pages\ManageTransactions::route('/')];
    }
}
