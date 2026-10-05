<?php

namespace App\Filament\Widgets\Concerns;

use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

trait DateFilter
{
    use InteractsWithPageFilters;

    protected function day(): Carbon
    {
        return Carbon::parse($this->filters['date'] ?? today());
    }

    protected function range(): array
    {
        return [$this->day()->copy()->startOfDay(), $this->day()->copy()->endOfDay()];
    }
}
