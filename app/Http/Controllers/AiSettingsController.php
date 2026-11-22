<?php

namespace App\Http\Controllers;

use App\Models\AiUsageLog;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiContextBuilder;
use App\Services\Ai\AiException;
use App\Services\Ai\AiSettings;
use App\Services\Ai\AiUrlGuard;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Configuración de la IA opcional (solo admin): modo, proveedor, nivel de datos y
 * consentimiento. Por defecto está desactivada y no se envía nada.
 */
class AiSettingsController extends Controller
{
    public function edit()
    {
        $consent = AiSettings::consent();

        return view('settings.ai', [
            'mode' => AiSettings::mode(),
            'storedMode' => Setting::get('ai_mode', 'off'),
            'managedAvailable' => AiSettings::managedAvailable(),
            'managedModel' => config('ai.managed.model'),
            'baseUrl' => Setting::get('ai_base_url'),
            'model' => Setting::get('ai_model'),
            'keyHint' => AiSettings::ownKeyHint(),
            'dataLevel' => AiSettings::dataLevel(),
            // Vista previa de los dos niveles: el admin ve exactamente qué se enviaría
            'previews' => [
                'aggregate' => AiContextBuilder::build('aggregate'),
                'detailed' => AiContextBuilder::build('detailed'),
            ],
            'enabled' => AiSettings::enabled(),
            'consent' => $consent,
            'consentUser' => $consent['user_id'] ? User::find($consent['user_id']) : null,
            'consentValid' => AiSettings::hasValidConsent(),
            'logs' => AiUsageLog::with('user:id,name')->latest()->limit(20)->get(),
            'monthTokens' => (int) AiUsageLog::where('created_at', '>=', now()->startOfMonth())
                ->selectRaw('COALESCE(SUM(input_tokens + output_tokens), 0) as t')->value('t'),
            'monthCalls' => AiUsageLog::where('created_at', '>=', now()->startOfMonth())->count(),
            'managedLimit' => (int) config('ai.managed.monthly_token_limit'),
        ]);
    }

    public function update(Request $request)
    {
        $modes = AiSettings::managedAvailable() ? AiSettings::MODES : ['off', 'own'];

        $data = $request->validate([
            'mode' => 'required|in:' . implode(',', $modes),
            'base_url' => 'nullable|required_if:mode,own|url:https,http|max:255',
            'model' => 'nullable|required_if:mode,own|string|max:100',
            'api_key' => 'nullable|string|max:500',
            'data_level' => 'required|in:' . implode(',', AiSettings::DATA_LEVELS),
            'consent' => 'nullable|accepted',
        ], [
            'base_url.required_if' => 'Indica la URL de la API del proveedor.',
            'model.required_if' => 'Indica el modelo que quieres usar.',
        ]);

        if ($data['mode'] === 'off') {
            return $this->disable($request);
        }

        if ($data['mode'] === 'own') {
            try {
                AiUrlGuard::check($data['base_url']);
            } catch (AiException $e) {
                throw ValidationException::withMessages(['base_url' => $e->getMessage()]);
            }
            if (blank($data['api_key'] ?? null) && !AiSettings::hasOwnKey()) {
                throw ValidationException::withMessages(['api_key' => 'Introduce la clave de API de tu proveedor.']);
            }
        }

        $levelChanged = $data['data_level'] !== AiSettings::dataLevel();
        $needsConsent = !AiSettings::hasValidConsent() || $levelChanged;
        if ($needsConsent && !$request->boolean('consent')) {
            throw ValidationException::withMessages([
                'consent' => 'Para activar la IA debes revisar los datos de ejemplo y marcar la autorización.',
            ]);
        }

        $before = ['mode' => Setting::get('ai_mode', 'off'), 'data_level' => AiSettings::dataLevel(), 'model' => Setting::get('ai_model')];

        Setting::set('ai_mode', $data['mode']);
        Setting::set('ai_data_level', $data['data_level']);
        if ($data['mode'] === 'own') {
            Setting::set('ai_base_url', rtrim($data['base_url'], '/'));
            Setting::set('ai_model', $data['model']);
            if (filled($data['api_key'] ?? null)) {
                AiSettings::storeOwnKey(trim($data['api_key']));
            }
        }
        if ($request->boolean('consent')) {
            AiSettings::recordConsent($request->user()->id, $data['data_level']);
        }

        $this->audit($request, 'ai.settings.update', 'Configuró la IA', $before, [
            'mode' => $data['mode'],
            'data_level' => $data['data_level'],
            'model' => $data['mode'] === 'own' ? $data['model'] : config('ai.managed.model'),
            'provider_host' => $data['mode'] === 'own' ? parse_url($data['base_url'], PHP_URL_HOST) : 'gestionado',
            'key_changed' => filled($data['api_key'] ?? null),
            'consent_recorded' => $request->boolean('consent'),
        ]);

        return redirect()->route('settings.ai.edit')->with('success', 'Configuración de IA guardada.');
    }

    public function disable(Request $request)
    {
        $before = ['mode' => Setting::get('ai_mode', 'off')];
        AiSettings::purge();
        $this->audit($request, 'ai.disable', 'Desactivó la IA y borró la clave y el consentimiento', $before, ['mode' => 'off']);

        return redirect()->route('settings.ai.edit')
            ->with('success', 'IA desactivada. Se borraron la clave, la URL, el modelo y el consentimiento. No se enviarán datos.');
    }

    public function test(Request $request)
    {
        try {
            $result = AiClient::testConnection($request->user()->id);
        } catch (AiException $e) {
            return redirect()->route('settings.ai.edit')->with('error', 'Prueba fallida: ' . $e->getMessage());
        }

        return redirect()->route('settings.ai.edit')->with('success', sprintf(
            'Conexión correcta. El modelo respondió: «%s» (%d tokens).',
            mb_strimwidth(trim($result['content']), 0, 60, '…'),
            $result['input_tokens'] + $result['output_tokens']
        ));
    }

    protected function audit(Request $request, string $action, string $description, array $before, array $after): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'entity_type' => 'settings.ai',
            'description' => $description,
            'data_before' => $before,
            'data_after' => $after,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);
    }
}
