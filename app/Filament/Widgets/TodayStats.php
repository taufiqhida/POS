<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\DateFilter;
use App\Models\Ingredient;
use App\Models\Transaction;
use App\Services\ReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodayStats extends BaseWidget
{
    use DateFilter;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $svc = app(ReportService::class);
        [$from, $to] = $this->range();
        $outlets = $svc->salesByOutlet($from, $to);
        $total = array_sum(array_column($outlets, 'total'));
        $count = array_sum(array_column($outlets, 'count'));
        $diff = (int) $svc->cashDifferences($from, $to)->sum('cash_difference');
        $critical = Ingredient::critical()->count();
        $yesterday = Transaction::where('status', 'paid')->whereDate('created_at', $from->copy()->subDay())->sum('total');

        $stats = [
            Stat::make('Total penjualan', ReportService::rp($total))
                ->description("{$count} transaksi · kemarin ".ReportService::rp($yesterday))
                ->descriptionIcon($total >= $yesterday ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($total >= $yesterday ? 'success' : 'warning'),
        ];
        foreach ($outlets as $o) {
            $online = $o['total'] - ($o['by_channel']['offline'] ?? 0);
            $stats[] = Stat::make('🧋 '.$o['outlet']->name, ReportService::rp($o['total']))
                ->description("{$o['count']} trx · online ".ReportService::rp($online));
        }
        $stats[] = Stat::make('Selisih kas', ReportService::rp($diff))
            ->description('Total shift yang sudah ditutup')->color($diff < 0 ? 'danger' : 'success');
        $stats[] = Stat::make('Stok kritis', $critical.' bahan')
            ->description($critical ? 'Segera restock' : 'Semua aman')->color($critical ? 'danger' : 'success')
            ->url(route('filament.admin.resources.ingredients.index', ['tableFilters[critical][isActive]' => 1]));

        return $stats;
    }
}
