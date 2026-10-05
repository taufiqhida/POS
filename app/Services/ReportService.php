<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Outlet;
use App\Models\Recipe;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReportService
{
    public static function rp(int|float $v): string
    {
        return ($v < 0 ? '-' : '').'Rp'.number_format(abs($v), 0, ',', '.');
    }

    public function salesByOutlet(Carbon $from, Carbon $to): array
    {
        $rows = [];
        foreach (Outlet::orderBy('name')->get() as $o) {
            $q = Transaction::where('outlet_id', $o->id)->where('status', 'paid')->whereBetween('created_at', [$from, $to]);
            $rows[] = [
                'outlet' => $o,
                'total' => (int) (clone $q)->sum('total'),
                'count' => (clone $q)->count(),
                'by_channel' => (clone $q)->selectRaw('channel, SUM(total) t')->groupBy('channel')->pluck('t', 'channel')->map(fn ($v) => (int) $v)->all(),
                'by_method' => (clone $q)->selectRaw('payment_method, SUM(total) t')->groupBy('payment_method')->pluck('t', 'payment_method')->map(fn ($v) => (int) $v)->all(),
            ];
        }

        return $rows;
    }

    public function salesByChannel(Carbon $from, Carbon $to, ?string $outletId = null): array
    {
        return Transaction::where('status', 'paid')->whereBetween('created_at', [$from, $to])
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->selectRaw('channel, SUM(total) t, COUNT(*) c')->groupBy('channel')->get()
            ->mapWithKeys(fn ($r) => [$r->channel => ['total' => (int) $r->t, 'count' => (int) $r->c]])->all();
    }

    public function topProducts(Carbon $from, Carbon $to, ?string $outletId = null, int $limit = 5)
    {
        return TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.status', 'paid')
            ->whereBetween('transactions.created_at', [$from, $to])
            ->when($outletId, fn ($q) => $q->where('transactions.outlet_id', $outletId))
            ->selectRaw('transaction_items.menu_name, SUM(transaction_items.qty) qty, SUM(transaction_items.subtotal) omzet')
            ->groupBy('transaction_items.menu_name')
            ->orderByDesc('qty')->limit($limit)->get();
    }

    public function cashDifferences(Carbon $from, Carbon $to)
    {
        return Shift::with(['outlet', 'opener', 'closer'])->where('status', 'closed')
            ->whereBetween('closed_at', [$from, $to])->orderByDesc('closed_at')->get();
    }

    public function criticalStock()
    {
        return Ingredient::with('outlet')->critical()->orderBy('outlet_id')->get();
    }

    /** Waste: selisih antara pemakaian menurut resep (penjualan) vs pengurangan stok non-penjualan (waste/koreksi). */
    public function wasteReport(Carbon $from, Carbon $to, ?string $outletId = null)
    {
        return Ingredient::with('outlet')->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))->get()->map(function ($i) use ($from, $to) {
            $mv = StockMovement::where('ingredient_id', $i->id)->whereBetween('created_at', [$from, $to]);
            $recipeUse = -1 * (float) (clone $mv)->whereIn('reason', ['sale', 'void'])->sum('qty_change');
            $waste = -1 * (float) (clone $mv)->whereIn('reason', ['waste', 'adjustment'])->sum('qty_change');

            return ['ingredient' => $i, 'recipe_use' => $recipeUse, 'waste' => $waste];
        })->filter(fn ($r) => $r['recipe_use'] != 0 || $r['waste'] != 0)->values();
    }

    /** Prediksi kebutuhan stok: rata-rata pemakaian harian 7 hari terakhir × N hari. */
    public function stockForecast(?string $outletId = null, int $days = 3)
    {
        $from = now()->subDays(7)->startOfDay();

        return Ingredient::with('outlet')->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))->get()->map(function ($i) use ($from, $days) {
            $used = -1 * (float) StockMovement::where('ingredient_id', $i->id)->where('created_at', '>=', $from)
                ->whereIn('reason', ['sale', 'void', 'waste'])->sum('qty_change');
            $daily = max(0, $used / 7);
            $need = $daily * $days;

            return [
                'ingredient' => $i,
                'daily' => round($daily, 2),
                'need' => round($need, 2),
                'days_left' => $daily > 0 ? round($i->stock_current / $daily, 1) : null,
                'restock' => round(max(0, $need + $i->stock_min - $i->stock_current), 2),
            ];
        })->sortBy(fn ($r) => $r['days_left'] ?? INF)->values();
    }

    public function employeePerformance(Carbon $from, Carbon $to)
    {
        return \App\Models\User::where('role', '!=', 'owner')->with('outlet')->get()->map(function ($u) use ($from, $to) {
            $trx = Transaction::where('cashier_id', $u->id)->whereBetween('created_at', [$from, $to]);
            $shifts = Shift::where('opened_by', $u->id)->where('status', 'closed')->whereBetween('closed_at', [$from, $to]);

            return [
                'user' => $u,
                'count' => (clone $trx)->where('status', 'paid')->count(),
                'sales' => (int) (clone $trx)->where('status', 'paid')->sum('total'),
                'void' => (clone $trx)->whereIn('status', ['void', 'refunded'])->count(),
                'discount' => (int) (clone $trx)->where('status', 'paid')->sum('discount'),
                'cash_diff' => (int) (clone $shifts)->sum('cash_difference'),
                'shifts' => (clone $shifts)->count(),
            ];
        });
    }

    public function dailySummary(?Carbon $date = null): array
    {
        $date ??= today();
        $from = $date->copy()->startOfDay();
        $to = $date->copy()->endOfDay();
        $outlets = $this->salesByOutlet($from, $to);
        $top = $this->topProducts($from, $to, null, 1)->first();
        $critical = $this->criticalStock();
        $diff = (int) $this->cashDifferences($from, $to)->sum('cash_difference');

        return [
            'date' => $date,
            'outlets' => $outlets,
            'total' => array_sum(array_column($outlets, 'total')),
            'top' => $top,
            'critical' => $critical,
            'cash_diff' => $diff,
            'channels' => $this->salesByChannel($from, $to),
        ];
    }

    public function dailyMessage(?Carbon $date = null): string
    {
        $s = $this->dailySummary($date);
        $lines = [Setting::get('store_name'), $s['date']->translatedFormat('l, d M Y')];
        foreach ($s['outlets'] as $o) {
            $lines[] = sprintf('🧋 %s : %s', $o['outlet']->name, self::rp($o['total']));
        }
        $lines[] = '📈 Total hari ini  : '.self::rp($s['total']);
        foreach ($s['channels'] as $ch => $v) {
            $lines[] = '   • '.(Transaction::CHANNELS[$ch] ?? $ch).': '.self::rp($v['total']);
        }
        $lines[] = '🏆 Produk terlaris : '.($s['top'] ? "{$s['top']->menu_name} ({$s['top']->qty})" : '-');
        $lines[] = '📦 Stok kritis     : '.($s['critical']->isEmpty() ? 'aman' : $s['critical']->map(fn ($i) => "{$i->name} ({$i->outlet->name})")->implode(', '));
        $lines[] = '💰 Selisih kas     : '.self::rp($s['cash_diff']);

        return implode("\n", $lines);
    }

    public function shiftCloseMessage(Shift $shift): string
    {
        $shift->loadMissing(['outlet', 'opener', 'closer']);
        $sum = $shift->summary();

        return implode("\n", [
            Setting::get('store_name').' — TUTUP SHIFT',
            "Outlet : {$shift->outlet->name}",
            'Pegawai: '.$shift->opener->name.($shift->closer && $shift->closer->id !== $shift->opener->id ? " → {$shift->closer->name}" : ''),
            'Penjualan: '.self::rp($sum['sales_total'])." ({$sum['transactions']} trx)",
            'Seharusnya: '.self::rp($shift->expected_cash),
            'Fisik     : '.self::rp($shift->actual_cash),
            '💰 Selisih: '.self::rp($shift->cash_difference),
        ]);
    }

    /** Link WhatsApp siap kirim ke HP owner. */
    public function whatsappLink(string $message): ?string
    {
        $phone = preg_replace('/\D/', '', (string) Setting::get('owner_whatsapp'));
        if (! $phone) {
            return null;
        }
        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        }

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }

    /**
     * Kirim notifikasi ke owner lewat webhook (mis. gateway WhatsApp seperti Fonnte) bila dikonfigurasi.
     * Selalu dicatat ke log agar tetap bisa dilihat bila webhook belum diatur.
     */
    public function notifyOwner(string $message): bool
    {
        Log::channel('single')->info("[Notifikasi Owner]\n".$message);
        $url = Setting::get('report_webhook_url');
        $phone = Setting::get('owner_whatsapp');
        if (! $url) {
            return false;
        }
        try {
            return Http::timeout(10)->withHeaders(array_filter(['Authorization' => config('services.owner_notify.token')]))
                ->asForm()->post($url, ['target' => $phone, 'message' => $message])->successful();
        } catch (\Throwable $e) {
            Log::warning('Gagal kirim notifikasi owner: '.$e->getMessage());

            return false;
        }
    }
}
