<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements FilamentUser
{
    use HasUuids, Notifiable;

    public const ROLES = ['owner' => 'Owner', 'manager' => 'Manager', 'kasir' => 'Kasir'];

    protected $fillable = ['outlet_id', 'name', 'email', 'password', 'role', 'pin','can_change_price', 'is_active'];

    protected $hidden = ['password', 'pin_hash', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'can_change_price' => 'boolean',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'cashier_id');
    }

    public function shiftsOpened(): HasMany
    {
        return $this->hasMany(Shift::class, 'opened_by');
    }

    /** Atribut virtual `pin` — disimpan sebagai hash di kolom pin_hash. */
    public function setPinAttribute(?string $pin): void
    {
        if (filled($pin)) {
            $this->attributes['pin_hash'] = Hash::make($pin);
        }
    }

    public function checkPin(string $pin): bool
    {
        return $this->pin_hash && Hash::check($pin, $this->pin_hash);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    /** Owner & manager boleh menyetujui aksi sensitif (void, refund, ubah harga, diskon manual). */
    public function canApprove(): bool
    {
        return $this->is_active && ($this->isOwner() || $this->isManager());
    }

    public function canChangePrice(): bool
    {
        return $this->is_active && ($this->isOwner() || $this->can_change_price);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && in_array($this->role, ['owner', 'manager']);
    }
}
