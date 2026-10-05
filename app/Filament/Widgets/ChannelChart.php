<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\DateFilter;
use App\Models\Transaction;
use App\Services\ReportService;
use Filament\Widgets\ChartWidget;

class ChannelChart extends ChartWidget
{
    use DateFilter;

    protected static ?string $heading = 'Omzet per kanal (offline + online)';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        [$from, $to] = $this->range();
        $rows = app(ReportService::class)->salesByChannel($from, $to);

        return [
            'datasets' => [[
                'data' => collect(Transaction::CHANNELS)->keys()->map(fn ($k) => $rows[$k]['total'] ?? 0)->all(),
                'backgroundColor' => ['#7c3aed', '#16a34a', '#059669', '#ea580c'],
            ]],
            'labels' => array_values(Transaction::CHANNELS),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
