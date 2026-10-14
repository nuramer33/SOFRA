<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Nightly backup: a gzipped database dump and a zip of every uploaded image.
 * Old backups are deleted after `sofra.backup.keep_days`.
 *
 * Copy the backups folder off the server too (a backup that lives only on the
 * machine that dies is not a backup).
 */
#[Signature('sofra:backup')]
#[Description('Back up the database and uploaded images, and delete old backups')]
class Backup extends Command
{
    public function handle(): int
    {
        $directory = config('sofra.backup.path');
        File::ensureDirectoryExists($directory);
        $stamp = now()->format('Y-m-d_His');

        if (! $this->dumpDatabase("{$directory}/db-{$stamp}.sql")) {
            return self::FAILURE;
        }

        $this->zipUploads("{$directory}/files-{$stamp}.zip");
        $this->prune($directory);

        $this->info("Backup saved in {$directory}");

        return self::SUCCESS;
    }

    private function dumpDatabase(string $sqlPath): bool
    {
        $connection = config('database.connections.'.config('database.default'));

        // The password travels in the environment, not on the command line
        // (where any user on the server could read it with `ps`).
        $result = Process::env(['MYSQL_PWD' => (string) ($connection['password'] ?? '')])->run([
            config('sofra.backup.mysqldump'),
            '--host='.($connection['host'] ?? '127.0.0.1'),
            '--port='.($connection['port'] ?? 3306),
            '--user='.($connection['username'] ?? 'root'),
            '--single-transaction',
            '--no-tablespaces',
            "--result-file={$sqlPath}",
            $connection['database'],
        ]);

        if ($result->failed() || ! File::exists($sqlPath)) {
            $this->error('The database dump failed: '.trim($result->errorOutput()));
            File::delete($sqlPath);

            return false;
        }

        $this->gzip($sqlPath);

        return true;
    }

    private function gzip(string $path): void
    {
        $target = gzopen("{$path}.gz", 'wb9');
        $source = fopen($path, 'rb');

        while (! feof($source)) {
            gzwrite($target, fread($source, 1024 * 512));
        }

        fclose($source);
        gzclose($target);
        File::delete($path);
    }

    private function zipUploads(string $zipPath): void
    {
        $disk = Storage::disk('public');
        $files = $disk->allFiles('shops');

        if ($files === []) {
            return;
        }

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $file) {
            $zip->addFile($disk->path($file), $file);
        }

        $zip->close();
    }

    private function prune(string $directory): void
    {
        $limit = now()->subDays(config('sofra.backup.keep_days'))->getTimestamp();

        foreach (File::files($directory) as $file) {
            if (preg_match('/^(db|files)-.*\.(sql\.gz|zip)$/', $file->getFilename()) && $file->getMTime() < $limit) {
                File::delete($file->getPathname());
            }
        }
    }
}
