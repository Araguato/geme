<?php

return [
    'product' => 'geme',

    'trial_days' => 7,

    'public_key_pem' => (function () {
        $path = base_path('license_public.pem');
        if (is_string($path) && file_exists($path)) {
            $contents = file_get_contents($path);
            return is_string($contents) ? $contents : '';
        }
        return env('LICENSE_PUBLIC_KEY_PEM', '');
    })(),

    'private_key_pem' => (function () {
        $path = base_path('license_private.pem');
        if (is_string($path) && file_exists($path)) {
            $contents = file_get_contents($path);
            return is_string($contents) ? $contents : '';
        }
        return env('LICENSE_PRIVATE_KEY_PEM', '');
    })(),

    'clock_rollback_grace_seconds' => 300,
];
