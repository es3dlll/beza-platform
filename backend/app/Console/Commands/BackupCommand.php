<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class BackupCommand extends Command
{
    protected $signature = 'beza:backup
        {--database : Backup database only}
        {--audit : Backup audit logs only}
        {--output= : Custom output directory}';

    protected $description = 'إنشاء نسخة احتياطية مشفرة للبيانات وسجلات التدقيق';

    private const CIPHER = 'aes-256-cbc';

    public function handle(): int
    {
        $outputDir = $this->option('output') ?? config('app.backup_path', storage_path('backups'));
        $timestamp = now()->format('Y-m-d_H-i-s');
        $key = config('app.backup_encryption_key');

        if (empty($key)) {
            $this->error('BACKUP_ENCRYPTION_KEY غير مضبوط في متغيرات البيئة');
            return self::FAILURE;
        }

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0750, true);
        }

        $dbOnly = (bool) $this->option('database');
        $auditOnly = (bool) $this->option('audit');
        $backupAll = !$dbOnly && !$auditOnly;
        $successCount = 0;

        if ($backupAll || $dbOnly) {
            $this->backupDatabase($outputDir, $timestamp, $key)
                ? $successCount++
                : $this->error('فشل نسخ قاعدة البيانات');
        }

        if ($backupAll || $auditOnly) {
            $this->backupAuditLogs($outputDir, $timestamp, $key)
                ? $successCount++
                : $this->error('فشل نسخ سجلات التدقيق');
        }

        if ($successCount > 0) {
            $this->info("تم إنشاء {$successCount} نسخة احتياطية بنجاح في {$outputDir}");
            Log::info('Backup completed', ['count' => $successCount, 'dir' => $outputDir]);
            return self::SUCCESS;
        }

        return self::FAILURE;
    }

    private function backupDatabase(string $dir, string $ts, string $key): bool
    {
        $dbPath = database_path('database.sqlite');

        if (!file_exists($dbPath)) {
            $this->warn('ملف قاعدة البيانات غير موجود: ' . $dbPath);
            return false;
        }

        $rawPath = "{$dir}/beza-db-{$ts}.sqlite";
        $encPath = "{$rawPath}.enc";

        if (!copy($dbPath, $rawPath)) {
            return false;
        }

        $result = $this->encryptFile($rawPath, $encPath, $key);
        unlink($rawPath);

        if ($result) {
            $this->line("  قاعدة البيانات: " . basename($encPath));
        }

        return $result;
    }

    private function backupAuditLogs(string $dir, string $ts, string $key): bool
    {
        $rawPath = "{$dir}/beza-audit-{$ts}.csv";
        $encPath = "{$rawPath}.enc";

        $rows = DB::table('audit_logs')->orderBy('created_at')->get();
        $handle = fopen($rawPath, 'wb');

        if (!$handle) {
            return false;
        }

        fputcsv($handle, ['id', 'user_id', 'action', 'resource_type', 'resource_id', 'result', 'metadata', 'ip_address', 'user_agent', 'created_at']);

        foreach ($rows as $row) {
            fputcsv($handle, (array) $row);
        }

        fclose($handle);
        $result = $this->encryptFile($rawPath, $encPath, $key);
        unlink($rawPath);

        if ($result) {
            $this->line("  سجلات التدقيق: " . basename($encPath));
        }

        return $result;
    }

    private function encryptFile(string $source, string $destination, string $key): bool
    {
        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER));
        $data = file_get_contents($source);

        if ($data === false) {
            return false;
        }

        $encrypted = openssl_encrypt($data, self::CIPHER, hex2bin($key), OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            return false;
        }

        $result = file_put_contents($destination, $iv . $encrypted);

        return $result !== false;
    }
}
