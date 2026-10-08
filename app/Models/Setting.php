<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public const DELIVERY_FEE = 'delivery_fee';

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting.$key", 300, fn () => static::where('key', $key)->value('value') ?? $default);
    }

    public static function put(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        Cache::forget("setting.$key");
    }

    public static function deliveryFee(): float
    {
        return (float) static::get(self::DELIVERY_FEE, 30);
    }
}
