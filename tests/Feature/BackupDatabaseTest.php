<?php

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

function backupTempSqliteWithProbe(): array
{
    $path = tempnam(sys_get_temp_dir(), 'sqlite_');
    @unlink($path);

    $pdo = new PDO('sqlite:'.$path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE TABLE backup_probe (id INTEGER PRIMARY KEY, label TEXT NOT NULL)');
    $pdo->exec("INSERT INTO backup_probe (label) VALUES ('snapshot-ok')");
    $pdo = null;

    return [$path, 'snapshot-ok'];
}

function restoreSqliteFromGz(FilesystemAdapter $disk, string $key): string
{
    $path = tempnam(sys_get_temp_dir(), 'restore_');
    file_put_contents($path, gzdecode((string) $disk->get($key)));

    return $path;
}

test('it uploads a gzipped sqlite backup that contains the source data', function (): void {
    [$dbPath, $label] = backupTempSqliteWithProbe();
    config(['database.connections.sqlite.database' => $dbPath]);
    config(['backup.disk' => 's3']);
    Storage::fake('s3');

    $restored = null;

    try {
        $this->artisan('app:backup-database')->assertSuccessful();

        $disk = Storage::disk('s3');
        $files = $disk->allFiles('backups/testing');
        expect($files)->toHaveCount(1)
            ->and($files[0])->toEndWith('.sqlite.gz');

        $restored = restoreSqliteFromGz($disk, $files[0]);
        $row = (new PDO('sqlite:'.$restored))->query('SELECT label FROM backup_probe LIMIT 1')->fetchColumn();
        expect($row)->toBe($label);
    } finally {
        @unlink($dbPath);
        if (is_string($restored)) {
            @unlink($restored);
        }
    }
});

test('it prunes backups beyond the retention limit', function (): void {
    [$dbPath] = backupTempSqliteWithProbe();
    config(['database.connections.sqlite.database' => $dbPath]);
    config(['backup.disk' => 's3', 'backup.retention' => 3]);
    Storage::fake('s3');

    $disk = Storage::disk('s3');
    $stamps = [
        '2026-07-18/010000',
        '2026-07-19/020000',
        '2026-07-20/030000',
        '2026-07-21/040000',
        '2026-07-22/050000',
    ];
    foreach ($stamps as $stamp) {
        $disk->put("backups/testing/{$stamp}-database.sqlite.gz", 'old');
    }

    try {
        $this->artisan('app:backup-database')->assertSuccessful();

        expect($disk->allFiles('backups/testing'))->toHaveCount(3);

        $disk->assertMissing('backups/testing/2026-07-18/010000-database.sqlite.gz');
        $disk->assertMissing('backups/testing/2026-07-19/020000-database.sqlite.gz');
        $disk->assertMissing('backups/testing/2026-07-20/030000-database.sqlite.gz');
        $disk->assertExists('backups/testing/2026-07-21/040000-database.sqlite.gz');
        $disk->assertExists('backups/testing/2026-07-22/050000-database.sqlite.gz');
    } finally {
        @unlink($dbPath);
    }
});

test('it fails fast when the default connection is not sqlite', function (): void {
    config(['backup.disk' => 's3']);
    Storage::fake('s3');

    $original = config('database.default');
    try {
        config(['database.default' => 'mysql']);

        $this->artisan('app:backup-database')->assertFailed();

        expect(Storage::disk('s3')->allFiles('backups/testing'))->toBeEmpty();
    } finally {
        config(['database.default' => $original]);
    }
});

test('it fails fast when the sqlite database is in-memory or missing', function (): void {
    config(['backup.disk' => 's3']);
    Storage::fake('s3');

    $this->artisan('app:backup-database')->assertFailed();

    expect(Storage::disk('s3')->allFiles('backups/testing'))->toBeEmpty();
});
