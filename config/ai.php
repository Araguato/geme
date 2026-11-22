<?php

/*
| Inteligencia artificial (opcional).
|
| La IA está desactivada por defecto en cada tienda. El administrador de la tienda puede:
|  - usar su propia clave de API (modo "own"), configurada desde la pantalla de IA, o
|  - usar la IA gestionada por GEME (modo "managed"), solo si este servidor tiene
|    configuradas las variables AI_MANAGED_*.
*/

return [
    'managed' => [
        'base_url' => env('AI_MANAGED_BASE_URL'),
        'api_key' => env('AI_MANAGED_API_KEY'),
        'model' => env('AI_MANAGED_MODEL'),
        // Tokens (entrada + salida) por tienda y mes en modo gestionado
        'monthly_token_limit' => (int) env('AI_MANAGED_MONTHLY_TOKENS', 300000),
    ],

    // Tiempo máximo de espera de una respuesta (segundos)
    'timeout' => (int) env('AI_TIMEOUT', 25),

    // Máximo de tokens de respuesta por consulta
    'max_output_tokens' => (int) env('AI_MAX_OUTPUT_TOKENS', 800),

    // Permitir URLs http:// o de redes privadas (p. ej. un Ollama en la red local).
    // Desactivado por defecto: evita que la URL configurable se use para llegar a
    // servicios internos del servidor (SSRF).
    'allow_private_urls' => (bool) env('AI_ALLOW_PRIVATE_URLS', false),
];
