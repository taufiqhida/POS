<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\OwnerOnly;
use App\Models\Setting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Settings extends Page implements HasForms
{
    use InteractsWithForms, OwnerOnly;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?string $title = 'Struk & Identitas Toko';

    protected static string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(collect(Setting::DEFAULTS)->map(fn ($v, $k) => Setting::get($k))->all());
    }

    public function form(Form $form): Form
    {
        return $form->statePath('data')->schema([
            Section::make('Identitas toko & struk')->columns(2)->schema([
                TextInput::make('store_name')->label('Nama toko')->required()
                    ->helperText('Dipakai di laporan harian, dan sebagai judul nota bila "Judul nota" outlet dikosongkan. Judul, alamat & No. HP nota diatur per outlet di menu Outlet.'),
                Textarea::make('receipt_footer')->label('Catatan bawah struk')->columnSpanFull(),
            ]),
            Section::make('Notifikasi ke HP owner')->columns(2)->schema([
                TextInput::make('owner_whatsapp')->label('No. WhatsApp owner')->tel()->placeholder('08xxxxxxxxxx'),
                TextInput::make('report_webhook_url')->label('URL webhook gateway WA (opsional)')->url()
                    ->placeholder('https://api.fonnte.com/send')
                    ->helperText('Token diisi di .env: OWNER_NOTIFY_TOKEN'),
            ]),
        ]);
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $k => $v) {
            Setting::put($k, $v);
        }
        Notification::make()->title('Pengaturan disimpan')->success()->send();
    }
}
