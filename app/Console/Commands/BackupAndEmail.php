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

            // 3. Agregar CREATE SCHEMA al SQL
            $this->fixDatabaseDeclaration($backupPath);

            // 4. Enviar por email
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

        $to = config('mail.backup_email') ?? config('mail.from.address');

        Mail::send('emails.backup', [
            'fecha' => now()->format('d/m/Y H:i:s'),
            'archivo' => $fileName,
            'tamano' => $fileSize,
        ], function ($message) use ($fullPath, $fileName, $to) {
            $message->to($to)
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

    private function fixDatabaseDeclaration(string $backupPath): void
    {
        $disk = Storage::disk('local');
        $fullPath = $disk->path($backupPath);
        $tempDir = storage_path('app/backup-temp');
        
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($fullPath) !== true) {
            $this->warn('No se pudo abrir el zip');
            return;
        }

        // Buscar archivo SQL
        $sqlFileName = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $fileName = $zip->getNameIndex($i);
            if (str_contains($fileName, '.sql')) {
                $sqlFileName = $fileName;
                $this->info('SQL encontrado: ' . $sqlFileName);
                break;
            }
        }

        if (!$sqlFileName) {
            $zip->close();
            $this->error('No se encontro SQL en el backup');
            return;
        }

        // Extraer SQL
        $tempSqlPath = $tempDir . '/temp_backup.sql';
        $zip->extractTo($tempDir, $sqlFileName);
        rename($tempDir . '/' . $sqlFileName, $tempSqlPath);
        
        // Leer contenido
        $sqlContent = file_get_contents($tempSqlPath);
        
        // Verificar si ya tiene CREATE
        if (str_contains($sqlContent, 'CREATE DATABASE') || str_contains($sqlContent, 'CREATE SCHEMA')) {
            unlink($tempSqlPath);
            $zip->close();
            return;
        }

        // Agregar CREATE SCHEMA
        $dbName = config('database.connections.mysql.database', 'control_internet');
        $declaration = "CREATE SCHEMA IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
        $declaration .= "USE `{$dbName}`;\n\n";
        $newContent = $declaration . $sqlContent;
        
        // Guardar modificado
        file_put_contents($tempSqlPath, $newContent);
        
        // Reemplazar en zip
        $zip->deleteName($sqlFileName);
        $zip->addFile($tempSqlPath, $sqlFileName);
        $zip->close();
        
        // Limpiar
        unlink($tempSqlPath);
        
        $this->info('CREATE SCHEMA agregado correctamente');
    }
}
