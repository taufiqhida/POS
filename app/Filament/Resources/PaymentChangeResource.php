<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ReadOnlyResource;
use App\Models\AuditLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/** Daftar khusus kasir yang mengganti metode bayar (salah pencet) — terpisah supaya tidak tenggelam di riwayat lain. */
class PaymentChangeResource extends Resource
{
    use ReadOnlyResource;

    protected static ?string $model = AuditLog::class;

    protected static ?string $slug = 'cek-ganti-metode-bayar';

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'Cek Ganti Metode Bayar';

    protected static ?string $modelLabel = 'perubahan';

    protected static ?string $pluralModelLabel = 'Cek Ganti Metode Bayar';

    protected static ?int $navigationSort = 0;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('action', 'payment_change')->with(['user', 'reviewer', 'transaction.outlet']);
    }

    public static function getNavigationBadge(): ?string
    {
        $n = static::getEloquentQuery()->whereNull('reviewed_at')->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        $review = fn (AuditLog $r) => $r->update(['reviewed_at' => now(), 'reviewed_by' => auth()->id()]);

        return $table
            ->emptyStateHeading('Belum ada kasir yang mengganti metode bayar')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Waktu')->dateTime('d/m/y H:i')->sortable(),
                Tables\Columns\TextColumn::make('transaction.outlet.name')->label('Outlet')->badge(),
                Tables\Columns\TextColumn::make('user.name')->label('Kasir'),
                Tables\Columns\TextColumn::make('detail')->label('Perubahan & alasan')->wrap()->weight('bold'),
                Tables\Columns\TextColumn::make('reviewed_at')->label('Status')->badge()
                    ->state(fn (AuditLog $r) => $r->reviewed_at ? 'Sudah dicek' : 'Belum dicek')
                    ->color(fn (AuditLog $r) => $r->reviewed_at ? 'success' : 'danger')
                    ->description(fn (AuditLog $r) => $r->reviewed_at ? $r->reviewer?->name.' · '.$r->reviewed_at->format('d/m H:i') : null),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('reviewed')->label('Status cek')
                    ->placeholder('Semua')->trueLabel('Sudah dicek')->falseLabel('Belum dicek')->default(false)
                    ->queries(true: fn (Builder $query) => $query->whereNotNull('reviewed_at'),
                        false: fn (Builder $query) => $query->whereNull('reviewed_at')),
            ])
            ->actions([
                Tables\Actions\Action::make('review')->label('Sudah dicek')->icon('heroicon-o-check')->color('success')
                    ->visible(fn (AuditLog $r) => ! $r->reviewed_at)->action($review),
                Tables\Actions\Action::make('struk')->label('Struk')->icon('heroicon-o-printer')->color('gray')
                    ->visible(fn (AuditLog $r) => $r->transaction !== null)
                    ->url(fn (AuditLog $r) => route('receipt', $r->reference_id))->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('reviewAll')->label('Tandai sudah dicek')->icon('heroicon-o-check')
                    ->action(fn (Collection $records) => $records->each($review))->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => PaymentChangeResource\Pages\ManagePaymentChanges::route('/')];
    }
}
