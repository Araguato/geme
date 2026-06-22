o<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\LicenseService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('license:issue {type : trial|permanent} {--hwid= : HWID hash to bind} {--days= : Trial days override}', function (LicenseService $license) {
    $type = (string) $this->argument('type');
    $hwid = $this->option('hwid');
    $days = $this->option('days');

    $daysInt = null;
    if ($days !== null && $days !== '') {
        $daysInt = (int) $days;
    }

    $result = $license->issueSerial($type, $hwid ?: null, $daysInt);
    if (!($result['ok'] ?? false)) {
        $this->error('Failed: '.($result['error'] ?? 'unknown'));
        return 1;
    }

    $this->line((string) $result['serial']);
    return 0;
})->purpose('Issue an offline license serial (trial/permanent)');

Artisan::command('license:hwid', function (LicenseService $license) {
    $this->line($license->hwidHash());
    return 0;
})->purpose('Show this PC HWID hash (for offline licensing)');

Artisan::command('license:activate {serial}', function (LicenseService $license) {
    $serial = (string) $this->argument('serial');
    $result = $license->activateFromSerial($serial);

    if (!($result['ok'] ?? false)) {
        $this->error('Activation failed: '.($result['error'] ?? 'unknown'));
        return 1;
    }

    $this->info('License activated successfully!');
    $this->line('Type: '.($result['type'] ?? 'unknown'));
    $this->line('Expires: '.($result['expires_at'] ?? 'never'));

    $status = $license->status();
    $this->line('Status OK: '.($status['ok'] ? 'yes' : 'no'));
    $this->line('State: '.($status['state'] ?? 'unknown'));

    return 0;
})->purpose('Activate a license from a serial string');

Artisan::command('license:status', function (LicenseService $license) {
    $status = $license->status();

    $this->line('OK: '.($status['ok'] ? 'yes' : 'no'));
    $this->line('State: '.($status['state'] ?? 'unknown'));
    $this->line('Type: '.($status['type'] ?? 'none'));
    $this->line('Expires: '.($status['expires_at'] ?? 'never'));
    $this->line('HWID: '.($status['hwid'] ?? 'unknown'));

    return $status['ok'] ? 0 : 1;
})->purpose('Show current license status');
