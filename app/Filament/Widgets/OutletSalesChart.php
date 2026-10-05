<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\DateFilter;
use App\Models\Outlet;
use App\Models\Transaction;
use Filament\Widgets\ChartWidget;

class OutletSalesChart extends ChartWidget
{
    use DateFilter;

    protected static ?string $heading = 'Penjualan 7 hari — Tembalang vs Grafika';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $end = $this->day();
        $days = collect(range(6, 0))->map(fn ($i) => $end->copy()->subDays($i));
        $colors = ['#7c3aed', '#db2777', '#0891b2', '#16a34a'];
        $datasets = [];
        foreach (Outlet::orderBy('name')->get() as $n => $o) {
            $rows = Transaction::where('outlet_id', $o->id)->where('status', 'paid')
                ->whereBetween('created_at', [$days->first()->copy()->startOfDay(), $end->copy()->endOfDay()])
                ->selectRaw('DATE(created_at) d, SUM(total) t')->groupBy('d')->pluck('t', 'd');
            $datasets[] = [
                'label' => $o->name,
                'data' => $days->map(fn ($d) => (int) ($rows[$d->toDateString()] ?? 0))->all(),
                'backgroundColor' => $colors[$n % 4],
                'borderColor' => $colors[$n % 4],
            ];
        }

        return ['datasets' => $datasets, 'labels' => $days->map(fn ($d) => $d->translatedFormat('D d/m'))->all()];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
