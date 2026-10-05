<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ReadOnlyResource;
use App\Models\AuditLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    use ReadOnlyResource;

    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Pegawai';

    protected static ?string $pluralModelLabel = 'Riwayat Void, Refund & Diskon';

    public const ACTIONS = [
        'void' => 'Void', 'refund' => 'Refund', 'discount' => 'Diskon manual', 'price_change' => 'Ubah harga',
        'shift_open' => 'Buka shift', 'shift_close' => 'Tutup shift', 'expense' => 'Pengeluaran', 'login' => 'Login kasir',
    ];

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('created_at')->label('Waktu')->dateTime('d/m/y H:i')->sortable(),
            Tables\Columns\TextColumn::make('user.name')->label('Pegawai')->searchable(),
            Tables\Columns\TextColumn::make('action')->label('Aksi')->badge()->formatStateUsing(fn ($state) => self::ACTIONS[$state] ?? $state)
                ->color(fn ($state) => in_array($state, ['void', 'refund', 'price_change', 'discount']) ? 'danger' : 'gray'),
            Tables\Columns\TextColumn::make('detail')->label('Detail')->wrap(),
            Tables\Columns\TextColumn::make('approver.name')->label('Disetujui')->placeholder('—'),
        ])->filters([
            Tables\Filters\SelectFilter::make('action')->options(self::ACTIONS)->label('Aksi')->multiple()
                ->default(['void', 'refund', 'discount', 'price_change']),
            Tables\Filters\SelectFilter::make('user_id')->relationship('user', 'name')->label('Pegawai'),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => AuditLogResource\Pages\ManageAuditLogs::route('/')];
    }
}
