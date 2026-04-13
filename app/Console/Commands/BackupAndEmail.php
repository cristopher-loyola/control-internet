<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BackupAndEmail extends Command
{
    protected $signature = 'backup:email';
    protected $description = 'Genera backup y lo envia por email';

    public function handle(): int
    {
        $this->info('Generando backup...');

        try {
            // 1. Generar backup local
            $this->call('backup:run');

            // 2. Buscar el ultimo backup generado
            $backupPath = $this->getLatestBackup();

            if (!$backupPath) {
                $this->error('No se encontro el backup');
                return 1;
            }

            $this->info('Backup encontrado: ' . basename($backupPath));

            // 3. Enviar por email
            $this->sendEmail($backupPath);

            $this->info('Backup enviado exitosamente a tu email');
            Log::info('Backup enviado por email: ' . basename($backupPath));

            return 0;

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('Backup email fallo: ' . $e->getMessage());
            return 1;
        }
    }

    private function getLatestBackup(): ?string
    {
        $disk = Storage::disk('local');
        
        // Buscar el directorio de backups (Laravel 11 usa storage/app/private/)
        $baseDir = 'private/control-internet';
        
        if (!$disk->exists($baseDir)) {
            // Fallback a ruta anterior por si acaso
            $baseDir = 'control-internet';
            if (!$disk->exists($baseDir)) {
                $this->warn('Directorio no encontrado: ' . $baseDir);
                $this->warn('Directorios en storage/app: ' . implode(', ', $disk->directories()));
                return null;
            }
        }

        // Listar subdirectorios (Spatie crea subcarpetas con fechas)
        $subDirs = $disk->directories($baseDir);
        
        if (empty($subDirs)) {
            // Buscar directamente en el directorio base
            $files = $disk->files($baseDir);
            $zipFiles = array_filter($files, fn($f) => str_ends_with($f, '.zip'));
            sort($zipFiles);
            return !empty($zipFiles) ? $zipFiles[array_key_last($zipFiles)] : null;
        }
        
        // Ordenar subdirectorios por fecha (nombre)
        sort($subDirs);
        $latestDir = $subDirs[array_key_last($subDirs)];
        
        // Buscar archivos zip en el subdirectorio más reciente
        $files = $disk->files($latestDir);
        $zipFiles = array_filter($files, fn($f) => str_ends_with($f, '.zip'));
        sort($zipFiles);
        
        return !empty($zipFiles) ? $zipFiles[array_key_last($zipFiles)] : null;
    }

    private function sendEmail(string $backupPath): void
    {
        $disk = Storage::disk('local');
        $fullPath = $disk->path($backupPath);
        $fileName = basename($backupPath);
        $fileSize = $this->formatBytes($disk->size($backupPath));

        Mail::send('emails.backup', [
            'fecha' => now()->format('d/m/Y H:i:s'),
            'archivo' => $fileName,
            'tamano' => $fileSize,
        ], function ($message) use ($fullPath, $fileName) {
            $message->to(env('BACKUP_EMAIL', env('MAIL_FROM_ADDRESS')))
                    ->subject('Backup Control Internet - ' . now()->format('d/m/Y'))
                    ->attach($fullPath, [
                        'as' => $fileName,
                        'mime' => 'application/zip',
                    ]);
        });
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}
