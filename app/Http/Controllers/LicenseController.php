<?php

namespace App\Http\Controllers;

use App\Services\LicenseService;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function show(LicenseService $license)
    {
        $status = $license->status();

        return view('license.activate', [
            'status' => $status,
        ]);
    }

    public function activate(Request $request, LicenseService $license)
    {
        $data = $request->validate([
            'serial' => 'required|string|min:10',
        ]);

        $result = $license->activateFromSerial($data['serial']);

        if (!($result['ok'] ?? false)) {
            return back()
                ->withInput()
                ->withErrors(['serial' => $this->errorMessage((string) ($result['error'] ?? 'invalid'))]);
        }

        return redirect()->to('/');
    }

    private function errorMessage(string $code): string
    {
        return match ($code) {
            'invalid_format' => __('ui.license.invalid_format'),
            'missing_public_key' => __('ui.license.missing_public_key'),
            'invalid_signature' => __('ui.license.invalid_signature'),
            'invalid_payload' => __('ui.license.invalid_payload'),
            'invalid_type' => __('ui.license.invalid_type'),
            'hwid_mismatch' => __('ui.license.hwid_mismatch'),
            'expired' => __('ui.license.expired'),
            default => __('ui.license.invalid_serial'),
        };
    }
}
