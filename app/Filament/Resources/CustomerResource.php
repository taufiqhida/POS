<?php

namespace App\Filament\Resources;

use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Penjualan';

    protected static ?string $modelLabel = 'member';

    protected static ?string $pluralModelLabel = 'Member Jelly Point';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nama')->required()->maxLength(60),
            Forms\Components\TextInput::make('phone')->label('No. HP')->disabled()->dehydrated(false),
            Forms\Components\Toggle::make('is_staff')->label('Karyawan (tidak mendapat cap)'),
            Forms\Components\Toggle::make('can_contact')->label('Setuju dihubungi lewat WhatsApp'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Member')->searchable()->weight('bold')
                ->description(fn (Customer $r) => $r->phone),
            Tables\Columns\TextColumn::make('stamps')->label('Cap')->sortable()
                ->formatStateUsing(fn ($state) => $state.' / '.Customer::perReward()),
            Tables\Columns\TextColumn::make('visits')->label('Kunjungan')->sortable(),
            Tables\Columns\TextColumn::make('last_visit_at')->label('Terakhir datang')->since()->sortable()->placeholder('—'),
            Tables\Columns\IconColumn::make('can_contact')->label('Boleh WA')->boolean(),
            Tables\Columns\IconColumn::make('is_staff')->label('Karyawan')->boolean(),
            Tables\Columns\TextColumn::make('pin_failed')->label('PIN')
                ->formatStateUsing(fn ($state) => $state >= Customer::MAX_PIN_FAILS ? 'TERKUNCI' : 'ok')
                ->badge()->color(fn ($state) => $state >= Customer::MAX_PIN_FAILS ? 'danger' : 'gray'),
            Tables\Columns\TextColumn::make('last_contacted_at')->label('Terakhir dihubungi')->since()->placeholder('belum'),
        ])->filters([
            Tables\Filters\Filter::make('follow_up')->label('Perlu dihubungi')
                ->query(fn (Builder $query) => $query->where('can_contact', true)->where('is_staff', false)
                    ->where(fn ($w) => $w->where('last_visit_at', '<', now()->subDays(30))
                        ->orWhere('stamps', '>=', max(1, Customer::perReward() - 3)))
                    ->where(fn ($w) => $w->whereNull('last_contacted_at')->orWhere('last_contacted_at', '<', now()->subDays(14)))),
            Tables\Filters\TernaryFilter::make('is_staff')->label('Karyawan'),
        ])->actions([
            // WhatsApp hanya manual: membuka chat dengan pesan terisi, dikirim satu per satu oleh orangnya sendiri.
            Tables\Actions\Action::make('wa')->label('Kirim WA')->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->visible(fn (Customer $r) => $r->can_contact)
                ->url(fn (Customer $r) => $r->whatsappUrl(), shouldOpenInNewTab: true),
            Tables\Actions\Action::make('contacted')->label('Sudah dihubungi')->icon('heroicon-o-check')->color('gray')
                ->action(fn (Customer $r) => $r->update(['last_contacted_at' => now()])),
            Tables\Actions\Action::make('resetPin')->label('Reset PIN')->icon('heroicon-o-key')
                ->modalDescription('Cocokkan dulu nomor HP dengan pelanggan. PIN baru diketik pelanggan sendiri.')
                ->form([Forms\Components\TextInput::make('pin')->label('PIN baru (4 angka)')->required()->password()->length(4)->regex('/^\d{4}$/')])
                ->action(function (Customer $r, array $data) {
                    $r->update(['pin' => $data['pin'], 'pin_failed' => 0]);
                    Notification::make()->title('PIN direset')->success()->send();
                }),
            Tables\Actions\EditAction::make(),
        ])->defaultSort('last_visit_at', 'desc')
            ->emptyStateHeading('Belum ada member')
            ->emptyStateDescription('Member didaftarkan kasir di layar bayar dengan nomor HP pelanggan.');
    }

    public static function getPages(): array
    {
        return ['index' => CustomerResource\Pages\ManageCustomers::route('/')];
    }
}
