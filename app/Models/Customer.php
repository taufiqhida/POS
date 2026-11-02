<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

/** Member Jelly Point: nomor HP + PIN 4 angka. Cap digabung untuk semua outlet. */
class Customer extends Model
{
    use HasUuids;

    public const MAX_PIN_FAILS = 5;

    /** Cap hangus bila tidak berkunjung selama ini. */
    public const EXPIRE_MONTHS = 6;

    protected $fillable = ['phone', 'name', 'pin', 'pin_failed', 'stamps', 'stamps_lifetime', 'visits', 'is_staff',
        'can_contact', 'last_visit_at', 'last_contacted_at'];

    protected $hidden = ['pin_hash'];

    protected $casts = ['is_staff' => 'boolean', 'can_contact' => 'boolean', 'stamps' => 'integer',
        'last_visit_at' => 'datetime', 'last_contacted_at' => 'datetime'];

    /** Atribut virtual `pin` — disimpan sebagai hash. */
    public function setPinAttribute(?string $pin): void
    {
        if (filled($pin)) {
            $this->attributes['pin_hash'] = Hash::make($pin);
        }
    }

    /** Rapikan nomor HP ke format 62xxxxxxxxxx; null bila bukan nomor HP Indonesia yang masuk akal. */
    public static function normalizePhone(?string $raw): ?string
    {
        $d = preg_replace('/\D/', '', (string) $raw);
        if (str_starts_with($d, '0')) {
            $d = '62'.substr($d, 1);
        } elseif (str_starts_with($d, '8')) {
            $d = '62'.$d;
        }

        return preg_match('/^628\d{8,11}$/', $d) ? $d : null;
    }

    public static function findByPhone(?string $raw): ?self
    {
        $phone = static::normalizePhone($raw);

        return $phone ? static::where('phone', $phone)->first() : null;
    }

    public function isLocked(): bool
    {
        return $this->pin_failed >= self::MAX_PIN_FAILS;
    }

    /** Cek PIN; gagal 5× berturut-turut mengunci PIN sampai direset owner/manager. */
    public function checkPin(string $pin): bool
    {
        if ($this->isLocked()) {
            return false;
        }
        if (Hash::check($pin, $this->pin_hash)) {
            if ($this->pin_failed) {
                $this->update(['pin_failed' => 0]);
            }

            return true;
        }
        $this->increment('pin_failed');

        return false;
    }

    /** Hanguskan cap bila sudah lewat batas waktu sejak kunjungan terakhir. */
    public function expireIfStale(): self
    {
        if ($this->stamps > 0 && $this->last_visit_at && $this->last_visit_at->lt(now()->subMonths(self::EXPIRE_MONTHS))) {
            StampLog::create(['customer_id' => $this->id, 'change' => -$this->stamps, 'reason' => 'expire',
                'note' => 'Tidak berkunjung '.self::EXPIRE_MONTHS.' bulan']);
            $this->update(['stamps' => 0]);
        }

        return $this;
    }

    public static function perReward(): int
    {
        return max(1, (int) Setting::get('stamps_per_reward', '20'));
    }

    public function canRedeem(): bool
    {
        return ! $this->is_staff && $this->stamps >= self::perReward();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(StampLog::class)->latest();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** Pesan WA manual (dikirim kasir/owner dengan sekali klik, bukan massal). */
    public function whatsappUrl(?string $message = null): string
    {
        $message ??= sprintf('Halo %s, ini Jelly Potter 👋 Cap kamu sekarang %d dari %d. Yuk mampir lagi, ada minuman gratis menunggu!',
            $this->name, $this->stamps, self::perReward());

        return 'https://wa.me/'.$this->phone.'?text='.rawurlencode($message);
    }
}
