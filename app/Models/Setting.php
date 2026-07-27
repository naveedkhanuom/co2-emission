<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Global, app-wide key/value settings (not tenant-specific) — e.g. the app logo
 * and name shown on the login screen and as the sidebar fallback. Per-company
 * settings live in CompanySetting instead.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /** Get a global setting value (null-safe with a default). */
    public static function get(string $key, $default = null)
    {
        $value = static::query()->where('key', $key)->value('value');

        return $value ?? $default;
    }

    /** Create or update a global setting. */
    public static function set(string $key, $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
