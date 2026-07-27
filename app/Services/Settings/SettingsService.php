<?php

namespace App\Services\Settings;

use App\Models\Setting;
use App\Models\SystemSettingRevision;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Central key/value settings store. Everything an admin can toggle (fees
 * defaults, automation switches, branding, contact info...) lives here so the
 * platform has "no hardcoded content". Values are cached and type-cast.
 */
class SettingsService
{
    private const CACHE_KEY = 'platform.settings';

    /** Keys whose values are secrets — masked in revision history, never stored raw. */
    private const SENSITIVE_KEYS = ['mail_password'];

    /** @var array<string, mixed>|null */
    private ?array $cache = null;

    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        return $this->cache = Cache::rememberForever(self::CACHE_KEY, function () {
            return Setting::all()->mapWithKeys(fn (Setting $s) => [
                $s->key => $this->castValue($s->value, $s->type),
            ])->all();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    public function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): Setting
    {
        $oldValue = Setting::where('key', $key)->value('value');
        $newValue = is_array($value) ? json_encode($value) : (string) $value;

        $setting = Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $newValue, 'type' => $type, 'group' => $group],
        );

        if ($oldValue !== $newValue) {
            $masked = $this->isSensitiveKey($key);
            SystemSettingRevision::create([
                'key' => $key,
                'old_value' => $masked ? ($oldValue !== null ? '••••••••' : null) : $oldValue,
                'new_value' => $masked ? '••••••••' : $newValue,
                'changed_by' => Auth::id(),
            ]);
        }

        $this->flush();

        return $setting;
    }

    public function isSensitiveKey(string $key): bool
    {
        return in_array($key, self::SENSITIVE_KEYS, true);
    }

    public function flush(): void
    {
        $this->cache = null;
        Cache::forget(self::CACHE_KEY);
    }

    private function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'json', 'array' => json_decode((string) $value, true) ?? [],
            default => $value,
        };
    }
}
