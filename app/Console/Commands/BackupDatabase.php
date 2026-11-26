<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    protected $signature = 'bargo:backup';

    protected $description = 'Write a timestamped database backup under storage/app/backups.';

    public function handle(): int
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        $connection = config('database.default');
        $stamp = now()->format('Ymd-His');

        if ($connection === 'sqlite') {
            $source = config('database.connections.sqlite.database');
            $target = $dir.DIRECTORY_SEPARATOR.'bargo-'.$stamp.'.sqlite';
            File::copy($source, $target);
            $this->info('SQLite copy saved: '.$target);

            return self::SUCCESS;
        }

        if ($connection === 'mysql') {
            $cfg = config('database.connections.mysql');
            $target = $dir.DIRECTORY_SEPARATOR.'bargo-'.$stamp.'.sql';
            $user = escapeshellarg($cfg['username']);
            $pass = $cfg['password'] !== '' ? '-p'.escapeshellarg($cfg['password']) : '';
            $host = escapeshellarg($cfg['host']);
            $db = escapeshellarg($cfg['database']);
            $cmd = "mysqldump -h {$host} -u {$user} {$pass} {$db} > ".escapeshellarg($target);

            exec($cmd, $output, $code);

            if ($code !== 0) {
                $this->error('mysqldump failed. Install MySQL client tools or run a dump manually.');

                return self::FAILURE;
            }

            $this->info('MySQL dump saved: '.$target);

            return self::SUCCESS;
        }

        $this->error('Unsupported database driver: '.$connection);

        return self::FAILURE;
    }
}
