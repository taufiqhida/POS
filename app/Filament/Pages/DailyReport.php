<?php

namespace App\Filament\Pages;

use App\Services\ReportService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class DailyReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $title = 'Laporan Harian ke HP Owner';

    protected static ?string $navigationLabel = 'Laporan Harian';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.daily-report';

    public string $date;

    public function mount(): void
    {
        $this->date = today()->toDateString();
    }

    protected function getViewData(): array
    {
        $svc = app(ReportService::class);
        $msg = $svc->dailyMessage(Carbon::parse($this->date));

        return ['message' => $msg, 'wa' => $svc->whatsappLink($msg), 'summary' => $svc->dailySummary(Carbon::parse($this->date))];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send')->label('Kirim sekarang')->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->action(function () {
                    $ok = app(ReportService::class)->notifyOwner(app(ReportService::class)->dailyMessage(Carbon::parse($this->date)));
                    $ok ? Notification::make()->title('Ringkasan terkirim ke HP owner')->success()->send()
                        : Notification::make()->title('Webhook notifikasi belum diatur / gagal')->body('Atur di menu Pengaturan, atau pakai tombol "Buka di WhatsApp".')->warning()->send();
                }),
        ];
    }
}
