<?php

namespace App\Services\Ai;

/**
 * Valida la URL del proveedor de IA que configura el cliente. Sin esta comprobación, la
 * URL podría usarse para que el servidor haga peticiones a servicios internos (SSRF):
 * la base de datos, otras apps de CapRover o metadatos del proveedor cloud.
 */
class AiUrlGuard
{
    /** @var callable|null Resolución DNS reemplazable en tests: fn(string $host): array<string> */
    public static $resolver = null;

    public static function check(string $url): void
    {
        $allowPrivate = (bool) config('ai.allow_private_urls');
        $parts = parse_url($url);

        if (!$parts || empty($parts['host']) || empty($parts['scheme'])) {
            throw new AiException('La URL del proveedor no es válida.');
        }

        $scheme = strtolower($parts['scheme']);
        if ($scheme !== 'https' && !($allowPrivate && $scheme === 'http')) {
            throw new AiException('La URL del proveedor debe usar https://.');
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new AiException('La URL no puede contener usuario ni contraseña.');
        }

        if ($allowPrivate) {
            return;
        }

        $host = trim($parts['host'], '[]');
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : self::resolve($host);

        if (!$ips) {
            throw new AiException('No se pudo resolver el nombre del proveedor.');
        }

        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new AiException('La URL apunta a una red interna o reservada; no está permitida.');
            }
        }
    }

    protected static function resolve(string $host): array
    {
        if (self::$resolver) {
            return (self::$resolver)($host);
        }

        $v4 = @gethostbynamel($host) ?: [];
        $v6 = [];
        $records = @dns_get_record($host, DNS_AAAA) ?: [];
        foreach ($records as $r) {
            if (!empty($r['ipv6'])) {
                $v6[] = $r['ipv6'];
            }
        }

        return array_merge($v4, $v6);
    }
}
