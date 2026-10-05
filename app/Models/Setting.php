<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'store_name' => 'JELLY POTTER',
        'store_tagline' => 'Jelly Drink & Smoothies',
        'receipt_footer' => 'Terima kasih! Follow IG @jellypotter',
        'owner_whatsapp' => '',
        'report_webhook_url' => '',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        $v = static::find($key)?->value;

        return $v ?? $default ?? (static::DEFAULTS[$key] ?? null);
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
