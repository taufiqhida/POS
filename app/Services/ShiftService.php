<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Outlet;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ShiftService
{
    public function open(User $user, Outlet $outlet, int $openingCash): Shift
    {
        if ($outlet->openShift()) {
            throw ValidationException::withMessages(['shift' => "Masih ada shift terbuka di {$outlet->name}. Tutup dulu sebelum membuka shift baru."]);
        }
        if ($openingCash < 0) {
            throw ValidationException::withMessages(['opening_cash' => 'Modal awal tidak boleh negatif.']);
        }

        $shift = Shift::create([
            'outlet_id' => $outlet->id,
            'opened_by' => $user->id,
            'opened_at' => now(),
            'opening_cash' => $openingCash,
            'status' => 'open',
        ]);
        AuditLog::record('shift_open', $user->id, 'shift', $shift->id, 'Modal awal Rp'.number_format($openingCash, 0, ',', '.'));

        return $shift;
    }

    public function close(Shift $shift, User $user, int $actualCash, ?string $note = null): Shift
    {
        if ($shift->status !== 'open') {
            throw ValidationException::withMessages(['shift' => 'Shift sudah ditutup.']);
        }
        $expected = $shift->computeExpectedCash();
        $shift->update([
            'closed_by' => $user->id,
            'closed_at' => now(),
            'expected_cash' => $expected,
            'actual_cash' => $actualCash,
            'cash_difference' => $actualCash - $expected,
            'status' => 'closed',
            'note' => $note,
        ]);
        AuditLog::record('shift_close', $user->id, 'shift', $shift->id,
            'Seharusnya Rp'.number_format($expected, 0, ',', '.').', fisik Rp'.number_format($actualCash, 0, ',', '.'));

        app(ReportService::class)->notifyOwner(app(ReportService::class)->shiftCloseMessage($shift));

        return $shift;
    }
}
