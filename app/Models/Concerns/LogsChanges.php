<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Catat siapa mengubah harga / bahan / data master (nilai lama → baru) ke Riwayat Aktivitas.
 * Hanya mencatat bila ada yang login (perubahan lewat panel admin), bukan seeder / sistem.
 *
 * Model menentukan: AUDITED (kolom => label), MONEY (kolom yang berupa rupiah), dan auditLabel().
 */
trait LogsChanges
{
    public static function bootLogsChanges(): void
    {
        static::created(fn (Model $m) => $m->writeAudit('ditambahkan'.$m->auditSummary()));
        static::updated(function (Model $m) {
            $diff = [];
            foreach (static::AUDITED as $col => $label) {
                if ($m->wasChanged($col)) {
                    $diff[] = "{$label}: ".$m->auditValue($col, $m->getOriginal($col)).' → '.$m->auditValue($col, $m->$col);
                }
            }
            if ($diff) {
                $m->writeAudit(implode('; ', $diff), $m->auditAction($m->getChanges()));
            }
        });
        static::deleted(fn (Model $m) => $m->writeAudit('dihapus'));
    }

    /** Harga berubah = 'master_price'; stok berubah = 'stock_update'; selain itu 'master_edit'. */
    protected function auditAction(array $changes): string
    {
        if (array_key_exists('stock_current', $changes)) {
            return 'stock_update';
        }

        return array_intersect(array_keys($changes), static::MONEY) ? 'master_price' : 'master_edit';
    }

    protected function auditSummary(): string
    {
        $parts = [];
        foreach (static::MONEY as $col) {
            $parts[] = static::AUDITED[$col].' '.$this->auditValue($col, $this->$col);
        }

        return $parts ? ' ('.implode(', ', $parts).')' : '';
    }

    protected function auditValue(string $col, mixed $v): string
    {
        if (in_array($col, static::MONEY)) {
            return 'Rp'.number_format((int) $v, 0, ',', '.');
        }
        if (is_bool($v) || in_array($v, [0, 1, '0', '1'], true) && str_starts_with($col, 'is_')) {
            return $v ? 'ya' : 'tidak';
        }

        return $v === null || $v === '' ? '—' : (string) $v;
    }

    protected function writeAudit(string $detail, ?string $action = null): void
    {
        if (! auth()->check()) {
            return;
        }
        $action ??= str_starts_with($detail, 'ditambahkan') && static::MONEY ? 'master_price' : 'master_edit';
        AuditLog::record($action, auth()->id(), static::class, $this->getKey(), $this->auditLabel().' '.$detail);
    }
}
