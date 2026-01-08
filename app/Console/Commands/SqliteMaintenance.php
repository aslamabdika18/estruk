<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SqliteMaintenance extends Command
{
    protected $signature = 'sqlite:maintenance';
    protected $description = 'Monthly SQLite maintenance (VACUUM + ANALYZE)';

    public function handle(): int
    {
        $this->info('Starting SQLite maintenance...');

        // ⚠️ WAJIB: pastikan tidak ada transaksi lain
        DB::statement('PRAGMA wal_checkpoint(TRUNCATE);');

        $this->info('Checkpoint WAL done');

        DB::statement('ANALYZE;');
        $this->info('ANALYZE completed');

        DB::statement('VACUUM;');
        $this->info('VACUUM completed');

        $this->info('SQLite maintenance finished');
        return self::SUCCESS;
    }
}
