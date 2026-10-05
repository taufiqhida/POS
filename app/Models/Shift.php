<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasUuids;

    protected $fillable = ['outlet_id', 'opened_by', 'closed_by', 'opened_at', 'closed_at', 'opening_cash',
        'expected_cash', 'actual_cash', 'cash_difference', 'status', 'note'];

    protected $casts = ['opened_at' => 'datetime', 'closed_at' => 'datetime'];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function paidTransactions(): HasMany
    {
        return $this->transactions()->where('status', 'paid');
    }

    public function cashSales(): int
    {
        return (int) $this->paidTransactions()->where('payment_method', 'cash')->sum('total');
    }

    public function totalExpenses(): int
    {
        return (int) $this->expenses()->sum('amount');
    }

    /** Cash seharusnya = modal awal + penjualan tunai − pengeluaran. */
    public function computeExpectedCash(): int
    {
        return $this->opening_cash + $this->cashSales() - $this->totalExpenses();
    }

    public function summary(): array
    {
        $paid = $this->paidTransactions();

        return [
            'transactions' => (clone $paid)->count(),
            'sales_total' => (int) (clone $paid)->sum('total'),
            'by_method' => (clone $paid)->selectRaw('payment_method, SUM(total) as total')->groupBy('payment_method')->pluck('total', 'payment_method')->map(fn ($v) => (int) $v)->all(),
            'cash_sales' => $this->cashSales(),
            'expenses' => $this->totalExpenses(),
            'opening_cash' => $this->opening_cash,
            'expected_cash' => $this->computeExpectedCash(),
            'void_count' => $this->transactions()->whereIn('status', ['void', 'refunded'])->count(),
        ];
    }
}
