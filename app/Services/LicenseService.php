<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

class LicenseService
{
    private const SETTING_KEY = 'license_data';

    public function status(): array
    {
        $now = CarbonImmutable::now();

        try {
            $raw = Setting::get(self::SETTING_KEY, '');
        } catch (\Throwable $e) {
            return [
                'ok' => true,
                'state' => 'db_unavailable',
                'message' => '',
                'type' => null,
                'expires_at' => null,
                'days_left' => null,
                'hwid' => $this->hwidHash(),
            ];
        }

        if (!$raw) {
            return [
                'ok' => false,
                'state' => 'missing',
                'message' => '',
                'type' => null,
                'expires_at' => null,
                'days_left' => null,
                'hwid' => $this->hwidHash(),
            ];
        }

        $data = json_decode((string) $raw, true);
        if (!is_array($data)) {
            return [
                'ok' => false,
                'state' => 'corrupt',
                'message' => '',
                'type' => null,
                'expires_at' => null,
                'days_left' => null,
                'hwid' => $this->hwidHash(),
            ];
        }

        $lastSeen = isset($data['last_seen_at']) ? CarbonImmutable::parse($data['last_seen_at']) : null;
        if ($lastSeen && $now->lessThan($lastSeen->subSeconds((int) config('license.clock_rollback_grace_seconds', 300)))) {
            return [
                'ok' => false,
                'state' => 'clock_rollback',
                'message' => '',
                'type' => $data['type'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'days_left' => null,
                'hwid' => $this->hwidHash(),
            ];
        }

        $type = $data['type'] ?? null;
        $expiresAt = $data['expires_at'] ?? null;

        $ok = true;
        $state = 'active';
        $daysLeft = null;

        if ($type === 'trial') {
            if (!$expiresAt) {
                $ok = false;
                $state = 'expired';
            } else {
                $exp = CarbonImmutable::parse($expiresAt);
                if ($now->greaterThan($exp)) {
                    $ok = false;
                    $state = 'expired';
                } else {
                    $daysLeft = (int) max(0, $now->diffInDays($exp, false));
                }
            }
        }

        if (($data['hwid'] ?? '') !== $this->hwidHash()) {
            $ok = false;
            $state = 'hwid_mismatch';
        }

        if ($lastSeen && $now->greaterThanOrEqualTo($lastSeen)) {
            $data['last_seen_at'] = $now->toIso8601String();
            $this->persist($data);
        }

        return [
            'ok' => $ok,
            'state' => $state,
            'message' => '',
            'type' => $type,
            'expires_at' => $expiresAt,
            'days_left' => $daysLeft,
            'hwid' => $this->hwidHash(),
        ];
    }

    public function activateFromSerial(string $serial): array
    {
        $serial = trim($serial);
        $parsed = $this->parseSerial($serial);
        if (!$parsed['ok']) {
            return $parsed;
        }

        $payload = $parsed['payload'];

        $type = $payload['type'] ?? null;
        if (!in_array($type, ['trial', 'permanent'], true)) {
            return ['ok' => false, 'error' => 'invalid_type'];
        }

        $hwid = (string) ($payload['hwid'] ?? '');
        if ($hwid !== $this->hwidHash()) {
            return ['ok' => false, 'error' => 'hwid_mismatch'];
        }

        $now = CarbonImmutable::now();
        $expiresAt = null;
        if ($type === 'trial') {
            $expiresAt = $payload['expires_at'] ?? null;
            if (!$expiresAt) {
                $issuedAt = $payload['issued_at'] ?? null;
                if ($issuedAt) {
                    $expiresAt = CarbonImmutable::parse($issuedAt)->addDays((int) config('license.trial_days', 7))->toIso8601String();
                } else {
                    $expiresAt = $now->addDays((int) config('license.trial_days', 7))->toIso8601String();
                }
            }

            if ($now->greaterThan(CarbonImmutable::parse($expiresAt))) {
                return ['ok' => false, 'error' => 'expired'];
            }
        }

        $data = [
            'type' => $type,
            'expires_at' => $expiresAt,
            'hwid' => $this->hwidHash(),
            'serial_hash' => hash('sha256', $serial),
            'activated_at' => $now->toIso8601String(),
            'last_seen_at' => $now->toIso8601String(),
        ];

        $this->persist($data);

        return ['ok' => true, 'type' => $type, 'expires_at' => $expiresAt];
    }

    public function issueSerial(string $type, ?string $hwidHash = null, ?int $days = null): array
    {
        $type = trim($type);
        if (!in_array($type, ['trial', 'permanent'], true)) {
            return ['ok' => false, 'error' => 'invalid_type'];
        }

        $privateKeyPem = (string) config('license.private_key_pem', '');
        if (!$privateKeyPem) {
            return ['ok' => false, 'error' => 'missing_private_key'];
        }

        $now = CarbonImmutable::now();
        $payload = [
            'v' => 1,
            'product' => (string) config('license.product', 'WAWI'),
            'type' => $type,
            'hwid' => $hwidHash ?: $this->hwidHash(),
            'issued_at' => $now->toIso8601String(),
        ];

        if ($type === 'trial') {
            $trialDays = $days ?? (int) config('license.trial_days', 7);
            $payload['expires_at'] = $now->addDays($trialDays)->toIso8601String();
        }

        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if (!is_string($payloadJson)) {
            return ['ok' => false, 'error' => 'payload_encode_failed'];
        }

        $payloadB64 = $this->b64urlEncode($payloadJson);

        $signature = '';
        $ok = openssl_sign($payloadJson, $signature, $privateKeyPem, OPENSSL_ALGO_SHA256);
        if (!$ok) {
            return ['ok' => false, 'error' => 'sign_failed'];
        }

        $sigB64 = $this->b64urlEncode($signature);

        return ['ok' => true, 'serial' => 'WAWI1.'.$payloadB64.'.'.$sigB64, 'payload' => $payload];
    }

    private function persist(array $data): void
    {
        try {
            Setting::set(self::SETTING_KEY, json_encode($data, JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            Log::warning('License persist failed: '.$e->getMessage());
        }
    }

    private function parseSerial(string $serial): array
    {
        if (!str_starts_with($serial, 'GEME1.') && !str_starts_with($serial, 'WAWI1.')) {
            return ['ok' => false, 'error' => 'invalid_format'];
        }

        $parts = explode('.', $serial);
        if (count($parts) !== 3) {
            return ['ok' => false, 'error' => 'invalid_format'];
        }

        [$prefix, $payloadB64, $sigB64] = $parts;
        if (!in_array($prefix, ['GEME1', 'WAWI1'], true)) {
            return ['ok' => false, 'error' => 'invalid_format'];
        }

        $payloadJson = $this->b64urlDecode($payloadB64);
        $sig = $this->b64urlDecode($sigB64);

        if ($payloadJson === null || $sig === null) {
            return ['ok' => false, 'error' => 'invalid_format'];
        }

        $pub = (string) config('license.public_key_pem', '');
        if (!$pub) {
            return ['ok' => false, 'error' => 'missing_public_key'];
        }

        $verify = openssl_verify($payloadJson, $sig, $pub, OPENSSL_ALGO_SHA256);
        if ($verify !== 1) {
            return ['ok' => false, 'error' => 'invalid_signature'];
        }

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            return ['ok' => false, 'error' => 'invalid_payload'];
        }

        return ['ok' => true, 'payload' => $payload];
    }

    public function hwidHash(): string
    {
        $hwid = $this->machineGuid() ?: $this->biosUuid() ?: gethostname() ?: php_uname('n');
        $hwid = strtolower(trim((string) $hwid));

        return hash('sha256', 'geme|'.$hwid);
    }

    private function machineGuid(): ?string
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return null;
        }

        $cmd = 'reg query "HKLM\\SOFTWARE\\Microsoft\\Cryptography" /v MachineGuid';
        $out = @shell_exec($cmd);
        if (!is_string($out) || $out === '') {
            return null;
        }

        if (preg_match('/MachineGuid\s+REG_SZ\s+([a-fA-F0-9\-]+)/', $out, $m)) {
            return $m[1];
        }

        return null;
    }

    private function biosUuid(): ?string
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return null;
        }

        $out = @shell_exec('wmic csproduct get uuid');
        if (!is_string($out) || $out === '') {
            return null;
        }

        if (preg_match('/\b([0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12})\b/', $out, $m)) {
            return $m[1];
        }

        return null;
    }

    private function b64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function b64urlDecode(string $data): ?string
    {
        $data = strtr($data, '-_', '+/');
        $pad = strlen($data) % 4;
        if ($pad) {
            $data .= str_repeat('=', 4 - $pad);
        }

        $decoded = base64_decode($data, true);
        return $decoded === false ? null : $decoded;
    }
}
