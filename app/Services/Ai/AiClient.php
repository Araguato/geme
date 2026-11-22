<?php

namespace App\Services\Ai;

use App\Models\AiUsageLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Cliente para cualquier proveedor con API compatible con OpenAI (/chat/completions):
 * OpenAI, Mistral, DeepSeek, Qwen, OpenRouter, Ollama, etc.
 *
 * Todo pasa por aquí: comprueba que la IA esté activada y con consentimiento, valida la
 * URL, aplica el límite mensual del modo gestionado y registra cada llamada.
 */
class AiClient
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{content: string, input_tokens: int, output_tokens: int}
     */
    public static function chat(array $messages, string $purpose, ?int $userId = null, bool $requireConsent = true): array
    {
        $mode = AiSettings::mode();
        if ($mode === 'off') {
            throw new AiException('La IA está desactivada en esta tienda.');
        }
        if ($requireConsent && !AiSettings::hasValidConsent()) {
            throw new AiException('Falta el consentimiento del administrador para enviar datos a la IA.');
        }

        $baseUrl = rtrim((string) AiSettings::baseUrl(), '/');
        $model = AiSettings::model();
        $apiKey = AiSettings::apiKey();
        if (!$baseUrl || !$model) {
            throw new AiException('Falta configurar la URL o el modelo del proveedor.');
        }

        if ($mode === 'own') {
            AiUrlGuard::check($baseUrl);
        }

        if ($mode === 'managed') {
            $used = (int) AiUsageLog::where('mode', 'managed')
                ->where('created_at', '>=', now()->startOfMonth())
                ->selectRaw('COALESCE(SUM(input_tokens + output_tokens), 0) as t')->value('t');
            if ($used >= (int) config('ai.managed.monthly_token_limit')) {
                throw new AiException('Se alcanzó el límite mensual de la IA gestionada.');
            }
        }

        $log = [
            'user_id' => $userId,
            'mode' => $mode,
            'provider_host' => parse_url($baseUrl, PHP_URL_HOST),
            'model' => $model,
            'purpose' => $purpose,
            'data_level' => AiSettings::dataLevel(),
        ];
        $started = microtime(true);

        try {
            $request = Http::acceptJson()
                ->timeout((int) config('ai.timeout', 25))
                ->withoutRedirecting();
            if ($apiKey) {
                $request = $request->withToken($apiKey);
            }

            $response = $request->post($baseUrl . '/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.2,
                'max_tokens' => (int) config('ai.max_output_tokens', 800),
            ]);
        } catch (ConnectionException) {
            self::log($log + ['status' => 'error', 'error' => 'Sin conexión o tiempo agotado'], $started);
            throw new AiException('No se pudo conectar con el proveedor de IA (sin conexión o tiempo agotado).');
        } catch (Throwable $e) {
            self::log($log + ['status' => 'error', 'error' => substr(class_basename($e), 0, 255)], $started);
            throw new AiException('Error inesperado al llamar a la IA.');
        }

        if (!$response->successful()) {
            $status = $response->status();
            $message = match (true) {
                in_array($status, [401, 403], true) => 'El proveedor rechazó la clave de API (revisa la clave y los permisos).',
                $status === 404 => 'URL o modelo no encontrados en el proveedor.',
                $status === 429 => 'El proveedor indica límite de uso o saldo insuficiente.',
                $status >= 300 && $status < 400 => 'El proveedor respondió con una redirección; usa la URL final de la API.',
                default => 'El proveedor respondió con un error (' . $status . ').',
            };
            self::log($log + ['status' => 'error', 'error' => 'HTTP ' . $status], $started);
            throw new AiException($message);
        }

        $content = (string) data_get($response->json(), 'choices.0.message.content', '');
        $in = (int) data_get($response->json(), 'usage.prompt_tokens', 0);
        $out = (int) data_get($response->json(), 'usage.completion_tokens', 0);

        self::log($log + ['status' => 'ok', 'input_tokens' => $in, 'output_tokens' => $out], $started);

        return ['content' => $content, 'input_tokens' => $in, 'output_tokens' => $out];
    }

    /** Prueba de conexión: no envía ningún dato del negocio. */
    public static function testConnection(?int $userId = null): array
    {
        return self::chat([
            ['role' => 'system', 'content' => 'Eres una prueba de conexión. Responde únicamente "OK".'],
            ['role' => 'user', 'content' => 'Prueba de conexión de GEME.'],
        ], 'test', $userId, false);
    }

    protected static function log(array $data, float $started): void
    {
        AiUsageLog::create($data + ['duration_ms' => (int) round((microtime(true) - $started) * 1000)]);
    }
}
