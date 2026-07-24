<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;
use Throwable;

class BackupDatabase extends Command
{
    protected $signature = 'app:backup-database';

    protected $description = 'Create a consistent SQLite backup via VACUUM INTO, gzip it, and upload it to the configured disk.';

    public function handle(): int
    {
        $started = microtime(true);

        if (config('database.default') !== 'sqlite') {
            $this->error('Backup command only supports the SQLite connection.');

            return self::FAILURE;
        }

        $diskName = (string) config('backup.disk', 's3');

        if (! is_array(config("filesystems.disks.{$diskName}"))) {
            $this->error("Backup disk [{$diskName}] is not configured.");

            return self::FAILURE;
        }

        $disk = Storage::disk($diskName);

        $databasePath = config('database.connections.sqlite.database');

        if (! is_string($databasePath) || $databasePath === ':memory:' || ! is_file($databasePath)) {
            $this->error('SQLite database file is missing or in-memory; cannot back up.');

            return self::FAILURE;
        }

        $snapshotPath = storage_path('app/backups/tmp/'.now()->format('Ymd-His').'-'.Str::random(6).'.sqlite');
        $tempDir = dirname($snapshotPath);
        if (! is_dir($tempDir) && ! @mkdir($tempDir, 0755, true) && ! is_dir($tempDir)) {
            $this->error("Unable to create temporary backup directory [{$tempDir}].");

            return self::FAILURE;
        }

        $gzPath = $snapshotPath.'.gz';

        try {
            $this->snapshotDatabase($databasePath, $snapshotPath);
            $this->gzip($snapshotPath, $gzPath);

            $key = $this->buildKey();
            $stream = fopen($gzPath, 'rb');
            if ($stream === false) {
                throw new RuntimeException("Unable to open [{$gzPath}] for upload.");
            }
            try {
                $disk->put($key, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            $localSize = filesize($gzPath);
            $remoteSize = $disk->size($key);

            if ($localSize !== $remoteSize) {
                $disk->delete($key);

                throw new RuntimeException("Upload size mismatch: local {$localSize} bytes vs remote {$remoteSize} bytes.");
            }

            $this->pruneOldBackups($disk);

            $duration = round(microtime(true) - $started, 2);
            Log::info('database backup completed', [
                'key' => $key,
                'size' => $remoteSize,
                'duration_seconds' => $duration,
                'disk' => $diskName,
            ]);
            $this->info("Database backup uploaded to [{$diskName}] as [{$key}] ({$remoteSize} bytes, {$duration}s).");

            return self::SUCCESS;
        } catch (Throwable $e) {
            Log::error('database backup failed', ['error' => $e->getMessage()]);
            $this->error("Database backup failed: {$e->getMessage()}");

            return self::FAILURE;
        } finally {
            if (is_file($snapshotPath)) {
                @unlink($snapshotPath);
            }
            if (is_file($gzPath)) {
                @unlink($gzPath);
            }
        }
    }

    protected function snapshotDatabase(string $databasePath, string $snapshotPath): void
    {
        $pdo = new PDO('sqlite:'.$databasePath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $escaped = str_replace('\\', '/', $snapshotPath);
        $escaped = str_replace("'", "''", $escaped);
        $pdo->exec("VACUUM INTO '{$escaped}'");
        $pdo = null;

        if (! is_file($snapshotPath) || filesize($snapshotPath) === 0) {
            throw new RuntimeException('VACUUM INTO did not produce a snapshot file.');
        }
    }

    protected function gzip(string $source, string $destination): void
    {
        $read = fopen($source, 'rb');
        if ($read === false) {
            throw new RuntimeException("Unable to read snapshot [{$source}].");
        }

        try {
            $write = gzopen($destination, 'wb9');
            if ($write === false) {
                throw new RuntimeException("Unable to open gzip target [{$destination}].");
            }

            try {
                while (! feof($read)) {
                    $chunk = fread($read, 65536);
                    if ($chunk === false) {
                        break;
                    }
                    gzwrite($write, $chunk);
                }
            } finally {
                gzclose($write);
            }
        } finally {
            fclose($read);
        }

        if (! is_file($destination) || filesize($destination) === 0) {
            throw new RuntimeException('Gzip compression did not produce an archive.');
        }
    }

    protected function buildKey(): string
    {
        $now = now();
        $prefix = (string) config('backup.prefix', 'backups/'.config('app.env'));

        return $prefix.'/'.$now->format('Y-m-d').'/'.$now->format('His').'-database.sqlite.gz';
    }

    protected function pruneOldBackups(FilesystemAdapter $disk): void
    {
        $retention = (int) config('backup.retention', 112);
        if ($retention <= 0) {
            return;
        }

        $prefix = (string) config('backup.prefix', 'backups/'.config('app.env'));

        $files = $disk->allFiles($prefix);
        rsort($files);

        $deletable = array_slice($files, $retention);

        foreach ($deletable as $file) {
            $disk->delete($file);
        }
    }
}
