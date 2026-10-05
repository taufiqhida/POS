<?php

namespace App\Filament\Pages;

use App\Models\Outlet;
use App\Models\Transaction;
use App\Services\ReportService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class Reports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $title = 'Laporan & Analisis';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.reports';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(['from' => today()->subDays(6)->toDateString(), 'until' => today()->toDateString(), 'outlet_id' => null]);
    }

    public function form(Form $form): Form
    {
        return $form->statePath('data')->columns(3)->live()->schema([
            DatePicker::make('from')->label('Dari')->required(),
            DatePicker::make('until')->label('Sampai')->required(),
            Select::make('outlet_id')->label('Outlet')->options(Outlet::orderBy('name')->pluck('name', 'id'))->placeholder('Gabungan semua outlet'),
        ]);
    }

    protected function range(): array
    {
        return [Carbon::parse($this->data['from'] ?? today())->startOfDay(), Carbon::parse($this->data['until'] ?? today())->endOfDay()];
    }

    protected function getViewData(): array
    {
        $svc = app(ReportService::class);
        [$from, $to] = $this->range();
        $outletId = $this->data['outlet_id'] ?? null;

        return [
            'outlets' => $svc->salesByOutlet($from, $to),
            'channels' => $svc->salesByChannel($from, $to, $outletId),
            'top' => $svc->topProducts($from, $to, $outletId, 10),
            'employees' => $svc->employeePerformance($from, $to),
            'waste' => $svc->wasteReport($from, $to, $outletId),
            'forecast' => $svc->stockForecast($outletId, 3),
            'rp' => fn ($v) => ReportService::rp($v),
            'channelLabels' => Transaction::CHANNELS,
            'methodLabels' => Transaction::METHODS,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')->label('Unduh laporan (CSV)')->icon('heroicon-o-arrow-down-tray')->action(function () {
                $d = $this->getViewData();
                [$from, $to] = $this->range();

                return response()->streamDownload(function () use ($d, $from, $to) {
                    $o = fopen('php://output', 'w');
                    fwrite($o, "\xEF\xBB\xBF");
                    $w = fn (array $r) => fputcsv($o, $r, ';');
                    $w(['LAPORAN JELLY POTTER', $from->format('d/m/Y').' - '.$to->format('d/m/Y')]);
                    $w([]);
                    $w(['PENJUALAN PER OUTLET', 'Transaksi', 'Total', ...array_values(Transaction::CHANNELS)]);
                    foreach ($d['outlets'] as $r) {
                        $w([$r['outlet']->name, $r['count'], $r['total'], ...array_map(fn ($k) => $r['by_channel'][$k] ?? 0, array_keys(Transaction::CHANNELS))]);
                    }
                    $w(['GABUNGAN', array_sum(array_column($d['outlets'], 'count')), array_sum(array_column($d['outlets'], 'total'))]);
                    $w([]);
                    $w(['PRODUK TERLARIS', 'Qty', 'Omzet']);
                    foreach ($d['top'] as $r) {
                        $w([$r->menu_name, $r->qty, $r->omzet]);
                    }
                    $w([]);
                    $w(['PEGAWAI', 'Outlet', 'Transaksi', 'Penjualan', 'Void/Refund', 'Diskon', 'Shift', 'Selisih kas']);
                    foreach ($d['employees'] as $r) {
                        $w([$r['user']->name, $r['user']->outlet?->name, $r['count'], $r['sales'], $r['void'], $r['discount'], $r['shifts'], $r['cash_diff']]);
                    }
                    $w([]);
                    $w(['WASTE BAHAN', 'Outlet', 'Pemakaian resep', 'Waste/selisih', 'Satuan']);
                    foreach ($d['waste'] as $r) {
                        $w([$r['ingredient']->name, $r['ingredient']->outlet->name, $r['recipe_use'], $r['waste'], $r['ingredient']->unit]);
                    }
                    fclose($o);
                }, 'laporan-jelly-potter-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv');
            }),
        ];
    }
}
