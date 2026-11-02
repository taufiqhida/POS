<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Kategori menu. Menu menyimpan nama kategorinya; mengganti nama di sini ikut mengganti di semua menu. */
class Category extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'sort_order'];

    protected static function booted(): void
    {
        static::updating(function (Category $c) {
            if ($c->isDirty('name')) {
                Menu::withTrashed()->where('category', $c->getOriginal('name'))->update(['category' => $c->name]);
            }
        });

        static::creating(function (Category $c) {
            if (! $c->sort_order) {
                $c->sort_order = ((int) static::max('sort_order')) + 10;
            }
        });
    }

    public function menuCount(): int
    {
        return Menu::where('category', $this->name)->count();
    }

    /** Nama kategori berurutan sesuai pengaturan owner. @return \Illuminate\Support\Collection<int, string> */
    public static function ordered()
    {
        return static::orderBy('sort_order')->orderBy('name')->pluck('name');
    }

    /** Pastikan kategori ada (dipakai seeder & pembuatan menu). */
    public static function ensure(string $name): self
    {
        return static::firstOrCreate(['name' => $name]);
    }
}
