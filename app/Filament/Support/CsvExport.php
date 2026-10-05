<?php

namespace App\Filament\Support;

use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;

/** Unduh isi tabel (sesuai filter aktif) sebagai CSV — bisa dibuka di Excel. */
class CsvExport
{
    /** @param array<string, callable> $columns judul kolom => fn($record) */
    public static function action(string $filename, array $columns): Action
    {
        return Action::make('csv')->label('Unduh CSV')->icon('heroicon-o-arrow-down-tray')->color('gray')
            ->action(function ($livewire) use ($filename, $columns) {
                /** @var Builder $query */
                $query = $livewire->getFilteredSortedTableQuery();

                return response()->streamDownload(function () use ($query, $columns) {
                    $out = fopen('php://output', 'w');
                    fwrite($out, "\xEF\xBB\xBF");
                    fputcsv($out, array_keys($columns), ';');
                    $query->chunk(500, function ($rows) use ($out, $columns) {
                        foreach ($rows as $r) {
                            fputcsv($out, array_map(fn ($fn) => $fn($r), array_values($columns)), ';');
                        }
                    });
                    fclose($out);
                }, $filename.'-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
            });
    }
}
