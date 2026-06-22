<?php

namespace App\Http\Controllers;

use App\Mail\ErrorReportMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ErrorReportController extends Controller
{
    public function create(Request $request)
    {
        $currentUrl = $request->fullUrl();

        return view('admin.error_report.form', [
            'currentUrl' => $currentUrl,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $user = $request->user();

        // Extraer un extracto de los últimos logs para ayudar al diagnóstico
        $logExcerpt = null;
        try {
            $logPath = storage_path('logs/laravel.log');
            if (is_readable($logPath)) {
                $contents = file($logPath, FILE_IGNORE_NEW_LINES);
                if ($contents !== false) {
                    $maxLines = 150;
                    $lines = array_slice($contents, -$maxLines);
                    $logExcerpt = implode("\n", $lines);
                }
            }
        } catch (\Throwable $e) {
            // Si hay algún problema leyendo el log, simplemente no adjuntamos nada
            $logExcerpt = null;
        }

        $payload = [
            'subject' => $data['subject'],
            'description' => $data['description'],
            'url' => $request->input('url') ?: $request->fullUrl(),
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'log_excerpt' => $logExcerpt,
        ];

        Mail::to('schiwatsch@hotmail.com')->send(new ErrorReportMail($payload));

        return redirect()->route('error-report.create')
            ->with('status', 'Tu reporte fue enviado. ¡Gracias por avisar!');
    }
}
