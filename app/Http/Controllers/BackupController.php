<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    public function create()
    {
        $dbName = env('DB_DATABASE');
        $dbUser = env('DB_USERNAME');
        $dbPass = env('DB_PASSWORD');
        $host = env('DB_HOST', '127.0.0.1');

        $dumpPath = env('MYSQL_DUMP_PATH', 'C:\\xampp\\mysql\\bin\\mysqldump.exe');

        $timestamp = now()->format('Ymd_His');
        $fileName = "backup_{$dbName}_{$timestamp}.sql";
        $storagePath = storage_path('app/backups');

        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $filePath = $storagePath . DIRECTORY_SEPARATOR . $fileName;

        $command = '"' . $dumpPath . '" --skip-ssl --host=' . escapeshellarg($host) . ' --user=' . escapeshellarg($dbUser);
        if ($dbPass !== null && $dbPass !== '') {
            $command .= ' --password=' . escapeshellarg($dbPass);
        }
        $command .= ' ' . escapeshellarg($dbName) . ' > ' . escapeshellarg($filePath);

        $result = null;
        $output = [];
        exec($command, $output, $result);

        if ($result !== 0) {
            return back()->with('error', 'No se pudo generar el respaldo. Verifica la ruta de mysqldump y las credenciales de la base de datos.');
        }

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
