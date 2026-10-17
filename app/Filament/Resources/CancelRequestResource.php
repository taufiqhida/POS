<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ReadOnlyResource;
use App\Models\Transaction;
use App\Services\PosService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/** Pengajuan void/refund dari kasir yang menunggu persetujuan owner/manager. */
class CancelRequestResource extends Resource
{
    use ReadOnlyResource;

    protected static ?string $model = Transaction::class;

    protected static ?string $slug = 'verifikasi-void-refund';

    protected static ?string $navigationIcon = 'heroicon-o-hand-raised';

    protected static ?string $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'Verifikasi Void/Refund';

    protected static ?string $modelLabel = 'pengajuan';

    protected static ?string $pluralModelLabel = 'Verifikasi Void/Refund';

    protected static ?int $navigationSort = -1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('status', 'paid')->whereNotNull('cancel_request')
            ->with(['outlet', 'cashier', 'requester', 'items']);
    }

    public static function getNavigationBadge(): ?string
    {
        $n = static::getEloquentQuery()->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        $run = fn (callable $fn, string $ok) => function (Transaction $record, array $data = []) use ($fn, $ok) {
            try {
                $fn($record, $data);
                Notification::make()->title($ok)->success()->send();
            } catch (ValidationException $e) {
                Notification::make()->title($e->validator->errors()->first())->danger()->send();
            }
        };

        return $table
            ->emptyStateHeading('Tidak ada pengajuan yang menunggu')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->columns([
                Tables\Columns\TextColumn::make('cancel_requested_at')->label('Diajukan')->dateTime('d/m H:i')->sortable(),
                Tables\Columns\TextColumn::make('cancel_request')->label('Jenis')->badge()->color('warning')
                    ->formatStateUsing(fn ($state) => $state === 'refund' ? 'Refund' : 'Void'),
                Tables\Columns\TextColumn::make('number')->label('Transaksi')
                    ->description(fn (Transaction $record) => $record->created_at->format('d/m H:i').' · '.$record->items->map(fn ($i) => $i->qty.'× '.$i->menu_name)->implode(', ')),
                Tables\Columns\TextColumn::make('outlet.name')->label('Outlet')->badge(),
                Tables\Columns\TextColumn::make('requester.name')->label('Diajukan oleh'),
                Tables\Columns\TextColumn::make('cancel_reason')->label('Alasan')->wrap(),
                Tables\Columns\TextColumn::make('total')->label('Total')->money('IDR', locale: 'id')->weight('bold'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('outlet_id')->relationship('outlet', 'name')->label('Outlet'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')->label('Setujui')->icon('heroicon-o-check')->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Transaction $record) => "Setujui {$record->cancel_request} {$record->number}?")
                    ->modalDescription('Transaksi dibatalkan, tidak dihitung lagi di penjualan, dan stok bahannya dikembalikan.')
                    ->action($run(fn (Transaction $r) => app(PosService::class)->approveCancel($r, auth()->user()), 'Pengajuan disetujui')),
                Tables\Actions\Action::make('reject')->label('Tolak')->icon('heroicon-o-x-mark')->color('danger')
                    ->form([Forms\Components\TextInput::make('note')->label('Catatan untuk kasir (opsional)')])
                    ->modalHeading(fn (Transaction $record) => "Tolak pengajuan {$record->number}?")
                    ->modalDescription('Transaksi tetap sah dan tetap dihitung.')
                    ->action($run(fn (Transaction $r, array $d) => app(PosService::class)->rejectCancel($r, auth()->user(), $d['note'] ?? null), 'Pengajuan ditolak')),
                Tables\Actions\Action::make('struk')->label('Struk')->icon('heroicon-o-printer')->color('gray')
                    ->url(fn (Transaction $record) => route('receipt', $record))->openUrlInNewTab(),
            ])
            ->defaultSort('cancel_requested_at');
    }

    public static function getPages(): array
    {
        return ['index' => CancelRequestResource\Pages\ManageCancelRequests::route('/')];
    }
}
