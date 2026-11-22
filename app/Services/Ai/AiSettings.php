<?php

namespace App\Services\Ai;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Configuración de IA de la tienda (tabla settings). La clave de API se guarda cifrada
 * con la APP_KEY y nunca se devuelve a la vista.
 */
class AiSettings
{
    public const MODES = ['off', 'own', 'managed'];
    public const DATA_LEVELS = ['aggregate', 'detailed'];

    /** Versión del texto de consentimiento: si cambia, hay que volver a aceptar. */
    public const CONSENT_VERSION = '2026-11-v1';

    public static function mode(): string
    {
        $mode = Setting::get('ai_mode', 'off');

        if (!in_array($mode, self::MODES, true)) {
            return 'off';
        }
        if ($mode === 'managed' && !self::managedAvailable()) {
            return 'off';
        }

        return $mode;
    }

    public static function enabled(): bool
    {
        return self::mode() !== 'off' && self::hasValidConsent();
    }

    public static function managedAvailable(): bool
    {
        return filled(config('ai.managed.base_url')) && filled(config('ai.managed.api_key')) && filled(config('ai.managed.model'));
    }

    public static function dataLevel(): string
    {
        $level = Setting::get('ai_data_level', 'aggregate');

        return in_array($level, self::DATA_LEVELS, true) ? $level : 'aggregate';
    }

    public static function baseUrl(): ?string
    {
        return self::mode() === 'managed' ? config('ai.managed.base_url') : Setting::get('ai_base_url');
    }

    public static function model(): ?string
    {
        return self::mode() === 'managed' ? config('ai.managed.model') : Setting::get('ai_model');
    }

    public static function apiKey(): ?string
    {
        if (self::mode() === 'managed') {
            return config('ai.managed.api_key');
        }

        $encrypted = Setting::get('ai_api_key');
        if (!$encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            return null;
        }
    }

    public static function hasOwnKey(): bool
    {
        return filled(Setting::get('ai_api_key'));
    }

    /** Últimos 4 caracteres de la clave propia, para que el admin la reconozca. */
    public static function ownKeyHint(): ?string
    {
        $key = self::mode() === 'own' || self::hasOwnKey() ? self::decryptOwnKey() : null;

        return $key ? '••••' . substr($key, -4) : null;
    }

    protected static function decryptOwnKey(): ?string
    {
        $encrypted = Setting::get('ai_api_key');
        if (!$encrypted) {
            return null;
        }
        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            return null;
        }
    }

    public static function storeOwnKey(string $key): void
    {
        Setting::set('ai_api_key', Crypt::encryptString($key));
    }

    public static function consent(): array
    {
        return [
            'at' => Setting::get('ai_consent_at'),
            'user_id' => Setting::get('ai_consent_by'),
            'version' => Setting::get('ai_consent_version'),
            'data_level' => Setting::get('ai_consent_data_level'),
        ];
    }

    /** El consentimiento vale si es de la versión actual y del nivel de datos actual. */
    public static function hasValidConsent(): bool
    {
        $c = self::consent();

        return filled($c['at']) && $c['version'] === self::CONSENT_VERSION && $c['data_level'] === self::dataLevel();
    }

    public static function recordConsent(int $userId, string $dataLevel): void
    {
        Setting::set('ai_consent_at', now()->toDateTimeString());
        Setting::set('ai_consent_by', (string) $userId);
        Setting::set('ai_consent_version', self::CONSENT_VERSION);
        Setting::set('ai_consent_data_level', $dataLevel);
    }

    /** Desactiva la IA y borra clave, URL, modelo y consentimiento. */
    public static function purge(): void
    {
        Setting::set('ai_mode', 'off');
        foreach (['ai_api_key', 'ai_base_url', 'ai_model', 'ai_consent_at', 'ai_consent_by', 'ai_consent_version', 'ai_consent_data_level'] as $key) {
            Setting::where('key', $key)->delete();
        }
    }
}
